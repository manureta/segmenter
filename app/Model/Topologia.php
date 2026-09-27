<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class Topologia extends Model
{
    // Tabla de sistema de postgis_topology (schema topology, no gestionado
    // por nuestras migraciones - la crea/puebla topology.CreateTopology()).
    protected $table = 'topology.topology';

    public $timestamps = false;

    public function operativos()
    {
        return $this->hasMany('App\Model\Operativo', 'topologia_id');
    }
}
