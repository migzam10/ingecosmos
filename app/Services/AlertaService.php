<?php

namespace App\Services;

use App\Models\EntregaParcial;
use App\Models\Festivo;
use App\Models\OrdenTrabajo;
use Carbon\Carbon;

class AlertaService
{
    private array $festivos = [];

    public function __construct()
    {
        $this->festivos = Festivo::pluck('fecha')
            ->map(fn($f) => Carbon::parse($f)->format('Y-m-d'))
            ->toArray();
    }

    // Días hábiles entre dos fechas
    private function diasHabiles(Carbon $desde, Carbon $hasta): int
    {
        $dias  = 0;
        $fecha = $desde->copy()->addDay();
        while ($fecha->lte($hasta)) {
            if (!$fecha->isWeekend() && !in_array($fecha->format('Y-m-d'), $this->festivos)) {
                $dias++;
            }
            $fecha->addDay();
        }
        return $dias;
    }

    // Calcula y retorna todas las alertas activas agrupadas por tipo
    public function calcular(): array
    {
        $hoy = Carbon::today();

        $activas = OrdenTrabajo::with(['vehiculo.marca', 'empresaCliente'])
            ->whereNotIn('estado_proceso', [
                'ENTREGADO','NO_AUTORIZADO','ORDEN_ANULADA',
                'PERDIDA_TOTAL','VFT','GARANTIA','ARREGLO_DIRECTO',
            ])->get();

        $alertas = [
            'roja'    => [],
            'naranja' => [],
            'amarilla'=> [],
        ];

        foreach ($activas as $ot) {
            // ── OT INCUMPLIDA: salida estimada < hoy ──────────────────────
            if ($ot->salida_estimada && $ot->salida_estimada->lt($hoy)) {
                $diasVencida = $ot->salida_estimada->diffInDays($hoy);
                $alertas['roja'][] = [
                    'ot'      => $ot,
                    'tipo'    => 'incumplida',
                    'mensaje' => "Venció hace {$diasVencida} " . ($diasVencida === 1 ? 'día' : 'días'),
                ];
                continue; // Una OT incumplida no necesita más alertas
            }

            // ── ENTREGAR HOY ───────────────────────────────────────────────
            if ($ot->salida_estimada && $ot->salida_estimada->isToday()) {
                $alertas['amarilla'][] = [
                    'ot'      => $ot,
                    'tipo'    => 'entregar_hoy',
                    'mensaje' => 'Debe entregarse hoy',
                ];
            }

            // ── SIN COTIZACIÓN > 2 días hábiles desde ingreso ─────────────
            if ($ot->estado_proceso === 'PTE_COTIZACION') {
                $diasHab = $this->diasHabiles(
                    Carbon::parse($ot->fecha_ingreso), $hoy
                );
                if ($diasHab > 2) {
                    $alertas['amarilla'][] = [
                        'ot'      => $ot,
                        'tipo'    => 'sin_cotizacion',
                        'mensaje' => "Sin cotizar hace {$diasHab} días hábiles",
                    ];
                }
            }

            // ── ESPERANDO AUTORIZACIÓN > 5 días ───────────────────────────
            if ($ot->estado_proceso === 'PTE_AUTORIZACION' && $ot->fecha_cotizacion) {
                $dias = Carbon::parse($ot->fecha_cotizacion)->diffInDays($hoy);
                if ($dias > 5) {
                    $alertas['amarilla'][] = [
                        'ot'      => $ot,
                        'tipo'    => 'sin_autorizacion',
                        'mensaje' => "Esperando autorización hace {$dias} días",
                    ];
                }
            }

            // ── AUTORIZADA SIN INICIAR > 1 día ────────────────────────────
            if (in_array($ot->estado_proceso, ['PTE_ORDEN','RTO_INSTALADO']) && $ot->fecha_autorizacion) {
                $dias = Carbon::parse($ot->fecha_autorizacion)->diffInDays($hoy);
                if ($dias > 1) {
                    $alertas['naranja'][] = [
                        'ot'      => $ot,
                        'tipo'    => 'autorizada_sin_iniciar',
                        'mensaje' => "Autorizada hace {$dias} días sin iniciar proceso",
                    ];
                }
            }

            // ── REPUESTOS SIN LLEGAR > 10 días desde autorización ─────────
            if ($ot->estado_proceso === 'PTE_REPUESTOS' && $ot->fecha_autorizacion) {
                $dias = Carbon::parse($ot->fecha_autorizacion)->diffInDays($hoy);
                if ($dias > 10) {
                    $alertas['naranja'][] = [
                        'ot'      => $ot,
                        'tipo'    => 'repuestos_demorados',
                        'mensaje' => "Repuestos no llegan hace {$dias} días",
                    ];
                }
            }
        }

        // ── ENTREGAS PARCIALES SIN RESOLVER > 30 días ─────────────────────
        $parciales = EntregaParcial::with(['ot.vehiculo.marca', 'ot.empresaCliente'])
            ->whereNull('fecha_retorno')
            ->where('fecha_entrega', '<=', $hoy->copy()->subDays(30))
            ->get();

        foreach ($parciales as $ep) {
            $dias = Carbon::parse($ep->fecha_entrega)->diffInDays($hoy);
            $alertas['naranja'][] = [
                'ot'      => $ep->ot,
                'tipo'    => 'entrega_parcial',
                'mensaje' => "Entrega parcial sin retorno hace {$dias} días",
            ];
        }

        // Ordenar cada grupo por número de OT
        foreach ($alertas as &$grupo) {
            usort($grupo, fn($a, $b) => $a['ot']->numero_ot <=> $b['ot']->numero_ot);
        }

        return $alertas;
    }

    public function totalAlertas(array $alertas): int
    {
        return count($alertas['roja']) + count($alertas['naranja']) + count($alertas['amarilla']);
    }
}
