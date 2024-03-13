<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class Geometria extends Model
{
    //
    protected $table='geometrias';
    protected $primaryKey = 'id';
    protected $fillable = ['poligono', 'punto', 'topogeometria'];

    // Sin fecha de creación o modificación
    //
    public $timestamps = false;

    /**
     * Relación con Entidad.
     *
     */

    public function entidad()
    {
        return $this->hasMany('App\Model\Entidad','geometria_id', 'id');
    }

}
