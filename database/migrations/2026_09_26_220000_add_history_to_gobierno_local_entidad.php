<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AddHistoryToGobiernoLocalEntidad extends Migration
{
    /**
     * gobierno_local_entidad venía como pivot plano (gobierno_local_id, entidad_id,
     * sin PK) - no alcanzaba para saber SI y CUÁNDO una entidad estuvo bajo un
     * gobierno local. Se agrega PK propia (permite más de una fila histórica por
     * par) + fecha_desde/fecha_hasta/observacion_id, mismo patrón que ya usa
     * el resto del catálogo (entidad, gobierno_local, etc.). Un índice único
     * parcial garantiza que un par no tenga dos relaciones "vigentes"
     * (fecha_hasta null) al mismo tiempo. Confirmado antes: tabla vacía, sin
     * datos que migrar ni romper.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('gobierno_local_entidad', function (Blueprint $table) {
            $table->bigIncrements('id')->first();
            $table->timestamp('fecha_desde')->nullable();
            $table->timestamp('fecha_hasta')->nullable();
            $table->integer('observacion_id')->nullable();
            $table->foreign('gobierno_local_id')->references('id')->on('gobierno_local');
            $table->foreign('entidad_id')->references('id')->on('entidad');
        });

        DB::statement('
            create unique index gobierno_local_entidad_vigente_unique
            on gobierno_local_entidad (gobierno_local_id, entidad_id)
            where (fecha_hasta is null)
        ');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::statement('drop index if exists gobierno_local_entidad_vigente_unique');

        Schema::table('gobierno_local_entidad', function (Blueprint $table) {
            $table->dropForeign(['gobierno_local_id']);
            $table->dropForeign(['entidad_id']);
            $table->dropColumn(['id', 'fecha_desde', 'fecha_hasta', 'observacion_id']);
        });
    }
}
