<?php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\QueryException;

class SqlLocSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $this->command->info('Sembrando localidades...');
        $path = 'app/developer_docs/localidad.sql';
        DB::unprepared(file_get_contents($path));
        $this->command->info('Localidades plantados!');
    }
}
