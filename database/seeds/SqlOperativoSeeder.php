<?php

use Illuminate\Database\Seeder;

class SqlOperativoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Eloquent::unguard();
        $this->command->info('Sembrando operativos...');
        $path = 'app/developer_docs/operativo.sql';
        DB::unprepared(file_get_contents($path));
        $this->command->info('Operativos plantados!');
        //
    }
}
