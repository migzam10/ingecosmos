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
        // OTs del mes donde el técnico tiene trabajo FINALIZADO o EN_PROCESO
        $trabajos = TrabajoTecnico::with(['ot.empresaCliente', 'ot.vehiculo.marca'])
            ->where('id_tecnico', $tecnico->id)
            ->whereHas('ot', function ($q) {
                $q->whereIn('estado_proceso', [
                    'EN_PROCESO', 'PROGRAMADO_ENTREGA', 'ENTREGADO',
                ]);
            })
            ->where(function ($q) use ($mes, $anio) {
                // OTs que estuvieron activas en este mes
                $q->whereMonth('created_at', $mes)->whereYear('created_at', $anio)
                  ->orWhere(function ($q2) use ($mes, $anio) {
                      $q2->whereHas('ot', function ($q3) use ($mes, $anio) {
                          $q3->whereMonth('fecha_inicio_proceso', $mes)
                             ->whereYear('fecha_inicio_proceso', $anio);
                      });
                  });
            })
            ->get();

        $totalGanado = $trabajos->sum('valor_liquidar');

        // Avances ya pagados en este mes
        $avances = PagoTecnico::where('id_tecnico', $tecnico->id)
            ->where('anio', $anio)
            ->where('mes', $mes)
            ->get();

        $totalAvances = $avances->sum('monto');
        $saldo        = $totalGanado - $totalAvances;

        return [
            'tecnico'      => $tecnico,
            'trabajos'     => $trabajos,
            'avances'      => $avances,
            'total_ganado' => $totalGanado,
            'total_avances'=> $totalAvances,
            'saldo'        => $saldo,
            'mes'          => $mes,
            'anio'         => $anio,
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
