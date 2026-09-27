<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class Operativo extends Model
{
    protected $table = 'operativo';

    protected $fillable = [
        'nombre', 'observacion', 'topologia_id',
    ];

    // Sin fecha de creación o modificación
    public $timestamps = false;

    /**
     * Relación con Provincias que participan de este Operativo.
     */
    public function provincias()
    {
        return $this->belongsToMany('App\Model\Provincia', 'operativo_provincia')->withTimestamps();
    }

    /**
     * Relación con Localidades que participan de este Operativo.
     */
    public function localidades()
    {
        return $this->belongsToMany('App\Model\Localidad', 'operativo_localidad');
    }

    /**
     * Relación con Entidades que participan de este Operativo.
     */
    public function entidades()
    {
        return $this->belongsToMany('App\Model\Entidad', 'operativo_entidad');
    }

    /**
     * Relación con Gobiernos Locales que participan de este Operativo.
     */
    public function gobiernosLocales()
    {
        return $this->belongsToMany('App\Model\GobiernoLocal', 'operativo_gobierno_local');
    }

    /**
     * Relación con Parajes que participan de este Operativo.
     */
    public function parajes()
    {
        return $this->belongsToMany('App\Model\Paraje', 'operativo_paraje');
    }

    /**
     * Relación con Bases Antárticas que participan de este Operativo.
     */
    public function baseAntarticas()
    {
        return $this->belongsToMany('App\Model\BaseAntartica', 'operativo_base_antartica');
    }

    /**
     * topology.topology asociada a este Operativo - hoy nullable y sin uso
     * real: indec.cargartopologia()/cargar_topologia_pais() todavía nombran
     * la topología por SRID (<srid>_topo) o una única nacional (pais_topo),
     * compartida entre operativos, no por esta columna. Queda el modelo
     * listo para cuando se decida cambiar esa convención (fuera de este
     * spike: son funciones de riesgo, no se tocan sin test de
     * caracterización antes).
     */
    public function topologia()
    {
        return $this->belongsTo('App\Model\Topologia', 'topologia_id');
    }
}
