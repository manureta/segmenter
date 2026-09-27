<?php

namespace App\Http\Controllers;

use App\Model\Operativo;
use App\Model\Topologia;
use App\Model\Provincia;
use Illuminate\Http\Request;

class OperativoController extends Controller
{
    /**
     * Mismo gate que SetupController: operativo se scaffoldeó bajo el
     * paraguas de /setup, administrarlo requiere el mismo permiso.
     */
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('can:run-setup');
    }

    public function index()
    {
        $operativos = Operativo::withCount(['provincias', 'localidades', 'entidades', 'gobiernosLocales', 'parajes', 'baseAntarticas'])
            ->orderBy('nombre')
            ->get();

        return view('operativo.index', compact('operativos'));
    }

    public function create()
    {
        $topologias = Topologia::orderBy('name')->get();

        return view('operativo.form', [
            'operativo' => new Operativo,
            'topologias' => $topologias,
        ]);
    }

    public function store(Request $request)
    {
        $datos = $request->validate([
            'nombre' => 'required|string|max:50|unique:operativo,nombre',
            'observacion' => 'nullable|string|max:50',
            'topologia_id' => ['nullable', 'integer', $this->reglaTopologiaExiste()],
        ]);

        $operativo = Operativo::create($datos);

        flash('Operativo "'.$operativo->nombre.'" creado.')->success();

        return redirect()->route('operativos.show', $operativo);
    }

    public function show(Operativo $operativo)
    {
        $operativo->loadCount(['provincias', 'localidades', 'entidades', 'gobiernosLocales', 'parajes', 'baseAntarticas']);
        $provinciasVinculadas = $operativo->provincias()->orderBy('nombre')->get();
        $provinciasDisponibles = Provincia::whereNotIn('id', $provinciasVinculadas->pluck('id'))->orderBy('nombre')->get();

        return view('operativo.show', compact('operativo', 'provinciasVinculadas', 'provinciasDisponibles'));
    }

    public function edit(Operativo $operativo)
    {
        $topologias = Topologia::orderBy('name')->get();

        return view('operativo.form', compact('operativo', 'topologias'));
    }

    public function update(Request $request, Operativo $operativo)
    {
        $datos = $request->validate([
            'nombre' => 'required|string|max:50|unique:operativo,nombre,'.$operativo->id,
            'observacion' => 'nullable|string|max:50',
            'topologia_id' => ['nullable', 'integer', $this->reglaTopologiaExiste()],
        ]);

        $operativo->update($datos);

        flash('Operativo "'.$operativo->nombre.'" actualizado.')->success();

        return redirect()->route('operativos.show', $operativo);
    }

    /**
     * Antes de borrar, desvincula todos los pivots (operativo_provincia,
     * operativo_localidad, etc no tienen ON DELETE CASCADE en operativo_id -
     * confirmado en las migraciones que las crearon) - si no, el DELETE
     * falla por violación de FK en vez de dar un error entendible.
     */
    public function destroy(Operativo $operativo)
    {
        $operativo->provincias()->detach();
        $operativo->localidades()->detach();
        $operativo->entidades()->detach();
        $operativo->gobiernosLocales()->detach();
        $operativo->parajes()->detach();
        $operativo->baseAntarticas()->detach();

        $nombre = $operativo->nombre;
        $operativo->delete();

        flash('Operativo "'.$nombre.'" eliminado (se desvincularon sus '.
            'relaciones con provincias/localidades/etc antes de borrar).')->success();

        return redirect()->route('operativos.index');
    }

    /**
     * `exists:topology.topology,id` de Laravel interpreta el "." como
     * connection.tabla, no como schema.tabla - probado, revienta con
     * "Database connection [topology] not configured". Reemplazado por un
     * closure que usa el modelo Topologia (que sí resuelve el schema bien).
     */
    private function reglaTopologiaExiste()
    {
        return function ($attribute, $value, $fail) {
            if ($value && ! Topologia::where('id', $value)->exists()) {
                $fail('La topología seleccionada no existe.');
            }
        };
    }

    public function attachProvincia(Request $request, Operativo $operativo)
    {
        $request->validate(['provincia_id' => 'required|integer|exists:provincia,id']);

        $operativo->provincias()->syncWithoutDetaching([$request->provincia_id]);

        flash('Provincia vinculada.')->success();

        return redirect()->route('operativos.show', $operativo);
    }

    public function detachProvincia(Operativo $operativo, Provincia $provincia)
    {
        $operativo->provincias()->detach($provincia->id);

        flash('Provincia desvinculada.')->success();

        return redirect()->route('operativos.show', $operativo);
    }
}
