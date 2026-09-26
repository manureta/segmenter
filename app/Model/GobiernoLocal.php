<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class GobiernoLocal extends Model
{
    protected $table = 'gobierno_local';

    protected $fillable = [
        'codigo', 'nombre', 'categoria_de_agl_id', 'tipo_de_agl', 'tipo_de_poblacion_id',
    ];

    // Sin fecha de creación o modificación
    public $timestamps = false;

    /**
     * Relación con Departamentos, con historial (fecha_desde/fecha_hasta propias del vínculo).
     */
    public function departamentos()
    {
        return $this->belongsToMany('App\Model\Departamento', 'gobierno_local_departamento')
            ->using('App\Model\GobiernoLocalDepartamento')
            ->withPivot(['id', 'fecha_desde', 'fecha_hasta', 'observacion_id']);
    }

    /**
     * Solo los Departamentos cuyo vínculo con este Gobierno Local está vigente hoy.
     */
    public function departamentosVigentes()
    {
        return $this->departamentos()->wherePivotNull('fecha_hasta');
    }

    /**
     * Relación con Localidades, con historial (fecha_desde/fecha_hasta propias del vínculo).
     */
    public function localidades()
    {
        return $this->belongsToMany('App\Model\Localidad', 'localidad_gobierno_local')
            ->using('App\Model\LocalidadGobiernoLocal')
            ->withPivot(['id', 'fecha_desde', 'fecha_hasta', 'observacion_id']);
    }

    /**
     * Solo las Localidades cuyo vínculo con este Gobierno Local está vigente hoy.
     */
    public function localidadesVigentes()
    {
        return $this->localidades()->wherePivotNull('fecha_hasta');
    }

    /**
     * Relación con Entidades directamente asignadas (entidad.gobierno_local_id).
     */
    public function entidades()
    {
        return $this->hasMany('App\Model\Entidad');
    }

    /**
     * Relación con Entidades vía tabla pivot (gobierno_local_entidad), con historial:
     * a diferencia de entidad.gobierno_local_id (puntero al vínculo actual), esta
     * tabla puede tener varias filas por par con fecha_desde/fecha_hasta propias.
     */
    public function entidadesPivot()
    {
        return $this->belongsToMany('App\Model\Entidad', 'gobierno_local_entidad')
            ->using('App\Model\GobiernoLocalEntidad')
            ->withPivot(['id', 'fecha_desde', 'fecha_hasta', 'observacion_id']);
    }

    /**
     * Solo las Entidades cuyo vínculo con este Gobierno Local está vigente hoy.
     */
    public function entidadesVigentes()
    {
        return $this->entidadesPivot()->wherePivotNull('fecha_hasta');
    }

    /**
     * Relación con Parajes directamente asignados (paraje.gobierno_local_id).
     */
    public function parajes()
    {
        return $this->hasMany('App\Model\Paraje');
    }

    /**
     * Relación con Operativos, un Gobierno Local puede participar de varios operativos.
     */
    public function operativos()
    {
        return $this->belongsToMany('App\Model\Operativo', 'operativo__gobierno_local');
    }
}
