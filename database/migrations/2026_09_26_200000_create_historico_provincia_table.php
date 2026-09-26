<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateHistoricoProvinciaTable extends Migration
{
    /**
     * Spike de prueba: instala historico_provincia, definida en
     * app/developer_docs/provincias_historico.sql (dump nunca referenciado
     * por ninguna migración - falla ahí por falta de ';' entre los dos
     * CREATE TABLE del archivo). Reescrita acá con Blueprint, mismas
     * columnas/PK/FK que el dump, respetando que provincia.id es bigint.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasTable('historico_provincia')) {
            Schema::create('historico_provincia', function (Blueprint $table) {
                $table->unsignedBigInteger('nuevo_id');
                $table->unsignedBigInteger('viejo_id');
                $table->primary(['nuevo_id', 'viejo_id']);
                $table->foreign('nuevo_id')->references('id')->on('provincia');
                $table->foreign('viejo_id')->references('id')->on('provincia');
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
        Schema::dropIfExists('historico_provincia');
    }
}
