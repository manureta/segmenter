<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class TipoDeSegmento extends Model
{
    protected $table = 'tipo_de_segmento';

    protected $fillable = ['nombre', 'descripcion'];

    public $timestamps = false;

    // Sin relación todavía: la tabla "segmentos" de este esquema no tiene
    // columna tipo_de_segmento_id.
}
