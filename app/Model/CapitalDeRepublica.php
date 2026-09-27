<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class CapitalDeRepublica extends Model
{
    protected $table = 'capital_de_republica';

    protected $fillable = [
        'pais_id', 'localidad_id', 'fecha_desde', 'fecha_hasta', 'observacion_id',
    ];

    public $timestamps = false;

    public function pais()
    {
        return $this->belongsTo('App\Model\Pais');
    }

    public function localidad()
    {
        return $this->belongsTo('App\Model\Localidad');
    }

    public function observacion()
    {
        return $this->belongsTo('App\Model\Observacion');
    }

    public function scopeVigente($query)
    {
        return $query->whereNull('fecha_hasta');
    }
}
