<?php

namespace App\Model\Concerns;

/**
 * Filtra automáticamente por el operativo seleccionado en sesión (ver
 * App\Http\Middleware\CompartirOperativoActual), vía la relación
 * belongsToMany "operativos" que ya tiene el modelo (operativo_provincia,
 * operativo_localidad, etc).
 *
 * Sin operativo seleccionado: no filtra nada, comportamiento actual sin
 * cambios. Con uno seleccionado: solo devuelve filas vinculadas a ese
 * operativo - para entidad/gobierno_local/paraje/base_antartica eso hoy
 * es "nada" (0 filas tageadas todavía en ninguna), es el comportamiento
 * esperado, no un bug.
 *
 * Se puede desactivar puntualmente con
 * Modelo::withoutGlobalScope('operativo_actual')->...
 */
trait EscopadoPorOperativo
{
    public static function bootEscopadoPorOperativo()
    {
        static::addGlobalScope('operativo_actual', function ($query) {
            if (app()->bound('operativoActual') && app('operativoActual')) {
                $operativoId = app('operativoActual')->id;
                $query->whereHas('operativos', function ($q) use ($operativoId) {
                    $q->where('operativo.id', $operativoId);
                });
            }
        });
    }
}
