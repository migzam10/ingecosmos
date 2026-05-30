<?php

namespace Database\Seeders;

use App\Models\Tecnico;
use Illuminate\Database\Seeder;

class TecnicosSeeder extends Seeder
{
    public function run(): void
    {
        $tecnicos = [
            ['nombre' => 'Edwin H',    'especialidades' => ['LAT']],
            ['nombre' => 'Alvaro M',   'especialidades' => ['LAT']],
            ['nombre' => 'Larry A',    'especialidades' => ['LAT']],
            ['nombre' => 'Felix V',    'especialidades' => ['PREP', 'PINT']],
            ['nombre' => 'Martin N',   'especialidades' => ['PREP', 'PINT']],
            ['nombre' => 'Alberto P',  'especialidades' => ['PREP']],
            ['nombre' => 'Luis',       'especialidades' => ['PREP']],
            ['nombre' => 'Willian',    'especialidades' => ['PREP', 'PINT']],
            ['nombre' => 'Emanuel',    'especialidades' => ['PREP']],
            ['nombre' => 'Jose R',     'especialidades' => ['MEC']],
            ['nombre' => 'Benjamin',   'especialidades' => ['MEC']],
            ['nombre' => 'Hector C',   'especialidades' => ['MEC']],
            ['nombre' => 'Keiner C',   'especialidades' => ['MEC', 'AA']],
            ['nombre' => 'Fabio M',    'especialidades' => ['ELEC']],
        ];

        foreach ($tecnicos as $t) {
            Tecnico::firstOrCreate(
                ['nombre' => $t['nombre']],
                array_merge($t, ['activo' => true])
            );
        }
    }
}
