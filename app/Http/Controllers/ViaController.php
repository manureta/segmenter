<?php

namespace App\Http\Controllers;

use App\Model\Via;

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
