<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AddHistoryToGobiernoLocalPivots extends Migration
{
    /**
     * Mismo patrón aplicado a gobierno_local_entidad (migración 2026_09_26_220000),
     * ahora sobre gobierno_local_departamento y localidad_gobierno_local: venían
     * como pivot plano sin PK. Se agrega id propio + fecha_desde/fecha_hasta/
     * observacion_id + FKs + índice único parcial (1 vínculo vigente por par).
     * Confirmado antes: ambas tablas vacías, sin datos que romper.
     *
     * @return void
     */
    public function up()
    {
        $pivots = [
            'gobierno_local_departamento' => ['gobierno_local_id', 'gobierno_local', 'departamento_id', 'departamentos'],
            'localidad_gobierno_local' => ['localidad_id', 'localidad', 'gobierno_local_id', 'gobierno_local'],
        ];

        foreach ($pivots as $table => [$colA, $refA, $colB, $refB]) {
            Schema::table($table, function (Blueprint $blueprint) use ($colA, $refA, $colB, $refB) {
                $blueprint->bigIncrements('id')->first();
                $blueprint->timestamp('fecha_desde')->nullable();
                $blueprint->timestamp('fecha_hasta')->nullable();
                $blueprint->integer('observacion_id')->nullable();
                $blueprint->foreign($colA)->references('id')->on($refA);
                $blueprint->foreign($colB)->references('id')->on($refB);
            });

            DB::statement("
                create unique index {$table}_vigente_unique
                on {$table} ({$colA}, {$colB})
                where (fecha_hasta is null)
            ");
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        $pivots = [
            'gobierno_local_departamento' => ['gobierno_local_id', 'departamento_id'],
            'localidad_gobierno_local' => ['localidad_id', 'gobierno_local_id'],
        ];

        foreach ($pivots as $table => [$colA, $colB]) {
            DB::statement("drop index if exists {$table}_vigente_unique");

            Schema::table($table, function (Blueprint $blueprint) use ($colA, $colB) {
                $blueprint->dropForeign([$colA]);
                $blueprint->dropForeign([$colB]);
                $blueprint->dropColumn(['id', 'fecha_desde', 'fecha_hasta', 'observacion_id']);
            });
        }
    }
}
