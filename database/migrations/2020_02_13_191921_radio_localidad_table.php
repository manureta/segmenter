<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class RadioLocalidadTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        //
        // SI ya no esta la tabla de radio_localidad.
        if (! Schema::hasTable('radio_localidad')){
   	    $sql = file_get_contents(app_path() . '/developer_docs/radio_localidad.up.sql');
         try{
            DB::unprepared($sql);
         }catch(Illuminate\Database\QueryException $e){
            DB::Rollback();
	          echo _('Omitiendo creación de tabla de relación radio localidad...
');
         }
        }else{
             echo 'No se crea tabla de radio_localidad xq ya se encuentra una.
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
        //
        Schema::dropIfExists('radio_localidad');
    }
}
