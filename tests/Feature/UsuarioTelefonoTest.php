<?php

namespace Tests\Feature;

use App\Models\Cotizacion;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class UsuarioTelefonoTest extends TestCase
{
    use DatabaseTransactions;

    private function admin(): User
    {
        return User::create([
            'name'     => 'Admin ' . random_int(1000, 9999),
            'email'    => 'admin' . random_int(1000, 9999) . '@test.local',
            'telefono' => '3001234567',
            'password' => bcrypt('secret123'),
            'roles'    => ['ADMIN'],
            'activo'   => true,
        ]);
    }

    public function test_crea_usuario_con_telefono(): void
    {
        $admin = $this->admin();
        $email = 'nuevo' . random_int(1000, 9999) . '@test.local';

        $this->actingAs($admin)
            ->post(route('admin.usuarios.store'), [
                'name'     => 'Carlos Perez',
                'email'    => $email,
                'telefono' => '3123456789',
                'password' => 'password123',
                'roles'    => ['COTIZADOR'],
            ])
            ->assertRedirect(route('admin.usuarios.index'));

        $this->assertDatabaseHas('users', [
            'email'    => $email,
            'telefono' => '3123456789',
        ]);
    }

    public function test_telefono_maximo_10_caracteres(): void
    {
        $admin = $this->admin();
        $email = 'invalido' . random_int(1000, 9999) . '@test.local';

        $this->actingAs($admin)
            ->post(route('admin.usuarios.store'), [
                'name'     => 'Usuario Telefono Largo',
                'email'    => $email,
                'telefono' => '12345678901', // 11 caracteres
                'password' => 'password123',
                'roles'    => ['COTIZADOR'],
            ])
            ->assertSessionHasErrors(['telefono']);

        $this->assertDatabaseMissing('users', [
            'email' => $email,
        ]);
    }

    public function test_actualiza_usuario_con_telefono(): void
    {
        $admin = $this->admin();
        $usuario = User::create([
            'name'     => 'User Para Editar',
            'email'    => 'editar' . random_int(1000, 9999) . '@test.local',
            'telefono' => '3001112233',
            'password' => bcrypt('password123'),
            'roles'    => ['COTIZADOR'],
            'activo'   => true,
        ]);

        $this->actingAs($admin)
            ->put(route('admin.usuarios.update', $usuario), [
                'name'     => 'User Editado',
                'email'    => $usuario->email,
                'telefono' => '3109876543',
                'roles'    => ['COTIZADOR'],
                'activo'   => 1,
            ])
            ->assertRedirect(route('admin.usuarios.index'));

        $this->assertDatabaseHas('users', [
            'id'       => $usuario->id,
            'telefono' => '3109876543',
        ]);
    }
}
