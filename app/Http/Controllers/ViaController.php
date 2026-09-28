<?php

namespace App\Http\Controllers;

use App\Model\Via;
use App\Model\Provincia;
use App\Model\ViaTipoCorreccion;
use App\Http\Controllers\Concerns\TieneAlcanceGeografico;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ViaController extends Controller
{
    use TieneAlcanceGeografico;

    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * vias sólo comparte de forma confiable el nivel provincia (codloc =
     * prov(2)+dpto(3)+loc(3), no hay fracción/radio ahí) - un prefijo más
     * largo (depto/fracción/radio) se recorta a sus primeros 2 dígitos.
     * null = sin alcance activo (reporte global de verdad).
     */
    private function codigosProvinciaDelAlcance(): ?array
    {
        $prefijos = $this->alcanceActivo()['prefijos'];
        return $prefijos === null ? null : collect($prefijos)->map(fn ($p) => substr($p, 0, 2))->unique()->values()->all();
    }

    /**
     * ?tipo=X (llegando desde el tablero de resumen, sección de valores
     * raros) precarga el filtro de la columna en el DataTable para poder
     * entrar directo a ver esas filas puntuales.
     */
    public function index(Request $request)
    {
        return view('vias.index', ['tipoFiltro' => $request->query('tipo')]);
    }

    /**
     * Base de todas las queries de este controller: aplica el alcance
     * geográfico activo (objeto elegido en el selector de arriba, o la
     * restricción fija del usuario) vía codloc - antes esto no se
     * aplicaba nunca y el resumen mostraba todas las provincias sin
     * importar qué hubiera seleccionado.
     */
    private function viasQuery()
    {
        $query = DB::table('vias');
        $codigosProvincia = $this->codigosProvinciaDelAlcance();
        if ($codigosProvincia !== null) {
            \App\Support\AlcanceGeografico::filtrarPorPrefijos($query, 'codloc', $codigosProvincia);
        }
        return $query;
    }

    /**
     * Tablero de resumen: siempre agregado en SQL (group by), nunca trae
     * las 159k filas a PHP para contar - eso sería recorrer todo en cada
     * carga de la página por gusto.
     */
    public function resumen()
    {
        $alcance = $this->alcanceActivo();
        $total = $this->viasQuery()->count();

        $porTipoCrudo = $this->viasQuery()
            ->selectRaw("coalesce(nullif(trim(tipo), ''), '(sin tipo)') as tipo, count(*) as cantidad")
            ->groupBy('tipo')->orderByDesc('cantidad')->get();
        // Hay 37 valores de "tipo" distintos, varios sucios (numéricos, vacíos,
        // variantes de una misma palabra) - top 10 para que el gráfico sea legible.
        $porTipo = $porTipoCrudo->take(10);
        $restoTipo = $porTipoCrudo->skip(10)->sum('cantidad');
        if ($restoTipo > 0) {
            $porTipo->push((object) ['tipo' => 'Otros ('.($porTipoCrudo->count() - 10).' valores)', 'cantidad' => $restoTipo]);
        }

        // Top 10 (pedido explícito, antes eran 25 acá + otros 10/12 en el
        // gráfico - redundante) y "valores raros": umbral fijo <=10
        // apariciones en 159611 filas - a esa frecuencia son casi siempre
        // errores de tipeo/import (CALE, CLLE, OTRP, "TIPO" literal,
        // códigos numéricos sueltos como "1015"), no tipos de vía real
        // infrecuentes pero válidos.
        $top10Tipos = $porTipoCrudo->take(10)->values();
        $tiposRaros = $porTipoCrudo->filter(fn ($t) => $t->cantidad <= 10)->values();

        // Top 25 nombres más repetidos: upper(trim()) para no repetir el
        // mismo error de conteo del nombre de Aglomerado (mismo nombre
        // contado aparte por diferencias de mayúscula/minúscula).
        $top25NombresRepetidos = $this->viasQuery()
            ->whereNotNull('nombre')->where('nombre', '!=', '')
            ->selectRaw("upper(trim(nombre)) as nombre, count(*) as cantidad, count(distinct codloc) as localidades")
            ->groupBy(DB::raw('upper(trim(nombre))'))
            ->orderByDesc('cantidad')->limit(25)->get();

        // Top 25 por longitud real (ST_Length sobre geography - geom está
        // en SRID 4326/WGS84, ST_Length crudo daría grados, no metros).
        // Full scan de las 159k filas (~1.6s medido) - aceptable para un
        // tablero que se carga bajo demanda, no en cada request.
        $top25PorMetros = $this->viasQuery()
            ->selectRaw('nomencla, nombre, tipo, codloc, ST_Length(geom::geography) as metros')
            ->orderByDesc('metros')->limit(25)->get();

        $localidadesDistintas = $this->viasQuery()->distinct()->count('codloc');

        $sinAlturas = $this->viasQuery()
            ->where('desdei', '0')->where('hastai', '0')
            ->where('desded', '0')->where('hastad', '0')
            ->count();

        $porProvincia = $this->viasQuery()
            ->selectRaw('substr(codloc, 1, 2) as prov_codigo, count(*) as cantidad, count(distinct codloc) as localidades')
            ->groupBy('prov_codigo')->orderByDesc('cantidad')->get();
        $nombresProvincia = Provincia::withoutGlobalScope('operativo_actual')->pluck('nombre', 'codigo');

        // Correcciones ya registradas + si "volvió a entrar el dato viejo":
        // vias es una tabla derivada que se reconstruye entera desde el
        // origen (ver migración via_tipo_correccion) - si count > 0 acá es
        // que una reconstrucción posterior trajo de nuevo el tipo sucio.
        $correcciones = ViaTipoCorreccion::orderByDesc('created_at')->get()
            ->map(function ($correccion) {
                $correccion->revertidos = $this->viasQuery()->where('tipo', $correccion->tipo_original)->count();
                return $correccion;
            });

        return view('vias.resumen', compact(
            'alcance', 'total', 'porTipo', 'top10Tipos', 'tiposRaros',
            'top25NombresRepetidos', 'top25PorMetros',
            'localidadesDistintas', 'sinAlturas', 'porProvincia', 'nombresProvincia', 'correcciones'
        ));
    }

    /**
     * "Registrar cambio para versionar y luego impactar": guarda el
     * diccionario tipo_original -> tipo_corregido (para poder reaplicarlo
     * cuando `vias` se reconstruya de nuevo) y de una actualiza las filas
     * actuales que tengan ese tipo sucio.
     */
    public function corregirTipo(Request $request)
    {
        $data = $request->validate([
            'tipo_original' => 'required|string|max:255',
            'tipo_corregido' => 'required|string|max:255',
            'motivo' => 'nullable|string|max:1000',
        ]);

        ViaTipoCorreccion::updateOrCreate(
            ['tipo_original' => $data['tipo_original']],
            ['tipo_corregido' => $data['tipo_corregido'], 'motivo' => $data['motivo'] ?? null, 'user_id' => auth()->id()]
        );

        $actualizadas = DB::table('vias')->where('tipo', $data['tipo_original'])->update(['tipo' => $data['tipo_corregido']]);

        flash('Corregido: '.$actualizadas.' vía(s) con tipo "'.$data['tipo_original'].'" ahora son "'.$data['tipo_corregido'].'". Queda registrado para reaplicar si vias se reconstruye.')->success();

        return redirect()->route('vias.resumen');
    }

    /**
     * Reaplica TODO el diccionario de correcciones de una - para cuando
     * "volvió a entrar el dato viejo" (vias se reconstruyó de cero y trajo
     * de nuevo los tipos sucios que ya se habían corregido).
     */
    public function reaplicarCorrecciones()
    {
        $total = 0;
        foreach (ViaTipoCorreccion::all() as $correccion) {
            $total += DB::table('vias')->where('tipo', $correccion->tipo_original)
                ->update(['tipo' => $correccion->tipo_corregido]);
        }

        flash($total > 0
            ? 'Reaplicadas las correcciones: '.$total.' vía(s) tenían de nuevo un tipo sucio ya corregido antes.'
            : 'Nada para reaplicar - ningún tipo sucio había vuelto a aparecer.')->success();

        return redirect()->route('vias.resumen');
    }

    /**
     * 159k+ filas - siempre server-side (Yajra), nunca cargar todo de
     * una. Sin select('geom'): son WKB gigantes que no hacen falta acá.
     */
    public function listAll(Request $request)
    {
        $vias = Via::select([
            'nomencla', 'codigo', 'tipo', 'nombre',
            'desdei', 'desded', 'hastai', 'hastad', 'codloc', 'codaglo',
        ])->with(['localidad', 'aglomerado']);

        if ($tipo = $request->input('tipo')) {
            $vias->where('tipo', $tipo);
        }

        $codigosProvincia = $this->codigosProvinciaDelAlcance();
        if ($codigosProvincia !== null) {
            \App\Support\AlcanceGeografico::filtrarPorPrefijos($vias, 'codloc', $codigosProvincia);
        }

        return datatables()->of($vias)
            ->addColumn('localidad', fn (Via $via) => optional($via->localidad)->nombre ?? '—')
            ->addColumn('aglomerado', fn (Via $via) => optional($via->aglomerado)->nombre ?? '—')
            ->make(true);
    }
}
