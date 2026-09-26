<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class SeedFuenteTable extends Migration
{
    /**
     * La tabla fuente se crea vacía en create_fuente_table (solo corre
     * fuente.up.sql, el CREATE TABLE). Los datos de referencia (fuente.sql)
     * nunca se cargaron acá; sin esto, geometria.fuente_id (IGN=4, INDEC=5)
     * no resuelve a nada.
     *
     * @return void
     */
    public function up()
    {
        if (DB::table('fuente')->count() == 0) {
            $sql = file_get_contents(app_path() . '/developer_docs/fuente.sql');
            DB::unprepared($sql);
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::table('fuente')->truncate();
    }
}
