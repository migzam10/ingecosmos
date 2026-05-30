<?php

namespace Database\Seeders;

use App\Models\EmpresaCliente;
use Illuminate\Database\Seeder;

class EmpresasClienteSeeder extends Seeder
{
    public function run(): void
    {
        $empresas = [
            ['nombre' => 'PERSONAL',          'tipo' => 'A', 'tarifa_hora' => 50000],
            ['nombre' => 'RENTING',            'tipo' => 'A', 'tarifa_hora' => 53250],
            ['nombre' => 'SURA',               'tipo' => 'A', 'tarifa_hora' => 70403],
            ['nombre' => 'BOLIVAR',            'tipo' => 'A', 'tarifa_hora' => 59171],
            ['nombre' => 'COLPATRIA',          'tipo' => 'A', 'tarifa_hora' => 45500],
            ['nombre' => 'AUTOSEGURO',         'tipo' => 'A', 'tarifa_hora' => 53250],
            ['nombre' => 'SOLIDARIA',          'tipo' => 'A', 'tarifa_hora' => 29000],
            ['nombre' => 'ZURICH',             'tipo' => 'B', 'tarifa_hora' => 50000],
            ['nombre' => 'QUALITAS',           'tipo' => 'B', 'tarifa_hora' => 50000],
            ['nombre' => 'MAREAUTOS',          'tipo' => 'A', 'tarifa_hora' => 50000],
            ['nombre' => 'DHL',                'tipo' => 'A', 'tarifa_hora' => 50000],
            ['nombre' => 'UNINORTE',           'tipo' => 'A', 'tarifa_hora' => 50000],
            ['nombre' => 'FRITOLAY',           'tipo' => 'A', 'tarifa_hora' => 50000],
            ['nombre' => 'NETCOL',             'tipo' => 'A', 'tarifa_hora' => 50000],
            ['nombre' => 'PERSONAL SEGUROS',   'tipo' => 'A', 'tarifa_hora' => 50000],
            ['nombre' => 'HYUNDAUTOS',         'tipo' => 'A', 'tarifa_hora' => 50000],
            ['nombre' => 'TALLERES DEL NORTE', 'tipo' => 'A', 'tarifa_hora' => 50000],
        ];

        foreach ($empresas as $e) {
            EmpresaCliente::firstOrCreate(
                ['nombre' => $e['nombre']],
                array_merge($e, [
                    'meta_dias_leve'   => 5,
                    'meta_dias_medio'  => 10,
                    'meta_dias_fuerte' => 13,
                    'activa'           => true,
                ])
            );
        }
    }
}
