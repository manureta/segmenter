<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class TipoDeAgl extends Model
{
    protected $table = 'tipo_de_agl';

    protected $fillable = ['nombre', 'descripcion'];

    public $timestamps = false;

    // Nota: gobierno_local.tipo_de_agl no tiene sufijo _id (así viene del dump
    // original), pero funciona igual como FK hacia esta tabla.
    public function gobiernosLocales()
    {
        return $this->hasMany('App\Model\GobiernoLocal', 'tipo_de_agl');
    }
}
