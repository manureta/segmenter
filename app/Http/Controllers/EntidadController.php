<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Model\Entidad;
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
        return view('entidad.index',['entidades' => $entidades]);
    }

    public function store(Request $request)
    {
      if (! Auth::check()) {
          $mensaje = 'No tiene permiso para cargar Entidades o no esta logueado';
          flash($mensaje)->error()->important();
          return $mensaje;
      }

      $AppUser = Auth::user();
      flash('TODO: Funcion a desarrollar')->warning()->important();
      return view('entidad.cargar');
    }
    public function cargar(Request $request)
    {
      return view('entidad.cargar');
    }
}
