<?php

namespace App\Http\Middleware;

use App\Support\AlcanceGeografico;
use Closure;
use Illuminate\Support\Facades\View;

/**
 * Mismo patrón que CompartirOperativoActual, pero para el "objeto
 * geográfico actual" (Provincia/Departamento/Aglomerado/Localidad/
 * Fracción/Radio) - pedido explícito para acotar reportes y navegar la
 * jerarquía territorial subiendo/bajando de nivel.
 */
class CompartirObjetoActual
{
    public function handle($request, Closure $next)
    {
        $objetoActual = null;
        $tipoActual = null;

        if ($request->user() && session()->has('objeto_actual_tipo') && session()->has('objeto_actual_id')) {
            $tipoActual = session('objeto_actual_tipo');
            $objetoActual = AlcanceGeografico::resolver($tipoActual, session('objeto_actual_id'));

            if (!$objetoActual) {
                session()->forget(['objeto_actual_tipo', 'objeto_actual_id']);
                $tipoActual = null;
            }
        }

        app()->instance('objetoActual', $objetoActual);
        app()->instance('objetoActualTipo', $tipoActual);
        View::share('objetoActual', $objetoActual);
        View::share('objetoActualTipo', $tipoActual);

        return $next($request);
    }
}
