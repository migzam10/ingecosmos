<?php

namespace App\Services;

use App\Models\Cotizacion;
use App\Models\ItemCotizacionMo;
use App\Models\ItemCotizacionSuministro;
use App\Models\OrdenTrabajo;
use App\Models\Secuencia;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class CotizacionService
{
    public function __construct(private OTService $otService) {}

    public function siguiente(): int
    {
        return DB::transaction(function () {
            $sec = Secuencia::lockForUpdate()->where('tipo', 'COTIZACION')->first();
            $sec->increment('ultimo_numero');
            return $sec->ultimo_numero;
        });
    }

    public function crear(OrdenTrabajo $ot, array $data): Cotizacion
    {
        return DB::transaction(function () use ($ot, $data) {

            $numeroCot = $this->siguiente();

            // Totales
            $subtotalMo  = collect($data['items_mo'] ?? [])->sum('precio');
            $subtotalSum = collect($data['items_suministro'] ?? [])->sum('precio');

            $total = $subtotalMo + $subtotalSum
                   + ($data['subtotal_rto']      ?? 0)
                   + ($data['subtotal_terceros']  ?? 0)
                   + ($data['subtotal_op']        ?? 0);

            $cot = Cotizacion::create([
                'numero_cot'            => $numeroCot,
                'id_ot'                 => $ot->id,
                'creada_por'            => Auth::id(),
                'estado'                => 'BORRADOR',
                'subtotal_mo'           => $subtotalMo,
                'subtotal_suministros'  => $subtotalSum,
                'subtotal_rto'          => $data['subtotal_rto']      ?? 0,
                'subtotal_terceros'     => $data['subtotal_terceros']  ?? 0,
                'subtotal_op'           => $data['subtotal_op']        ?? 0,
                'total'                 => $total,
                'observaciones'         => $data['observaciones']      ?? null,
            ]);

            // Items MO
            foreach ($data['items_mo'] ?? [] as $item) {
                if (empty($item['descripcion']) || ($item['precio'] ?? 0) <= 0) continue;
                ItemCotizacionMo::create([
                    'id_cotizacion'  => $cot->id,
                    'id_catalogo_mo' => $item['id_catalogo_mo'] ?? null,
                    'descripcion'    => $item['descripcion'],
                    'precio'         => $item['precio'],
                ]);
            }

            // Items Suministros
            foreach ($data['items_suministro'] ?? [] as $item) {
                if (empty($item['descripcion'])) continue;
                $costo  = (float) ($item['costo']  ?? 0);
                $precio = (float) ($item['precio'] ?? round($costo * 1.25));
                if ($precio <= 0) continue;
                ItemCotizacionSuministro::create([
                    'id_cotizacion' => $cot->id,
                    'descripcion'   => $item['descripcion'],
                    'costo'         => $costo,
                    'precio'        => $precio,
                ]);
            }

            // Actualizar OT con los valores de la cotización
            $empresa = $ot->empresaCliente;

            // HA = TOTAL / tarifa_hora (igual que el Excel — usa el total facturado)
            $totalParaHA = $subtotalMo + $subtotalSum
                         + ($data['subtotal_terceros'] ?? 0)
                         + ($data['subtotal_op']       ?? 0);
            if ($empresa->tipo === 'A') {
                $totalParaHA += ($data['subtotal_rto'] ?? 0);
            }
            $ha = $empresa->tarifa_hora > 0
                ? round($totalParaHA / $empresa->tarifa_hora, 2)
                : 0;
            $dr = $ha > 0 ? (int) ceil($ha / 8 * 1.5) : null;
            $tg = $dr ? $this->otService->calcularTG($dr) : null;

            $salida = null;
            if ($dr && $ot->fecha_inicio_proceso) {
                $salida = $this->otService->workday(
                    Carbon::parse($ot->fecha_inicio_proceso), $dr
                );
            }

            $totalOT = $subtotalMo + $subtotalSum
                     + ($data['subtotal_terceros'] ?? 0)
                     + ($data['subtotal_op']       ?? 0);

            if ($empresa->tipo === 'A') {
                $totalOT += ($data['subtotal_rto'] ?? 0);
            }

            $ot->update([
                'valor_mo'           => $subtotalMo,
                'valor_insumos_pint' => $subtotalSum,
                'valor_rto'          => $data['subtotal_rto']     ?? 0,
                'valor_terceros'     => $data['subtotal_terceros'] ?? 0,
                'valor_op'           => $data['subtotal_op']       ?? 0,
                'total'              => $totalOT,
                'ha'                 => $ha,
                'dr'                 => $dr,
                'tg'                 => $tg,
                'salida_estimada'    => $salida,
                'fecha_cotizacion'   => now()->toDateString(),
            ]);

            // Cambiar estado OT a PTE_AUTORIZACION
            $this->otService->cambiarEstado(
                $ot,
                'PTE_AUTORIZACION',
                "Cotización #{$numeroCot} creada por " . Auth::user()->name
            );

            return $cot;
        });
    }

    public function actualizar(Cotizacion $cot, array $data): Cotizacion
    {
        return DB::transaction(function () use ($cot, $data) {
            $subtotalMo  = collect($data['items_mo'] ?? [])->sum('precio');
            $subtotalSum = collect($data['items_suministro'] ?? [])->sum('precio');
            $total = $subtotalMo + $subtotalSum
                   + ($data['subtotal_rto']     ?? 0)
                   + ($data['subtotal_terceros'] ?? 0)
                   + ($data['subtotal_op']       ?? 0);

            // Reemplazar ítems
            $cot->itemsMo()->delete();
            $cot->itemsSuministro()->delete();

            foreach ($data['items_mo'] ?? [] as $item) {
                if (empty($item['descripcion']) || ($item['precio'] ?? 0) <= 0) continue;
                \App\Models\ItemCotizacionMo::create([
                    'id_cotizacion'  => $cot->id,
                    'id_catalogo_mo' => $item['id_catalogo_mo'] ?? null,
                    'descripcion'    => $item['descripcion'],
                    'precio'         => $item['precio'],
                ]);
            }

            foreach ($data['items_suministro'] ?? [] as $item) {
                if (empty($item['descripcion'])) continue;
                $costo  = (float) ($item['costo']  ?? 0);
                $precio = (float) ($item['precio'] ?? round($costo * 1.25));
                if ($precio <= 0) continue;
                \App\Models\ItemCotizacionSuministro::create([
                    'id_cotizacion' => $cot->id,
                    'descripcion'   => $item['descripcion'],
                    'costo'         => $costo,
                    'precio'        => $precio,
                ]);
            }

            $cot->update([
                'subtotal_mo'          => $subtotalMo,
                'subtotal_suministros' => $subtotalSum,
                'subtotal_rto'         => $data['subtotal_rto']     ?? 0,
                'subtotal_terceros'    => $data['subtotal_terceros'] ?? 0,
                'subtotal_op'          => $data['subtotal_op']       ?? 0,
                'total'                => $total,
            ]);

            // Recalcular OT
            $ot      = $cot->ot;
            $empresa = $ot->empresaCliente;
            $totalHA = $subtotalMo + $subtotalSum + ($data['subtotal_terceros'] ?? 0) + ($data['subtotal_op'] ?? 0);
            if ($empresa->tipo === 'A') $totalHA += ($data['subtotal_rto'] ?? 0);
            $ha = $empresa->tarifa_hora > 0 ? round($totalHA / $empresa->tarifa_hora, 2) : 0;
            $dr = $ha > 0 ? (int) ceil($ha / 8 * 1.5) : null;
            $tg = $dr ? $this->otService->calcularTG($dr) : null;
            $salida = ($dr && $ot->fecha_inicio_proceso)
                ? $this->otService->workday(\Carbon\Carbon::parse($ot->fecha_inicio_proceso), $dr)
                : null;
            $totalOT = $subtotalMo + $subtotalSum + ($data['subtotal_terceros'] ?? 0) + ($data['subtotal_op'] ?? 0);
            if ($empresa->tipo === 'A') $totalOT += ($data['subtotal_rto'] ?? 0);

            $ot->update([
                'valor_mo'           => $subtotalMo,
                'valor_insumos_pint' => $subtotalSum,
                'valor_rto'          => $data['subtotal_rto']     ?? 0,
                'valor_terceros'     => $data['subtotal_terceros'] ?? 0,
                'valor_op'           => $data['subtotal_op']       ?? 0,
                'total'              => $totalOT,
                'ha'                 => $ha,
                'dr'                 => $dr,
                'tg'                 => $tg,
                'salida_estimada'    => $salida,
            ]);

            return $cot;
        });
    }
}
