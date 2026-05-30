<?php

namespace Database\Seeders;

use App\Models\Secuencia;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{
    public function run(): void
    {
        // Usuario administrador
        User::firstOrCreate(
            ['email' => 'admin@taller.co'],
            [
                'name'     => 'Administrador',
                'password' => Hash::make('taller2025'),
                'roles'    => ['ADMIN', 'COORDINADOR'],
                'activo'   => true,
            ]
        );

        // Secuencias iniciales (continúan del Excel real)
        Secuencia::firstOrCreate(['tipo' => 'OT'],         ['ultimo_numero' => 49631]);
        Secuencia::firstOrCreate(['tipo' => 'COTIZACION'], ['ultimo_numero' => 137125]);
    }
}
