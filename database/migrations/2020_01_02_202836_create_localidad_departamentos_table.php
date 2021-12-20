<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateLocalidadDepartamentosTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
      // SI ya no esta la tabla de localidad_departamento.
      if (! Schema::hasTable('localidad_departamento')){
	    
	      $sql = file_get_contents(app_path() . '/developer_docs/localidad_departamento.up.sql');
        try{
           DB::unprepared($sql);
         }catch(Illuminate\Database\QueryException $e){
          DB::Rollback();
	        echo _('Omitiendo creación de tabla de relación localidad con departamentos...
');
         }
       }else{
             echo 'No se crea tabla de localidad_departamento xq ya se encuentra una.
';
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('localidad_departamento');
    }
}
