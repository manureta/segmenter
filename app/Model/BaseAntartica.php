<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class BaseAntartica extends Model
{
    use \App\Model\Concerns\EscopadoPorOperativo;

    protected $table = 'base_antartica';

    protected $fillable = [
        'codigo', 'nombre', 'departamento_id',
    ];

    public $timestamps = false;

    public function departamento()
    {
        return $this->belongsTo('App\Model\Departamento');
    }

    public function observacion()
    {
        return $this->belongsTo('App\Model\Observacion');
    }

    public function fuente()
    {
        return $this->belongsTo('App\Model\Fuente');
    }

    public function geometria()
    {
        return $this->belongsTo('App\Model\Geometria');
    }

    public function tipoDeBaseAntartica()
    {
        return $this->belongsTo('App\Model\TipoDeBaseAntartica', 'tipo_de_base_antartica_id');
    }

    public function tipoDePoblacion()
    {
        return $this->belongsTo('App\Model\TipoDePoblacion', 'tipo_de_poblacion_id');
    }

    public function operativos()
    {
        return $this->belongsToMany('App\Model\Operativo', 'operativo_base_antartica');
    }
}
