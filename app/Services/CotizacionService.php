<?php

namespace App\Services;

use App\Models\Cotizacion;
use App\Models\HistorialOt;
use App\Models\ItemCotizacionInsumo;
use App\Models\ItemCotizacionMo;
use App\Models\ItemCotizacionRepuesto;
use App\Models\OrdenTrabajo;
use App\Models\Secuencia;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class CotizacionService
{
    public function __construct(private OTService $otService) {}

    public function sugerirNumeroCot(): int
    {
        $maxCot = Cotizacion::max('numero_cot') ?? 0;
        $secCot = Secuencia::where('tipo', 'COTIZACION')->value('ultimo_numero') ?? 0;
        return max($maxCot, $secCot) + 1;
    }

    public function siguiente(?int $numeroManual = null): int
    {
        return DB::transaction(function () use ($numeroManual) {
            $sec    = Secuencia::lockForUpdate()->where('tipo', 'COTIZACION')->first();
            $numero = $numeroManual ?? (max(Cotizacion::max('numero_cot') ?? 0, $sec->ultimo_numero) + 1);
            if ($numero > $sec->ultimo_numero) {
                $sec->update(['ultimo_numero' => $numero]);
            }
            return $numero;
        });
    }

    // ── CREAR cotización anclada a una OT ─────────────────────────────────────
    public function crear(OrdenTrabajo $ot, array $data): Cotizacion
    {
        return DB::transaction(function () use ($ot, $data) {
            $numeroCot = $this->siguiente(!empty($data['numero_cot']) ? (int) $data['numero_cot'] : null);

            [$subtotalMo, $subtotalRto, $subtotalInsumos, $descVal, $ivaVal, $total] = $this->calcularTotales($data);
            $descPct = $this->calcularDescPct($subtotalMo, $subtotalRto, $subtotalInsumos, $descVal);
            $ivaPct  = $this->calcularIvaPct($subtotalMo, $subtotalRto, $subtotalInsumos, $descVal, $ivaVal);

            $cot = Cotizacion::create([
                'numero_cot'          => $numeroCot,
                'id_ot'               => $ot->id,
                'creada_por'          => Auth::id(),
                'estado'              => 'BORRADOR',
                'fecha_cotizacion'    => $data['fecha_cotizacion'] ?? now()->toDateString(),
                'subtotal_mo'         => $subtotalMo,
                'subtotal_rto'        => $subtotalRto,
                'subtotal_insumos'    => $subtotalInsumos,
                'descuento_porcentaje'=> $descPct,
                'descuento_valor'     => $descVal,
                'iva_porcentaje'      => $ivaPct,
                'iva_valor'           => $ivaVal,
                'total'               => $total,
                'observaciones'       => $data['observaciones'] ?? null,
                'subtotal_suministros'=> 0,
                'subtotal_terceros'   => 0,
                'subtotal_op'         => 0,
            ]);

            $this->guardarItems($cot, $data);
            $this->recalcularOTDesdeCotizaciones($ot);

            $fechaCot = $data['fecha_cotizacion'] ?? now()->toDateString();

            if (in_array($ot->estado_proceso, ['PTE_COTIZACION', 'PTE_AUTORIZACION'])) {
                // Flujo normal: la(s) primera(s) cotización(es) llevan la OT a autorización.
                $this->otService->cambiarEstado(
                    $ot,
                    'PTE_AUTORIZACION',
                    "Cotización #{$numeroCot} creada por " . Auth::user()->name,
                    $fechaCot
                );
            } else {
                // Cotización ADICIONAL sobre una OT ya avanzada: NO se regresa el
                // flujo a autorización; solo se deja constancia en el historial.
                // La autoriza aparte el coordinador/administrador.
                HistorialOt::create([
                    'id_ot'           => $ot->id,
                    'id_user'         => Auth::id(),
                    'estado_anterior' => $ot->estado_proceso,
                    'estado_nuevo'    => $ot->estado_proceso,
                    'comentario'      => "Cotización adicional #{$numeroCot} creada por " . Auth::user()->name,
                    'fecha_evento'    => $fechaCot,
                ]);
            }

            return $cot;
        });
    }

    // ── CREAR cotización previa (sin OT) ──────────────────────────────────────
    public function crearPrevia(array $data): Cotizacion
    {
        return DB::transaction(function () use ($data) {
            $numeroCot = $this->siguiente(!empty($data['numero_cot']) ? (int) $data['numero_cot'] : null);

            [$subtotalMo, $subtotalRto, $subtotalInsumos, $descVal, $ivaVal, $total] = $this->calcularTotales($data);
            $descPct   = $this->calcularDescPct($subtotalMo, $subtotalRto, $subtotalInsumos, $descVal);
            $ivaPct    = $this->calcularIvaPct($subtotalMo, $subtotalRto, $subtotalInsumos, $descVal, $ivaVal);
            $idCliente = $this->buscarOCrearCliente($data);

            $cot = Cotizacion::create([
                'numero_cot'          => $numeroCot,
                'id_ot'               => null,
                'creada_por'          => Auth::id(),
                'estado'              => 'BORRADOR',
                'fecha_cotizacion'    => $data['fecha_cotizacion'] ?? now()->toDateString(),
                'es_previa'           => true,
                'placa_previa'        => strtoupper(trim($data['placa_previa'])),
                'km_previa'           => $data['km_previa'] ?? null,
                'id_cliente_previa'   => $idCliente,
                'descripcion_previa'  => $data['descripcion_previa'] ?? null,
                'id_marca_previa'     => $data['id_marca_previa']    ?? null,
                'id_modelo_previa'    => $data['id_modelo_previa']   ?? null,
                'subtotal_mo'         => $subtotalMo,
                'subtotal_rto'        => $subtotalRto,
                'subtotal_insumos'    => $subtotalInsumos,
                'descuento_porcentaje'=> $descPct,
                'descuento_valor'     => $descVal,
                'iva_porcentaje'      => $ivaPct,
                'iva_valor'           => $ivaVal,
                'total'               => $total,
                'observaciones'       => $data['observaciones'] ?? null,
                'subtotal_suministros'=> 0,
                'subtotal_terceros'   => 0,
                'subtotal_op'         => 0,
            ]);

            $this->guardarItems($cot, $data);

            return $cot;
        });
    }

    // ── VINCULAR previa a OT ──────────────────────────────────────────────────
    public function vincularOT(Cotizacion $cot, OrdenTrabajo $ot): void
    {
        DB::transaction(function () use ($cot, $ot) {
            $cot->update(['id_ot' => $ot->id, 'es_previa' => false]);

            $this->recalcularOTDesdeCotizaciones($ot);

            $this->otService->cambiarEstado(
                $ot,
                'PTE_AUTORIZACION',
                "Cotización previa #{$cot->numero_cot} vinculada por " . Auth::user()->name
            );
        });
    }

    // ── ACTUALIZAR cotización existente ───────────────────────────────────────
    public function actualizar(Cotizacion $cot, array $data): Cotizacion
    {
        return DB::transaction(function () use ($cot, $data) {
            [$subtotalMo, $subtotalRto, $subtotalInsumos, $descVal, $ivaVal, $total] = $this->calcularTotales($data);
            $descPct = $this->calcularDescPct($subtotalMo, $subtotalRto, $subtotalInsumos, $descVal);
            $ivaPct  = $this->calcularIvaPct($subtotalMo, $subtotalRto, $subtotalInsumos, $descVal, $ivaVal);

            $cot->itemsMo()->delete();
            $cot->itemsRepuesto()->delete();
            $this->guardarItems($cot, $data, skipInsumos: true);
            $this->upsertItemsInsumo($cot, $data);

            $updateCot = [
                'subtotal_mo'          => $subtotalMo,
                'subtotal_rto'         => $subtotalRto,
                'subtotal_insumos'     => $subtotalInsumos,
                'descuento_porcentaje' => $descPct,
                'descuento_valor'      => $descVal,
                'iva_porcentaje'       => $ivaPct,
                'iva_valor'            => $ivaVal,
                'total'                => $total,
                'observaciones'        => $data['observaciones'] ?? $cot->observaciones,
            ];

            if (!empty($data['fecha_cotizacion'])) {
                $updateCot['fecha_cotizacion'] = $data['fecha_cotizacion'];
            }

            // En una previa los datos del vehículo/cliente viven en la propia
            // cotización, así que también deben guardarse al editarla.
            if ($cot->es_previa) {
                if (!empty($data['placa_previa'])) {
                    $updateCot['placa_previa'] = strtoupper(trim($data['placa_previa']));
                }
                $updateCot['km_previa']          = $data['km_previa']          ?? null;
                $updateCot['id_marca_previa']    = $data['id_marca_previa']    ?? null;
                $updateCot['id_modelo_previa']   = $data['id_modelo_previa']   ?? null;
                $updateCot['descripcion_previa'] = $data['descripcion_previa'] ?? null;

                if (!empty($data['nombre_cliente'])) {
                    $updateCot['id_cliente_previa'] = $this->actualizarClientePrevia($cot, $data);
                }
            }

            if (!empty($data['numero_cot'])) {
                $nuevoCot = (int) $data['numero_cot'];
                $updateCot['numero_cot'] = $nuevoCot;
                $sec = Secuencia::where('tipo', 'COTIZACION')->first();
                if ($sec && $nuevoCot > $sec->ultimo_numero) {
                    $sec->update(['ultimo_numero' => $nuevoCot]);
                }
            }

            $cot->update($updateCot);

            if ($cot->id_ot) {
                $this->recalcularOTDesdeCotizaciones($cot->ot);
            }

            return $cot;
        });
    }

    // ── HELPERS PRIVADOS ──────────────────────────────────────────────────────

    /** Returns [$subtotalMo, $subtotalRto, $subtotalInsumos, $descVal, $ivaVal, $total] */
    private function calcularTotales(array $data): array
    {
        $subtotalMo      = collect($data['items_mo']       ?? [])->sum('precio');
        $subtotalRto     = collect($data['items_repuesto'] ?? [])->sum('precio_total');
        $subtotalInsumos = collect($data['items_insumo']   ?? [])->sum('precio_total');

        $subtotalNeto = $subtotalMo + $subtotalRto + $subtotalInsumos;

        // Descuento comercial: se aplica ANTES del IVA. No puede superar el neto.
        $descVal = isset($data['descuento_valor']) && $data['descuento_valor'] !== ''
            ? max(0, (float) $data['descuento_valor'])
            : 0;
        $descVal = min($descVal, $subtotalNeto);

        $baseIva = $subtotalNeto - $descVal;

        $ivaVal = isset($data['iva_valor']) && $data['iva_valor'] !== ''
            ? max(0, (float) $data['iva_valor'])
            : round($baseIva * 0.19);

        $total = max($baseIva, $baseIva + $ivaVal);

        return [(float)$subtotalMo, (float)$subtotalRto, (float)$subtotalInsumos, (float)$descVal, (float)$ivaVal, (float)$total];
    }

    /** % de descuento respecto al subtotal neto (solo informativo). */
    private function calcularDescPct(float $mo, float $rto, float $ins, float $descVal): float
    {
        $base = $mo + $rto + $ins;
        return $base > 0 ? round($descVal / $base * 100, 2) : 0;
    }

    private function calcularIvaPct(float $mo, float $rto, float $ins, float $descVal, float $ivaVal): float
    {
        $base = $mo + $rto + $ins - $descVal;
        return $base > 0 ? round($ivaVal / $base * 100, 2) : 19;
    }

    private function guardarItems(Cotizacion $cot, array $data, bool $skipInsumos = false): void
    {
        foreach ($data['items_mo'] ?? [] as $item) {
            if (empty($item['descripcion']) || ($item['precio'] ?? 0) <= 0) continue;
            ItemCotizacionMo::create([
                'id_cotizacion'  => $cot->id,
                'id_catalogo_mo' => $item['id_catalogo_mo'] ?? null,
                'descripcion'    => $item['descripcion'],
                'precio'         => (float) $item['precio'],
            ]);
        }

        foreach ($data['items_repuesto'] ?? [] as $item) {
            if (empty($item['descripcion'])) continue;
            $unidades = max(0.01, (float)($item['unidades']        ?? 1));
            $unitario = max(0,    (float)($item['precio_unitario'] ?? 0));
            $pTotal   = (float)($item['precio_total'] ?? round($unidades * $unitario));
            if ($pTotal <= 0) continue;
            ItemCotizacionRepuesto::create([
                'id_cotizacion'        => $cot->id,
                'id_catalogo_repuesto' => $item['id_catalogo_repuesto'] ?? null,
                'descripcion'          => $item['descripcion'],
                'unidades'             => $unidades,
                'precio_unitario'      => $unitario,
                'precio_total'         => $pTotal,
            ]);
        }

        if (!$skipInsumos) {
            foreach ($data['items_insumo'] ?? [] as $item) {
                if (empty($item['descripcion'])) continue;
                $cantidad = max(0.01, (float)($item['cantidad']     ?? 1));
                $pventa   = max(0,    (float)($item['precio_venta'] ?? 0));
                $pTotal   = (float)($item['precio_total'] ?? round($cantidad * $pventa));
                if ($cantidad <= 0) continue;
                ItemCotizacionInsumo::create([
                    'id_cotizacion'       => $cot->id,
                    'id_insumo'           => $item['id_insumo'] ?? null,
                    'descripcion'         => $item['descripcion'],
                    'cantidad_solicitada' => $cantidad,
                    'precio_venta'        => $pventa,
                    'precio_total'        => $pTotal,
                ]);
            }
        }
    }

    /**
     * Actualiza insumos de cotización sin borrar filas existentes,
     * preservando los FK en items_salida_almacen ya registrados.
     */
    private function upsertItemsInsumo(Cotizacion $cot, array $data): void
    {
        $nuevos = collect($data['items_insumo'] ?? [])
            ->filter(fn($it) => !empty($it['descripcion']) && (float)($it['cantidad'] ?? 0) > 0);

        $idInsumosNuevos = $nuevos->pluck('id_insumo')->filter()->unique()->values()->toArray();

        // Eliminar filas que ya no vienen (por id_insumo) o que son manuales (sin id_insumo)
        $cot->itemsInsumo()->whereNotNull('id_insumo')
            ->whereNotIn('id_insumo', $idInsumosNuevos)->delete();
        $cot->itemsInsumo()->whereNull('id_insumo')->delete();

        // Recargar existentes tras la limpieza
        $existentes = $cot->itemsInsumo()->get()->keyBy('id_insumo');

        foreach ($nuevos as $item) {
            $idInsumo = isset($item['id_insumo']) ? (int) $item['id_insumo'] : null;
            $cantidad  = max(0.01, (float)($item['cantidad']     ?? 1));
            $pventa    = max(0,    (float)($item['precio_venta'] ?? 0));
            $pTotal    = (float)($item['precio_total'] ?? round($cantidad * $pventa));

            if ($idInsumo && $existentes->has($idInsumo)) {
                // UPDATE en lugar de delete+insert — preserva el id y el FK en salidas
                $existentes[$idInsumo]->update([
                    'descripcion'         => $item['descripcion'],
                    'cantidad_solicitada' => $cantidad,
                    'precio_venta'        => $pventa,
                    'precio_total'        => $pTotal,
                ]);
            } else {
                ItemCotizacionInsumo::create([
                    'id_cotizacion'       => $cot->id,
                    'id_insumo'           => $idInsumo,
                    'descripcion'         => $item['descripcion'],
                    'cantidad_solicitada' => $cantidad,
                    'precio_venta'        => $pventa,
                    'precio_total'        => $pTotal,
                ]);
            }
        }
    }

    /**
     * Recalcula los valores de la OT SUMANDO todas sus cotizaciones vigentes
     * (BORRADOR + AUTORIZADA; se excluyen las RECHAZADA). Así una cotización
     * adicional se agrega al valor de la reparación en vez de reemplazarlo, y
     * HA/DR/TG y la salida estimada se recalculan sobre el total acumulado.
     */
    public function recalcularOTDesdeCotizaciones(OrdenTrabajo $ot): void
    {
        $cots = $ot->cotizaciones()
            ->whereIn('estado', ['BORRADOR', 'AUTORIZADA'])
            ->get();

        $subtotalMo      = (float) $cots->sum('subtotal_mo');
        $subtotalRto     = (float) $cots->sum('subtotal_rto');
        $subtotalInsumos = (float) $cots->sum('subtotal_insumos');
        $descuentoTotal  = (float) $cots->sum('descuento_valor');

        $empresa = $ot->empresaCliente;

        // HA/DR/TG se calculan sobre el valor BRUTO (horas de trabajo, no precio):
        // el descuento comercial no cambia el tiempo estimado de reparación.
        $baseHA = $subtotalMo + ($empresa->tipo === 'A' ? $subtotalRto : 0) + $subtotalInsumos;

        $ha = $empresa->tarifa_hora > 0 ? round($baseHA / $empresa->tarifa_hora, 2) : 0;
        $dr = $ha > 0 ? (int) ceil($ha / 8 * 1.5) : null;
        $tg = $dr ? $this->otService->calcularTG($dr) : null;

        $salida = null;
        if ($dr && $ot->fecha_inicio_proceso) {
            $salida = $this->otService->workday(Carbon::parse($ot->fecha_inicio_proceso), $dr);
        }

        // El total facturado SÍ descuenta (sin IVA, que vive solo en la cotización).
        $totalOT = max(0, $baseHA - $descuentoTotal);

        $ot->update([
            'valor_mo'           => $subtotalMo,
            'valor_rto'          => $subtotalRto,
            'valor_insumos_pint' => $subtotalInsumos,
            'valor_terceros'     => 0,
            'valor_op'           => 0,
            'total'              => $totalOT,
            'ha'                 => $ha,
            'dr'                 => $dr,
            'tg'                 => $tg,
            'salida_estimada'    => $salida,
            'fecha_cotizacion'   => $cots->min('fecha_cotizacion') ?? $ot->fecha_cotizacion,
        ]);
    }

    /**
     * Al editar una previa: actualiza el cliente ya ligado, o busca/crea uno
     * nuevo si la cotización todavía no tenía cliente.
     */
    private function actualizarClientePrevia(Cotizacion $cot, array $data): ?int
    {
        $cliente = $cot->clientePrevia;

        if (!$cliente) {
            return $this->buscarOCrearCliente($data);
        }

        $cliente->update([
            'nombre'   => trim($data['nombre_cliente']),
            'cedula'   => $data['cedula_cliente']   ?? $cliente->cedula,
            'telefono' => $data['telefono_cliente'] ?? $cliente->telefono,
            'email'    => $data['email_cliente']    ?? $cliente->email,
        ]);

        return $cliente->id;
    }

    private function buscarOCrearCliente(array $data): ?int
    {
        if (empty($data['nombre_cliente'])) return null;

        if (!empty($data['cedula_cliente'])) {
            $cliente = \App\Models\ClientePersona::where('cedula', $data['cedula_cliente'])->first();
            if ($cliente) return $cliente->id;
        }

        return \App\Models\ClientePersona::create([
            'nombre'   => trim($data['nombre_cliente']),
            'cedula'   => $data['cedula_cliente']  ?? null,
            'telefono' => $data['telefono_cliente'] ?? null,
            'email'    => $data['email_cliente']    ?? null,
        ])->id;
    }
}
