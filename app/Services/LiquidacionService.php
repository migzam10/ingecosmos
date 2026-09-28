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

        if ($esHistorico) {
            // Períodos históricos cerrados (anteriores a septiembre 2026):
            // Se calcula por fecha de asignación para preservar exactamente los pagos y saldos ya registrados.
            $trabajos = TrabajoTecnico::with(['ot.empresaCliente', 'ot.vehiculo.marca'])
                ->where('id_tecnico', $tecnico->id)
                ->whereHas('ot', function ($q) {
                    $q->whereIn('estado_proceso', [
                        'EN_PROCESO', 'PROGRAMADO_ENTREGA', 'ENTREGADO',
                    ]);
                })
                ->whereMonth('fecha_asignacion', $mes)
                ->whereYear('fecha_asignacion', $anio)
                ->orderBy('fecha_asignacion', 'DESC')
                ->get();
        } else {
            // Período activo y futuro (desde septiembre 2026 en adelante):
            // Rige la fecha en que el técnico finalizó la tarea (fin_en).
            // Se excluyen trabajos ya liquidados en períodos anteriores.
            $trabajos = TrabajoTecnico::with(['ot.empresaCliente', 'ot.vehiculo.marca'])
                ->where('id_tecnico', $tecnico->id)
                ->whereHas('ot', function ($q) {
                    $q->whereIn('estado_proceso', [
                        'EN_PROCESO', 'PROGRAMADO_ENTREGA', 'ENTREGADO',
                    ]);
                })
                ->where(function ($q) use ($mes, $anio) {
                    $q->where(function ($q2) use ($mes, $anio) {
                        $q2->whereNotNull('fin_en')
                           ->whereMonth('fin_en', $mes)
                           ->whereYear('fin_en', $anio);
                    })->orWhere(function ($q2) use ($mes, $anio) {
                        $q2->whereNull('fin_en')
                           ->whereMonth('fecha_asignacion', $mes)
                           ->whereYear('fecha_asignacion', $anio);
                    });
                })
                ->orderByRaw('COALESCE(fin_en, fecha_asignacion) DESC')
                ->get();
        }

        $totalGanado = $trabajos->sum('valor_liquidar');

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
