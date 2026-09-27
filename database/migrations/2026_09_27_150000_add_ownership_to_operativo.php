<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddOwnershipToOperativo extends Migration
{
    /**
     * Cada operativo puede tener un dueño (quien lo creó) y ser privado
     * (solo su dueño lo ve/edita) o público (todos lo ven). user_id nullable
     * a proposito: los operativos ya existentes (creados por consola/tinker,
     * como "precenso2020-dev") no tienen dueño - se tratan como públicos
     * de hecho vía OperativoPolicy (ver es_publico default true solo para
     * los preexistentes, los nuevos nacen privados).
     *
     * @return void
     */
    public function up()
    {
        Schema::table('operativo', function (Blueprint $table) {
            $table->unsignedBigInteger('user_id')->nullable();
            $table->boolean('es_publico')->default(false);
            $table->foreign('user_id')->references('id')->on('users');
        });

        // Los operativos que ya existían antes de este spike no tienen
        // dueño identificable - se marcan públicos para no dejarlos
        // invisibles para todo el mundo de un día para el otro.
        \App\Model\Operativo::whereNull('user_id')->update(['es_publico' => true]);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('operativo', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropColumn(['user_id', 'es_publico']);
        });
    }
}
