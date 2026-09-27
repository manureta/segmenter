<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class SedeGobiernoLocal extends Model
{
    protected $table = 'sede_gobierno_local';

    protected $fillable = [
        'gobierno_local_id', 'entidad_id', 'localidad_id', 'paraje_id', 'fecha_desde', 'fecha_hasta', 'observacion_id',
    ];

    public $timestamps = false;

    public function gobiernoLocal()
    {
        return $this->belongsTo('App\Model\GobiernoLocal');
    }

    public function entidad()
    {
        return $this->belongsTo('App\Model\Entidad');
    }

    public function localidad()
    {
        return $this->belongsTo('App\Model\Localidad');
    }

    public function paraje()
    {
        return $this->belongsTo('App\Model\Paraje');
    }

    public function observacion()
    {
        return $this->belongsTo('App\Model\Observacion');
    }

    public function scopeVigente($query)
    {
        return $query->whereNull('fecha_hasta');
    }

    /**
     * La sede en sí (exactamente una de entidad/localidad/paraje, garantizado
     * por el CHECK de la tabla), sin que el caller tenga que saber cuál es.
     */
    public function getSedeAttribute()
    {
        return $this->entidad ?? $this->localidad ?? $this->paraje;
    }
}
