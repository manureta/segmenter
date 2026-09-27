<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class Pais extends Model
{
    protected $table = 'pais';

    protected $fillable = [
        'codigo', 'nombre',
    ];

    public $timestamps = false;

    public function observacion()
    {
        return $this->belongsTo('App\Model\Observacion');
    }

    public function geometria()
    {
        return $this->belongsTo('App\Model\Geometria');
    }

    // Nota: provincia no tiene columna pais_id todavía - sin relación
    // hacia abajo hasta que se decida agregarla.

    public function capitalVigente()
    {
        return $this->hasOne('App\Model\CapitalDeRepublica')->vigente();
    }

    public function historialCapital()
    {
        return $this->hasMany('App\Model\CapitalDeRepublica');
    }
}
