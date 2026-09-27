<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class Via extends Model
{
    /**
     * Tabla `vias` (159k+ filas) sin PK propia - nomencla no es única
     * (153573 de 159611). Se usa como clave "suficientemente única" para
     * Eloquent, sin depender de que lo sea de verdad (no se hace find()
     * por ella, sólo listados).
     */
    protected $table = 'vias';
    protected $primaryKey = 'nomencla';
    public $incrementing = false;
    public $timestamps = false;

    protected $fillable = [
        'nomencla', 'codigo', 'tipo', 'nombre',
        'desdei', 'desded', 'hastai', 'hastad', 'codloc', 'codaglo', 'link',
    ];

    /**
     * codloc no siempre matchea una Localidad cargada (dataset de
     * desarrollo, carga parcial) - null es un resultado normal, no un error.
     */
    public function localidad()
    {
        return $this->belongsTo('App\Model\Localidad', 'codloc', 'codigo');
    }

    public function aglomerado()
    {
        return $this->belongsTo('App\Model\Aglomerado', 'codaglo', 'codigo');
    }
}
