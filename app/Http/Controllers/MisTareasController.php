<?php

namespace App\Http\Controllers;

use App\Models\OrdenTrabajo;
use App\Models\TrabajoTecnico;
use App\Services\OTService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class MisTareasController extends Controller
{
    public function __construct(private OTService $otService) {}

    public function index()
    {
        $tecnico = Auth::user()->tecnico;

        if (!$tecnico) {
            return view('mis-tareas.index', ['trabajos' => collect(), 'tecnico' => null]);
        }

        $trabajos = TrabajoTecnico::with(['ot.vehiculo.marca', 'ot.vehiculo.modelo', 'ot.empresaCliente'])
            ->where('id_tecnico', $tecnico->id)
            ->whereIn('estado', ['PENDIENTE', 'EN_PROCESO'])
            ->orderByRaw("FIELD(estado,'EN_PROCESO','PENDIENTE')")
            ->orderBy('created_at')
            ->get();

        // Finalizados del mes actual para referencia
        $finalizados = TrabajoTecnico::with(['ot.vehiculo.marca'])
            ->where('id_tecnico', $tecnico->id)
            ->where('estado', 'FINALIZADO')
            ->whereMonth('fin_en', now()->month)
            ->whereYear('fin_en', now()->year)
            ->orderByDesc('fin_en')
            ->limit(10)
            ->get();

        return view('mis-tareas.index', compact('trabajos', 'finalizados', 'tecnico'));
    }

    public function iniciar(TrabajoTecnico $trabajo)
    {
        $this->autorizarTecnico($trabajo);

        if ($trabajo->estado !== 'PENDIENTE') {
            return back()->with('error', 'Esta tarea ya fue iniciada.');
        }

        $trabajo->update([
            'estado'    => 'EN_PROCESO',
            'inicio_en' => now(),
        ]);

        // Cambiar OT a EN_PROCESO si aún no lo está
        $ot = $trabajo->ot;
        if ($ot->estado_proceso === 'RTO_INSTALADO' || $ot->estado_proceso === 'PTE_REPUESTOS') {
            $this->otService->cambiarEstado($ot, 'EN_PROCESO', 'Técnico inició el trabajo');
        }

        return back()->with('success', 'Trabajo iniciado.');
    }

    public function comentar(Request $request, TrabajoTecnico $trabajo)
    {
        $this->autorizarTecnico($trabajo);

        $request->validate([
            'comentario' => 'required|string|max:500',
        ]);

        $trabajo->update([
            'comentarios' => $request->comentario,
        ]);

        return back()->with('success', 'Comentario guardado.');
    }

    public function finalizar(Request $request, TrabajoTecnico $trabajo)
    {
        $this->autorizarTecnico($trabajo);

        if ($trabajo->estado !== 'EN_PROCESO') {
            return back()->with('error', 'Debes iniciar el trabajo antes de finalizarlo.');
        }

        $trabajo->update([
            'estado' => 'FINALIZADO',
            'fin_en' => now(),
        ]);

        // Automatismo: si todos los técnicos de la OT finalizaron → PROGRAMADO_ENTREGA
        $this->verificarFinOT($trabajo->ot);

        return back()->with('success', 'Trabajo finalizado correctamente.');
    }

    // Verifica si todos los trabajos de la OT están FINALIZADOS
    private function verificarFinOT(OrdenTrabajo $ot): void
    {
        $ot->load('trabajosTecnico');

        $total      = $ot->trabajosTecnico->count();
        $finalizados = $ot->trabajosTecnico->where('estado', 'FINALIZADO')->count();

        if ($total > 0 && $total === $finalizados) {
            $this->otService->cambiarEstado(
                $ot,
                'PROGRAMADO_ENTREGA',
                'Todos los técnicos finalizaron el trabajo'
            );
        }
    }

    private function autorizarTecnico(TrabajoTecnico $trabajo): void
    {
        $tecnico = Auth::user()->tecnico;

        abort_unless(
            $tecnico && $trabajo->id_tecnico === $tecnico->id,
            403,
            'No tienes permiso para esta acción.'
        );
    }
}
