<?php

namespace Tests\Feature;

use App\Models\CatalogoInsumo;
use App\Models\Cotizacion;
use App\Models\EmpresaCliente;
use App\Models\ItemCotizacionInsumo;
use App\Models\MarcaVehiculo;
use App\Models\OrdenTrabajo;
use App\Models\User;
use App\Models\Vehiculo;
use App\Services\AlmacenService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

/**
 * Salida de almacén por OT: agrega los insumos pendientes de TODAS las
 * cotizaciones AUTORIZADAS (una salida puede cubrir varias, o hacerse por
 * partes). Las cotizaciones NO autorizadas (BORRADOR) no interfieren.
 */
class SalidaAlmacenOTTest extends TestCase
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

    private function crearOT(): OrdenTrabajo
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
            'estado_proceso'     => 'EN_PROCESO',
            'estado_semaforo'    => 'A_TIEMPO',
            'fecha_ingreso'      => now()->subDays(3)->toDateString(),
            'creado_por'         => User::value('id'),
        ]);
    }

    private function insumo(float $stock): CatalogoInsumo
    {
        return CatalogoInsumo::create([
            'nombre'        => 'Insumo ' . random_int(1000, 9999),
            'unidad_medida' => 'und',
            'precio_venta'  => 1000,
            'stock_minimo'  => 0,
            'stock_actual'  => $stock,
            'activo'        => true,
        ]);
    }

    private function cotizacionConInsumo(OrdenTrabajo $ot, string $estado, CatalogoInsumo $ins, float $cant): Cotizacion
    {
        $cot = Cotizacion::create([
            'numero_cot'       => 800000 + random_int(1, 99999),
            'id_ot'            => $ot->id,
            'creada_por'       => User::value('id'),
            'estado'           => $estado,
            'fecha_cotizacion' => now()->toDateString(),
            'subtotal_insumos' => 1000 * $cant,
            'total'            => 1000 * $cant,
        ]);

        ItemCotizacionInsumo::create([
            'id_cotizacion'       => $cot->id,
            'id_insumo'           => $ins->id,
            'descripcion'         => $ins->nombre,
            'cantidad_solicitada' => $cant,
            'precio_venta'        => 1000,
            'precio_total'        => 1000 * $cant,
        ]);

        return $cot;
    }

    /** getPendientesOT ignora la BORRADOR y suma las AUTORIZADAS. */
    public function test_pendientes_ot_solo_autorizadas(): void
    {
        $ot   = $this->crearOT();
        $insA = $this->insumo(50);
        $insB = $this->insumo(50);
        $insBorr = $this->insumo(50);

        $this->cotizacionConInsumo($ot, 'AUTORIZADA', $insA, 4);
        $this->cotizacionConInsumo($ot, 'AUTORIZADA', $insB, 2);
        $this->cotizacionConInsumo($ot, 'BORRADOR',   $insBorr, 9);

        $pend = app(AlmacenService::class)->getPendientesOT($ot);

        $this->assertCount(2, $pend, 'Solo los insumos de las 2 cotizaciones autorizadas');
        $ids = $pend->pluck('id_insumo')->all();
        $this->assertContains($insA->id, $ids);
        $this->assertContains($insB->id, $ids);
        $this->assertNotContains($insBorr->id, $ids, 'La BORRADOR no debe aparecer');
    }

    /** La vista de crear salida lista los insumos de las autorizadas y no la borrador. */
    public function test_create_view_lista_autorizadas_y_oculta_borrador(): void
    {
        $ot   = $this->crearOT();
        $insA = $this->insumo(50);
        $insBorr = $this->insumo(50);
        $cotAut  = $this->cotizacionConInsumo($ot, 'AUTORIZADA', $insA, 4);
        $cotBorr = $this->cotizacionConInsumo($ot, 'BORRADOR',   $insBorr, 9);

        $resp = $this->actingAs($this->admin())
            ->get(route('almacen.salidas.create', ['tipo' => 'COTIZACION', 'numero_ot' => $ot->numero_ot]));

        $resp->assertOk();
        $resp->assertSee('COT #' . $cotAut->numero_cot);
        $resp->assertDontSee('COT #' . $cotBorr->numero_cot);
        $resp->assertSee('name="id_ot"', false);
    }

    /** Una salida cubre varias cotizaciones; entrega parcial deja el resto pendiente. */
    public function test_salida_cubre_varias_cotizaciones_y_parcial(): void
    {
        $ot   = $this->crearOT();
        $insA = $this->insumo(50);
        $insB = $this->insumo(50);
        $this->cotizacionConInsumo($ot, 'AUTORIZADA', $insA, 4);
        $this->cotizacionConInsumo($ot, 'AUTORIZADA', $insB, 2);

        $svc  = app(AlmacenService::class);
        $pend = $svc->getPendientesOT($ot);
        $items = $pend->map(fn($it) => [
            'id_insumo'                 => $it->id_insumo,
            'id_item_cotizacion_insumo' => $it->id,
            'descripcion'               => $it->descripcion,
            'cantidad'                  => 1, // entrega 1 de cada (parcial)
            'precio_venta'              => 1000,
        ])->all();

        $resp = $this->actingAs($this->admin())->post(route('almacen.salidas.store'), [
            'tipo'        => 'COTIZACION',
            'id_ot'       => $ot->id,
            'entregado_a' => 'Taller',
            'fecha'       => now()->toDateString(),
            'items'       => $items,
        ]);
        $resp->assertSessionHasNoErrors();

        // Stock decrementado en 1 cada uno.
        $this->assertEquals(49, (float) $insA->fresh()->stock_actual);
        $this->assertEquals(49, (float) $insB->fresh()->stock_actual);

        // Quedan pendientes: A=3, B=1.
        $pend2 = $svc->getPendientesOT($ot->fresh())->keyBy('id_insumo');
        $this->assertEquals(3, (float) $pend2[$insA->id]->cantidad_pendiente);
        $this->assertEquals(1, (float) $pend2[$insB->id]->cantidad_pendiente);
    }
}
