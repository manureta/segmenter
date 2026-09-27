<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Relations\Pivot;

class GobiernoLocalDepartamento extends Pivot
{
    protected $table = 'gobierno_local_departamento';

    protected $fillable = [
        'gobierno_local_id', 'departamento_id', 'fecha_desde', 'fecha_hasta', 'observacion_id',
    ];

    public $incrementing = true;

    // Vigencia se controla con fecha_desde/fecha_hasta, no con timestamps de Eloquent.
    public $timestamps = false;

    public function gobiernoLocal()
    {
        return $this->belongsTo('App\Model\GobiernoLocal');
    }

    public function departamento()
    {
        return $this->belongsTo('App\Model\Departamento');
    }

    public function observacion()
    {
        return $this->belongsTo('App\Model\Observacion');
    }

    /**
     * Solo la relación vigente (fecha_hasta null) - la BD garantiza que hay
     * a lo sumo una por par gobierno_local/departamento (índice único parcial).
     */
    public function scopeVigente($query)
    {
        return $query->whereNull('fecha_hasta');
    }
}
