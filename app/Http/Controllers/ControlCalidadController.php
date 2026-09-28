<?php

namespace App\Http\Controllers;

use App\Model\Radio;
use App\Model\Fraccion;
use App\Model\Departamento;
use App\Model\Localidad;
use App\Model\HistoricoRadio;
use App\Model\ViaTipoCorreccion;
use App\Support\AlcanceGeografico;
use App\Http\Controllers\Concerns\TieneAlcanceGeografico;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ControlCalidadController extends Controller
{
    use TieneAlcanceGeografico;

    /**
     * "Informes con objetos que no cumplen algún control" - primer control
     * concreto: los 2 radios huérfanos encontrados probando el scope de
     * operativo (ver SIGeo-documentacion-general.md §2.9). Diseñado para
     * sumar más controles como métodos nuevos, no como un mecanismo
     * genérico todavía - no hay suficientes controles reales hoy (2) como
     * para justificar una tabla de "reglas" configurable.
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index()
    {
        $alcance = $this->alcanceActivo();

        $controles = [
            'radios_sin_fraccion' => $this->radiosSinFraccion($alcance['prefijos']),
            'radios_codigo_duplicado' => $this->radiosConCodigoDuplicado($alcance['prefijos']),
            'localidades_sin_departamento' => $this->localidadesSinDepartamento($alcance['prefijos']),
            'vias_correccion_revertida' => $this->viasConCorreccionRevertida($alcance['prefijos']),
        ];

        return view('control-calidad.index', compact('controles', 'alcance'));
    }

    /**
     * radio.fraccion_id nulo - sin él, EscopadoPorOperativoHeredado no
     * puede ubicar el radio en ninguna provincia (y `fraccion` en general
     * no tiene forma de asociarlo a nada más arriba en la jerarquía).
     */
    private function radiosSinFraccion(?array $prefijos)
    {
        // withoutGlobalScope a propósito: un control de calidad tiene que
        // ver TODO el dato sucio, no lo que quede filtrado por el operativo
        // que alguien tenga seleccionado en su sesión en este momento (un
        // radio sin fraccion_id de por sí nunca matchea ningún operativo,
        // así que sin este bypass el reporte daría "0 problemas" siempre
        // que hay un operativo activo, aunque el problema exista). El
        // alcance geográfico (provincia/objeto elegido, o restricción fija
        // del usuario) es un filtro aparte, no el scope de operativo.
        // withCount para poder mostrar el botón "Eliminar" en la vista sin
        // disparar una query de relaciones por fila (mismo chequeo de
        // seguridad real que hace eliminarRadio(), acá sólo decide si se
        // muestra el botón).
        $query = Radio::withoutGlobalScope('operativo_actual')
            ->withCount(['localidades', 'entidades', 'historicoComoVigente', 'historicoComoReemplazado'])
            ->whereNull('fraccion_id');

        if ($prefijos !== null) {
            AlcanceGeografico::filtrarPorPrefijos($query, 'codigo', $prefijos);
        }

        return $query->orderBy('codigo')->get();
    }

    /**
     * Dos o más radios con el mismo código de 15 dígitos - no debería
     * pasar nunca (el código es la identidad territorial del radio), pero
     * los 2 huérfanos encontrados comparten código exacto.
     */
    private function radiosConCodigoDuplicado(?array $prefijos)
    {
        $base = Radio::withoutGlobalScope('operativo_actual')->select('codigo')->whereNotNull('codigo');
        if ($prefijos !== null) {
            AlcanceGeografico::filtrarPorPrefijos($base, 'codigo', $prefijos);
        }
        $codigosDuplicados = $base->groupBy('codigo')->havingRaw('count(*) > 1')->pluck('codigo');

        return Radio::withoutGlobalScope('operativo_actual')
            ->whereIn('codigo', $codigosDuplicados)->orderBy('codigo')->get();
    }

    /**
     * Localidad sin ninguna fila en localidad_departamento (ver
     * SIGeo-documentacion-general.md §2.13) - 678 de 907 hoy. Localidad no
     * tiene departamento_id propio, así que sin esta fila no hay forma de
     * ubicarla en la jerarquía (afecta el conteo "Localidades" del listado
     * de Departamentos, entre otras cosas).
     */
    private function localidadesSinDepartamento(?array $prefijos)
    {
        $query = Localidad::withoutGlobalScope('operativo_actual')->whereDoesntHave('departamentos');
        if ($prefijos !== null) {
            AlcanceGeografico::filtrarPorPrefijos($query, 'codigo', $prefijos);
        }
        return $query->orderBy('codigo')->get();
    }

    /**
     * Correcciones de vias.tipo (ver §2.14) donde el valor sucio original
     * volvió a aparecer - vias es una tabla derivada que se reconstruye
     * entera, así que esto pasa cada vez que se recarga cartografía sin
     * reaplicar el diccionario después.
     */
    private function viasConCorreccionRevertida(?array $prefijos)
    {
        return ViaTipoCorreccion::get()->map(function ($correccion) use ($prefijos) {
            $query = DB::table('vias')->where('tipo', $correccion->tipo_original);
            if ($prefijos !== null) {
                // vias.codloc es prov(2)+dpto(3)+loc(3) - un prefijo de
                // radio/fracción (9/7 dígitos) no matchea nada acá, así que
                // se recorta a los primeros 2 (nivel provincia, el único
                // que vias comparte de forma confiable con el resto).
                $codigosProvincia = collect($prefijos)->map(fn ($p) => substr($p, 0, 2))->unique()->all();
                AlcanceGeografico::filtrarPorPrefijos($query, 'codloc', $codigosProvincia);
            }
            $correccion->revertidos = $query->count();
            return $correccion;
        })->filter(fn ($c) => $c->revertidos > 0)->values();
    }

    /**
     * Página de conciliación de un código duplicado puntual: diff entre
     * las filas, relaciones de cada una y qué se puede hacer con esto.
     */
    public function verDuplicado($codigo)
    {
        $radios = Radio::withoutGlobalScope('operativo_actual')
            ->with(['tipo', 'observacion', 'fraccion.departamento.provincia',
                'historicoComoReemplazado.vigente', 'historicoComoVigente.reemplazado'])
            ->where('codigo', $codigo)->orderBy('id')->get();

        if ($radios->count() < 2) {
            abort(404, 'Ese código no tiene duplicados (o ya no).');
        }

        $diff = $this->diffRadios($radios);

        $deptoCodigo = substr($codigo, 0, 5);
        $fracCodigo = substr($codigo, 5, 2);
        $depto = Departamento::withoutGlobalScope('operativo_actual')
            ->where('codigo', $deptoCodigo)->first();
        $fraccionExistente = $depto
            ? Fraccion::withoutGlobalScope('operativo_actual')
                ->where('departamento_id', $depto->id)
                ->where('codigo', $deptoCodigo.$fracCodigo)->first()
            : null;

        $infoRelacionada = $radios->mapWithKeys(function ($radio) {
            return [$radio->id => $this->infoRelacionada($radio)];
        });

        return view('control-calidad.duplicado', compact(
            'codigo', 'radios', 'diff', 'depto', 'fracCodigo', 'fraccionExistente', 'infoRelacionada'
        ));
    }

    /**
     * Compara todas las columnas (menos id/timestamps) entre las filas
     * duplicadas: para cada campo, si difiere entre alguna de ellas.
     */
    private function diffRadios($radios)
    {
        // getRawOriginal (no el accessor mágico $radio->{$columna}): varias
        // columnas de Radio tienen un get*Attribute que dispara consultas
        // pesadas (issegmentado corre contra el esquema de segmentación) -
        // acá solo interesa comparar lo que hay guardado en la fila.
        $columnas = collect(Schema::getColumnListing('radio'))
            ->reject(fn ($c) => in_array($c, ['id', 'created_at', 'updated_at']));

        return $columnas->mapWithKeys(function ($columna) use ($radios) {
            $valores = $radios->map(fn ($r) => $r->getRawOriginal($columna));
            return [$columna => $valores->unique()->count() > 1];
        });
    }

    /**
     * "Buscar más info posible relacionada": cuántas localidades/entidades
     * tiene, y si hay algo cargado en algún esquema e<codigo> (listados,
     * segmentación) para ese radio puntual.
     */
    private function infoRelacionada(Radio $radio)
    {
        $esquema = 'e'.trim($radio->codigo);
        return [
            'localidades' => $radio->localidades()->count(),
            'entidades' => $radio->entidades()->count(),
            'tiene_listado' => Schema::hasTable($esquema.'.listado_geo'),
            'tiene_segmentacion' => Schema::hasTable($esquema.'.segmentacion'),
            'esquema' => $esquema,
        ];
    }

    /**
     * Conciliar: de las filas con el mismo código, una queda "vigente" y
     * el resto se marca histórico (no se borran - otras tablas pueden
     * seguir referenciándolas por id).
     */
    public function marcarHistorico(Request $request, $codigo)
    {
        $data = $request->validate([
            'vigente_id' => 'required|integer',
            'motivo' => 'nullable|string|max:1000',
        ]);

        $radios = Radio::withoutGlobalScope('operativo_actual')
            ->where('codigo', $codigo)->pluck('id');

        if (!$radios->contains($data['vigente_id'])) {
            abort(422, 'El radio elegido como vigente no tiene ese código.');
        }

        foreach ($radios as $id) {
            if ($id == $data['vigente_id']) {
                continue;
            }
            HistoricoRadio::firstOrCreate(
                ['nuevo_id' => $data['vigente_id'], 'viejo_id' => $id],
                ['motivo' => $data['motivo'] ?? null, 'user_id' => auth()->id(), 'created_at' => now()]
            );
        }

        flash('Conciliado: quedó vigente el radio #'.$data['vigente_id'].', el resto marcado histórico.')->success();
        return redirect()->route('controles-calidad.duplicado', $codigo);
    }

    /**
     * Eliminar directamente: para cuando el duplicado es un error de carga
     * (no dos radios reales compitiendo por una fracción faltante). Chequeo
     * de seguridad: sólo permite borrar si el radio no tiene ninguna
     * relación real (localidades, entidades, histórico) - si las tiene, hay
     * que reasignarlas primero o conciliar por histórico en cambio.
     */
    public function eliminarRadio($codigo, $radioId)
    {
        $radio = Radio::withoutGlobalScope('operativo_actual')
            ->where('codigo', $codigo)->findOrFail($radioId);

        $tieneRelaciones = $radio->localidades()->exists()
            || $radio->entidades()->exists()
            || $radio->historicoComoVigente()->exists()
            || $radio->historicoComoReemplazado;

        if ($tieneRelaciones) {
            flash('No se puede eliminar el radio #'.$radio->id.': tiene relaciones (localidades, entidades o histórico). Conciliá por histórico en cambio.')->error();
            return redirect()->route('controles-calidad.duplicado', $codigo);
        }

        $radio->delete();

        $quedan = Radio::withoutGlobalScope('operativo_actual')->where('codigo', $codigo)->count();
        flash('Radio #'.$radio->id.' eliminado (duplicado sin fracción ni relaciones).')->success();

        return $quedan >= 2
            ? redirect()->route('controles-calidad.duplicado', $codigo)
            : redirect()->route('controles-calidad');
    }

    /**
     * "Completar información faltante": crea (si no existe) la Fraccion
     * que le corresponde al código y la asigna a los radios de ese código
     * que todavía tengan fraccion_id nulo.
     */
    public function completarFraccion(Request $request, $codigo)
    {
        $deptoCodigo = substr($codigo, 0, 5);
        $fracCodigo = substr($codigo, 5, 2);

        $depto = Departamento::withoutGlobalScope('operativo_actual')
            ->where('codigo', $deptoCodigo)->first();

        if (!$depto) {
            flash('No existe el departamento '.$deptoCodigo.' - no se puede crear la fracción a ciegas.')->error();
            return redirect()->route('controles-calidad.duplicado', $codigo);
        }

        $fraccion = Fraccion::withoutGlobalScope('operativo_actual')->firstOrCreate(
            ['departamento_id' => $depto->id, 'codigo' => $deptoCodigo.$fracCodigo]
        );

        $actualizados = Radio::withoutGlobalScope('operativo_actual')
            ->where('codigo', $codigo)->whereNull('fraccion_id')
            ->update(['fraccion_id' => $fraccion->id]);

        flash('Fracción '.$fraccion->codigo.' ('.$depto->nombre.') lista. Se completó fraccion_id en '.$actualizados.' radio(s).')->success();
        return redirect()->route('controles-calidad.duplicado', $codigo);
    }
}
