<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class EliminaRegistrosRepetidos extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
       DB::beginTransaction();
       $sql = file_get_contents(app_path() . '/developer_docs/segmentacion-core/elimina_registros_repetidos.sql');
       try{
           DB::unprepared($sql);
//           DB::unprepared('select indec.elimina_registros_repetidos()');
// no la ejecuta xq no anda en php artisan migrate:refresh 
// necesita que se haga seed de subtipos de viviendas colectivas
           DB::commit();
       }catch(Illuminate\Database\QueryException $e){
          DB::Rollback();
          echo _('Error borrando los registros repetidos de todos los listados...');
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
    }
}
