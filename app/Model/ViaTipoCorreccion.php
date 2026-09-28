<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class ViaTipoCorreccion extends Model
{
    protected $table = 'via_tipo_correccion';

    protected $fillable = ['tipo_original', 'tipo_corregido', 'motivo', 'user_id'];

    public function usuario()
    {
        return $this->belongsTo('App\User', 'user_id');
    }
}
