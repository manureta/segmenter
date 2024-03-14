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
        // Tabla para modelo geometrias, manejo poligono y/o punto etiqueta y/o topogeometria.
        If (! Schema::hasTable('geometrias')){
          Schema::create('geometrias', function (Blueprint $table) {
             $table->bigIncrements('id')->index();
             $table->polygon('poligono')->isGeometry()->nullable();
             $table->point('punto')->isGeometry() ->nullable();
             $table->binary('topogeometria')->nullable();
             $table->timestamps();
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
        //
        Schema::dropIfExists('geometrias');
}
};
