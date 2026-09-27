<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class CategoriaDeSegmento extends Model
{
    protected $table = 'categoria_de_segmento';

    protected $fillable = ['nombre', 'descripcion'];

    public $timestamps = false;

    // Sin relación todavía: la tabla "segmentos" de este esquema no tiene
    // columna categoria_de_segmento_id.
}
