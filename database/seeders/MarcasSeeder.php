<?php

namespace Database\Seeders;

use App\Models\MarcaVehiculo;
use App\Models\ModeloVehiculo;
use Illuminate\Database\Seeder;

class MarcasSeeder extends Seeder
{
    public function run(): void
    {
        $datos = [
            'RENAULT'     => ['Duster', 'Logan', 'Kangoo', 'Alaskan', 'Sandero', 'Oroch'],
            'CHEVROLET'   => ['Onix', 'Tracker', 'Sail', 'Captiva', 'Cruze', 'Spark'],
            'KIA'         => ['Rio', 'Picanto', 'Sportage', 'Seltos'],
            'MAZDA'       => ['Mazda 2', 'Mazda 3', 'CX-5', 'CX-30'],
            'SUZUKI'      => ['Swift', 'Vitara', 'S-Presso', 'Baleno'],
            'TOYOTA'      => ['Hilux', 'Corolla', 'RAV4', 'Fortuner'],
            'HYUNDAI'     => ['Tucson', 'Elantra', 'Accent', 'Creta', 'Grand i10'],
            'FORD'        => ['Explorer', 'Ranger', 'Mustang', 'EcoSport'],
            'VOLKSWAGEN'  => ['Polo', 'Golf', 'Tiguan', 'T-Cross'],
            'NISSAN'      => ['Frontier', 'X-Trail', 'March', 'Kicks'],
            'HONDA'       => ['Civic', 'CR-V', 'HR-V', 'Pilot'],
            'BMW'         => ['Serie 3', 'Serie 5', 'X1', 'X3'],
            'MERCEDES'    => ['Clase A', 'Clase C', 'Clase E', 'GLC'],
            'JEEP'        => ['Renegade', 'Compass', 'Cherokee', 'Wrangler'],
            'MITSUBISHI'  => ['L200', 'Outlander', 'ASX'],
        ];

        foreach ($datos as $marcaNombre => $modelos) {
            $marca = MarcaVehiculo::firstOrCreate(['nombre' => $marcaNombre]);

            foreach ($modelos as $modeloNombre) {
                ModeloVehiculo::firstOrCreate([
                    'id_marca' => $marca->id,
                    'nombre'   => $modeloNombre,
                ]);
            }
        }
    }
}
