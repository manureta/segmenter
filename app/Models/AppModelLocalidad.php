<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AppModelLocalidad extends Model
{
    protected $table = 'localidad';

    protected $fillable = [
        'codigo',
        'nombre',
        'aglomerado_id',
        'tipo_de_localidad_id',
        'tipo_de_poblacion_id',
        'fecha_desde',
        'fecha_hasta',
        'observacion_id',
        'geometria_id',
        'cap_de_rep',
        'cap_de_pcia',
        'cab_de_depto',
        'sede_gob_loc',
    
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
        return url('/admin/app-model-localidads/'.$this->getKey());
    }
}
