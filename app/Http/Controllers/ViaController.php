<?php

namespace App\Http\Controllers;

use App\Model\Via;
use App\Model\Provincia;
use Illuminate\Support\Facades\DB;

class ViaController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index()
    {
        return view('vias.index');
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

        return view('vias.resumen', compact(
            'total', 'porTipo', 'top25Tipos', 'tiposRaros',
            'localidadesDistintas', 'sinAlturas', 'porProvincia', 'nombresProvincia'
        ));
    }

    /**
     * 159k+ filas - siempre server-side (Yajra), nunca cargar todo de
     * una. Sin select('geom'): son WKB gigantes que no hacen falta acá.
     */
    public function listAll()
    {
        $vias = Via::select([
            'nomencla', 'codigo', 'tipo', 'nombre',
            'desdei', 'desded', 'hastai', 'hastad', 'codloc', 'codaglo',
        ])->with(['localidad', 'aglomerado']);

        return datatables()->of($vias)
            ->addColumn('localidad', fn (Via $via) => optional($via->localidad)->nombre ?? '—')
            ->addColumn('aglomerado', fn (Via $via) => optional($via->aglomerado)->nombre ?? '—')
            ->make(true);
    }
}
