<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class RenameOperativoDoubleUnderscorePivots extends Migration
{
    /**
     * operativo__localidad/entidad/gobierno_local/paraje/base_antartica venían
     * con "__" (doble guion bajo) del dump original de toto5 - inconsistente
     * con el resto de los pivots del propio esquema (operativo_provincia,
     * gobierno_local_entidad, localidad_gobierno_local, todos con "_" simple).
     * Antes de seguir agregando cosas sobre este nombre, se saneo para no
     * propagar la inconsistencia. Solo rename de tabla + constraints
     * (PK/FK), ninguna columna ni dato cambia.
     *
     * @return void
     */
    private $tablas = [
        'operativo__localidad' => 'operativo_localidad',
        'operativo__entidad' => 'operativo_entidad',
        'operativo__gobierno_local' => 'operativo_gobierno_local',
        'operativo__paraje' => 'operativo_paraje',
        'operativo__base_antartica' => 'operativo_base_antartica',
    ];

    private $columnaHermana = [
        'operativo__localidad' => 'localidad_id',
        'operativo__entidad' => 'entidad_id',
        'operativo__gobierno_local' => 'gobierno_local_id',
        'operativo__paraje' => 'paraje_id',
        'operativo__base_antartica' => 'base_antartica_id',
    ];

    public function up()
    {
        foreach ($this->tablas as $vieja => $nueva) {
            $col = $this->columnaHermana[$vieja];
            Schema::rename($vieja, $nueva);
            DB::statement("alter table {$nueva} rename constraint {$vieja}_pkey to {$nueva}_pkey");
            DB::statement("alter table {$nueva} rename constraint {$vieja}_{$col}_foreign to {$nueva}_{$col}_foreign");
            DB::statement("alter table {$nueva} rename constraint {$vieja}_operativo_id_foreign to {$nueva}_operativo_id_foreign");
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        foreach ($this->tablas as $vieja => $nueva) {
            $col = $this->columnaHermana[$vieja];
            DB::statement("alter table {$nueva} rename constraint {$nueva}_pkey to {$vieja}_pkey");
            DB::statement("alter table {$nueva} rename constraint {$nueva}_{$col}_foreign to {$vieja}_{$col}_foreign");
            DB::statement("alter table {$nueva} rename constraint {$nueva}_operativo_id_foreign to {$vieja}_operativo_id_foreign");
            Schema::rename($nueva, $vieja);
        }
    }
}
