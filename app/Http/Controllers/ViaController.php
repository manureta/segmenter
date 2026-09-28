<?php

namespace App\Http\Controllers;

use App\Model\Via;
use App\Model\Provincia;
use App\Model\ViaTipoCorreccion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ViaController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
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
     * Tablero de resumen: siempre agregado en SQL (group by), nunca trae
     * las 159k filas a PHP para contar - eso sería recorrer todo en cada
     * carga de la página por gusto.
     */
    public function resumen()
    {
        $total = DB::table('vias')->count();

        $porTipoCrudo = DB::table('vias')
            ->selectRaw("coalesce(nullif(trim(tipo), ''), '(sin tipo)') as tipo, count(*) as cantidad")
            ->groupBy('tipo')->orderByDesc('cantidad')->get();
        // Hay 37 valores de "tipo" distintos, varios sucios (numéricos, vacíos,
        // variantes de una misma palabra) - top 12 + resto agrupado como
        // "Otros" para que el gráfico sea legible.
        $porTipo = $porTipoCrudo->take(12);
        $restoTipo = $porTipoCrudo->skip(12)->sum('cantidad');
        if ($restoTipo > 0) {
            $porTipo->push((object) ['tipo' => 'Otros ('.($porTipoCrudo->count() - 12).' valores)', 'cantidad' => $restoTipo]);
        }

        // Top 25 completo (para la tabla, no sólo el gráfico) y "valores
        // raros": umbral fijo <=10 apariciones en 159611 filas - a esa
        // frecuencia son casi siempre errores de tipeo/import (CALE, CLLE,
        // OTRP, "TIPO" literal, códigos numéricos sueltos como "1015"), no
        // tipos de vía real infrecuentes pero válidos.
        $top25Tipos = $porTipoCrudo->take(25)->values();
        $tiposRaros = $porTipoCrudo->filter(fn ($t) => $t->cantidad <= 10)->values();

        $localidadesDistintas = DB::table('vias')->distinct()->count('codloc');

        $sinAlturas = DB::table('vias')
            ->where('desdei', '0')->where('hastai', '0')
            ->where('desded', '0')->where('hastad', '0')
            ->count();

        $porProvincia = DB::table('vias')
            ->selectRaw('substr(codloc, 1, 2) as prov_codigo, count(*) as cantidad, count(distinct codloc) as localidades')
            ->groupBy('prov_codigo')->orderByDesc('cantidad')->get();
        $nombresProvincia = Provincia::withoutGlobalScope('operativo_actual')->pluck('nombre', 'codigo');

        // Correcciones ya registradas + si "volvió a entrar el dato viejo":
        // vias es una tabla derivada que se reconstruye entera desde el
        // origen (ver migración via_tipo_correccion) - si count > 0 acá es
        // que una reconstrucción posterior trajo de nuevo el tipo sucio.
        $correcciones = ViaTipoCorreccion::orderByDesc('created_at')->get()
            ->map(function ($correccion) {
                $correccion->revertidos = DB::table('vias')->where('tipo', $correccion->tipo_original)->count();
                return $correccion;
            });

        return view('vias.resumen', compact(
            'total', 'porTipo', 'top25Tipos', 'tiposRaros',
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

        return datatables()->of($vias)
            ->addColumn('localidad', fn (Via $via) => optional($via->localidad)->nombre ?? '—')
            ->addColumn('aglomerado', fn (Via $via) => optional($via->aglomerado)->nombre ?? '—')
            ->make(true);
    }
}
