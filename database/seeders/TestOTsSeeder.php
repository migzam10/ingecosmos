<?php

namespace Database\Seeders;

use App\Models\ClientePersona;
use App\Models\EmpresaCliente;
use App\Models\HistorialOt;
use App\Models\MarcaVehiculo;
use App\Models\OrdenTrabajo;
use App\Models\Secuencia;
use App\Models\User;
use App\Models\Vehiculo;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TestOTsSeeder extends Seeder
{
    public function run(): void
    {
        $admin    = User::first();
        $sura     = EmpresaCliente::where('nombre', 'SURA')->first();
        $personal = EmpresaCliente::where('nombre', 'PERSONAL')->first();
        $bolivar  = EmpresaCliente::where('nombre', 'BOLIVAR')->first();
        $zurich   = EmpresaCliente::where('nombre', 'ZURICH')->first();
        $renault  = MarcaVehiculo::where('nombre', 'RENAULT')->first();
        $chevrolet= MarcaVehiculo::where('nombre', 'CHEVROLET')->first();
        $kia      = MarcaVehiculo::where('nombre', 'KIA')->first();
        $mazda    = MarcaVehiculo::where('nombre', 'MAZDA')->first();
        $toyota   = MarcaVehiculo::where('nombre', 'TOYOTA')->first();

        $casos = [
            // INCUMPLIDO: salida_estimada hace 5 días
            [
                'placa' => 'ABC001', 'marca' => $renault,  'empresa' => $sura,
                'salida_estimada' => Carbon::today()->subDays(5),
                'estado_proceso' => 'EN_PROCESO', 'estado_semaforo' => 'INCUMPLIDO',
                'tg' => 'Fuerte', 'dr' => 13, 'ha' => 69.3, 'valor_mo' => 4876000,
                'nombre_cliente' => 'Carlos Mendoza',
            ],
            // INCUMPLIDO: salida_estimada hace 2 días
            [
                'placa' => 'DEF002', 'marca' => $chevrolet, 'empresa' => $bolivar,
                'salida_estimada' => Carbon::today()->subDays(2),
                'estado_proceso' => 'EN_PROCESO', 'estado_semaforo' => 'INCUMPLIDO',
                'tg' => 'Medio', 'dr' => 8, 'ha' => 40.0, 'valor_mo' => 2365000,
                'nombre_cliente' => 'María Pérez',
            ],
            // ENTREGAR HOY
            [
                'placa' => 'GHI003', 'marca' => $kia,       'empresa' => $personal,
                'salida_estimada' => Carbon::today(),
                'estado_proceso' => 'PROGRAMADO_ENTREGA', 'estado_semaforo' => 'ENTREGAR_HOY',
                'tg' => 'Leve', 'dr' => 4, 'ha' => 19.0, 'valor_mo' => 950000,
                'nombre_cliente' => 'Luis Rodríguez',
            ],
            // A TIEMPO: 3 días restantes
            [
                'placa' => 'JKL004', 'marca' => $mazda,     'empresa' => $zurich,
                'salida_estimada' => Carbon::today()->addDays(3),
                'estado_proceso' => 'EN_PROCESO', 'estado_semaforo' => 'A_TIEMPO',
                'tg' => 'Medio', 'dr' => 9, 'ha' => 45.0, 'valor_mo' => 2250000,
                'nombre_cliente' => 'Ana Torres',
            ],
            // A TIEMPO: 6 días restantes
            [
                'placa' => 'MNO005', 'marca' => $toyota,    'empresa' => $sura,
                'salida_estimada' => Carbon::today()->addDays(6),
                'estado_proceso' => 'RTO_INSTALADO', 'estado_semaforo' => 'A_TIEMPO',
                'tg' => 'Fuerte', 'dr' => 12, 'ha' => 60.0, 'valor_mo' => 4218000,
                'nombre_cliente' => 'Pedro Gómez',
            ],
            // SIN_FECHA: PTE_COTIZACION sin calcular
            [
                'placa' => 'PQR006', 'marca' => $renault,  'empresa' => $personal,
                'salida_estimada' => null,
                'estado_proceso' => 'PTE_COTIZACION', 'estado_semaforo' => 'SIN_FECHA',
                'tg' => null, 'dr' => null, 'ha' => null, 'valor_mo' => 0,
                'nombre_cliente' => 'Sofía Castro',
            ],
            // SIN_FECHA: PTE_AUTORIZACION
            [
                'placa' => 'STU007', 'marca' => $chevrolet, 'empresa' => $bolivar,
                'salida_estimada' => null,
                'estado_proceso' => 'PTE_AUTORIZACION', 'estado_semaforo' => 'SIN_FECHA',
                'tg' => 'Leve', 'dr' => 5, 'ha' => 25.0, 'valor_mo' => 1477550,
                'nombre_cliente' => 'Jhon Vargas',
            ],
        ];

        DB::transaction(function () use ($casos, $admin) {
            foreach ($casos as $caso) {
                $secuencia = Secuencia::lockForUpdate()->where('tipo', 'OT')->first();
                $secuencia->increment('ultimo_numero');
                $numeroOT = $secuencia->ultimo_numero;

                $cliente = ClientePersona::create(['nombre' => $caso['nombre_cliente']]);

                $vehiculo = Vehiculo::updateOrCreate(
                    ['placa' => $caso['placa']],
                    ['id_marca' => $caso['marca']->id, 'id_cliente_persona' => $cliente->id]
                );

                $ot = OrdenTrabajo::create([
                    'numero_ot'          => $numeroOT,
                    'area'               => 'LYP',
                    'id_vehiculo'        => $vehiculo->id,
                    'id_cliente_persona' => $cliente->id,
                    'id_empresa_cliente' => $caso['empresa']->id,
                    'km_ingreso'         => rand(30000, 120000),
                    'fecha_ingreso'      => Carbon::today()->subDays(rand(5, 20)),
                    'fecha_inicio_proceso' => $caso['salida_estimada']
                        ? Carbon::today()->subDays(rand(5,15))
                        : null,
                    'estado_proceso'     => $caso['estado_proceso'],
                    'estado_semaforo'    => $caso['estado_semaforo'],
                    'salida_estimada'    => $caso['salida_estimada'],
                    'tg'                 => $caso['tg'],
                    'dr'                 => $caso['dr'],
                    'ha'                 => $caso['ha'],
                    'valor_mo'           => $caso['valor_mo'],
                    'total'              => $caso['valor_mo'],
                    'creado_por'         => $admin->id,
                ]);

                HistorialOt::create([
                    'id_ot'       => $ot->id,
                    'id_user'     => $admin->id,
                    'estado_anterior' => null,
                    'estado_nuevo'    => $caso['estado_proceso'],
                    'comentario'  => 'OT de prueba creada por seeder',
                ]);
            }
        });

        $this->command->info('7 OTs de prueba creadas con todos los estados del semáforo.');
    }
}
