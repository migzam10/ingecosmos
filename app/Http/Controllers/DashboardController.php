<?php

namespace App\Http\Controllers;

use App\Models\OrdenTrabajo;
use App\Services\AlertaService;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __construct(private AlertaService $alertaService) {}

    public function index(Request $request)
    {
        $kpis = [
            'total_activas'      => OrdenTrabajo::whereNotIn('estado_proceso', [
                'ENTREGADO', 'NO_AUTORIZADO', 'ORDEN_ANULADA', 'PERDIDA_TOTAL', 'VFT',
            ])->count(),
            'incumplidas'        => OrdenTrabajo::where('estado_semaforo', 'INCUMPLIDO')->count(),
            'entregar_hoy'       => OrdenTrabajo::where('estado_semaforo', 'ENTREGAR_HOY')->count(),
            'pte_cotizacion'     => OrdenTrabajo::where('estado_proceso', 'PTE_COTIZACION')->count(),
            'pte_autorizacion'   => OrdenTrabajo::where('estado_proceso', 'PTE_AUTORIZACION')->count(),
            'en_proceso'         => OrdenTrabajo::where('estado_proceso', 'EN_PROCESO')->count(),
            'programado_entrega' => OrdenTrabajo::where('estado_proceso', 'PROGRAMADO_ENTREGA')->count(),
        ];

        $alertas      = $this->alertaService->calcular();
        $totalAlertas = $this->alertaService->totalAlertas($alertas);

        return view('dashboard.index', compact('kpis', 'alertas', 'totalAlertas'));
    }
}
