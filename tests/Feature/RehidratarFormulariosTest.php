<?php

namespace Tests\Feature;

use App\Models\CatalogoInsumo;
use App\Models\EmpresaCliente;
use App\Models\MarcaVehiculo;
use App\Models\OrdenTrabajo;
use App\Models\User;
use App\Models\Vehiculo;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

/**
 * Cuando un guardado rebota por validación, el formulario NO debe perder lo
 * escrito. Aquí se cubre la cotización (ítems dinámicos) y la OT.
 */
class RehidratarFormulariosTest extends TestCase
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

    private function crearOT(string $estado = 'PTE_COTIZACION'): OrdenTrabajo
    {
        $veh = Vehiculo::first() ?? Vehiculo::create([
            'placa'    => 'TST' . random_int(100, 999),
            'id_marca' => MarcaVehiculo::value('id'),
        ]);

        return OrdenTrabajo::create([
            'numero_ot'          => 900000 + random_int(1, 99999),
            'area'               => 'MECANICA',
            'id_vehiculo'        => $veh->id,
            'id_empresa_cliente' => EmpresaCliente::value('id'),
            'estado_proceso'     => $estado,
            'estado_semaforo'    => 'SIN_FECHA',
            'fecha_ingreso'      => now()->subDays(2)->toDateString(),
            'creado_por'         => User::value('id'),
        ]);
    }

    /** La cotización conserva ítems y observaciones tras un rebote de validación. */
    public function test_cotizacion_conserva_items_al_rebotar(): void
    {
        $admin = $this->admin();
        $ot    = $this->crearOT('PTE_COTIZACION');

        // Fecha futura → falla la validación (before_or_equal:today).
        $this->actingAs($admin)
            ->from(route('cotizaciones.create', $ot))
            ->post(route('cotizaciones.store', $ot), [
                'fecha_cotizacion' => now()->addDay()->toDateString(),
                'items_mo'         => [['descripcion' => 'REPARACION PRUEBA', 'precio' => 150000]],
                'items_repuesto'   => [['descripcion' => 'FILTRO PRUEBA', 'unidades' => 2, 'precio_unitario' => 30000, 'precio_total' => 60000]],
                'observaciones'    => 'NOTA QUE NO SE PIERDE',
                'descuento_valor'  => 10000,
            ])
            ->assertRedirect(route('cotizaciones.create', $ot))
            ->assertSessionHasErrors('fecha_cotizacion');

        // Al volver al formulario, lo escrito sigue ahí (rehidratado desde old()).
        $page = $this->actingAs($admin)->get(route('cotizaciones.create', $ot));
        $page->assertOk()
            ->assertSee('REPARACION PRUEBA')
            ->assertSee('FILTRO PRUEBA')
            ->assertSee('NOTA QUE NO SE PIERDE');
    }

    /** El formulario muestra un resumen de errores (no falla en silencio). */
    public function test_ot_muestra_resumen_de_errores(): void
    {
        $admin = $this->admin();

        // Teléfono de 30 caracteres (supera 25) → rebota.
        $this->actingAs($admin)
            ->from(route('ordenes.create'))
            ->post(route('ordenes.store'), [
                'placa'              => 'ABC123',
                'id_marca'           => MarcaVehiculo::value('id'),
                'nombre_cliente'     => 'Cliente Prueba',
                'telefono_cliente'   => str_repeat('9', 30),
                'id_empresa_cliente' => EmpresaCliente::value('id'),
                'area'               => 'MECANICA',
                'km_ingreso'         => 0,
                'nivel_combustible'  => 5,
                'fecha_ingreso'      => now()->toDateString(),
            ])
            ->assertRedirect(route('ordenes.create'))
            ->assertSessionHasErrors('telefono_cliente');

        $page = $this->actingAs($admin)->get(route('ordenes.create'));
        $page->assertOk()->assertSee('Revisa lo siguiente');
    }

    private function insumo(): CatalogoInsumo
    {
        return CatalogoInsumo::first() ?? CatalogoInsumo::create([
            'nombre' => 'Insumo ' . random_int(1000, 9999), 'unidad_medida' => 'und',
            'precio_venta' => 1000, 'stock_minimo' => 0, 'stock_actual' => 100, 'activo' => true,
        ]);
    }

    /** Una salida directa conserva los ítems agregados tras un rebote. */
    public function test_salida_conserva_items_al_rebotar(): void
    {
        $admin = $this->admin();
        $ins   = $this->insumo();

        // Sin 'entregado_a' (requerido) → rebota.
        $this->actingAs($admin)
            ->from(route('almacen.salidas.create'))
            ->post(route('almacen.salidas.store'), [
                'tipo'  => 'DIRECTA',
                'fecha' => now()->toDateString(),
                'items' => [['id_insumo' => $ins->id, 'descripcion' => 'ACEITE DIRECTO PRUEBA', 'cantidad' => 3, 'precio_venta' => 1000]],
            ])
            ->assertRedirect(route('almacen.salidas.create'))
            ->assertSessionHasErrors('entregado_a');

        $page = $this->actingAs($admin)->get(route('almacen.salidas.create'));
        $page->assertOk()->assertSee('ACEITE DIRECTO PRUEBA');
    }

    /** Una entrada conserva los ítems agregados tras un rebote. */
    public function test_entrada_conserva_items_al_rebotar(): void
    {
        $admin = $this->admin();
        $ins   = $this->insumo();

        // Fecha futura → rebota (before_or_equal:today).
        $this->actingAs($admin)
            ->from(route('almacen.entradas.create'))
            ->post(route('almacen.entradas.store'), [
                'fecha' => now()->addDay()->toDateString(),
                'items' => [['id_insumo' => $ins->id, 'cantidad' => 7, 'precio_compra' => 12345]],
            ])
            ->assertRedirect(route('almacen.entradas.create'))
            ->assertSessionHasErrors('fecha');

        $page = $this->actingAs($admin)->get(route('almacen.entradas.create'));
        $page->assertOk()->assertSee('12345'); // precio_compra rehidratado en ITEMS_INIT
    }
}
