<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class CreateGeometriaTable extends Migration
{
    /**
     * Tabla genérica de geometrías cacheadas, referenciada por provincia.geometria_id
     * (y en el futuro departamentos.geometria_id / entidades.geometria_id), columnas
     * que ya existían sin uso desde migraciones anteriores. fuente_id sigue el mismo
     * patrón que fuente_id en paraje (integer suelto, sin FK), referenciando la tabla
     * fuente ya existente (id 4 IGN, id 5 INDEC).
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasTable('geometria')) {
            DB::unprepared('
                CREATE TABLE geometria (
                    id bigserial PRIMARY KEY,
                    geom geometry(MultiPolygon, 4326),
                    fuente_id integer,
                    updated_at timestamp
                )
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
        Schema::dropIfExists('geometria');
    }
}
