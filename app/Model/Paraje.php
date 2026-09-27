<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class Paraje extends Model
{
    protected $table = 'paraje';

    protected $fillable = [
        'codigo', 'nombre', 'departamento_id', 'fuente_id', 'gobierno_local_id', 'tipo_de_poblacion_id',
    ];

    // Sin fecha de creación o modificación
    public $timestamps = false;

    /**
     * Relación con Departamento, un Paraje pertenece a un departamento.
     */
    public function departamento()
    {
        return $this->belongsTo('App\Model\Departamento');
    }

    /**
     * Relación con Gobierno Local (paraje.gobierno_local_id).
     */
    public function gobiernoLocal()
    {
        return $this->belongsTo('App\Model\GobiernoLocal', 'gobierno_local_id');
    }

    /**
     * Relación con Fuente del dato (paraje.fuente_id).
     */
    public function fuente()
    {
        return $this->belongsTo('App\Model\Fuente');
    }

    public function observacion()
    {
        return $this->belongsTo('App\Model\Observacion');
    }

    public function tipoDePoblacion()
    {
        return $this->belongsTo('App\Model\TipoDePoblacion', 'tipo_de_poblacion_id');
    }

    public function sedeDeGobiernoLocal()
    {
        return $this->belongsTo('App\Model\GobiernoLocal', 'sede_gob_loc');
    }

    /**
     * Relación con Operativos, un Paraje puede participar de varios operativos.
     */
    public function operativos()
    {
        return $this->belongsToMany('App\Model\Operativo', 'operativo_paraje');
    }
}
