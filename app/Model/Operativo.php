<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class Operativo extends Model
{
    protected $table = 'operativo';

    protected $fillable = [
        'nombre', 'observacion',
    ];

    // Sin fecha de creación o modificación
    public $timestamps = false;

    /**
     * Relación con Provincias que participan de este Operativo.
     */
    public function provincias()
    {
        return $this->belongsToMany('App\Model\Provincia', 'operativo_provincia');
    }

    /**
     * Relación con Localidades que participan de este Operativo.
     */
    public function localidades()
    {
        return $this->belongsToMany('App\Model\Localidad', 'operativo__localidad');
    }

    /**
     * Relación con Entidades que participan de este Operativo.
     */
    public function entidades()
    {
        return $this->belongsToMany('App\Model\Entidad', 'operativo__entidad');
    }

    /**
     * Relación con Gobiernos Locales que participan de este Operativo.
     */
    public function gobiernosLocales()
    {
        return $this->belongsToMany('App\Model\GobiernoLocal', 'operativo__gobierno_local');
    }

    /**
     * Relación con Parajes que participan de este Operativo.
     */
    public function parajes()
    {
        return $this->belongsToMany('App\Model\Paraje', 'operativo__paraje');
    }

    // Nota: operativo__base_antartica existe como tabla pivot pero la tabla
    // base_antartica que debería referenciar no está creada todavía en este
    // esquema (confirmado); por eso no hay relación baseAntarticas() acá.
}
