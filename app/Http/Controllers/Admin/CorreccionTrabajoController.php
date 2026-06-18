<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\HistorialOt;
use App\Models\TrabajoTecnico;
use App\Services\OTService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CorreccionTrabajoController extends Controller
{
    public function __construct(private OTService $otService) {}

    // Deshacer FINALIZADO → EN_PROCESO (el técnico marcó fin por error)
    public function deshacerFinalizar(TrabajoTecnico $trabajo)
    {
        if ($trabajo->estado !== 'FINALIZADO') {
            return back()->with('error', 'Este trabajo no está finalizado.');
        }

        if ($trabajo->liquidado) {
            return back()->with('error', 'No se puede revertir: este trabajo ya fue liquidado.');
        }

        $ot = $trabajo->ot;

        if ($ot->estado_proceso === 'ENTREGADO') {
            return back()->with('error', 'No se puede revertir: la OT ya fue entregada al cliente.');
        }

        $trabajo->update([
            'estado' => 'EN_PROCESO',
            'fin_en' => null,
        ]);

        // Si la OT había avanzado a PROGRAMADO_ENTREGA por este trabajo, devolverla a EN_PROCESO
        if ($ot->estado_proceso === 'PROGRAMADO_ENTREGA') {
            $this->otService->cambiarEstado(
                $ot,
                'EN_PROCESO',
                'Reversa ADMIN: se deshizo la finalización del trabajo de ' . $trabajo->tecnico->nombre . ' (' . $trabajo->especialidad . ')',
                now()->toDateString()
            );
        } else {
            $this->registrarCorreccion($trabajo, 'Reversa ADMIN: finalización deshecha (' . $trabajo->tecnico->nombre . ' / ' . $trabajo->especialidad . '). El trabajo vuelve a EN PROCESO.');
        }

        return back()->with('success', 'Finalización revertida. El trabajo quedó EN PROCESO.');
    }

    // Deshacer inicio: EN_PROCESO / PAUSADO → PENDIENTE (se inició/eligió por error)
    public function deshacerInicio(TrabajoTecnico $trabajo)
    {
        if (!in_array($trabajo->estado, ['EN_PROCESO', 'PAUSADO'])) {
            return back()->with('error', 'Solo se puede deshacer el inicio de un trabajo en proceso o detenido.');
        }

        if ($trabajo->liquidado) {
            return back()->with('error', 'No se puede revertir: este trabajo ya fue liquidado.');
        }

        $estadoAnterior = $trabajo->estado;

        // Borra las pausas registradas (el inicio nunca debió ocurrir)
        $trabajo->pausas()->delete();

        $trabajo->update([
            'estado'    => 'PENDIENTE',
            'inicio_en' => null,
            'fin_en'    => null,
        ]);

        $this->registrarCorreccion($trabajo, 'Reversa ADMIN: inicio deshecho (' . $trabajo->tecnico->nombre . ' / ' . $trabajo->especialidad . '). El trabajo vuelve a PENDIENTE desde ' . $estadoAnterior . '.');

        return back()->with('success', 'Inicio revertido. El trabajo quedó PENDIENTE.');
    }

    // Corregir las fechas de inicio / fin (se equivocó de fecha)
    public function editarFechas(Request $request, TrabajoTecnico $trabajo)
    {
        if ($trabajo->estado === 'PENDIENTE') {
            return back()->with('error', 'Un trabajo pendiente no tiene fechas que corregir.');
        }

        if ($trabajo->liquidado) {
            return back()->with('error', 'No se puede corregir fechas: este trabajo ya fue liquidado.');
        }

        $esFinalizado = $trabajo->estado === 'FINALIZADO';

        $request->validate([
            'inicio_en' => 'required|date|before_or_equal:today',
            'fin_en'    => ($esFinalizado ? 'required' : 'nullable') . '|date|before_or_equal:today',
        ]);

        $inicio = Carbon::parse($request->inicio_en)->startOfDay();
        $fin    = $request->filled('fin_en') ? Carbon::parse($request->fin_en)->endOfDay() : null;

        if ($fin && $fin->lt($inicio)) {
            return back()->with('error', 'La fecha de fin no puede ser anterior al inicio.');
        }

        // Coherencia con las pausas existentes
        if ($trabajo->pausas->count() > 0) {
            $primeraDetencion = $trabajo->pausas->min('detenido_en');
            $ultimaReanuda    = $trabajo->pausas->filter(fn($p) => $p->retomado_en)->max('retomado_en');

            if ($primeraDetencion && $inicio->gt(Carbon::parse($primeraDetencion)->startOfDay())) {
                return back()->with('error', 'El inicio no puede ser posterior a la primera detención (' . Carbon::parse($primeraDetencion)->format('d/m/Y') . ').');
            }
            if ($fin && $ultimaReanuda && $fin->lt(Carbon::parse($ultimaReanuda)->startOfDay())) {
                return back()->with('error', 'El fin no puede ser anterior a la última reanudación (' . Carbon::parse($ultimaReanuda)->format('d/m/Y') . ').');
            }
        }

        $datos = ['inicio_en' => $inicio];
        if ($esFinalizado) {
            $datos['fin_en'] = $fin;
        }

        $trabajo->update($datos);

        $detalle = 'Inicio: ' . $inicio->format('d/m/Y');
        if ($esFinalizado) {
            $detalle .= ', Fin: ' . $fin->format('d/m/Y');
        }
        $this->registrarCorreccion($trabajo, 'Corrección ADMIN de fechas (' . $trabajo->tecnico->nombre . ' / ' . $trabajo->especialidad . '): ' . $detalle . '.');

        return back()->with('success', 'Fechas corregidas.');
    }

    // Deja constancia en historial_ot sin alterar el estado de la OT
    private function registrarCorreccion(TrabajoTecnico $trabajo, string $comentario): void
    {
        $ot = $trabajo->ot;

        HistorialOt::create([
            'id_ot'           => $ot->id,
            'id_user'         => Auth::id(),
            'estado_anterior' => $ot->estado_proceso,
            'estado_nuevo'    => $ot->estado_proceso,
            'comentario'      => $comentario,
            'fecha_evento'    => now()->toDateString(),
        ]);
    }
}
