<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class TipoDePoblacion extends Model
{
    protected $table = 'tipo_de_poblacion';

    protected $fillable = ['nombre', 'descripcion'];

    public $timestamps = false;

    public function localidades()
    {
        return $this->hasMany('App\Model\Localidad', 'tipo_de_poblacion_id');
    }

    public function gobiernosLocales()
    {
        return $this->hasMany('App\Model\GobiernoLocal', 'tipo_de_poblacion_id');
    }

    public function aglomerados()
    {
        return $this->hasMany('App\Model\Aglomerado', 'tipo_de_poblacion_id');
    }

    public function parajes()
    {
        return $this->hasMany('App\Model\Paraje', 'tipo_de_poblacion_id');
    }
}
