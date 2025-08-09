<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Provincium extends Model
{
    protected $fillable = [
        'codigo',
        'nombre',
        'fecha_desde',
        'fecha_hasta',
        'observacion_id',
        'geometria_id',
    
    ];
    
    
    protected $dates = [
        'fecha_desde',
        'fecha_hasta',
    
    ];
    public $timestamps = false;
    
    protected $appends = ['resource_url'];

    /* ************************ ACCESSOR ************************* */

    public function getResourceUrlAttribute()
    {
        return url('/admin/provincia/'.$this->getKey());
    }
}
