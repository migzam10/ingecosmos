<?php

namespace Tests\Feature;

use App\Models\EmpresaCliente;
use App\Models\MarcaVehiculo;
use App\Models\OrdenTrabajo;
use App\Models\Tecnico;
use App\Models\TrabajoTecnico;
use App\Models\User;
use App\Models\Vehiculo;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class OrdenFacturaYVftTest extends TestCase
{
    use DatabaseTransactions;

    private function admin(): User
    {
        return User::first() ?? User::create([
            'name'     => 'Admin Test',
            'email'    => 'admin' . random_int(1000, 9999) . '@test.local',
            'password' => bcrypt('secret'),
            'roles'    => ['ADMIN'],
            'activo'   => true,
        ]);
    }

    private function crearOT(array $attrs = []): OrdenTrabajo
    {
        $marca = MarcaVehiculo::first() ?? MarcaVehiculo::create(['nombre' => 'CHEVROLET']);
        $veh = Vehiculo::first() ?? Vehiculo::create([
            'placa'    => 'FAC' . random_int(100, 999),
            'id_marca' => $marca->id,
        ]);
        $emp = EmpresaCliente::first() ?? EmpresaCliente::create([
            'nombre'      => 'EMPRESA TEST',
            'tipo'        => 'A',
            'tarifa_hora' => 50000,
        ]);

        return OrdenTrabajo::create(array_merge([
            'numero_ot'          => 800000 + random_int(1, 99999),
            'area'               => 'MECANICA',
            'id_vehiculo'        => $veh->id,
            'id_empresa_cliente' => $emp->id,
            'estado_proceso'     => 'EN_PROCESO',
            'estado_semaforo'    => 'A_TIEMPO',
            'fecha_ingreso'      => now()->toDateString(),
            'creado_por'         => $this->admin()->id,
        ], $attrs));
    }

    /** Filtro de estado 'FACTURADO' lista solo OTs que tienen número de factura. */
    public function test_filtro_facturados(): void
    {
        $admin = $this->admin();

        $otFacturada = $this->crearOT([
            'estado_proceso' => 'ENTREGADO',
            'numero_factura' => 'FAC-99991',
            'fecha_factura'  => now()->toDateString(),
        ]);

        $otSinFactura = $this->crearOT([
            'estado_proceso' => 'ENTREGADO',
            'numero_factura' => null,
        ]);

        $resp = $this->actingAs($admin)->get(route('ordenes.index', ['estado' => 'FACTURADO']));

        $resp->assertOk();
        $resp->assertSee($otFacturada->numero_ot);
        $resp->assertDontSee($otSinFactura->numero_ot);
    }

    /** Filtro de estado 'NO_FACTURADO' lista OTs sin número de factura. */
    public function test_filtro_no_facturados(): void
    {
        $admin = $this->admin();

        $otFacturada = $this->crearOT([
            'estado_proceso' => 'ENTREGADO',
            'numero_factura' => 'FAC-88882',
            'fecha_factura'  => now()->toDateString(),
        ]);

        $otSinFactura = $this->crearOT([
            'estado_proceso' => 'EN_PROCESO',
            'numero_factura' => null,
        ]);

        $resp = $this->actingAs($admin)->get(route('ordenes.index', ['estado' => 'NO_FACTURADO']));

        $resp->assertOk();
        $resp->assertSee($otSinFactura->numero_ot);
        $resp->assertDontSee('FAC-88882');
    }

    /** VFT permite salida con tareas en proceso, las pausa, y permite reingresar al taller para continuar el proceso. */
    public function test_vft_salida_y_reingreso_al_taller(): void
    {
        $admin = $this->admin();
        $ot = $this->crearOT(['estado_proceso' => 'EN_PROCESO']);

        $tec = Tecnico::first() ?? Tecnico::create([
            'nombre' => 'Tecnico MEC', 'especialidades' => ['MEC'], 'activo' => true,
        ]);

        $trabajo = TrabajoTecnico::create([
            'id_ot'        => $ot->id,
            'id_tecnico'   => $tec->id,
            'especialidad' => 'MEC',
            'estado'       => 'EN_PROCESO',
            'inicio_en'    => now()->subDays(2),
        ]);

        // 1. Cliente se lleva el auto temporalmente a su casa -> VFT
        $respSalida = $this->actingAs($admin)->post(route('ot.especial', $ot), [
            'nuevo_estado' => 'VFT',
            'comentario'   => 'El cliente se llevó el carro temporalmente a su casa',
            'fecha_evento' => now()->toDateString(),
        ]);

        $respSalida->assertSessionHasNoErrors();
        $this->assertSame('VFT', $ot->fresh()->estado_proceso);
        $this->assertSame('PAUSADO', $trabajo->fresh()->estado);

        // 2. Cliente regresa con el auto al taller para seguir con el proceso
        $respReingreso = $this->actingAs($admin)->post(route('ot.reingresar-vft', $ot), [
            'estado_destino'  => 'EN_PROCESO',
            'fecha_reingreso' => now()->toDateString(),
            'comentario'      => 'Vehículo regresa al taller para continuar reparación',
        ]);

        $respReingreso->assertSessionHasNoErrors();
        $this->assertSame('EN_PROCESO', $ot->fresh()->estado_proceso);
    }
}
