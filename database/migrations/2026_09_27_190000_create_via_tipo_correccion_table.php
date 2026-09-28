<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateViaTipoCorreccionTable extends Migration
{
    /**
     * `public.vias` es una tabla DERIVADA: MyDB (linea ~2351) la borra y la
     * reconstruye entera con `CREATE TABLE vias AS SELECT * FROM indec.vias()`,
     * que a su vez sale de un UNION crudo de todos los `e<esquema>.arc` -
     * el `tipo` que llega ahí es el dato de origen tal cual (typos
     * incluidos: CALE, CLLE, OTRP, hasta el literal "TIPO" de un
     * encabezado colado). Corregir `vias.tipo` a mano se pierde en la
     * próxima reconstrucción sin que nadie se entere.
     *
     * Esta tabla es el diccionario de corrección real: se aplica de
     * nuevo (impacta) contra `vias.tipo` cada vez que se corrige, y
     * sirve para detectar cuándo un valor ya corregido "reapareció"
     * (control de calidad nuevo en ControlCalidadController).
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasTable('via_tipo_correccion')) {
            Schema::create('via_tipo_correccion', function (Blueprint $table) {
                $table->id();
                $table->string('tipo_original')->unique();
                $table->string('tipo_corregido');
                $table->text('motivo')->nullable();
                $table->unsignedBigInteger('user_id')->nullable();
                $table->timestamps();
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
        Schema::dropIfExists('via_tipo_correccion');
    }
}
