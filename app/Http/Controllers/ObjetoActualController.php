<?php

namespace App\Http\Controllers;

use App\Support\AlcanceGeografico;
use App\Model\Provincia;
use Illuminate\Http\Request;

class ObjetoActualController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Selecciona un objeto puntual (tipo+id) como alcance actual. Si el
     * usuario tiene alcance restringido (provincias asignadas fijo), no
     * puede elegir nada fuera de esas provincias - ver
     * App\Support\AlcanceGeografico::permitidoPara().
     */
    public function seleccionar(Request $request)
    {
        $data = $request->validate([
            'tipo' => 'required|in:'.implode(',', array_keys(AlcanceGeografico::TIPOS)),
            'id' => 'required|integer',
        ]);

        $objeto = AlcanceGeografico::resolver($data['tipo'], $data['id']);
        if (!$objeto) {
            flash('No se encontró ese objeto.')->error();
            return redirect()->back();
        }

        $prefijos = AlcanceGeografico::prefijosDe($data['tipo'], $objeto);
        if (!AlcanceGeografico::permitidoPara($request->user()->codigosProvinciaPermitidos(), $prefijos)) {
            flash('Tu alcance está restringido a tu(s) provincia(s) asignada(s) - no podés elegir esto.')->error();
            return redirect()->back();
        }

        session(['objeto_actual_tipo' => $data['tipo'], 'objeto_actual_id' => $objeto->id]);
        flash('Alcance: '.AlcanceGeografico::etiquetaDe($data['tipo'], $objeto))->success();

        return redirect()->back();
    }

    /**
     * "Subir" un nivel: Radio->Fracción->Departamento->Provincia siguiendo
     * las FK reales (no los pivots sparse de Localidad/Aglomerado). Desde
     * Localidad/Aglomerado sube directo a su provincia (derivada por
     * prefijo de código, ver AlcanceGeografico).
     */
    public function subirNivel(Request $request)
    {
        $tipo = session('objeto_actual_tipo');
        $objeto = $tipo ? AlcanceGeografico::resolver($tipo, session('objeto_actual_id')) : null;

        if (!$objeto) {
            return redirect()->back();
        }

        [$tipoPadre, $padre] = $this->padreDe($tipo, $objeto);

        if (!$padre) {
            session()->forget(['objeto_actual_tipo', 'objeto_actual_id']);
            flash('Alcance: global (ya no hay nivel más arriba).')->success();
        } else {
            session(['objeto_actual_tipo' => $tipoPadre, 'objeto_actual_id' => $padre->id]);
            flash('Alcance: '.AlcanceGeografico::etiquetaDe($tipoPadre, $padre))->success();
        }

        return redirect()->back();
    }

    private function padreDe(string $tipo, $objeto): array
    {
        switch ($tipo) {
            case 'radio':
                return $objeto->fraccion ? ['fraccion', $objeto->fraccion] : [null, null];
            case 'fraccion':
                return $objeto->departamento ? ['departamento', $objeto->departamento] : [null, null];
            case 'departamento':
                return $objeto->provincia ? ['provincia', $objeto->provincia] : [null, null];
            case 'localidad':
            case 'aglomerado':
                $prefijos = AlcanceGeografico::prefijosDe($tipo, $objeto);
                $provincia = $prefijos ? Provincia::withoutGlobalScope('operativo_actual')
                    ->where('codigo', $prefijos[0])->first() : null;
                return $provincia ? ['provincia', $provincia] : [null, null];
            default:
                return [null, null];
        }
    }

    public function deseleccionar()
    {
        session()->forget(['objeto_actual_tipo', 'objeto_actual_id']);
        flash('Alcance: global.')->success();
        return redirect()->back();
    }
}
