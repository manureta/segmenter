<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TipoDeRadio extends Model
{
    protected $table = 'tipo_de_radio';

    protected $fillable = [
        'nombre',
        'descripcion',
    
    ];
    
    
    protected $dates = [
    
    ];
    public $timestamps = false;
    
    protected $appends = ['resource_url'];

    /* ************************ ACCESSOR ************************* */

    public function getResourceUrlAttribute()
    {
        return url('/admin/tipo-de-radios/'.$this->getKey());
    }
}
