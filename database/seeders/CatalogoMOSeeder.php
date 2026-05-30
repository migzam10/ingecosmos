<?php

namespace Database\Seeders;

use App\Models\CatalogoMo;
use Illuminate\Database\Seeder;

class CatalogoMOSeeder extends Seeder
{
    public function run(): void
    {
        // Items genéricos nivel 1 (aplican a todos los vehículos)
        $genericos = [
            ['descripcion' => 'Desmonte y montaje capó', 'precio_referencia' => 150000],
            ['descripcion' => 'Desmonte y montaje puerta delantera', 'precio_referencia' => 120000],
            ['descripcion' => 'Desmonte y montaje puerta trasera', 'precio_referencia' => 110000],
            ['descripcion' => 'Desmonte y montaje guardabarro delantero', 'precio_referencia' => 100000],
            ['descripcion' => 'Desmonte y montaje guardabarro trasero', 'precio_referencia' => 100000],
            ['descripcion' => 'Desmonte y montaje bomper delantero', 'precio_referencia' => 130000],
            ['descripcion' => 'Desmonte y montaje bomper trasero', 'precio_referencia' => 120000],
            ['descripcion' => 'Desmonte y montaje rejilla', 'precio_referencia' => 80000],
            ['descripcion' => 'Desmonte y montaje faro delantero', 'precio_referencia' => 60000],
            ['descripcion' => 'Desmonte y montaje stop', 'precio_referencia' => 50000],
            ['descripcion' => 'Desmonte y montaje espejo', 'precio_referencia' => 50000],
            ['descripcion' => 'Reparación capó (latonería)', 'precio_referencia' => 200000],
            ['descripcion' => 'Reparación puerta (latonería)', 'precio_referencia' => 180000],
            ['descripcion' => 'Reparación guardabarro (latonería)', 'precio_referencia' => 150000],
            ['descripcion' => 'Masilla y preparación capó', 'precio_referencia' => 120000],
            ['descripcion' => 'Masilla y preparación puerta', 'precio_referencia' => 100000],
            ['descripcion' => 'Masilla y preparación guardabarro', 'precio_referencia' => 90000],
            ['descripcion' => 'Pintura capó (una capa)', 'precio_referencia' => 250000],
            ['descripcion' => 'Pintura puerta (una capa)', 'precio_referencia' => 220000],
            ['descripcion' => 'Pintura guardabarro (una capa)', 'precio_referencia' => 180000],
            ['descripcion' => 'Pintura bomper delantero', 'precio_referencia' => 200000],
            ['descripcion' => 'Pintura bomper trasero', 'precio_referencia' => 180000],
            ['descripcion' => 'Pulida y encerada general', 'precio_referencia' => 150000],
            ['descripcion' => 'Diagnóstico escáner', 'precio_referencia' => 80000],
            ['descripcion' => 'Alineación y balanceo', 'precio_referencia' => 90000],
            ['descripcion' => 'Cambio de aceite motor', 'precio_referencia' => 60000],
            ['descripcion' => 'Cambio pastillas de freno delantera', 'precio_referencia' => 80000],
            ['descripcion' => 'Cambio pastillas de freno trasera', 'precio_referencia' => 80000],
            ['descripcion' => 'Cambio cintas de freno', 'precio_referencia' => 100000],
            ['descripcion' => 'Revisión sistema de frenos', 'precio_referencia' => 50000],
            ['descripcion' => 'Revisión sistema eléctrico', 'precio_referencia' => 80000],
            ['descripcion' => 'Revisión aire acondicionado', 'precio_referencia' => 70000],
            ['descripcion' => 'Recarga de gas A/C', 'precio_referencia' => 120000],
        ];

        foreach ($genericos as $item) {
            CatalogoMo::firstOrCreate(
                ['nivel' => 1, 'id_marca' => null, 'id_modelo' => null, 'descripcion' => $item['descripcion']],
                ['precio_referencia' => $item['precio_referencia'], 'activo' => true]
            );
        }
    }
}
