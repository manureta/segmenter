<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class CabeceraDeDepartamento extends Model
{
    protected $table = 'cabecera_de_departamento';

    protected $fillable = [
        'departamento_id', 'localidad_id', 'fecha_desde', 'fecha_hasta', 'observacion_id',
    ];

    public $timestamps = false;

    public function departamento()
    {
        return $this->belongsTo('App\Model\Departamento');
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
