<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreateCapitalCabeceraSedeTables extends Migration
{
    /**
     * capital_de_republica, capital_de_provincia, cabecera_de_departamento,
     * sede_gobierno_local: definidas como tablas de vínculo/historial (decisión
     * confirmada), mismo patrón que gobierno_local_entidad - la columna suelta
     * (cap_de_rep/cap_de_pcia/cab_de_depto/sede_gob_loc, ya existente en
     * localidad/entidad/gobierno_local/paraje) es el puntero al vínculo actual,
     * la tabla nueva es el historial completo, con unique parcial de "a lo
     * sumo un vigente" - pero acá la unicidad es sobre la entidad geográfica
     * (una provincia no puede tener dos capitales vigentes a la vez), no sobre
     * el par como en gobierno_local_entidad.
     *
     * sede_gobierno_local es la más incierta: la sede puede ser una entidad,
     * una localidad o un paraje (la columna sede_gob_loc está repetida en los
     * tres) - modelada con 3 FK nullable + CHECK de que se cargue exactamente
     * una, en vez de un morphTo completo que sería sobre-ingeniería para lo
     * que se sabe hoy.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('capital_de_republica', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->integer('pais_id');
            $table->unsignedBigInteger('localidad_id');
            $table->timestamp('fecha_desde')->nullable();
            $table->timestamp('fecha_hasta')->nullable();
            $table->integer('observacion_id')->nullable();
            $table->foreign('pais_id')->references('id')->on('pais');
            $table->foreign('localidad_id')->references('id')->on('localidad');
            $table->foreign('observacion_id')->references('id')->on('observaciones');
        });
        DB::statement('create unique index capital_de_republica_vigente_unique on capital_de_republica (pais_id) where (fecha_hasta is null)');

        Schema::create('capital_de_provincia', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('provincia_id');
            $table->unsignedBigInteger('localidad_id');
            $table->timestamp('fecha_desde')->nullable();
            $table->timestamp('fecha_hasta')->nullable();
            $table->integer('observacion_id')->nullable();
            $table->foreign('provincia_id')->references('id')->on('provincia');
            $table->foreign('localidad_id')->references('id')->on('localidad');
            $table->foreign('observacion_id')->references('id')->on('observaciones');
        });
        DB::statement('create unique index capital_de_provincia_vigente_unique on capital_de_provincia (provincia_id) where (fecha_hasta is null)');

        Schema::create('cabecera_de_departamento', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('departamento_id');
            $table->unsignedBigInteger('localidad_id');
            $table->timestamp('fecha_desde')->nullable();
            $table->timestamp('fecha_hasta')->nullable();
            $table->integer('observacion_id')->nullable();
            $table->foreign('departamento_id')->references('id')->on('departamentos');
            $table->foreign('localidad_id')->references('id')->on('localidad');
            $table->foreign('observacion_id')->references('id')->on('observaciones');
        });
        DB::statement('create unique index cabecera_de_departamento_vigente_unique on cabecera_de_departamento (departamento_id) where (fecha_hasta is null)');

        Schema::create('sede_gobierno_local', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->integer('gobierno_local_id');
            $table->integer('entidad_id')->nullable();
            $table->integer('localidad_id')->nullable();
            $table->integer('paraje_id')->nullable();
            $table->timestamp('fecha_desde')->nullable();
            $table->timestamp('fecha_hasta')->nullable();
            $table->integer('observacion_id')->nullable();
            $table->foreign('gobierno_local_id')->references('id')->on('gobierno_local');
            $table->foreign('entidad_id')->references('id')->on('entidad');
            $table->foreign('localidad_id')->references('id')->on('localidad');
            $table->foreign('paraje_id')->references('id')->on('paraje');
            $table->foreign('observacion_id')->references('id')->on('observaciones');
        });
        DB::statement('create unique index sede_gobierno_local_vigente_unique on sede_gobierno_local (gobierno_local_id) where (fecha_hasta is null)');
        DB::statement("
            alter table sede_gobierno_local add constraint sede_gobierno_local_una_sola_sede check (
                (entidad_id is not null)::int + (localidad_id is not null)::int + (paraje_id is not null)::int = 1
            )
        ");

        // Columnas sueltas ya existentes (siempre NULL hoy) pasan a ser el
        // puntero "vigente", ahora con FK real hacia el mismo destino de su tabla de historial.
        Schema::table('localidad', function (Blueprint $table) {
            $table->foreign('cap_de_rep')->references('id')->on('pais');
            $table->foreign('cap_de_pcia')->references('id')->on('provincia');
            $table->foreign('cab_de_depto')->references('id')->on('departamentos');
            $table->foreign('sede_gob_loc')->references('id')->on('gobierno_local');
        });
        Schema::table('entidad', function (Blueprint $table) {
            $table->foreign('cap_de_pcia')->references('id')->on('provincia');
            $table->foreign('cab_de_depto')->references('id')->on('departamentos');
            $table->foreign('sede_gob_loc')->references('id')->on('gobierno_local');
        });
        Schema::table('gobierno_local', function (Blueprint $table) {
            $table->foreign('cap_de_rep')->references('id')->on('pais');
            $table->foreign('cap_de_pcia')->references('id')->on('provincia');
            $table->foreign('cab_de_depto')->references('id')->on('departamentos');
            $table->foreign('sede_gob_loc')->references('id')->on('gobierno_local');
        });
        Schema::table('paraje', function (Blueprint $table) {
            $table->foreign('sede_gob_loc')->references('id')->on('gobierno_local');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('localidad', function (Blueprint $table) {
            $table->dropForeign(['cap_de_rep']);
            $table->dropForeign(['cap_de_pcia']);
            $table->dropForeign(['cab_de_depto']);
            $table->dropForeign(['sede_gob_loc']);
        });
        Schema::table('entidad', function (Blueprint $table) {
            $table->dropForeign(['cap_de_pcia']);
            $table->dropForeign(['cab_de_depto']);
            $table->dropForeign(['sede_gob_loc']);
        });
        Schema::table('gobierno_local', function (Blueprint $table) {
            $table->dropForeign(['cap_de_rep']);
            $table->dropForeign(['cap_de_pcia']);
            $table->dropForeign(['cab_de_depto']);
            $table->dropForeign(['sede_gob_loc']);
        });
        Schema::table('paraje', function (Blueprint $table) {
            $table->dropForeign(['sede_gob_loc']);
        });

        Schema::dropIfExists('sede_gobierno_local');
        Schema::dropIfExists('cabecera_de_departamento');
        Schema::dropIfExists('capital_de_provincia');
        Schema::dropIfExists('capital_de_republica');
    }
}
