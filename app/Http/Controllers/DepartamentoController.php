<?php

namespace App\Http\Controllers;

use App\Model\Departamento;
use App\Model\Provincia;
use App\Model\Localidad;
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
        return view('deptoview',['departamento' => $this->cargarInfoDetallada($departamento)]);
    }

    public function show_post(Departamento $departamento)
    {
        //
        return view('deptoinfo',['departamento' => $this->cargarInfoDetallada($departamento)]);
    }

    /**
     * Info detallada para explorar un departamento en profundidad: además
     * de la relación real (pivot localidad_departamento, a veces vacía -
     * ver §2.13 de la documentación), se agrega localidadesPorCodigo como
     * cross-check derivado del propio código (localidad.codigo empieza con
     * departamento.codigo) para que la brecha entre ambas quede visible en
     * la pantalla, no escondida.
     */
    private function cargarInfoDetallada(Departamento $departamento)
    {
        $departamento->loadCount(['localidades', 'fracciones', 'radios',
            'radios as segmentados' => function ($query) {
                $query->whereNotNull('resultado');
            },
        ])->load(['provincia', 'localidades.aglomerado', 'cabeceraVigente', 'gobiernosLocalesVigentes', 'observacion']);

        $departamento->localidadesPorCodigo = Localidad::withoutGlobalScope('operativo_actual')
            ->where('codigo', 'like', $departamento->codigo.'%')->orderBy('codigo')->get();

        return $departamento;
    }

    /**
     * Reparar el vínculo faltante en localidad_departamento (ver §2.13):
     * vincula una localidad puntual. Chequeo de seguridad - el propio
     * código de la localidad tiene que empezar con el código del
     * departamento, para no poder vincular cualquier par de ids a mano
     * editando el form.
     */
    public function vincularLocalidad(Departamento $departamento, $localidadId)
    {
        $localidad = Localidad::withoutGlobalScope('operativo_actual')->findOrFail($localidadId);

        if (strpos($localidad->codigo, $departamento->codigo) !== 0) {
            flash('La localidad '.$localidad->codigo.' no corresponde al departamento '.$departamento->codigo.'.')->error();
            return redirect()->route('depto.show', $departamento);
        }

        $departamento->localidades()->syncWithoutDetaching([$localidad->id]);
        flash('Vinculada: ('.$localidad->codigo.') '.$localidad->nombre.' ahora pertenece a '.$departamento->nombre.'.')->success();

        return redirect()->route('depto.show', $departamento);
    }

    /**
     * Vincula de una todas las localidades del cross-check por código que
     * todavía no estén en el pivot.
     */
    public function vincularTodasLasLocalidades(Departamento $departamento)
    {
        $localidades = Localidad::withoutGlobalScope('operativo_actual')
            ->where('codigo', 'like', $departamento->codigo.'%')->pluck('id');

        $departamento->localidades()->syncWithoutDetaching($localidades);
        flash('Vinculadas '.$localidades->count().' localidades a '.$departamento->nombre.' (las que ya estaban no se duplican).')->success();

        return redirect()->route('depto.show', $departamento);
    }

}
