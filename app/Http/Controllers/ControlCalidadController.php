<?php

namespace App\Http\Controllers;

use App\Model\Radio;
use Illuminate\Support\Facades\DB;

class ControlCalidadController extends Controller
{
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
        $controles = [
            'radios_sin_fraccion' => $this->radiosSinFraccion(),
            'radios_codigo_duplicado' => $this->radiosConCodigoDuplicado(),
        ];

        return view('control-calidad.index', compact('controles'));
    }

    /**
     * radio.fraccion_id nulo - sin él, EscopadoPorOperativoHeredado no
     * puede ubicar el radio en ninguna provincia (y `fraccion` en general
     * no tiene forma de asociarlo a nada más arriba en la jerarquía).
     */
    private function radiosSinFraccion()
    {
        // withoutGlobalScope a propósito: un control de calidad tiene que
        // ver TODO el dato sucio, no lo que quede filtrado por el operativo
        // que alguien tenga seleccionado en su sesión en este momento (un
        // radio sin fraccion_id de por sí nunca matchea ningún operativo,
        // así que sin este bypass el reporte daría "0 problemas" siempre
        // que hay un operativo activo, aunque el problema exista).
        return Radio::withoutGlobalScope('operativo_actual')
            ->whereNull('fraccion_id')->orderBy('codigo')->get();
    }

    /**
     * Dos o más radios con el mismo código de 15 dígitos - no debería
     * pasar nunca (el código es la identidad territorial del radio), pero
     * los 2 huérfanos encontrados comparten código exacto.
     */
    private function radiosConCodigoDuplicado()
    {
        $codigosDuplicados = Radio::withoutGlobalScope('operativo_actual')
            ->select('codigo')
            ->whereNotNull('codigo')
            ->groupBy('codigo')
            ->havingRaw('count(*) > 1')
            ->pluck('codigo');

        return Radio::withoutGlobalScope('operativo_actual')
            ->whereIn('codigo', $codigosDuplicados)->orderBy('codigo')->get();
    }
}
