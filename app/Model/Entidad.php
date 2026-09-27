<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class Entidad extends Model
{
    protected $table = 'entidad';

    protected $fillable = [
        'codigo', 'nombre', 'localidad_id', 'gobierno_local_id',
    ];

    // Sin fecha de creación o modificación
    public $timestamps = false;

    /**
     * Relación con Localidad, una Entidad pertenece a una localidad.
     */
    public function localidad()
    {
        return $this->belongsTo('App\Model\Localidad');
    }

    /**
     * Relación con Gobierno Local (entidad.gobierno_local_id) — puntero al vínculo actual.
     */
    public function gobiernoLocal()
    {
        return $this->belongsTo('App\Model\GobiernoLocal', 'gobierno_local_id');
    }

    public function observacion()
    {
        return $this->belongsTo('App\Model\Observacion');
    }

    /**
     * Historial de Gobiernos Locales (gobierno_local_entidad, con fecha_desde/fecha_hasta) —
     * a diferencia de gobiernoLocal(), acá puede haber más de un vínculo a lo largo del tiempo.
     */
    public function gobiernosLocalesHistorial()
    {
        return $this->belongsToMany('App\Model\GobiernoLocal', 'gobierno_local_entidad')
            ->using('App\Model\GobiernoLocalEntidad')
            ->withPivot(['id', 'fecha_desde', 'fecha_hasta', 'observacion_id']);
    }

    /**
     * Relación con Radios, una Entidad puede estar en varios radios.
     */
    public function radios()
    {
        return $this->belongsToMany('App\Model\Radio', 'radio_entidad');
    }

    /**
     * Relación con Operativos, una Entidad puede participar de varios operativos.
     */
    public function operativos()
    {
        return $this->belongsToMany('App\Model\Operativo', 'operativo__entidad');
    }
}
