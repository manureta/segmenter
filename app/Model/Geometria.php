<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class Geometria extends Model
{
    //
    protected $table='geometrias';
    protected $primaryKey = 'id';
    protected $fillable = ['poligono', 'punto', 'topogeometria'];


    /**
     * Relación con Entidad.
     *
     */

    public function entidad()
    {
        return $this->hasMany('App\Model\Entidad');
    }

}
