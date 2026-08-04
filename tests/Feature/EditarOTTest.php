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
 * Una OT se puede editar en cualquier estado salvo cuando ya está ENTREGADO.
 */
class EditarOTTest extends TestCase
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

    private function crearOT(string $estado): OrdenTrabajo
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
            'estado_semaforo'    => 'A_TIEMPO',
            'fecha_ingreso'      => now()->subDays(5)->toDateString(),
            'creado_por'         => User::value('id'),
        ]);
    }

    /** @dataProvider estadosEditables */
    public function test_edita_en_estados_activos(string $estado): void
    {
        $ot = $this->crearOT($estado);

        $this->actingAs($this->admin())
            ->get(route('ordenes.edit', $ot))
            ->assertOk();
    }

    public static function estadosEditables(): array
    {
        return [
            'pendiente cotización' => ['PTE_COTIZACION'],
            'pendiente repuestos'  => ['PTE_REPUESTOS'],
            'en proceso'           => ['EN_PROCESO'],
            'programado entrega'   => ['PROGRAMADO_ENTREGA'],
        ];
    }

    /** Una OT ya entregada NO se puede editar. */
    public function test_no_edita_entregada(): void
    {
        $ot = $this->crearOT('ENTREGADO');

        $this->actingAs($this->admin())
            ->get(route('ordenes.edit', $ot))
            ->assertForbidden();
    }
}
