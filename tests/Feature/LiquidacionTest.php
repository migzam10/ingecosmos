<?php

namespace Tests\Feature;

use App\Models\EmpresaCliente;
use App\Models\MarcaVehiculo;
use App\Models\OrdenTrabajo;
use App\Models\PagoTecnico;
use App\Models\Tecnico;
use App\Models\TrabajoTecnico;
use App\Models\User;
use App\Models\Vehiculo;
use App\Services\LiquidacionService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class LiquidacionTest extends TestCase
{
    use DatabaseTransactions;

    private function admin(): User
    {
        return User::create([
            'name' => 'Admin ' . random_int(1000, 9999), 'email' => 'admin' . random_int(1000, 9999) . '@test.local',
            'password' => bcrypt('secret'), 'roles' => ['ADMIN'], 'activo' => true,
        ]);
    }

    private function tecnicoConUser(): Tecnico
    {
        $u = User::create([
            'name' => 'Tec ' . random_int(1000, 9999), 'email' => 'tec' . random_int(1000, 9999) . '@test.local',
            'password' => bcrypt('secret'), 'roles' => ['TECNICO'], 'activo' => true,
        ]);
        return Tecnico::create(['id_user' => $u->id, 'nombre' => $u->name, 'especialidades' => ['MEC'], 'activo' => true]);
    }

    private function otConTrabajo(Tecnico $tec, string $fechaFin, float $valor, ?string $fechaAsignacion = null): TrabajoTecnico
    {
        $empresa = EmpresaCliente::first() ?? EmpresaCliente::create([
            'nombre' => 'EMPRESA TEST', 'tipo' => 'A', 'tarifa_hora' => 50000,
        ]);
        $admin = User::first() ?? $this->admin();
        $marca = MarcaVehiculo::first() ?? MarcaVehiculo::create(['nombre' => 'RENAULT']);
        $veh = Vehiculo::first() ?? Vehiculo::create(['placa' => 'TST' . random_int(100, 999), 'id_marca' => $marca->id]);

        $ot = OrdenTrabajo::create([
            'numero_ot' => 900000 + random_int(1, 99999), 'area' => 'MECANICA', 'id_vehiculo' => $veh->id,
            'id_empresa_cliente' => $empresa->id, 'estado_proceso' => 'ENTREGADO',
            'estado_semaforo' => 'OK', 'fecha_ingreso' => now()->subMonths(3)->toDateString(), 'creado_por' => $admin->id,
        ]);
        return TrabajoTecnico::create([
            'id_ot' => $ot->id, 'id_tecnico' => $tec->id, 'especialidad' => 'MEC',
            'fecha_asignacion' => $fechaAsignacion ?? $fechaFin,
            'fin_en' => Carbon::parse($fechaFin)->setTime(17, 0),
            'estado' => 'FINALIZADO', 'valor_liquidar' => $valor,
        ]);
    }

    /** En períodos desde septiembre 2026, un trabajo cae en el mes cuando el técnico termina la tarea (fin_en), no cuando se asigna. */
    public function test_trabajo_cuenta_en_el_mes_de_finalizacion_no_de_creacion(): void
    {
        $svc = app(LiquidacionService::class);
        $tec = $this->tecnicoConUser();

        // Asignado en septiembre 2026 pero finalizado por el técnico en octubre 2026
        $this->otConTrabajo($tec, '2026-10-15', 244000, '2026-09-10');

        $septiembre = $svc->resumenTecnico($tec, 9, 2026);
        $octubre    = $svc->resumenTecnico($tec, 10, 2026);
        $noviembre  = $svc->resumenTecnico($tec, 11, 2026);

        $this->assertEquals(0, (float) $septiembre['total_ganado']); // NO cae en septiembre (cuando se asignó)
        $this->assertEquals(244000, (float) $octubre['total_ganado']); // Cae en octubre (cuando terminó la tarea)
        $this->assertEquals(1, $octubre['trabajos']->count());
        $this->assertEquals(0, (float) $noviembre['total_ganado']);
        $this->assertEquals(0, $noviembre['trabajos']->count());
    }

    /** En períodos históricos (anteriores a septiembre 2026), se preserva la liquidación por fecha de asignación. */
    public function test_periodos_historicos_anteriores_a_septiembre_2026_usan_fecha_asignacion(): void
    {
        $svc = app(LiquidacionService::class);
        $tec = $this->tecnicoConUser();

        // Asignado en junio 2026 pero con fin_en en julio 2026
        $this->otConTrabajo($tec, '2026-07-15', 300000, '2026-06-10');

        $junio = $svc->resumenTecnico($tec, 6, 2026);
        $julio = $svc->resumenTecnico($tec, 7, 2026);

        $this->assertEquals(300000, (float) $junio['total_ganado']); // Cae en junio por fecha_asignacion histórica
        $this->assertEquals(1, $junio['trabajos']->count());
    }

    /** El recibo de un pago: el dueño y ADMIN sí; otro técnico recibe 403. */
    public function test_recibo_pdf_respeta_propiedad(): void
    {
        $tecA = $this->tecnicoConUser();
        $tecB = $this->tecnicoConUser();

        $pago = PagoTecnico::create([
            'id_tecnico' => $tecA->id, 'id_user' => $this->admin()->id, 'anio' => 2026, 'mes' => 6,
            'monto' => 100000, 'tipo' => 'ABONO', 'fecha_pago' => '2026-06-20',
        ]);

        // Otro técnico: 403
        $this->actingAs($tecB->user)->get(route('pagos.pdf', $pago))->assertForbidden();
        // Dueño: 200 PDF
        $this->actingAs($tecA->user)->get(route('pagos.pdf', $pago))
            ->assertOk()->assertHeader('content-type', 'application/pdf');
        // ADMIN: 200 PDF
        $this->actingAs($this->admin())->get(route('pagos.pdf', $pago))
            ->assertOk()->assertHeader('content-type', 'application/pdf');
    }

    /** Las deducciones (incluyendo uniformes) salen del pago y no pueden superar el pago. */
    public function test_deducciones_ajustan_neto_y_validaciones(): void
    {
        $admin = $this->admin();
        $tec   = $this->tecnicoConUser();
        $this->otConTrabajo($tec, '2026-06-10', 1000000); // devengado 1.000.000

        // Pago válido: monto 400.000 con deducciones 250.000 (materiales 100k + uniformes 50k + ahorro1 100k)
        $this->actingAs($admin)->post(route('liquidacion.avance', $tec), [
            'mes' => 6, 'anio' => 2026, 'tipo' => 'ABONO', 'fecha_pago' => '2026-06-15',
            'monto' => 400000, 'ded_materiales' => 100000, 'ded_uniformes' => 50000, 'ded_ahorro_1' => 100000,
        ])->assertRedirect();

        $data = app(\App\Services\LiquidacionService::class)->resumenTecnico($tec, 6, 2026);
        $this->assertEquals(400000, (float) $data['total_avances']);
        $this->assertEquals(250000, (float) $data['total_deducciones']);
        $this->assertEquals(50000, (float) ($data['deducciones']['ded_uniformes'] ?? 0));
        // Neto entregado (Valor pago) = 400.000 - 250.000 = 150.000
        $this->assertEquals(150000, (float) $data['total_neto']);
        // Saldo = 1.000.000 - 400.000 = 600.000
        $this->assertEquals(600000, (float) $data['saldo']);

        // Deducciones mayores al pago → rebota, no se crea.
        $antes = \App\Models\PagoTecnico::count();
        $this->actingAs($admin)
            ->from(route('liquidacion.show', $tec))
            ->post(route('liquidacion.avance', $tec), [
                'mes' => 6, 'anio' => 2026, 'tipo' => 'ABONO', 'fecha_pago' => '2026-06-16',
                'monto' => 100000, 'ded_prestamo' => 150000,
            ])
            ->assertSessionHasErrors('deducciones');
        $this->assertEquals($antes, \App\Models\PagoTecnico::count());

        // Tipo ANTICIPO ya no se acepta.
        $this->actingAs($admin)
            ->from(route('liquidacion.show', $tec))
            ->post(route('liquidacion.avance', $tec), [
                'mes' => 6, 'anio' => 2026, 'tipo' => 'ANTICIPO', 'fecha_pago' => '2026-06-16', 'monto' => 50000,
            ])
            ->assertSessionHasErrors('tipo');
    }

    /** La planilla mensual responde en HTML y PDF. */
    public function test_planilla_responde(): void
    {
        $admin = $this->admin();
        $this->actingAs($admin)->get(route('liquidacion.planilla', ['mes' => 6, 'anio' => 2026]))->assertOk();
        $this->actingAs($admin)->get(route('liquidacion.planilla.pdf', ['mes' => 6, 'anio' => 2026]))
            ->assertOk()->assertHeader('content-type', 'application/pdf');
    }
}
