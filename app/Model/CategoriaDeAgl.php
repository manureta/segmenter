<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class CategoriaDeAgl extends Model
{
    protected $table = 'categoria_de_agl';

    protected $fillable = ['nombre', 'descripcion'];

    public $timestamps = false;

    public function gobiernosLocales()
    {
        return $this->hasMany('App\Model\GobiernoLocal', 'categoria_de_agl_id');
    }
}
