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

class CierreConTareasAbiertasTest extends TestCase
{
    use DatabaseTransactions; // rollback automático: no ensucia la BD

    /** Un vehículo cualquiera; si la BD de pruebas no tiene, se crea uno. */
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
            'estado_semaforo'    => 'A_TIEMPO',
            'fecha_ingreso'      => now()->subDays(10)->toDateString(),
            'fecha_inicio_proceso' => now()->subDays(5)->toDateString(),
            'fecha_terminacion'  => now()->subDay()->toDateString(),
            'creado_por'         => $this->admin()->id,
        ]);
    }

    /** Usuario con rol ADMIN (el seeder crea uno). */
    private function admin(): User
    {
        return User::all()->first(fn($u) => in_array('ADMIN', $u->roles ?: []))
            ?? User::first();
    }

    /**
     * Un técnico con su usuario. Si ningún técnico tiene usuario ligado,
     * se le crea uno para poder actuar como él.
     */
    private function tecnicoUser(): array
    {
        $user = User::all()->first(fn($u) => $u->tecnico);
        if ($user) {
            return [$user, $user->tecnico];
        }

        $tecnico = Tecnico::first();
        $user    = User::create([
            'name'     => $tecnico->nombre,
            'email'    => 'tec' . $tecnico->id . '@test.local',
            'password' => bcrypt('secret'),
            'roles'    => ['TECNICO'],
            'activo'   => true,
        ]);
        $tecnico->update(['id_user' => $user->id]);

        return [$user, $tecnico->fresh()];
    }

    /** El fix principal: finalizar la última tarea de una OT ENTREGADA NO la reabre. */
    public function test_finalizar_tarea_no_reabre_ot_entregada(): void
    {
        [$userTec, $tecnico] = $this->tecnicoUser();
        $ot = $this->crearOT('ENTREGADO');
        $trabajo = TrabajoTecnico::create([
            'id_ot'        => $ot->id,
            'id_tecnico'   => $tecnico->id,
            'especialidad' => 'MEC',
            'estado'       => 'EN_PROCESO',
            'inicio_en'    => now()->subDays(3),
        ]);

        $this->actingAs($userTec)
            ->post(route('mis-tareas.finalizar', $trabajo), ['fin_en' => now()->toDateString()]);

        $this->assertSame('ENTREGADO', $ot->fresh()->estado_proceso, 'La OT entregada NO debe reabrirse');
        $this->assertSame('FINALIZADO', $trabajo->fresh()->estado, 'La tarea sí se finaliza');
    }

    /** No se puede entregar si un técnico tiene trabajo EN_PROCESO. */
    public function test_no_entregar_con_tarea_en_proceso(): void
    {
        $admin = $this->admin();
        $tecnico = Tecnico::first();
        $ot = $this->crearOT('PROGRAMADO_ENTREGA');
        TrabajoTecnico::create([
            'id_ot' => $ot->id, 'id_tecnico' => $tecnico->id,
            'especialidad' => 'MEC', 'estado' => 'EN_PROCESO', 'inicio_en' => now()->subDay(),
        ]);

        $resp = $this->actingAs($admin)
            ->from(route('ordenes.show', $ot))
            ->post(route('ot.entregar', $ot), ['fecha_entrega_cliente' => now()->toDateString()]);

        $resp->assertSessionHasErrors('tareas_abiertas');
        $this->assertSame('PROGRAMADO_ENTREGA', $ot->fresh()->estado_proceso);
    }

    /** No se puede cerrar (salida especial) si un técnico tiene trabajo EN_PROCESO. */
    public function test_no_cerrar_especial_con_tarea_en_proceso(): void
    {
        $admin = $this->admin();
        $tecnico = Tecnico::first();
        $ot = $this->crearOT('EN_PROCESO');
        TrabajoTecnico::create([
            'id_ot' => $ot->id, 'id_tecnico' => $tecnico->id,
            'especialidad' => 'MEC', 'estado' => 'PAUSADO', 'inicio_en' => now()->subDay(),
        ]);

        $resp = $this->actingAs($admin)
            ->from(route('ordenes.show', $ot))
            ->post(route('ot.especial', $ot), [
                'nuevo_estado' => 'VFT',
                'comentario'   => 'prueba',
                'fecha_evento' => now()->toDateString(),
            ]);

        $resp->assertSessionHasErrors('tareas_abiertas');
        $this->assertSame('EN_PROCESO', $ot->fresh()->estado_proceso);
    }

    /** Una tarea PENDIENTE (asignada pero no iniciada) también bloquea la entrega. */
    public function test_pendiente_bloquea_entrega(): void
    {
        $admin = $this->admin();
        $tecnico = Tecnico::first();
        $ot = $this->crearOT('PROGRAMADO_ENTREGA');
        TrabajoTecnico::create([
            'id_ot' => $ot->id, 'id_tecnico' => $tecnico->id,
            'especialidad' => 'MEC', 'estado' => 'PENDIENTE',
        ]);

        $resp = $this->actingAs($admin)
            ->from(route('ordenes.show', $ot))
            ->post(route('ot.entregar', $ot), ['fecha_entrega_cliente' => now()->toDateString()]);

        $resp->assertSessionHasErrors('tareas_abiertas');
        $this->assertSame('PROGRAMADO_ENTREGA', $ot->fresh()->estado_proceso);
    }

    /** Con todas las tareas FINALIZADO sí se puede entregar. */
    public function test_entrega_ok_con_todo_finalizado(): void
    {
        $admin = $this->admin();
        $tecnico = Tecnico::first();
        $ot = $this->crearOT('PROGRAMADO_ENTREGA');
        TrabajoTecnico::create([
            'id_ot' => $ot->id, 'id_tecnico' => $tecnico->id,
            'especialidad' => 'MEC', 'estado' => 'FINALIZADO',
            'inicio_en' => now()->subDays(2), 'fin_en' => now()->subDay(),
            'valor_liquidar' => 50000,
        ]);

        $resp = $this->actingAs($admin)
            ->from(route('ordenes.show', $ot))
            ->post(route('ot.entregar', $ot), ['fecha_entrega_cliente' => now()->toDateString()]);

        $resp->assertSessionHasNoErrors();
        $this->assertSame('ENTREGADO', $ot->fresh()->estado_proceso);
    }
}
