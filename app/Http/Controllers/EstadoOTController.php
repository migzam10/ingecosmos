<?php

namespace App\Http\Controllers;

use App\Models\OrdenTrabajo;
use App\Services\OTService;
use Carbon\Carbon;
use Illuminate\Http\Request;

class EstadoOTController extends Controller
{
    public function __construct(private OTService $otService) {}

    // PTE_AUTORIZACION → PTE_ORDEN (CIA autorizó)
    public function autorizar(Request $request, OrdenTrabajo $orden)
    {
        $request->validate([
            'fecha_autorizacion' => 'required|date|before_or_equal:today',
            'comentario'         => 'nullable|string|max:300',
        ]);

        abort_if($orden->estado_proceso !== 'PTE_AUTORIZACION', 422, 'Estado incorrecto.');

        $orden->update(['fecha_autorizacion' => $request->fecha_autorizacion]);

        $this->otService->cambiarEstado(
            $orden,
            'PTE_ORDEN',
            $request->comentario ?? 'CIA autorizó la reparación'
        );

        return back()->with('success', 'Autorización registrada. OT pasa a PTE_ORDEN.');
    }

    // PTE_ORDEN → PTE_REPUESTOS (orden de repuestos enviada)
    public function ordenRepuestos(Request $request, OrdenTrabajo $orden)
    {
        $request->validate(['comentario' => 'nullable|string|max:300']);

        abort_if($orden->estado_proceso !== 'PTE_ORDEN', 422, 'Estado incorrecto.');

        $this->otService->cambiarEstado(
            $orden,
            'PTE_REPUESTOS',
            $request->comentario ?? 'Orden de repuestos enviada'
        );

        return back()->with('success', 'OT pasa a PTE_REPUESTOS.');
    }

    // PTE_REPUESTOS → RTO_INSTALADO (llegaron los repuestos)
    public function repuestosLlegaron(Request $request, OrdenTrabajo $orden)
    {
        $request->validate([
            'fecha_llegada_ultimo_rto' => 'required|date|before_or_equal:today',
            'comentario'               => 'nullable|string|max:300',
        ]);

        abort_if($orden->estado_proceso !== 'PTE_REPUESTOS', 422, 'Estado incorrecto.');

        $orden->update(['fecha_llegada_ultimo_rto' => $request->fecha_llegada_ultimo_rto]);

        $this->otService->cambiarEstado(
            $orden,
            'RTO_INSTALADO',
            $request->comentario ?? 'Repuestos llegaron e instalados'
        );

        return back()->with('success', 'Repuestos registrados. OT pasa a RTO_INSTALADO.');
    }

    // RTO_INSTALADO → EN_PROCESO (inicia la reparación)
    public function iniciarProceso(Request $request, OrdenTrabajo $orden)
    {
        $request->validate([
            'fecha_inicio_proceso' => 'required|date|before_or_equal:today',
            'comentario'           => 'nullable|string|max:300',
        ]);

        abort_if($orden->estado_proceso !== 'RTO_INSTALADO', 422, 'Estado incorrecto.');

        $orden->update(['fecha_inicio_proceso' => $request->fecha_inicio_proceso]);

        // Con fecha_inicio_proceso ya podemos calcular salida_estimada
        $this->otService->recalcularCampos($orden->fresh());

        $this->otService->cambiarEstado(
            $orden->fresh(),
            'EN_PROCESO',
            $request->comentario ?? 'Proceso de reparación iniciado'
        );

        return back()->with('success', 'Proceso iniciado. Salida estimada calculada.');
    }

    // EN_PROCESO → PROGRAMADO_ENTREGA
    public function programarEntrega(Request $request, OrdenTrabajo $orden)
    {
        $request->validate(['comentario' => 'nullable|string|max:300']);

        abort_if($orden->estado_proceso !== 'EN_PROCESO', 422, 'Estado incorrecto.');

        $orden->update(['fecha_terminacion' => now()->toDateString()]);

        $this->otService->cambiarEstado(
            $orden,
            'PROGRAMADO_ENTREGA',
            $request->comentario ?? 'Reparación terminada — programado para entrega'
        );

        return back()->with('success', 'OT programada para entrega.');
    }

    // PROGRAMADO_ENTREGA → ENTREGADO
    public function entregar(Request $request, OrdenTrabajo $orden)
    {
        $request->validate([
            'fecha_entrega_cliente' => 'required|date|before_or_equal:today',
            'comentario'            => 'nullable|string|max:300',
        ]);

        abort_if($orden->estado_proceso !== 'PROGRAMADO_ENTREGA', 422, 'Estado incorrecto.');

        // OPORTUNO: se mide contra fecha_terminacion_proceso (igual que el Excel)
        $fechaTerm = $orden->fecha_terminacion ?? Carbon::parse($request->fecha_entrega_cliente);
        $oportuno  = Carbon::parse($fechaTerm)
            ->lte($orden->salida_estimada ?? Carbon::parse($fechaTerm));

        $orden->update([
            'fecha_entrega_cliente' => $request->fecha_entrega_cliente,
            'pasado_a_facturar'     => false,
        ]);

        $this->otService->cambiarEstado(
            $orden,
            'ENTREGADO',
            ($request->comentario ?? 'Vehículo entregado al cliente')
            . ($oportuno ? ' — OPORTUNO' : ' — TARDÍO')
        );

        return back()->with('success', 'OT marcada como ENTREGADO.' . ($oportuno ? ' Entrega oportuna.' : ' Entrega tardía.'));
    }

    // Salidas especiales: NO_AUTORIZADO, ORDEN_ANULADA, PERDIDA_TOTAL, VFT, GARANTIA, ARREGLO_DIRECTO
    public function estadoEspecial(Request $request, OrdenTrabajo $orden)
    {
        $especiales = [
            'NO_AUTORIZADO', 'ORDEN_ANULADA', 'PERDIDA_TOTAL',
            'VFT', 'GARANTIA', 'ARREGLO_DIRECTO', 'EN_OTRO_TALLER', 'PTE_RETIRO',
        ];

        $request->validate([
            'nuevo_estado' => 'required|in:' . implode(',', $especiales),
            'comentario'   => 'required|string|max:500',
        ]);

        $this->otService->cambiarEstado(
            $orden,
            $request->nuevo_estado,
            $request->comentario
        );

        return back()->with('success', "OT marcada como {$request->nuevo_estado}.");
    }
}
