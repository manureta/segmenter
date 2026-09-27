<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class Fraccion extends Model
{
    use \App\Model\Concerns\EscopadoPorOperativoHeredado;

    //
    protected $table='fraccion';

    public function rutaHaciaOperativo()
    {
        return 'departamento.provincia';
    }

    protected $fillable = [
        'codigo'
    ];

    // Sin fecha de creación o modificación
    //
    public $timestamps = false;

     /**
      * Relación con Radios, una Fraccion tiene uno o varios radios.
      *
      */

     public function radios()
     {
         return $this->hasMany('App\Model\Radio');
     }

     /**
      * Relación con Departamento, una Fraccion pertenece a Un departamento. 
      *
      */

     public function departamento()
     {
         return $this->belongsTo('App\Model\Departamento');
     }

     public function observacion()
     {
         return $this->belongsTo('App\Model\Observacion');
     }

}
