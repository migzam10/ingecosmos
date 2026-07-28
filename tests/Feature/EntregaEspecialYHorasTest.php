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

/**
 * Punto 2: tras una salida especial no negativa se puede registrar la entrega
 *          real del vehículo y pasar a ENTREGADO.
 * Punto 3: iniciar/finalizar hoy guarda la hora real, no 00:00 / 23:59.
 */
class EntregaEspecialYHorasTest extends TestCase
{
    use DatabaseTransactions;

    private function admin(): User
    {
        return User::all()->first(fn($u) => in_array('ADMIN', $u->roles ?: [])) ?? User::first();
    }

    private function vehiculo(): Vehiculo
    {
        return Vehiculo::first() ?? Vehiculo::create([
            'placa'    => 'TST' . random_int(100, 999),
            'id_marca' => MarcaVehiculo::value('id'),
        ]);
    }

    private function crearOT(string $estado): OrdenTrabajo
    {
        return OrdenTrabajo::create([
            'numero_ot'          => 900000 + random_int(1, 99999),
            'area'               => 'MECANICA',
            'id_vehiculo'        => $this->vehiculo()->id,
            'id_empresa_cliente' => EmpresaCliente::value('id'),
            'estado_proceso'     => $estado,
            'estado_semaforo'    => 'OK',
            'fecha_ingreso'      => now()->subDays(10)->toDateString(),
            'creado_por'         => User::value('id'),
        ]);
    }

    private function tecnicoUser(): array
    {
        $user = User::all()->first(fn($u) => $u->tecnico);
        if ($user) {
            return [$user, $user->tecnico];
        }
        $tecnico = Tecnico::first();
        $user = User::create([
            'name' => $tecnico->nombre, 'email' => 'tec' . $tecnico->id . '@test.local',
            'password' => bcrypt('secret'), 'roles' => ['TECNICO'], 'activo' => true,
        ]);
        $tecnico->update(['id_user' => $user->id]);
        return [$user, $tecnico->fresh()];
    }

    /** @dataProvider estadosEntregables */
    public function test_entregar_desde_salida_especial(string $estado): void
    {
        $admin = $this->admin();
        $ot    = $this->crearOT($estado); // sin técnicos: sin bloqueos

        $resp = $this->actingAs($admin)
            ->from(route('ordenes.show', $ot))
            ->post(route('ot.entregar', $ot), [
                'fecha_entrega_cliente' => now()->toDateString(),
                'comentario'            => 'Entregado al cliente',
            ]);

        $resp->assertSessionHasNoErrors();
        $this->assertSame('ENTREGADO', $ot->fresh()->estado_proceso);
        $this->assertNotNull($ot->fresh()->fecha_entrega_cliente);
    }

    public static function estadosEntregables(): array
    {
        return [
            'PTE_RETIRO'           => ['PTE_RETIRO'],
            'GARANTIA'             => ['GARANTIA'],
            'ARREGLO_DIRECTO'      => ['ARREGLO_DIRECTO'],
            'EN_OTRO_TALLER'       => ['EN_OTRO_TALLER'],
            'VFT'                  => ['VFT'],
            'REPUESTOS_INSTALADOS' => ['REPUESTOS_INSTALADOS'],
        ];
    }

    /** @dataProvider estadosNegativos */
    public function test_no_entregar_desde_cierre_negativo(string $estado): void
    {
        $admin = $this->admin();
        $ot    = $this->crearOT($estado);

        $resp = $this->actingAs($admin)
            ->post(route('ot.entregar', $ot), [
                'fecha_entrega_cliente' => now()->toDateString(),
            ]);

        $resp->assertStatus(422);
        $this->assertSame($estado, $ot->fresh()->estado_proceso);
    }

    public static function estadosNegativos(): array
    {
        return [
            'NO_AUTORIZADO' => ['NO_AUTORIZADO'],
            'ORDEN_ANULADA' => ['ORDEN_ANULADA'],
            'PERDIDA_TOTAL' => ['PERDIDA_TOTAL'],
        ];
    }

    /** REPUESTOS_INSTALADOS es un estado especial válido en la salida especial. */
    public function test_repuestos_instalados_como_salida_especial(): void
    {
        $admin = $this->admin();
        $ot    = $this->crearOT('EN_PROCESO');

        $resp = $this->actingAs($admin)
            ->from(route('ordenes.show', $ot))
            ->post(route('ot.especial', $ot), [
                'nuevo_estado' => 'REPUESTOS_INSTALADOS',
                'comentario'   => 'Repuestos instalados',
                'fecha_evento' => now()->toDateString(),
            ]);

        $resp->assertSessionHasNoErrors();
        $this->assertSame('REPUESTOS_INSTALADOS', $ot->fresh()->estado_proceso);
    }

    /** Iniciar y finalizar HOY guardan la hora real, no medianoche / fin de día. */
    public function test_hora_real_al_iniciar_y_finalizar_hoy(): void
    {
        [$userTec, $tecnico] = $this->tecnicoUser();
        $ot = $this->crearOT('EN_PROCESO');
        $trabajo = TrabajoTecnico::create([
            'id_ot' => $ot->id, 'id_tecnico' => $tecnico->id,
            'especialidad' => 'MEC', 'estado' => 'PENDIENTE',
            'fecha_asignacion' => now()->subDays(2)->toDateString(),
        ]);

        $this->actingAs($userTec)
            ->post(route('mis-tareas.iniciar', $trabajo), ['inicio_en' => now()->toDateString()]);

        $inicio = $trabajo->fresh()->inicio_en;
        $this->assertSame(now()->toDateString(), $inicio->toDateString());
        $this->assertNotSame('00:00', $inicio->format('H:i'), 'El inicio de hoy no debe ser medianoche');

        $this->actingAs($userTec)
            ->post(route('mis-tareas.finalizar', $trabajo->fresh()), ['fin_en' => now()->toDateString()]);

        $fin = $trabajo->fresh()->fin_en;
        $this->assertSame('FINALIZADO', $trabajo->fresh()->estado);
        $this->assertNotSame('23:59', $fin->format('H:i'), 'El fin de hoy no debe ser fin de día');
    }

    /** Backdating a un día anterior conserva ese día (no se inventa hora). */
    public function test_backdating_conserva_dia_anterior(): void
    {
        [$userTec, $tecnico] = $this->tecnicoUser();
        $ot = $this->crearOT('EN_PROCESO');
        $trabajo = TrabajoTecnico::create([
            'id_ot' => $ot->id, 'id_tecnico' => $tecnico->id,
            'especialidad' => 'ELEC', 'estado' => 'PENDIENTE',
            'fecha_asignacion' => now()->subDays(6)->toDateString(),
        ]);

        $ayer = now()->subDays(3)->toDateString();
        $this->actingAs($userTec)
            ->post(route('mis-tareas.iniciar', $trabajo), ['inicio_en' => $ayer]);

        $this->assertSame($ayer, $trabajo->fresh()->inicio_en->toDateString());
    }
}
