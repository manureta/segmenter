<?php

namespace App\Http\Controllers;

use App\Model\Operativo;
use App\Model\Topologia;
use App\Model\Provincia;
use Illuminate\Http\Request;

class OperativoController extends Controller
{
    /**
     * Sin gate de "run-setup" acá a propósito: cualquier usuario autenticado
     * puede crear su propio operativo privado (pedido explícito). El control
     * fino de qué puede ver/editar/publicar de otros pasa por OperativoPolicy,
     * no por un gate parejo para todos.
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index(Request $request)
    {
        $operativos = Operativo::visiblesPara($request->user())
            ->withCount(['provincias', 'localidades', 'entidades', 'gobiernosLocales', 'parajes', 'baseAntarticas'])
            ->orderBy('nombre')
            ->get();

        return view('operativo.index', compact('operativos'));
    }

    public function create()
    {
        $this->authorize('create', Operativo::class);

        $topologias = Topologia::orderBy('name')->get();

        return view('operativo.form', [
            'operativo' => new Operativo,
            'topologias' => $topologias,
        ]);
    }

    public function store(Request $request)
    {
        $this->authorize('create', Operativo::class);

        $datos = $request->validate([
            'nombre' => 'required|string|max:50|unique:operativo,nombre',
            'observacion' => 'nullable|string|max:50',
            'topologia_id' => ['nullable', 'integer', $this->reglaTopologiaExiste()],
        ]);

        // Nace privado y con dueño - publicarlo es una acción aparte que
        // requiere el permiso "Publicar Operativos" (ver OperativoPolicy).
        $datos['user_id'] = $request->user()->id;
        $datos['es_publico'] = false;

        $operativo = Operativo::create($datos);

        flash('Operativo "'.$operativo->nombre.'" creado (privado).')->success();

        return redirect()->route('operativos.show', $operativo);
    }

    public function show(Request $request, Operativo $operativo)
    {
        $this->authorize('view', $operativo);

        $operativo->loadCount(['provincias', 'localidades', 'entidades', 'gobiernosLocales', 'parajes', 'baseAntarticas']);
        $provinciasVinculadas = $operativo->provincias()->orderBy('nombre')->get();
        $provinciasDisponibles = Provincia::whereNotIn('id', $provinciasVinculadas->pluck('id'))->orderBy('nombre')->get();
        $puedeEditar = $request->user()->can('update', $operativo);
        $puedePublicar = $request->user()->can('publicar', $operativo);

        return view('operativo.show', compact('operativo', 'provinciasVinculadas', 'provinciasDisponibles', 'puedeEditar', 'puedePublicar'));
    }

    public function edit(Operativo $operativo)
    {
        $this->authorize('update', $operativo);

        $topologias = Topologia::orderBy('name')->get();

        return view('operativo.form', compact('operativo', 'topologias'));
    }

    public function update(Request $request, Operativo $operativo)
    {
        $this->authorize('update', $operativo);

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
    public function destroy(Request $request, Operativo $operativo)
    {
        $this->authorize('delete', $operativo);

        $operativo->provincias()->detach();
        $operativo->localidades()->detach();
        $operativo->entidades()->detach();
        $operativo->gobiernosLocales()->detach();
        $operativo->parajes()->detach();
        $operativo->baseAntarticas()->detach();

        $nombre = $operativo->nombre;
        $eraElActual = session('operativo_actual_id') == $operativo->id;
        $operativo->delete();
        if ($eraElActual) {
            session()->forget('operativo_actual_id');
        }

        flash('Operativo "'.$nombre.'" eliminado (se desvincularon sus '.
            'relaciones con provincias/localidades/etc antes de borrar).')->success();

        return redirect()->route('operativos.index');
    }

    /**
     * Privado -> público. Requiere OperativoPolicy::publicar (dueño +
     * permiso "Publicar Operativos", o "Administrar Operativos").
     */
    public function publicar(Request $request, Operativo $operativo)
    {
        $this->authorize('publicar', $operativo);

        $operativo->update(['es_publico' => true]);

        flash('Operativo "'.$operativo->nombre.'" ahora es público - lo pueden ver todos.')->success();

        return redirect()->route('operativos.show', $operativo);
    }

    /**
     * Público -> privado (mismo permiso que publicar; volver a esconderlo
     * no es más peligroso que mostrarlo).
     */
    public function despublicar(Request $request, Operativo $operativo)
    {
        $this->authorize('publicar', $operativo);

        $operativo->update(['es_publico' => false]);

        flash('Operativo "'.$operativo->nombre.'" ahora es privado.')->success();

        return redirect()->route('operativos.show', $operativo);
    }

    /**
     * "Operativo actual" en sesión - el que usa EscopadoPorOperativo para
     * filtrar todo el resto de la app (ver App\Model\Concerns).
     */
    public function seleccionar(Request $request, Operativo $operativo)
    {
        $this->authorize('view', $operativo);

        session(['operativo_actual_id' => $operativo->id]);

        flash('Operativo actual: "'.$operativo->nombre.'". El resto de la navegación queda filtrado a lo que tiene vinculado.')->success();

        return redirect()->back();
    }

    public function deseleccionar(Request $request)
    {
        session()->forget('operativo_actual_id');

        flash('Se quitó el filtro de operativo - navegando sin restricción de nuevo.')->success();

        return redirect()->back();
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
        $this->authorize('update', $operativo);

        $request->validate(['provincia_id' => 'required|integer|exists:provincia,id']);

        $operativo->provincias()->syncWithoutDetaching([$request->provincia_id]);

        flash('Provincia vinculada.')->success();

        return redirect()->route('operativos.show', $operativo);
    }

    public function detachProvincia(Request $request, Operativo $operativo, Provincia $provincia)
    {
        $this->authorize('update', $operativo);

        $operativo->provincias()->detach($provincia->id);

        flash('Provincia desvinculada.')->success();

        return redirect()->route('operativos.show', $operativo);
    }
}
