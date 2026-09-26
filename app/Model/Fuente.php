<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class Fuente extends Model
{
    protected $table = 'fuente';

    protected $fillable = [
        'nombre'
    ];

    public $timestamps = false;

    /**
     * Geometrías cacheadas que provienen de esta fuente.
     */
    public function geometrias()
    {
        return $this->hasMany('App\Model\Geometria');
    }
}
