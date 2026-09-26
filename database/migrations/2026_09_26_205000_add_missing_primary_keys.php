<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AddMissingPrimaryKeys extends Migration
{
    /**
     * Spike: localidad, entidad, gobierno_local, paraje, aglomerados, fraccion,
     * fuente y tipo_de_radio vienen del dump de developer_docs con columna "id"
     * (con secuencia) pero sin PRIMARY KEY declarada a nivel de motor - Eloquent
     * funciona igual porque asume la PK, pero Postgres no puede validar FKs
     * contra ellas (bloqueaba la migración de pivots de operativo). Verificado
     * antes de aplicar: cero id NULL, cero id duplicados en las 8 tablas.
     *
     * @return void
     */
    public function up()
    {
        $tablas = ['localidad', 'entidad', 'gobierno_local', 'paraje', 'aglomerados', 'fraccion', 'fuente', 'tipo_de_radio'];

        foreach ($tablas as $tabla) {
            $tienePk = DB::selectOne(
                "select count(*) as n from pg_index i
                 join pg_class c on c.oid = i.indrelid
                 where c.relname = ? and i.indisprimary",
                [$tabla]
            );

            if ($tienePk->n == 0) {
                DB::statement("alter table {$tabla} add primary key (id)");
            }
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        $tablas = ['localidad', 'entidad', 'gobierno_local', 'paraje', 'aglomerados', 'fraccion', 'fuente', 'tipo_de_radio'];

        foreach ($tablas as $tabla) {
            DB::statement("alter table {$tabla} drop constraint if exists {$tabla}_pkey");
        }
    }
}
