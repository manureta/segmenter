<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePaisAndBaseAntartica extends Migration
{
    /**
     * pais y base_antartica de toto5 - a diferencia de los lookups de la
     * migración anterior, estas SON entidades territoriales completas, no
     * catálogos chicos. No hay dump de referencia para su estructura exacta
     * (a diferencia de entidad/localidad/etc, que sí venían en
     * developer_docs) - columnas inferidas por analogía con el resto del
     * catálogo (mismo patrón id/codigo/nombre/fecha_desde/fecha_hasta/
     * observacion_id/geometria_id) y, para base_antartica, con paraje
     * (fuente_id, tipo_de_poblacion_id) por ser el pariente más cercano en
     * la jerarquía. Certeza media, a confirmar contra el esquema real de
     * toto5 cuando esté disponible.
     *
     * Nota: provincia no tiene columna pais_id - esta tabla queda sin
     * ninguna referencia entrante hasta que se decida agregarla (no se tocó
     * la tabla provincia en este spike, fuera del alcance pedido).
     *
     * @return void
     */
    public function up()
    {
        Schema::create('pais', function (Blueprint $table) {
            $table->increments('id');
            $table->string('codigo')->nullable();
            $table->string('nombre');
            $table->timestamp('fecha_desde')->nullable();
            $table->timestamp('fecha_hasta')->nullable();
            $table->integer('observacion_id')->nullable();
            $table->integer('geometria_id')->nullable();
        });
        Schema::table('pais', function (Blueprint $table) {
            $table->foreign('observacion_id')->references('id')->on('observaciones');
            $table->foreign('geometria_id')->references('id')->on('geometria');
        });

        Schema::create('base_antartica', function (Blueprint $table) {
            $table->increments('id');
            $table->string('codigo')->nullable();
            $table->string('nombre');
            $table->unsignedBigInteger('departamento_id')->nullable();
            $table->timestamp('fecha_desde')->nullable();
            $table->timestamp('fecha_hasta')->nullable();
            $table->integer('observacion_id')->nullable();
            $table->integer('fuente_id')->nullable();
            $table->integer('geometria_id')->nullable();
            $table->integer('tipo_de_base_antartica_id')->nullable();
            $table->integer('tipo_de_poblacion_id')->nullable();
        });
        Schema::table('base_antartica', function (Blueprint $table) {
            $table->foreign('departamento_id')->references('id')->on('departamentos');
            $table->foreign('observacion_id')->references('id')->on('observaciones');
            $table->foreign('fuente_id')->references('id')->on('fuente');
            $table->foreign('geometria_id')->references('id')->on('geometria');
            $table->foreign('tipo_de_base_antartica_id')->references('id')->on('tipo_de_base_antartica');
            $table->foreign('tipo_de_poblacion_id')->references('id')->on('tipo_de_poblacion');
        });

        // Ahora que base_antartica existe, el pivot ya puede tener su FK real.
        Schema::table('operativo__base_antartica', function (Blueprint $table) {
            $table->primary(['base_antartica_id', 'operativo_id']);
            $table->foreign('base_antartica_id')->references('id')->on('base_antartica');
            $table->foreign('operativo_id')->references('id')->on('operativo');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('operativo__base_antartica', function (Blueprint $table) {
            $table->dropForeign(['base_antartica_id']);
            $table->dropForeign(['operativo_id']);
            $table->dropPrimary();
        });

        Schema::dropIfExists('base_antartica');
        Schema::dropIfExists('pais');
    }
}
