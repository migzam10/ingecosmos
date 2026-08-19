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
        Secuencia::firstOrCreate(['tipo' => 'OT'],           ['ultimo_numero' => 49631]);
        Secuencia::firstOrCreate(['tipo' => 'COTIZACION'],   ['ultimo_numero' => 137125]);
        Secuencia::firstOrCreate(['tipo' => 'ORDEN_COMPRA'], ['ultimo_numero' => 0]); // primera OC = 1
    }
}
