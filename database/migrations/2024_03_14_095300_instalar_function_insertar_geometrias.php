<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class InstalarFunctionInsertarGeometrias extends Migration
{
    /**
     * Run the migrations.
     * @return void
     */
    public function up()
    {
        //
        $path = 'app/developer_docs/funcion_insertar_geometrias.sql';
        DB::unprepared(file_get_contents($path));
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        //
        DB::statement('drop function if exists indec.insertar_geometrias(poligono geometry, punto geometry)');
    }
}


