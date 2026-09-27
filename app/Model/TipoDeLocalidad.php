<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class TipoDeLocalidad extends Model
{
    protected $table = 'tipo_de_localidad';

    protected $fillable = ['nombre', 'descripcion'];

    public $timestamps = false;

    public function localidades()
    {
        return $this->hasMany('App\Model\Localidad', 'tipo_de_localidad_id');
    }
}
