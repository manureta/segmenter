<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Model\Entidad;
use App\Model\Archivo;
use Auth;

class EntidadController extends Controller
{
    /**
     * Mostrar la Entidad
     */
    public function show(string $id): View
    {
        return view('entidad.view', [
            'entidad' => Entidad::findOrFail($id)
        ]);
    }

    public function index()
    {
        $entidades=Entidad::all();
        return view('entidad.list',['entidades' => $entidades]);
    }

    public function store(Request $request)
    {
      if (! Auth::check()) {
          $mensaje = 'No tiene permiso para cargar Entidades o no esta logueado';
          flash($mensaje)->error()->important();
          return $mensaje;
      }

      $AppUser = Auth::user();
      flash('TODO: Funcion en desarrollar')->warning()->important();

      // Carga de arcos o e00
    if ($request->hasFile('shp')) {
      if($shp_file = Archivo::cargar($request->shp, Auth::user(),
        'shape', [$request->shx, $request->dbf, $request->prj])) {
        flash("Archivo de geodatos SHP/E00. Identificado: ".$shp_file->tipo)->info();
      } else {
        flash("Error en el modelo cargar archivo al registrar SHP/E00")->error();
      }
      //$shp_file->epsg_def = $epsg_id;
      $shp_file->save();
    }
      return view('entidad.cargar');
    }
    public function cargar(Request $request)
    {
      return view('entidad.cargar');
    }

    public function entsList()
    {
           // Ents, pastores e árboles :D
           $aEnts=[];
           $entsQuery = Entidad::query();
           $codigo = (!empty($_GET["codigo"])) ? ($_GET["codigo"]) : ('');
           if ($codigo!='') {
              $provsQuery->where('codigo', '=', $codigo);
           }
    	  $qEnts = $entsQuery->select('*')
/*                ->withCount(['departamentos','fracciones'])
                ->with('departamentos')
                ->with('fracciones')
                ->with('fracciones.radios')
                ->with('fracciones.radios.tipo')
                ->with('departamentos.localidades') */
                ->get('codigo','nombre')
                ->sort();
//        dd($provs->get());
        foreach ($qEnts as $ent){

          $aEnts[$ent->codigo]=['id'=>$ent->id,'codigo'=>$ent->codigo,'nombre'=>$ent->nombre ];
        }
      return datatables()->of($aEnts)
                ->addColumn('action', function($data){
                    $button = '<button type="button" class="btn_descarga btn-sm btn-primary" > Descargar </button> ';
                    // botón de eliminar Entidad  en test, si esta logueado.
                    if (Auth::check()) {
                            try {
                                $filtro = Permission::where('name',$data['codigo'])->first();
                                if ( $filtro and ( Auth::user()->hasPermissionTo($data['codigo'], 'filters') and Auth::user()->can('Borrar Entidad') ) )
                                // Botón borrar sólo si tiene permiso y la Entiadd pertenece a la provincia (TODO).
                                {
                                    $button .= '<button type="button" class="btn_ent_delete btn-sm btn-danger "> Borrar </button>';
                                }
                            } catch (PermissionDoesNotExist $e) {
                            Log::warning('No existe el permiso "Borrar Entidad"');
                            }
                            return $button;
                        }
                })
            ->make(true);
    }

}
