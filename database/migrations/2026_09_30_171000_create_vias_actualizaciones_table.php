<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateViasActualizacionesTable extends Migration
{
    /**
     * Historial de cada aplicación de MyDB::aplicarCasosVias(): qué esquema,
     * quién, cuándo, por qué (motivo/origen del cambio) y qué casos tocó.
     * Sirve a la vez de registro de "última actualización por esquema" (se
     * consulta por MAX(created_at) agrupado por esquema, no hace falta una
     * tabla de registro aparte) y de auditoría caso por caso.
     *
     * user_id nullable por si alguna vez se aplica desde consola/tinker sin
     * sesión real -- no debería pasar desde la UI, que sí lo exige.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('vias_actualizaciones', function (Blueprint $table) {
            $table->id();
            $table->string('esquema', 20);
            $table->unsignedBigInteger('user_id')->nullable();
            $table->text('motivo')->nullable();
            $table->unsignedInteger('agregados')->default(0);
            $table->unsignedInteger('borrados')->default(0);
            $table->json('casos')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->foreign('user_id')->references('id')->on('users');
            $table->index(['esquema', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('vias_actualizaciones');
    }
}
