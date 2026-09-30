<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class ViaActualizacion extends Model
{
    protected $table = 'vias_actualizaciones';

    public $timestamps = false;

    protected $fillable = ['esquema', 'user_id', 'motivo', 'agregados', 'borrados', 'casos'];

    protected $casts = [
        'casos' => 'array',
        'created_at' => 'datetime',
    ];

    public function usuario()
    {
        return $this->belongsTo('App\User', 'user_id');
    }
}
