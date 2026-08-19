<?php

namespace Tests\Feature;

use App\Models\MarcaVehiculo;
use App\Models\OrdenCompra;
use App\Models\Proveedor;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class OrdenCompraTest extends TestCase
{
    use DatabaseTransactions;

    private function admin(): User
    {
        return $this->userCon(['ADMIN']);
    }

    private function userCon(array $roles): User
    {
        return User::create([
            'name'     => 'User ' . random_int(1000, 9999),
            'email'    => 'user' . random_int(1000, 9999) . '@test.local',
            'password' => bcrypt('secret'),
            'roles'    => $roles,
            'activo'   => true,
        ]);
    }

    private function payload(array $over = []): array
    {
        return array_merge([
            'numero'             => 900000 + random_int(1, 99999),
            'fecha'              => now()->toDateString(),
            'forma_pago'         => 'CONTADO',
            'proveedor_nombre'   => 'REPUESTOS DEL NORTE',
            'proveedor_nit'      => '80200' . random_int(1000, 9999),
            'proveedor_telefono' => '3001234567',
            'items'              => [
                ['cantidad' => 2, 'unidad' => 'und', 'descripcion' => 'FILTRO DE ACEITE', 'valor_unitario' => 15000, 'valor_total' => 30000],
                ['cantidad' => 1, 'unidad' => 'gl',  'descripcion' => 'ACEITE 20W50',      'valor_unitario' => 40000, 'valor_total' => 40000],
            ],
            'descuento_valor' => 0,
            'iva_valor'       => 0,
        ], $over);
    }

    public function test_crea_orden_con_items_proveedor_y_totales(): void
    {
        $admin = $this->admin();

        // subtotal 70.000, descuento 10.000 → base 60.000, IVA 11.400, total 71.400
        $this->actingAs($admin)
            ->post(route('ordenes-compra.store'), $this->payload([
                'descuento_valor' => 10000,
                'iva_valor'       => 11400,
            ]))
            ->assertRedirect();

        $orden = OrdenCompra::latest('id')->first();
        $this->assertNotNull($orden);
        $this->assertEquals(70000, (float) $orden->subtotal);
        $this->assertEquals(10000, (float) $orden->descuento_valor);
        $this->assertEquals(11400, (float) $orden->iva_valor);
        $this->assertEquals(71400, (float) $orden->total);
        $this->assertCount(2, $orden->items);
        $this->assertNotNull($orden->id_proveedor);
        $this->assertEquals('REPUESTOS DEL NORTE', $orden->proveedor->nombre);
    }

    public function test_numero_duplicado_rebota(): void
    {
        $admin = $this->admin();
        $num   = 900000 + random_int(1, 99999);

        $this->actingAs($admin)->post(route('ordenes-compra.store'), $this->payload(['numero' => $num]))->assertRedirect();

        $this->actingAs($admin)
            ->from(route('ordenes-compra.create'))
            ->post(route('ordenes-compra.store'), $this->payload(['numero' => $num]))
            ->assertRedirect(route('ordenes-compra.create'))
            ->assertSessionHasErrors('numero');
    }

    public function test_sin_items_rebota(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)
            ->from(route('ordenes-compra.create'))
            ->post(route('ordenes-compra.store'), $this->payload(['items' => []]))
            ->assertRedirect(route('ordenes-compra.create'))
            ->assertSessionHasErrors('items');
    }

    public function test_rehidrata_items_al_rebotar(): void
    {
        $admin = $this->admin();

        // fecha futura → rebota, pero los ítems deben volver al formulario.
        $this->actingAs($admin)
            ->from(route('ordenes-compra.create'))
            ->post(route('ordenes-compra.store'), $this->payload(['fecha' => now()->addDay()->toDateString()]))
            ->assertRedirect(route('ordenes-compra.create'))
            ->assertSessionHasErrors('fecha');

        $this->actingAs($admin)->get(route('ordenes-compra.create'))
            ->assertOk()
            ->assertSee('FILTRO DE ACEITE')
            ->assertSee('ACEITE 20W50');
    }

    public function test_buscar_proveedor_autollena(): void
    {
        $admin = $this->admin();
        $prov  = Proveedor::create(['nombre' => 'LUBRICANTES SA', 'nit' => '901' . random_int(100000, 999999), 'telefono' => '3157205280']);

        $this->actingAs($admin)
            ->getJson(route('api.proveedor', ['nit' => $prov->nit]))
            ->assertOk()
            ->assertJson(['encontrado' => true, 'proveedor_nombre' => 'LUBRICANTES SA', 'proveedor_telefono' => '3157205280']);
    }

    public function test_actualiza_y_elimina(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)->post(route('ordenes-compra.store'), $this->payload())->assertRedirect();
        $orden = OrdenCompra::latest('id')->first();

        $this->actingAs($admin)
            ->put(route('ordenes-compra.update', $orden), $this->payload([
                'numero'      => $orden->numero,
                'forma_pago'  => 'CREDITO',
                'items'       => [['cantidad' => 5, 'unidad' => 'und', 'descripcion' => 'PASTILLAS FRENO', 'valor_unitario' => 20000, 'valor_total' => 100000]],
            ]))
            ->assertRedirect(route('ordenes-compra.show', $orden));

        $orden->refresh();
        $this->assertEquals('CREDITO', $orden->forma_pago);
        $this->assertEquals(100000, (float) $orden->subtotal);
        $this->assertCount(1, $orden->items);

        $this->actingAs($admin)->delete(route('ordenes-compra.destroy', $orden))->assertRedirect(route('ordenes-compra.index'));
        $this->assertNull(OrdenCompra::find($orden->id));
    }

    public function test_pdf_y_show_responden(): void
    {
        $admin = $this->admin();
        $this->actingAs($admin)->post(route('ordenes-compra.store'), $this->payload())->assertRedirect();
        $orden = OrdenCompra::latest('id')->first();

        $this->actingAs($admin)->get(route('ordenes-compra.show', $orden))->assertOk()->assertSee('FILTRO DE ACEITE');
        $this->actingAs($admin)->get(route('ordenes-compra.pdf', $orden))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');
    }

    public function test_tecnico_no_accede(): void
    {
        $tecnico = $this->userCon(['TECNICO']);

        $this->actingAs($tecnico)->get(route('ordenes-compra.index'))->assertForbidden();
    }

    public function test_rol_no_admin_edita_pero_no_elimina(): void
    {
        // Se crea con un admin y luego edita/borra un rol con acceso (ALMACEN).
        $this->actingAs($this->admin())->post(route('ordenes-compra.store'), $this->payload())->assertRedirect();
        $orden = OrdenCompra::latest('id')->first();

        $almacen = $this->userCon(['ALMACEN']);

        // Editar: permitido.
        $this->actingAs($almacen)
            ->put(route('ordenes-compra.update', $orden), $this->payload(['numero' => $orden->numero, 'forma_pago' => 'CREDITO']))
            ->assertRedirect(route('ordenes-compra.show', $orden));

        // Eliminar: prohibido (solo ADMIN).
        $this->actingAs($almacen)->delete(route('ordenes-compra.destroy', $orden))->assertForbidden();
        $this->assertNotNull(OrdenCompra::find($orden->id));

        // ADMIN sí puede eliminar.
        $this->actingAs($this->admin())->delete(route('ordenes-compra.destroy', $orden))->assertRedirect(route('ordenes-compra.index'));
        $this->assertNull(OrdenCompra::find($orden->id));
    }

    public function test_recepcion_accede_y_cotizador_no(): void
    {
        // RECEPCION ahora tiene acceso.
        $this->actingAs($this->userCon(['RECEPCION']))->get(route('ordenes-compra.index'))->assertOk();
        // COTIZADOR fue removido → 403.
        $this->actingAs($this->userCon(['COTIZADOR']))->get(route('ordenes-compra.index'))->assertForbidden();
    }
}
