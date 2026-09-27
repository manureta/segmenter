<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class Observacion extends Model
{
    protected $table = 'observaciones';

    protected $fillable = [
        'texto',
    ];

    // Sí tiene created_at/updated_at, a diferencia del resto del catálogo:
    // es una nota puntual (por qué se hizo un cambio), no una entidad con vigencia propia.
}
