<?php

namespace App\Http\Controllers\Concerns;

use App\Support\AlcanceGeografico;

/**
 * Resuelve el alcance geográfico activo (objeto elegido en sesión, o la
 * restricción fija del usuario, o global) - usado por cualquier
 * controller que necesite acotar un reporte por Provincia/Departamento/
 * Aglomerado/Localidad/Fracción/Radio. Extraído de ControlCalidadController
 * para que ViaController (y los que vengan) no lo reimplementen.
 */
trait TieneAlcanceGeografico
{
    protected function alcanceActivo(): array
    {
        $codigosPermitidos = auth()->user()->codigosProvinciaPermitidos();

        if (app()->bound('objetoActual') && app('objetoActual')) {
            $tipo = app('objetoActualTipo');
            $objeto = app('objetoActual');
            $prefijos = AlcanceGeografico::prefijosDe($tipo, $objeto);

            if (AlcanceGeografico::permitidoPara($codigosPermitidos, $prefijos)) {
                return [
                    'prefijos' => $prefijos,
                    'etiqueta' => 'Filtrado por: '.AlcanceGeografico::etiquetaDe($tipo, $objeto),
                    'global' => false,
                ];
            }
        }

        if ($codigosPermitidos !== null) {
            return [
                'prefijos' => $codigosPermitidos,
                'etiqueta' => 'No es global - restringido a tu(s) provincia(s): '.implode(', ', $codigosPermitidos),
                'global' => false,
            ];
        }

        return ['prefijos' => null, 'etiqueta' => 'Reporte GLOBAL (todas las provincias)', 'global' => true];
    }
}
