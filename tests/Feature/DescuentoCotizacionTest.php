<?php

namespace Tests\Feature;

use App\Models\EmpresaCliente;
use App\Models\MarcaVehiculo;
use App\Models\OrdenTrabajo;
use App\Models\User;
use App\Models\Vehiculo;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

/**
 * El descuento de una cotización se aplica ANTES del IVA y reduce también el
 * total facturado de la OT (HA/DR se mantienen sobre el valor bruto).
 */
class DescuentoCotizacionTest extends TestCase
{
    use DatabaseTransactions;

    private function admin(): User
    {
        return User::create([
            'name'     => 'Admin ' . random_int(1000, 9999),
            'email'    => 'admin' . random_int(1000, 9999) . '@test.local',
            'password' => bcrypt('secret'),
            'roles'    => ['ADMIN'],
            'activo'   => true,
        ]);
    }

    private function crearOT(): OrdenTrabajo
    {
        $veh = Vehiculo::first() ?? Vehiculo::create([
            'placa'    => 'TST' . random_int(100, 999),
            'id_marca' => MarcaVehiculo::value('id'),
        ]);

        // Empresa Tipo A para que el repuesto/insumo entren al total.
        $empresa = EmpresaCliente::where('tipo', 'A')->value('id') ?? EmpresaCliente::value('id');

        return OrdenTrabajo::create([
            'numero_ot'          => 900000 + random_int(1, 99999),
            'area'               => 'MECANICA',
            'id_vehiculo'        => $veh->id,
            'id_empresa_cliente' => $empresa,
            'estado_proceso'     => 'PTE_COTIZACION',
            'estado_semaforo'    => 'SIN_FECHA',
            'fecha_ingreso'      => now()->subDays(2)->toDateString(),
            'creado_por'         => User::value('id'),
        ]);
    }

    public function test_descuento_antes_de_iva_y_reduce_total_ot(): void
    {
        $ot = $this->crearOT();

        // MO 1.000.000, descuento 100.000 → base 900.000, IVA 19% = 171.000, total 1.071.000
        $this->actingAs($this->admin())
            ->post(route('cotizaciones.store', $ot), [
                'fecha_cotizacion' => now()->toDateString(),
                'items_mo'         => [['descripcion' => 'Reparación', 'precio' => 1000000]],
                'descuento_valor'  => 100000,
            ])
            ->assertSessionHasNoErrors();

        $cot = $ot->cotizaciones()->latest('id')->first();
        $this->assertEquals(100000, (float) $cot->descuento_valor);
        $this->assertEquals(171000, (float) $cot->iva_valor, 'IVA sobre base con descuento');
        $this->assertEquals(1071000, (float) $cot->total, 'Total = base + IVA');

        // El total facturado de la OT baja por el descuento (sin IVA).
        $ot->refresh();
        $this->assertEquals(900000, (float) $ot->total, 'OT total = bruto - descuento');
    }
}
