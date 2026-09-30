<?php

namespace Database\seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;

class ViasPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        try {
            $this->command->info('Creando permiso Administrar Vias');
            Permission::firstOrCreate(['name' => 'Administrar Vias', 'guard_name' => 'web']);
            $this->command->info('Permiso Administrar Vias creado.');
        } catch (\Spatie\Permission\Exceptions\PermissionAlreadyExists $e) {
            $this->command->info('Permiso Administrar Vias ya existía.');
        }
    }
}
