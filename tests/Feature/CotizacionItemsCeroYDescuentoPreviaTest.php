<?php

namespace Tests\Feature;

use App\Models\Cotizacion;
use App\Models\EmpresaCliente;
use App\Models\MarcaVehiculo;
use App\Models\OrdenTrabajo;
use App\Models\User;
use App\Models\Vehiculo;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class CotizacionItemsCeroYDescuentoPreviaTest extends TestCase
{
    use DatabaseTransactions;

    private function admin(): User
    {
        return User::create([
            'name' => 'Admin ' . random_int(1000, 9999),
            'email' => 'admin' . random_int(1000, 9999) . '@test.local',
            'password' => bcrypt('secret'), 'roles' => ['ADMIN'], 'activo' => true,
        ]);
    }

    /** Una cotización previa guarda ítems en valor 0 (aparecen, no suman) y aplica descuento. */
    public function test_previa_guarda_items_en_cero_y_descuento(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)
            ->post(route('cotizaciones.previa.store'), [
                'numero_cot'       => 700000 + random_int(1, 99999),
                'fecha_cotizacion' => now()->toDateString(),
                'placa_previa'     => 'ABC' . random_int(100, 999),
                'nombre_cliente'   => 'Cliente Previa',
                'items_mo'         => [
                    ['descripcion' => 'REVISION SIN COSTO', 'precio' => 0],
                    ['descripcion' => 'REPARACION', 'precio' => 100000],
                ],
                'descuento_valor'  => 10000,
                'iva_valor'        => 0,
            ])
            ->assertRedirect();

        $cot = Cotizacion::where('es_previa', true)->latest('id')->first();
        $this->assertNotNull($cot);
        // Se guardaron los DOS ítems, incluido el de precio 0.
        $this->assertCount(2, $cot->itemsMo);
        $this->assertTrue($cot->itemsMo->contains(fn ($i) => (float) $i->precio === 0.0));
        // Descuento aplicado: subtotal 100.000 - 10.000 = total 90.000 (sin IVA).
        $this->assertEquals(10000, (float) $cot->descuento_valor);
        $this->assertEquals(90000, (float) $cot->total);
    }

    /** Una cotización normal (sobre OT) también guarda ítems en 0. */
    public function test_cotizacion_normal_guarda_repuesto_en_cero(): void
    {
        $admin = $this->admin();

        $veh = Vehiculo::first() ?? Vehiculo::create(['placa' => 'TST' . random_int(100, 999), 'id_marca' => MarcaVehiculo::value('id')]);
        $ot = OrdenTrabajo::create([
            'numero_ot' => 900000 + random_int(1, 99999), 'area' => 'MECANICA', 'id_vehiculo' => $veh->id,
            'id_empresa_cliente' => EmpresaCliente::value('id'), 'estado_proceso' => 'PTE_COTIZACION',
            'estado_semaforo' => 'SIN_FECHA', 'fecha_ingreso' => now()->subDay()->toDateString(), 'creado_por' => $admin->id,
        ]);

        $this->actingAs($admin)
            ->post(route('cotizaciones.store', $ot), [
                'fecha_cotizacion' => now()->toDateString(),
                'items_repuesto'   => [
                    ['descripcion' => 'REPUESTO CORTESIA', 'unidades' => 1, 'precio_unitario' => 0, 'precio_total' => 0],
                ],
                'iva_valor' => 0,
            ])
            ->assertRedirect();

        $cot = Cotizacion::where('id_ot', $ot->id)->latest('id')->first();
        $this->assertNotNull($cot);
        $this->assertCount(1, $cot->itemsRepuesto);
        $this->assertEquals(0.0, (float) $cot->itemsRepuesto->first()->precio_total);
    }
}
