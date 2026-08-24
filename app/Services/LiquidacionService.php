<?php

namespace App\Services;

use App\Models\PagoTecnico;
use App\Models\Tecnico;
use App\Models\TrabajoTecnico;
use Illuminate\Support\Collection;

class LiquidacionService
{
    // Resumen de un técnico en un mes/año
    public function resumenTecnico(Tecnico $tecnico, int $mes, int $anio): array
    {
        // Trabajos del técnico en OTs activas/entregadas, del período indicado.
        // El período se define ÚNICAMENTE por la fecha de asignación del trabajo:
        // así un mismo trabajo nunca cae en dos meses (evita duplicar el pago).
        $trabajos = TrabajoTecnico::with(['ot.empresaCliente', 'ot.vehiculo.marca'])
            ->where('id_tecnico', $tecnico->id)
            ->whereHas('ot', function ($q) {
                $q->whereIn('estado_proceso', [
                    'EN_PROCESO', 'PROGRAMADO_ENTREGA', 'ENTREGADO',
                ]);
            })
            ->whereMonth('fecha_asignacion', $mes)
            ->whereYear('fecha_asignacion', $anio)
            ->get();

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
