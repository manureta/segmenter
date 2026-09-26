<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Relations\Pivot;

class LocalidadGobiernoLocal extends Pivot
{
    protected $table = 'localidad_gobierno_local';

    protected $fillable = [
        'localidad_id', 'gobierno_local_id', 'fecha_desde', 'fecha_hasta', 'observacion_id',
    ];

    public $incrementing = true;

    // Vigencia se controla con fecha_desde/fecha_hasta, no con timestamps de Eloquent.
    public $timestamps = false;

    public function localidad()
    {
        return $this->belongsTo('App\Model\Localidad');
    }

    public function gobiernoLocal()
    {
        return $this->belongsTo('App\Model\GobiernoLocal');
    }

    /**
     * Solo la relación vigente (fecha_hasta null) - la BD garantiza que hay
     * a lo sumo una por par localidad/gobierno_local (índice único parcial).
     */
    public function scopeVigente($query)
    {
        return $query->whereNull('fecha_hasta');
    }
}
