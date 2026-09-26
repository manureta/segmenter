<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddConstraintsToOperativoPivots extends Migration
{
    /**
     * Spike: las 5 tablas pivot operativo__* llegaron del dump de developer_docs
     * (operativos.up.sql) sin PK ni FK - confirmado vacías (0 filas), sin
     * riesgo de duplicados/huérfanos al agregar las constraints ahora.
     * operativo__base_antartica queda afuera: la tabla base_antartica que
     * debería referenciar todavía no existe en este esquema.
     *
     * @return void
     */
    public function up()
    {
        $pivots = [
            'operativo__localidad' => ['localidad_id', 'localidad'],
            'operativo__entidad' => ['entidad_id', 'entidad'],
            'operativo__gobierno_local' => ['gobierno_local_id', 'gobierno_local'],
            'operativo__paraje' => ['paraje_id', 'paraje'],
        ];

        foreach ($pivots as $table => [$column, $referenced]) {
            Schema::table($table, function (Blueprint $blueprint) use ($column, $referenced) {
                $blueprint->primary([$column, 'operativo_id']);
                $blueprint->foreign($column)->references('id')->on($referenced);
                $blueprint->foreign('operativo_id')->references('id')->on('operativo');
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
        $pivots = ['operativo__localidad', 'operativo__entidad', 'operativo__gobierno_local', 'operativo__paraje'];

        foreach ($pivots as $table) {
            Schema::table($table, function (Blueprint $blueprint) use ($table) {
                $blueprint->dropForeign($table . '_' . explode('__', $table)[1] . '_id_foreign');
                $blueprint->dropForeign($table . '_operativo_id_foreign');
                $blueprint->dropPrimary();
            });
        }
    }
}
