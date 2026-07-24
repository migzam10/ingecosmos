<?php

namespace Tests\Feature;

use App\Models\Cotizacion;
use App\Models\EmpresaCliente;
use App\Models\MarcaVehiculo;
use App\Models\OrdenTrabajo;
use App\Models\Tecnico;
use App\Models\User;
use App\Models\Vehiculo;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

/**
 * Cubre las reglas de cotización:
 *  - Una cotización fuera de BORRADOR solo la edita un administrador.
 *  - Las previas guardan su propia fecha (que puede ser hacia atrás) y el
 *    kilometraje del vehículo.
 *
 * Nota: asignar técnicos NO exige cotización autorizada. El taller asigna
 * desde que entra el carro, tenga cotización o no.
 */
class CotizacionAutorizadaTest extends TestCase
{
    use DatabaseTransactions;

    private function usuario(array $roles): User
    {
        return User::create([
            'name'     => 'Test ' . implode('-', $roles),
            'email'    => strtolower(implode('', $roles)) . random_int(1000, 9999) . '@test.local',
            'password' => bcrypt('secret'),
            'roles'    => $roles,
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

    private function crearCotizacion(?OrdenTrabajo $ot, string $estado): Cotizacion
    {
        return Cotizacion::create([
            'numero_cot'       => 800000 + random_int(1, 99999),
            'id_ot'            => $ot?->id,
            'creada_por'       => User::value('id'),
            'estado'           => $estado,
            'fecha_cotizacion' => now()->toDateString(),
            'subtotal_mo'      => 100000,
            'total'            => 119000,
        ]);
    }

    /** Se asigna técnico aunque la OT no tenga ninguna cotización. */
    public function test_asignar_tecnico_sin_cotizacion(): void
    {
        $admin = $this->usuario(['ADMIN']);
        $ot    = $this->crearOT('PTE_COTIZACION');

        $resp = $this->actingAs($admin)
            ->from(route('ordenes.show', $ot))
            ->post(route('ordenes.tecnicos.store', $ot), [
                'id_tecnico'       => Tecnico::value('id'),
                'especialidad'     => 'MEC',
                'fecha_asignacion' => now()->toDateString(),
            ]);

        $resp->assertSessionHasNoErrors();
        $this->assertSame(1, $ot->trabajosTecnico()->count());
    }

    /** Y también con la cotización aún en BORRADOR (sin autorizar). */
    public function test_asignar_tecnico_con_cotizacion_sin_autorizar(): void
    {
        $admin = $this->usuario(['ADMIN']);
        $ot    = $this->crearOT('PTE_AUTORIZACION');
        $this->crearCotizacion($ot, 'BORRADOR');

        $resp = $this->actingAs($admin)
            ->from(route('ordenes.show', $ot))
            ->post(route('ordenes.tecnicos.store', $ot), [
                'id_tecnico'       => Tecnico::value('id'),
                'especialidad'     => 'MEC',
                'fecha_asignacion' => now()->toDateString(),
            ]);

        $resp->assertSessionHasNoErrors();
        $this->assertSame(1, $ot->trabajosTecnico()->count());
    }

    /** Una OT que ya pasó la autorización no queda bloqueada. */
    public function test_ot_en_proceso_permite_asignar(): void
    {
        $admin = $this->usuario(['ADMIN']);
        $ot    = $this->crearOT('EN_PROCESO');

        $resp = $this->actingAs($admin)
            ->from(route('ordenes.show', $ot))
            ->post(route('ordenes.tecnicos.store', $ot), [
                'id_tecnico'       => Tecnico::value('id'),
                'especialidad'     => 'LAT',
                'fecha_asignacion' => now()->toDateString(),
            ]);

        $resp->assertSessionHasNoErrors();
        $this->assertSame(1, $ot->trabajosTecnico()->count());
    }

    /** Un cotizador no puede editar una cotización ya autorizada. */
    public function test_cotizador_no_edita_cotizacion_autorizada(): void
    {
        $cotizador = $this->usuario(['COTIZADOR']);
        $ot        = $this->crearOT('PTE_ORDEN');
        $cot       = $this->crearCotizacion($ot, 'AUTORIZADA');

        $this->actingAs($cotizador)
            ->get(route('cotizaciones.edit', $cot))
            ->assertForbidden();
    }

    /** El administrador sí puede editarla. */
    public function test_admin_edita_cotizacion_autorizada(): void
    {
        $admin = $this->usuario(['ADMIN']);
        $ot    = $this->crearOT('PTE_ORDEN');
        $cot   = $this->crearCotizacion($ot, 'AUTORIZADA');

        $this->actingAs($admin)
            ->get(route('cotizaciones.edit', $cot))
            ->assertOk();
    }

    /** La fecha de una cotización previa se guarda y se puede poner hacia atrás. */
    public function test_previa_guarda_fecha_y_kilometraje(): void
    {
        $admin  = $this->usuario(['ADMIN']);
        $fecha  = now()->subDays(3)->toDateString();

        $resp = $this->actingAs($admin)->post(route('cotizaciones.previa.store'), [
            'fecha_cotizacion' => $fecha,
            'placa_previa'     => 'ABC123',
            'km_previa'        => 87500,
            'nombre_cliente'   => 'Cliente Prueba',
            'items_mo'         => [['descripcion' => 'Latoneo puerta', 'precio' => 200000]],
        ]);

        $resp->assertSessionHasNoErrors();

        $cot = Cotizacion::where('placa_previa', 'ABC123')->latest('id')->first();
        $this->assertNotNull($cot);
        $this->assertSame($fecha, $cot->fecha_cotizacion->toDateString());
        $this->assertSame(87500, $cot->kilometraje());
    }

    /** No se acepta una fecha futura. */
    public function test_previa_rechaza_fecha_futura(): void
    {
        $admin = $this->usuario(['ADMIN']);

        $this->actingAs($admin)
            ->from(route('cotizaciones.previa.create'))
            ->post(route('cotizaciones.previa.store'), [
                'fecha_cotizacion' => now()->addDay()->toDateString(),
                'placa_previa'     => 'XYZ987',
                'nombre_cliente'   => 'Cliente Prueba',
            ])
            ->assertSessionHasErrors('fecha_cotizacion');
    }
}
