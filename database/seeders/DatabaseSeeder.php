<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            FestivosSeeder::class,
            MarcasSeeder::class,
            EmpresasClienteSeeder::class,
            TecnicosSeeder::class,
            CatalogoMOSeeder::class,
            AdminSeeder::class,
        ]);
    }
}
