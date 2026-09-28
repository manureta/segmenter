<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUserProvinciaTable extends Migration
{
    /**
     * Asignación fija de alcance geográfico por usuario (pedido explícito):
     * un usuario con 1+ filas acá queda restringido a esas provincias (y
     * todo lo que cuelga de ellas) en los reportes que respeten esto -
     * Super Admin y usuarios sin ninguna fila acá ven todo (sin
     * restricción), no hace falta permiso aparte.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasTable('user_provincia')) {
            Schema::create('user_provincia', function (Blueprint $table) {
                $table->unsignedBigInteger('user_id');
                $table->unsignedBigInteger('provincia_id');
                $table->primary(['user_id', 'provincia_id']);
                $table->foreign('user_id')->references('id')->on('users');
                $table->foreign('provincia_id')->references('id')->on('provincia');
            });
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('user_provincia');
    }
}
