<?php

namespace App\Model\Concerns;

/**
 * Variante de EscopadoPorOperativo para modelos que NO tienen su propia
 * tabla puente con operativo (Departamento, Fraccion, Radio - toto5 nunca
 * trajo operativo__departamento/fraccion/radio). En vez de "sin filas
 * propias tageadas, esto da 0" (que sería el resultado incorrecto acá,
 * confirmado con datos reales: Departamento::count() y Radio::count()
 * seguían en 155/6924 sin cambios al seleccionar un operativo, mientras
 * Provincia/Localidad sí bajaban a 0), hereda el scope desde el ancestro
 * de jerarquía que si tiene tagging directo (Provincia, vía belongsTo en
 * cadena) - "verificar asignación propia o, si no tiene, heredar del
 * nivel superior".
 *
 * Cada modelo que usa este trait define rutaHaciaOperativo() con el
 * camino de relaciones belongsTo hasta llegar a un modelo con operativos().
 */
trait EscopadoPorOperativoHeredado
{
    public static function bootEscopadoPorOperativoHeredado()
    {
        static::addGlobalScope('operativo_actual', function ($query) {
            if (app()->bound('operativoActual') && app('operativoActual')) {
                $operativoId = app('operativoActual')->id;
                $ruta = (new static)->rutaHaciaOperativo().'.operativos';

                $query->whereHas($ruta, function ($q) use ($operativoId) {
                    $q->where('operativo.id', $operativoId);
                });
            }
        });
    }
}
