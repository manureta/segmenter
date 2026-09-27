<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class TipoDeBaseAntartica extends Model
{
    protected $table = 'tipo_de_base_antartica';

    protected $fillable = ['nombre', 'descripcion'];

    public $timestamps = false;

    // Sin relación todavía: la tabla base_antartica que debería referenciar
    // esto no existe en este esquema (ver SIGeo-informe-implementacion.md).
}
