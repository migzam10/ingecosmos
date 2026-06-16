<?php

namespace App\Services;

use App\Models\Festivo;
use App\Models\HistorialOt;
use App\Models\OrdenTrabajo;
use App\Models\Secuencia;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class OTService
{
    // Suma N días hábiles (sin sábados, domingos ni festivos Colombia)
    public function workday(Carbon $desde, int $dias): Carbon
    {
        $festivos = Festivo::pluck('fecha')
            ->map(fn($f) => Carbon::parse($f)->format('Y-m-d'))
            ->toArray();

        $fecha   = $desde->copy();
        $sumados = 0;

        while ($sumados < $dias) {
            $fecha->addDay();
            if (!$fecha->isWeekend() && !in_array($fecha->format('Y-m-d'), $festivos)) {
                $sumados++;
            }
        }

        return $fecha;
    }

    public function calcularHA(float $totalOt, float $tarifaHora): float
    {
        return $tarifaHora > 0 ? round($totalOt / $tarifaHora, 2) : 0;
    }

    public function calcularDR(float $ha): int
    {
        return (int) ceil($ha / 8 * 1.5);
    }

    public function calcularTG(int $dr): string
    {
        return match(true) {
            $dr <= 5  => 'Leve',
            $dr <= 10 => 'Medio',
            default   => 'Fuerte',
        };
    }

    public function calcularSemaforo(OrdenTrabajo $ot): string
    {
        $terminados = [
            'ENTREGADO', 'NO_AUTORIZADO', 'ORDEN_ANULADA',
            'PERDIDA_TOTAL', 'VFT', 'GARANTIA', 'ARREGLO_DIRECTO',
        ];

        if (in_array($ot->estado_proceso, $terminados)) {
            return 'OK';
        }

        if (is_null($ot->salida_estimada)) {
            return 'SIN_FECHA';
        }

        $hoy = Carbon::today();

        if ($ot->salida_estimada->lt($hoy)) {
            return 'INCUMPLIDO';
        }

        if ($ot->salida_estimada->isToday()) {
            return 'ENTREGAR_HOY';
        }

        return 'A_TIEMPO';
    }

    public function calcularTotal(OrdenTrabajo $ot): float
    {
        $total = $ot->valor_mo + $ot->valor_insumos_pint + $ot->valor_terceros + $ot->valor_op;

        if ($ot->empresaCliente->tipo === 'A') {
            $total += $ot->valor_rto;
        }

        return $total;
    }

    public function sugerirNumeroOT(): int
    {
        $maxOT  = OrdenTrabajo::max('numero_ot') ?? 0;
        $secOT  = Secuencia::where('tipo', 'OT')->value('ultimo_numero') ?? 0;
        return max($maxOT, $secOT) + 1;
    }

    public function siguienteNumeroOT(?int $numeroManual = null): int
    {
        return DB::transaction(function () use ($numeroManual) {
            $secuencia = Secuencia::lockForUpdate()->where('tipo', 'OT')->first();

            $numero = $numeroManual ?? (max(OrdenTrabajo::max('numero_ot') ?? 0, $secuencia->ultimo_numero) + 1);

            if ($numero > $secuencia->ultimo_numero) {
                $secuencia->update(['ultimo_numero' => $numero]);
            }

            return $numero;
        });
    }

    public function cambiarEstado(OrdenTrabajo $ot, string $nuevoEstado, ?string $comentario = null, ?string $fechaEvento = null): void
    {
        $estadoAnterior = $ot->estado_proceso;

        $ot->update(['estado_proceso' => $nuevoEstado]);

        HistorialOt::create([
            'id_ot'           => $ot->id,
            'id_user'         => Auth::id(),
            'estado_anterior' => $estadoAnterior,
            'estado_nuevo'    => $nuevoEstado,
            'comentario'      => $comentario,
            'fecha_evento'    => $fechaEvento,
        ]);

        // Recalcular semáforo tras cada cambio de estado
        $semaforo = $this->calcularSemaforo($ot->fresh());
        $ot->update(['estado_semaforo' => $semaforo]);
    }

    public function recalcularCampos(OrdenTrabajo $ot): void
    {
        $empresa = $ot->empresaCliente;

        if ($ot->valor_mo > 0 && $empresa->tarifa_hora > 0) {
            $ha = $this->calcularHA($ot->valor_mo, $empresa->tarifa_hora);
            $dr = $this->calcularDR($ha);
            $tg = $this->calcularTG($dr);

            $salida = null;
            if ($ot->fecha_inicio_proceso) {
                $salida = $this->workday(Carbon::parse($ot->fecha_inicio_proceso), $dr);
            }

            $ot->update([
                'ha'              => $ha,
                'dr'              => $dr,
                'tg'              => $tg,
                'salida_estimada' => $salida,
                'total'           => $this->calcularTotal($ot),
            ]);
        }

        $semaforo = $this->calcularSemaforo($ot->fresh());
        $ot->update(['estado_semaforo' => $semaforo]);
    }
}
