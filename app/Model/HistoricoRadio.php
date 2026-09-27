<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class HistoricoRadio extends Model
{
    // PK compuesta (nuevo_id, viejo_id): Eloquent no la maneja nativa,
    // se deshabilita el manejo automático y se guarda siempre vía create().
    protected $table = 'historico_radio';
    public $incrementing = false;
    protected $primaryKey = null;
    public $timestamps = false;

    protected $fillable = ['nuevo_id', 'viejo_id', 'motivo', 'user_id', 'created_at'];

    public function vigente()
    {
        return $this->belongsTo('App\Model\Radio', 'nuevo_id');
    }

    public function reemplazado()
    {
        return $this->belongsTo('App\Model\Radio', 'viejo_id');
    }

    public function usuario()
    {
        return $this->belongsTo('App\User', 'user_id');
    }
}
