<?php

namespace Tests\Feature;

use App\Models\OrdenCompra;
use App\Models\Proveedor;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class ProveedorCrudTest extends TestCase
{
    use DatabaseTransactions;

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

    public function test_crea_proveedor(): void
    {
        $nit = '900' . random_int(100000, 999999);

        $this->actingAs($this->userCon(['ALMACEN']))
            ->post(route('proveedores.store'), ['nombre' => 'REPUESTOS XYZ', 'nit' => $nit, 'telefono' => '3001112233'])
            ->assertRedirect(route('proveedores.index'));

        $this->assertDatabaseHas('proveedores', ['nombre' => 'REPUESTOS XYZ', 'nit' => $nit, 'telefono' => '3001112233']);
    }

    public function test_nit_duplicado_rebota(): void
    {
        $nit = '901' . random_int(100000, 999999);
        Proveedor::create(['nombre' => 'EXISTENTE', 'nit' => $nit]);

        $this->actingAs($this->userCon(['ADMIN']))
            ->from(route('proveedores.create'))
            ->post(route('proveedores.store'), ['nombre' => 'OTRO', 'nit' => $nit])
            ->assertRedirect(route('proveedores.create'))
            ->assertSessionHasErrors('nit');
    }

    public function test_edita_proveedor(): void
    {
        $prov = Proveedor::create(['nombre' => 'VIEJO', 'nit' => '80' . random_int(100000, 999999)]);

        $this->actingAs($this->userCon(['COORDINADOR']))
            ->put(route('proveedores.update', $prov), ['nombre' => 'NUEVO NOMBRE', 'nit' => $prov->nit, 'telefono' => '3009998877'])
            ->assertRedirect(route('proveedores.index'));

        $prov->refresh();
        $this->assertEquals('NUEVO NOMBRE', $prov->nombre);
        $this->assertEquals('3009998877', $prov->telefono);
    }

    public function test_admin_elimina_proveedor_sin_ordenes(): void
    {
        $prov = Proveedor::create(['nombre' => 'BORRABLE', 'nit' => '82' . random_int(100000, 999999)]);

        $this->actingAs($this->userCon(['ADMIN']))
            ->delete(route('proveedores.destroy', $prov))
            ->assertRedirect(route('proveedores.index'));

        $this->assertNull(Proveedor::find($prov->id));
    }

    public function test_no_elimina_proveedor_con_ordenes(): void
    {
        $prov = Proveedor::create(['nombre' => 'CON ORDENES', 'nit' => '83' . random_int(100000, 999999)]);
        OrdenCompra::create([
            'numero' => 900000 + random_int(1, 99999), 'fecha' => now()->toDateString(), 'forma_pago' => 'CONTADO',
            'id_proveedor' => $prov->id, 'subtotal' => 1000, 'total' => 1000, 'creado_por' => User::value('id'),
        ]);

        $this->actingAs($this->userCon(['ADMIN']))
            ->delete(route('proveedores.destroy', $prov))
            ->assertRedirect()
            ->assertSessionHas('error');

        $this->assertNotNull(Proveedor::find($prov->id));
    }

    public function test_recepcion_no_elimina(): void
    {
        // RECEPCION tiene acceso a ver/crear/editar pero NO a eliminar (solo ADMIN).
        $prov = Proveedor::create(['nombre' => 'X', 'nit' => '84' . random_int(100000, 999999)]);

        $this->actingAs($this->userCon(['RECEPCION']))
            ->delete(route('proveedores.destroy', $prov))
            ->assertForbidden();

        $this->assertNotNull(Proveedor::find($prov->id));
    }

    public function test_tecnico_y_cotizador_no_acceden(): void
    {
        $this->actingAs($this->userCon(['TECNICO']))->get(route('proveedores.index'))->assertForbidden();
        $this->actingAs($this->userCon(['COTIZADOR']))->get(route('proveedores.index'))->assertForbidden();
    }
}
