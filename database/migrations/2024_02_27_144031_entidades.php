<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        //
        if (!Schema::hasTable('entidades')) {
          Schema::create('entidades', function (Blueprint $table) {
              $table->bigIncrements('id')->index();
              $table->string('codigo')->index();
              $table->string('nombre')->index();
              $table->integer('localidad_id')->index()->references('id')->on('localidad');
              $table->timestamp('fecha_desde')->nullable();
              $table->timestamp('fecha_hasta')->nullable();
              $table->integer('observacion_id')->references('id')->on('observaciones')->nullable();
              $table->integer('cap_de_pcia')->nullable();
              $table->integer('cab_de_depto')->nullable();
              $table->integer('sede_gob_loc')->nullable();
              $table->integer('geometria_id')->nullable();
              $table->timestamps();
          });
      } else {
          echo __('Ya existe la tabla permissions');
      }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        //
        Schema::dropIfExists('entidades');

    }
};
