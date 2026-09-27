<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddTopologiaIdToOperativo extends Migration
{
    /**
     * Paso schema-only, deliberadamente NO toca indec.cargartopologia()/
     * cargar_topologia_pais() (funciones de riesgo del relevamiento, regla
     * dura del plan de refactor: nada se toca sin test de caracterización
     * antes). Hoy esas funciones nombran la topología por SRID/faja
     * (<srid>_topo) o una única nacional (pais_topo) - cualquier operativo
     * que caiga en la misma faja comparte la misma topología con otros,
     * sin separación. Esta columna deja al modelo Operativo listo para
     * asociar su propia topology.topology.id el día que se decida romper
     * esa convención de nombre en las funciones (fuera de este spike).
     *
     * @return void
     */
    public function up()
    {
        Schema::table('operativo', function (Blueprint $table) {
            $table->integer('topologia_id')->nullable();
        });
        Schema::table('operativo', function (Blueprint $table) {
            $table->foreign('topologia_id')->references('id')->on('topology.topology');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('operativo', function (Blueprint $table) {
            $table->dropForeign(['topologia_id']);
            $table->dropColumn('topologia_id');
        });
    }
}
