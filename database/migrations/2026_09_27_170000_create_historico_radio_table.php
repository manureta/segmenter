<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateHistoricoRadioTable extends Migration
{
    /**
     * Mismo patrón nuevo_id/viejo_id de historico_provincia (ver
     * 2026_09_26_200000_create_historico_provincia_table.php), acá para
     * radios duplicados: viejo_id queda registrado como reemplazado por
     * nuevo_id sin borrar la fila (otras tablas - radio_localidad,
     * radio_entidad, segmentaciones - pueden seguir referenciándola).
     * Suma motivo/user_id/created_at porque acá sí hay una UI real que
     * concilia (control-calidad/radios-duplicados) y conviene dejar
     * registrado quién y por qué.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasTable('historico_radio')) {
            Schema::create('historico_radio', function (Blueprint $table) {
                $table->unsignedBigInteger('nuevo_id');
                $table->unsignedBigInteger('viejo_id');
                $table->text('motivo')->nullable();
                $table->unsignedBigInteger('user_id')->nullable();
                $table->timestamp('created_at')->nullable();
                $table->primary(['nuevo_id', 'viejo_id']);
                $table->foreign('nuevo_id')->references('id')->on('radio');
                $table->foreign('viejo_id')->references('id')->on('radio');
                $table->foreign('user_id')->references('id')->on('users');
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
        Schema::dropIfExists('historico_radio');
    }
}
