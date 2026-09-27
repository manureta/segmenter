<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Relations\Pivot;

class GobiernoLocalEntidad extends Pivot
{
    protected $table = 'gobierno_local_entidad';

    protected $fillable = [
        'gobierno_local_id', 'entidad_id', 'fecha_desde', 'fecha_hasta', 'observacion_id',
    ];

    // Pivot asume PK compuesta sin auto-increment por defecto; acá sí hay id bigserial.
    public $incrementing = true;

    // Vigencia se controla con fecha_desde/fecha_hasta, no con timestamps de Eloquent.
    public $timestamps = false;

    public function gobiernoLocal()
    {
        return $this->belongsTo('App\Model\GobiernoLocal');
    }

    public function entidad()
    {
        return $this->belongsTo('App\Model\Entidad');
    }

    public function observacion()
    {
        return $this->belongsTo('App\Model\Observacion');
    }

    /**
     * Solo la relación vigente (fecha_hasta null) - la BD garantiza que hay
     * a lo sumo una por par gobierno_local/entidad (índice único parcial).
     */
    public function scopeVigente($query)
    {
        return $query->whereNull('fecha_hasta');
    }
}
