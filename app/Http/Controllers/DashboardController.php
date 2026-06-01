<?php

namespace App\Http\Controllers;

use App\Models\OrdenTrabajo;
use App\Models\PagoTecnico;
use App\Models\TrabajoTecnico;
use App\Services\AlertaService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function __construct(private AlertaService $alertaService) {}

    public function index(Request $request)
    {
        $roles = Auth::user()->roles ?? [];
        $esSoloTecnico = !empty($roles) && array_diff($roles, ['TECNICO']) === [];

        if ($esSoloTecnico) {
            return $this->dashboardTecnico();
        }

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

    private function dashboardTecnico()
    {
        $tecnico = Auth::user()->tecnico;

        if (!$tecnico) {
            return view('dashboard.tecnico', ['tecnico' => null, 'kpis' => [], 'tareasActivas' => collect()]);
        }

        $kpis = [
            'en_proceso'       => TrabajoTecnico::where('id_tecnico', $tecnico->id)->where('estado', 'EN_PROCESO')->count(),
            'pendientes'       => TrabajoTecnico::where('id_tecnico', $tecnico->id)->where('estado', 'PENDIENTE')->count(),
            'fin_este_mes'     => TrabajoTecnico::where('id_tecnico', $tecnico->id)
                                    ->where('estado', 'FINALIZADO')
                                    ->whereMonth('fin_en', now()->month)
                                    ->whereYear('fin_en', now()->year)
                                    ->count(),
            'fin_total'        => TrabajoTecnico::where('id_tecnico', $tecnico->id)->where('estado', 'FINALIZADO')->count(),
        ];

        $tareasActivas = TrabajoTecnico::with(['ot.vehiculo.marca', 'ot.empresaCliente'])
            ->where('id_tecnico', $tecnico->id)
            ->whereIn('estado', ['PENDIENTE', 'EN_PROCESO'])
            ->orderByRaw("FIELD(estado,'EN_PROCESO','PENDIENTE')")
            ->orderBy('created_at')
            ->get();

        // Liquidación: lo ganado vs lo pagado (total histórico)
        $totalGanado  = TrabajoTecnico::where('id_tecnico', $tecnico->id)
            ->where('estado', 'FINALIZADO')
            ->sum('valor_liquidar');
        $totalPagado  = PagoTecnico::where('id_tecnico', $tecnico->id)->sum('monto');
        $saldoPendiente = $totalGanado - $totalPagado;

        $ultimosPagos = PagoTecnico::where('id_tecnico', $tecnico->id)
            ->orderByDesc('fecha_pago')
            ->orderByDesc('created_at')
            ->limit(5)
            ->get();

        return view('dashboard.tecnico', compact(
            'tecnico', 'kpis', 'tareasActivas',
            'totalGanado', 'totalPagado', 'saldoPendiente', 'ultimosPagos'
        ));
    }
}
