<?php

namespace App\Http\Middleware;

use App\Model\Operativo;
use Closure;
use Illuminate\Support\Facades\View;

class CompartirOperativoActual
{
    /**
     * Lee el operativo_id guardado en sesión (si hay uno y el usuario
     * todavía puede verlo - puede haber perdido acceso, o haberse borrado),
     * lo comparte a todas las vistas como $operativoActual, y lo deja
     * bindeado en el container para que EscopadoPorOperativo (el trait de
     * scope global) lo pueda leer sin depender de la sesión directamente.
     */
    public function handle($request, Closure $next)
    {
        $operativoActual = null;

        if ($request->user() && session()->has('operativo_actual_id')) {
            $operativoActual = Operativo::visiblesPara($request->user())
                ->find(session('operativo_actual_id'));

            if (!$operativoActual) {
                session()->forget('operativo_actual_id');
            }
        }

        app()->instance('operativoActual', $operativoActual);
        View::share('operativoActual', $operativoActual);

        return $next($request);
    }
}
