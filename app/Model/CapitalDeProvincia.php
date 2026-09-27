<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class CapitalDeProvincia extends Model
{
    protected $table = 'capital_de_provincia';

    protected $fillable = [
        'provincia_id', 'localidad_id', 'fecha_desde', 'fecha_hasta', 'observacion_id',
    ];

    public $timestamps = false;

    public function provincia()
    {
        return $this->belongsTo('App\Model\Provincia');
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
