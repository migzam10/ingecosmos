<?php

namespace App\Services;

use App\Models\Cotizacion;
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
            $sec = Secuencia::lockForUpdate()->where('tipo', 'COTIZACION')->first();

            $numero = $numeroManual ?? (max(Cotizacion::max('numero_cot') ?? 0, $sec->ultimo_numero) + 1);

            if ($numero > $sec->ultimo_numero) {
                $sec->update(['ultimo_numero' => $numero]);
            }

            return $numero;
        });
    }

    // ── CREAR cotización anclada a una OT ──────────────────────────────────────
    public function crear(OrdenTrabajo $ot, array $data): Cotizacion
    {
        return DB::transaction(function () use ($ot, $data) {
            $numeroCot = $this->siguiente($data['numero_cot'] ? (int) $data['numero_cot'] : null);

            [$subtotalMo, $subtotalRto, $ivaVal, $total] = $this->calcularTotales($data);

            $ivaPct = ($subtotalMo + $subtotalRto) > 0
                ? round($ivaVal / ($subtotalMo + $subtotalRto) * 100, 2)
                : 19;

            $cot = Cotizacion::create([
                'numero_cot'     => $numeroCot,
                'id_ot'          => $ot->id,
                'creada_por'     => Auth::id(),
                'estado'         => 'BORRADOR',
                'subtotal_mo'    => $subtotalMo,
                'subtotal_rto'   => $subtotalRto,
                'iva_porcentaje' => $ivaPct,
                'iva_valor'      => $ivaVal,
                'total'          => $total,
                'observaciones'  => $data['observaciones'] ?? null,
                // Campos legacy — se mantienen en 0 para historial
                'subtotal_suministros' => 0,
                'subtotal_terceros'    => 0,
                'subtotal_op'          => 0,
            ]);

            $this->guardarItems($cot, $data);
            $this->actualizarOT($ot, $subtotalMo, $subtotalRto, $data);

            $fechaCot = $data['fecha_cotizacion'] ?? now()->toDateString();

            $this->otService->cambiarEstado(
                $ot,
                'PTE_AUTORIZACION',
                "Cotización #{$numeroCot} creada por " . Auth::user()->name,
                $fechaCot
            );

            return $cot;
        });
    }

    // ── CREAR cotización previa (sin OT) ──────────────────────────────────────
    public function crearPrevia(array $data): Cotizacion
    {
        return DB::transaction(function () use ($data) {
            $numeroCot = $this->siguiente($data['numero_cot'] ? (int) $data['numero_cot'] : null);

            [$subtotalMo, $subtotalRto, $ivaVal, $total] = $this->calcularTotales($data);

            $ivaPct = ($subtotalMo + $subtotalRto) > 0
                ? round($ivaVal / ($subtotalMo + $subtotalRto) * 100, 2)
                : 19;

            // Buscar o crear cliente
            $idCliente = $this->buscarOCrearCliente($data);

            $cot = Cotizacion::create([
                'numero_cot'          => $numeroCot,
                'id_ot'               => null,
                'creada_por'          => Auth::id(),
                'estado'              => 'BORRADOR',
                'es_previa'           => true,
                'placa_previa'        => strtoupper(trim($data['placa_previa'])),
                'id_cliente_previa'   => $idCliente,
                'descripcion_previa'  => $data['descripcion_previa'] ?? null,
                'id_marca_previa'     => $data['id_marca_previa'] ?? null,
                'id_modelo_previa'    => $data['id_modelo_previa'] ?? null,
                'subtotal_mo'         => $subtotalMo,
                'subtotal_rto'        => $subtotalRto,
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

    // ── VINCULAR previa a una OT ───────────────────────────────────────────────
    public function vincularOT(Cotizacion $cot, OrdenTrabajo $ot): void
    {
        DB::transaction(function () use ($cot, $ot) {
            $cot->update(['id_ot' => $ot->id, 'es_previa' => false]);

            $this->actualizarOT(
                $ot,
                (float) $cot->subtotal_mo,
                (float) $cot->subtotal_rto,
                ['fecha_cotizacion' => now()->toDateString()]
            );

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
            [$subtotalMo, $subtotalRto, $ivaVal, $total] = $this->calcularTotales($data);

            $ivaPct = ($subtotalMo + $subtotalRto) > 0
                ? round($ivaVal / ($subtotalMo + $subtotalRto) * 100, 2)
                : 19;

            $cot->itemsMo()->delete();
            $cot->itemsRepuesto()->delete();

            $this->guardarItems($cot, $data);

            $updateCot = [
                'subtotal_mo'    => $subtotalMo,
                'subtotal_rto'   => $subtotalRto,
                'iva_porcentaje' => $ivaPct,
                'iva_valor'      => $ivaVal,
                'total'          => $total,
            ];

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
                $this->actualizarOT($cot->ot, $subtotalMo, $subtotalRto, $data);
            }

            return $cot;
        });
    }

    // ── HELPERS PRIVADOS ──────────────────────────────────────────────────────

    private function calcularTotales(array $data): array
    {
        $subtotalMo  = collect($data['items_mo']       ?? [])->sum('precio');
        $subtotalRto = collect($data['items_repuesto']  ?? [])->sum('precio_total');

        $subtotalNeto = $subtotalMo + $subtotalRto;

        // IVA: si se pasa manual lo respetamos, si no calculamos 19%
        $ivaVal = isset($data['iva_valor']) && $data['iva_valor'] !== ''
            ? max(0, (float) $data['iva_valor'])
            : round($subtotalNeto * 0.19);

        // Total nunca puede ser menor al subtotal neto
        $total = max($subtotalNeto, $subtotalNeto + $ivaVal);

        return [$subtotalMo, $subtotalRto, $ivaVal, $total];
    }

    private function guardarItems(Cotizacion $cot, array $data): void
    {
        foreach ($data['items_mo'] ?? [] as $item) {
            if (empty($item['descripcion']) || ($item['precio'] ?? 0) <= 0) continue;
            ItemCotizacionMo::create([
                'id_cotizacion'  => $cot->id,
                'id_catalogo_mo' => $item['id_catalogo_mo'] ?? null,
                'descripcion'    => $item['descripcion'],
                'precio'         => $item['precio'],
            ]);
        }

        foreach ($data['items_repuesto'] ?? [] as $item) {
            if (empty($item['descripcion'])) continue;
            $unidades  = max(0.01, (float) ($item['unidades']        ?? 1));
            $unitario  = max(0,    (float) ($item['precio_unitario'] ?? 0));
            $pTotal    = round($unidades * $unitario);
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
    }

    private function actualizarOT(OrdenTrabajo $ot, float $subtotalMo, float $subtotalRto, array $data): void
    {
        $empresa = $ot->empresaCliente;

        $totalParaHA = $subtotalMo + ($empresa->tipo === 'A' ? $subtotalRto : 0);

        $ha = $empresa->tarifa_hora > 0
            ? round($totalParaHA / $empresa->tarifa_hora, 2)
            : 0;
        $dr = $ha > 0 ? (int) ceil($ha / 8 * 1.5) : null;
        $tg = $dr ? $this->otService->calcularTG($dr) : null;

        $salida = null;
        if ($dr && $ot->fecha_inicio_proceso) {
            $salida = $this->otService->workday(Carbon::parse($ot->fecha_inicio_proceso), $dr);
        }

        $totalOT = $subtotalMo + ($empresa->tipo === 'A' ? $subtotalRto : 0);

        $update = [
            'valor_mo'           => $subtotalMo,
            'valor_rto'          => $subtotalRto,
            'valor_insumos_pint' => 0,
            'valor_terceros'     => 0,
            'valor_op'           => 0,
            'total'              => $totalOT,
            'ha'                 => $ha,
            'dr'                 => $dr,
            'tg'                 => $tg,
            'salida_estimada'    => $salida,
        ];

        if (!empty($data['fecha_cotizacion'])) {
            $update['fecha_cotizacion'] = $data['fecha_cotizacion'];
        }

        $ot->update($update);
    }

    private function buscarOCrearCliente(array $data): ?int
    {
        if (empty($data['nombre_cliente'])) return null;

        // Buscar por cédula si la tienen
        if (!empty($data['cedula_cliente'])) {
            $cliente = \App\Models\ClientePersona::where('cedula', $data['cedula_cliente'])->first();
            if ($cliente) return $cliente->id;
        }

        $cliente = \App\Models\ClientePersona::create([
            'nombre'   => trim($data['nombre_cliente']),
            'cedula'   => $data['cedula_cliente']   ?? null,
            'telefono' => $data['telefono_cliente']  ?? null,
            'email'    => $data['email_cliente']     ?? null,
        ]);

        return $cliente->id;
    }
}
