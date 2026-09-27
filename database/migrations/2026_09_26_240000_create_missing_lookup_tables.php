<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateMissingLookupTables extends Migration
{
    /**
     * De las 73 tablas de toto5, estos lookups faltaban por completo pese a
     * que casi todo el catálogo ya tiene la columna que los referenciaría
     * (observacion_id, tipo_de_poblacion_id, etc, todas NULL hoy - confirmado
     * antes de escribir esto, cero filas con valor a validar). No se incluyen
     * acá las tablas de entidad más grandes que también faltan (base_antartica,
     * pais, capital_de_provincia, etc) - eso no es un lookup simple, queda
     * para otra pasada.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('observaciones', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->text('texto');
            $table->timestamps();
        });

        $lookups = [
            'tipo_de_poblacion',
            'tipo_de_localidad',
            'categoria_de_agl',
            'tipo_de_agl',
            'tipo_de_base_antartica',
            'categoria_de_segmento',
            'tipo_de_segmento',
        ];

        foreach ($lookups as $tabla) {
            Schema::create($tabla, function (Blueprint $table) {
                $table->increments('id');
                $table->string('nombre');
                $table->string('descripcion')->nullable();
            });
        }

        // observacion_id: presente en todo el catálogo + en los 3 pivots con historial.
        $conObservacion = [
            'provincia', 'departamentos', 'localidad', 'fraccion', 'aglomerados', 'radio',
            'gobierno_local', 'entidad', 'paraje',
            'gobierno_local_entidad', 'gobierno_local_departamento', 'localidad_gobierno_local',
        ];
        foreach ($conObservacion as $tabla) {
            Schema::table($tabla, function (Blueprint $table) {
                $table->foreign('observacion_id')->references('id')->on('observaciones');
            });
        }

        // tipo_de_poblacion_id: localidad, aglomerados, gobierno_local, paraje.
        foreach (['localidad', 'aglomerados', 'gobierno_local', 'paraje'] as $tabla) {
            Schema::table($tabla, function (Blueprint $table) {
                $table->foreign('tipo_de_poblacion_id')->references('id')->on('tipo_de_poblacion');
            });
        }

        Schema::table('localidad', function (Blueprint $table) {
            $table->foreign('tipo_de_localidad_id')->references('id')->on('tipo_de_localidad');
        });

        Schema::table('gobierno_local', function (Blueprint $table) {
            $table->foreign('categoria_de_agl_id')->references('id')->on('categoria_de_agl');
            // columna sin sufijo _id en el dump original, igual referencia a tipo_de_agl
            $table->foreign('tipo_de_agl')->references('id')->on('tipo_de_agl');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        $conObservacion = [
            'provincia', 'departamentos', 'localidad', 'fraccion', 'aglomerados', 'radio',
            'gobierno_local', 'entidad', 'paraje',
            'gobierno_local_entidad', 'gobierno_local_departamento', 'localidad_gobierno_local',
        ];
        foreach ($conObservacion as $tabla) {
            Schema::table($tabla, function (Blueprint $table) {
                $table->dropForeign(['observacion_id']);
            });
        }

        foreach (['localidad', 'aglomerados', 'gobierno_local', 'paraje'] as $tabla) {
            Schema::table($tabla, function (Blueprint $table) {
                $table->dropForeign(['tipo_de_poblacion_id']);
            });
        }

        Schema::table('localidad', function (Blueprint $table) {
            $table->dropForeign(['tipo_de_localidad_id']);
        });

        Schema::table('gobierno_local', function (Blueprint $table) {
            $table->dropForeign(['categoria_de_agl_id']);
            $table->dropForeign(['tipo_de_agl']);
        });

        Schema::dropIfExists('tipo_de_segmento');
        Schema::dropIfExists('categoria_de_segmento');
        Schema::dropIfExists('tipo_de_base_antartica');
        Schema::dropIfExists('tipo_de_agl');
        Schema::dropIfExists('categoria_de_agl');
        Schema::dropIfExists('tipo_de_localidad');
        Schema::dropIfExists('tipo_de_poblacion');
        Schema::dropIfExists('observaciones');
    }
}
