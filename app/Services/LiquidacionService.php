<?php

namespace App\Services;

use App\Models\PagoTecnico;
use App\Models\Tecnico;
use App\Models\TrabajoTecnico;
use Illuminate\Support\Collection;

class LiquidacionService
{
    public const CORTE_MES_NUEVA_LOGICA = 9;
    public const CORTE_ANIO_NUEVA_LOGICA = 2026;

    // Resumen de un técnico en un mes/año
    public function resumenTecnico(Tecnico $tecnico, int $mes, int $anio): array
    {
        $esHistorico = ($anio < self::CORTE_ANIO_NUEVA_LOGICA)
            || ($anio === self::CORTE_ANIO_NUEVA_LOGICA && $mes < self::CORTE_MES_NUEVA_LOGICA);

        $trabajos = TrabajoTecnico::with(['ot.empresaCliente', 'ot.vehiculo.marca'])
            ->where('id_tecnico', $tecnico->id)
            ->whereHas('ot', function ($q) {
                $q->whereIn('estado_proceso', [
                    'EN_PROCESO', 'PROGRAMADO_ENTREGA', 'ENTREGADO',
                ]);
            })
            ->where(function($q) use ($mes, $anio, $esHistorico) {
                // Tareas LIQUIDADAS (Preservan la historia)
                $q->where(function($q2) use ($mes, $anio, $esHistorico) {
                    $q2->where('liquidado', true);
                    if ($esHistorico) {
                        $q2->whereMonth('fecha_asignacion', $mes)
                           ->whereYear('fecha_asignacion', $anio);
                    } else {
                        $q2->where('mes_cobro', $mes)
                           ->where('anio_cobro', $anio);
                    }
                });

                // Tareas PENDIENTES (Siguen siempre al mes_cobro, sin importar si es mes histórico)
                $q->orWhere(function($q2) use ($mes, $anio) {
                    $q2->where('liquidado', false)
                       ->where(function($q3) use ($mes, $anio) {
                           $q3->where(function($q4) use ($mes, $anio) {
                               $q4->whereNotNull('mes_cobro')
                                  ->where('mes_cobro', $mes)
                                  ->where('anio_cobro', $anio);
                           })->orWhere(function($q4) use ($mes, $anio) {
                               $q4->whereNull('mes_cobro')
                                  ->whereMonth('fecha_asignacion', $mes)
                                  ->whereYear('fecha_asignacion', $anio);
                           });
                       });
                });
            })
            ->orderByRaw('COALESCE(fin_en, fecha_asignacion) DESC')
            ->get();

        if ($esHistorico) {
            $totalGanado = $trabajos->sum('valor_liquidar');
        } else {
            // En la lógica nueva, los trabajos en proceso se muestran pero no suman al total a pagar hasta que se terminen
            $totalGanado = $trabajos->filter(fn($t) => $t->fin_en !== null || $t->estado === 'FINALIZADO' || $t->liquidado)->sum('valor_liquidar');
        }

        // Avances ya pagados en este mes
        $avances = PagoTecnico::where('id_tecnico', $tecnico->id)
            ->where('anio', $anio)
            ->where('mes', $mes)
            ->get();

        $totalAvances = $avances->sum('monto');

        // Deducciones del mes por concepto (se restan del valor de liquidación).
        $deducciones = [];
        foreach (PagoTecnico::DEDUCCIONES as $col => $label) {
            $deducciones[$col] = (float) $avances->sum($col);
        }
        $totalDeducciones = array_sum($deducciones);

        // Las deducciones salen del pago: reducen el neto entregado, NO el saldo.
        // Saldo = lo devengado menos lo pagado (bruto). Neto = pago - deducciones.
        $saldo = $totalGanado - $totalAvances;
        $totalNeto = $totalAvances - $totalDeducciones;

        return [
            'tecnico'            => $tecnico,
            'trabajos'           => $trabajos,
            'avances'            => $avances,
            'total_ganado'       => $totalGanado,
            'total_avances'      => $totalAvances,
            'deducciones'        => $deducciones,       // [columna => suma]
            'total_deducciones'  => $totalDeducciones,
            'total_neto'         => $totalNeto,         // efectivo entregado al técnico
            'saldo'              => $saldo,
            'mes'                => $mes,
            'anio'               => $anio,
        ];
    }

    // Lista de todos los técnicos activos con su resumen del mes
    public function resumenMensual(int $mes, int $anio): Collection
    {
        return Tecnico::where('activo', true)
            ->orderBy('nombre')
            ->get()
            ->map(fn($tec) => $this->resumenTecnico($tec, $mes, $anio));
    }
}
