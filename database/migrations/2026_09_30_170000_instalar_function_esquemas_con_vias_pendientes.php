<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class InstalarFunctionEsquemasConViasPendientes extends Migration
{
    public function up()
    {
        $path = app_path('developer_docs/function.indec.esquemasConViasPendientes.sql');
        DB::unprepared(file_get_contents($path));
    }

    public function down()
    {
        DB::statement('DROP FUNCTION IF EXISTS indec.esquemas_con_vias_pendientes();');
    }
}
