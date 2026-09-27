<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;

class CreateOperativoPermissions extends Migration
{
    /**
     * "Publicar Operativos": pasar un operativo propio de privado a público.
     * "Administrar Operativos": ver/editar/borrar operativos de otros
     * usuarios, no solo el propio (el dueño de un operativo siempre puede
     * editarlo/borrarlo sin este permiso).
     *
     * @return void
     */
    public function up()
    {
        Artisan::call( 'db:seed', [
            '--class' => 'OperativoPermissionsSeeder',
            '--force' => true ]
        );
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::beginTransaction();
            try {
                Permission::where(['name'=>'Publicar Operativos'])->firstOrFail()->delete();
                Permission::where(['name'=>'Administrar Operativos'])->firstOrFail()->delete();
            } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
                echo $e->getMessage();
            }
            DB::commit();
    }
}
