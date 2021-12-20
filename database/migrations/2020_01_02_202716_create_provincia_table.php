<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateProvinciaTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // SI ya no esta la tabla de provincia.
        if (! Schema::hasTable('provincia')){
        	Schema::create('provincia', function (Blueprint $table) {
        		$table->bigIncrements('id')->index();
        		$table->string('codigo')->index();
         		$table->string('nombre')->index();
        		$table->date('fecha_desde')->nullable();
        		$table->date('fecha_hasta')->nullable();
        		$table->integer('observacion_id')->nullable();
        		$table->integer('geometria_id')->nullable();
	        });
        }else{
	           echo _('Omitiendo creación de tabla de provincia existente...
');
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('provincia');
    }

}
