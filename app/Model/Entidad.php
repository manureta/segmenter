<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;
use App\MyDB;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Log;
use Auth;

class Entidad extends Model
{
    //
    protected $table = 'entidades';
    protected $primaryKey = 'id';
    protected $fillable = ['codigo', 'nombre', 'localidad_id','geometria'];//,'fecha_desde','fecha_hasta'];

    public static function getEntidadData($table) {
        // devuelve todos los registros las entidades de la tabla entidades
        $value=DB::table($table)->orderBy('id', 'asc')->get();
        Log::notice('Se ejecuta getEntidadData() y devuelve '.count($value).' registros');
        return $value;
    }

    /**
     * Fix datos..
     *
     */
    public function getCodigoAttribute($value) {
        return trim($value);
    }

    /**
     * Relación con Localidad, un Radio puede pertenecer a varias localidades.
     *
     */
    public function localidad() {
        return $this->belongsTo('App\Model\Localidad','localidad_id','id');
    }

    /**
     * Relación con Entidad, un Radio puede estar en varias entidades de varias localidades.
     *
     */
    public function radios() {
        return $this->belongsToMany('App\Model\Radio', 'radio_entidad');
    }

    /**
     * Relación con Aglomerado,
     * un Radio puede pertenecer a varios aglomerados!
     * (? Esperaba que solo este en 1. :( )
     *
     */
    public function aglomerados() {
        $aglos=[];
        foreach ($this->localidades as $localidad) {
            $aglos[] = $localidad->aglomerado;
        }
        return $aglos;
    }

    /**
     * Fix Cantidad de manzanas en cartografia..
     *
     */
    public function getCantMzasAttribute($value) {
        $cant_mzas = MyDB::getCantMzas($this);
        return $cant_mzas;
    }


    /**
     * Relación con geometrias, una entidad puede tener una geometria.
     *
     */
    public function geometria() {
      return $this->belongsTo('App\Model\Geometria', 'geometria_id', 'id');
  }


    /**
     * Relación con geometrias, una entidad puede tener una geometria.
     *
     */
    public function setGeometriaAttribute($poligono = null, $punto = null) {
      Log::debug('SET geometría');
      return $this->geometria_id = MyDB::insertarGeometrias($poligono, $punto);
    }

    /**
     * Get geometrias, una entidad puede tener una geometria.
     *
     */
    public function getGeometriaAttribute($value) {
        // TODO
        //$geometria_id = MyDB::getGeometriasId($value);
        return $this->geometria_id;
    }

  }
