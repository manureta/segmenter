<?php

namespace App\Http\Controllers;

use App\Model\Departamento;
use App\Model\Provincia;
use Illuminate\Http\Request;

class DepartamentoController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    /**
     * Sin provincia: listado global (antes forzaba provincia_id=8, Entre
     * Ríos, a lo pincho cuando no se pasaba ninguna - bug real, "listado de
     * departamentos" sin más nunca mostraba todos, mostraba siempre Entre Ríos).
     */
    public function index(Provincia $provincia = null)
    {
        return view('deptos', ['provincia' => $provincia]);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
    }


    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Model\Departamento  $departamento
     * @return \Illuminate\Http\Response
     */
    public function edit(Departamento $departamento)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Model\Departamento  $departamento
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Departamento $departamento)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Model\Departamento  $departamento
     * @return \Illuminate\Http\Response
     */
    public function destroy(Departamento $departamento)
    {
        //
        //      dd($departamento->localidades) ;//->with('localidades')->get());
        $departamento->delete();//->with('localidades')->get());
        dd($departamento) ;//->with('localidades')->get());
    }


    public function list(Provincia $provincia)
    {   
	    $deptos = $provincia->departamentos()
            ->with('localidades')
            ->withCount(['localidades' =>function ($query) {
                $query->where('codigo','not like','%000');
              }
              ,'fracciones','radios',
              'radios as segmentados' => function ($query) {
                $query->whereNotNull('resultado');
              }])->get(); 
	    //Departamento::where('provincia_id',$provincia)->get(['codigo','nombre']);
        return datatables()->of($deptos)
            ->make(true);
    }

    /**
     * Igual que list($provincia) pero sin scope a una sola provincia - es
     * el endpoint que faltaba para que el listado "de todas" (que la propia
     * vista deptos.blade.php ya contemplaba en el título) funcionara.
     * Respeta el scope de operativo actual si hay uno seleccionado (via
     * EscopadoPorOperativoHeredado en Departamento).
     */
    public function listAll()
    {
        $deptos = Departamento::with(['provincia', 'localidades'])
            ->withCount(['localidades' => function ($query) {
                $query->where('codigo', 'not like', '%000');
            }, 'fracciones', 'radios',
              'radios as segmentados' => function ($query) {
                $query->whereNotNull('resultado');
            }])
            ->get();

        return datatables()->of($deptos)
            ->addColumn('provincia', function (Departamento $depto) {
                return optional($depto->provincia)->nombre ?? '(sin provincia)';
            })
            ->make(true);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Model\Departamento  $departamento
     * @return \Illuminate\Http\Response
     */
   public function show(Departamento $departamento)
    {
        //
        return view('deptoview',['departamento' =>
        $departamento->loadCount('localidades')]);
    }

    public function show_post(Departamento $departamento)
    {
        //
        //return view('provinfo',['provincia' => Provincia::withCount('departamentos')->findOrFail($provincia)]);
        return view('deptoinfo',['departamento' => $departamento->loadCount('localidades')]);
    }

}
