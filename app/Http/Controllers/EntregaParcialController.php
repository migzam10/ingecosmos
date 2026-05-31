<?php

namespace App\Http\Controllers;

use App\Models\EntregaParcial;
use App\Models\OrdenTrabajo;
use App\Services\OTService;
use Illuminate\Http\Request;

class EntregaParcialController extends Controller
{
    public function __construct(private OTService $otService) {}

    public function store(Request $request, OrdenTrabajo $orden)
    {
        $request->validate([
            'fecha_entrega' => 'required|date|before_or_equal:today',
            'descripcion'   => 'required|string|max:500',
        ]);

        EntregaParcial::create([
            'id_ot'        => $orden->id,
            'fecha_entrega'=> $request->fecha_entrega,
            'descripcion'  => $request->descripcion,
        ]);

        $this->otService->cambiarEstado(
            $orden,
            'ENTREGA_PARCIAL',
            'Entrega parcial registrada: ' . $request->descripcion,
            $request->fecha_entrega
        );

        return back()->with('success', 'Entrega parcial registrada.');
    }

    public function retorno(Request $request, EntregaParcial $entregaParcial)
    {
        $request->validate([
            'fecha_retorno'  => 'required|date|after_or_equal:' . $entregaParcial->fecha_entrega . '|before_or_equal:today',
            'motivo_retorno' => 'required|string|max:500',
        ]);

        $entregaParcial->update([
            'fecha_retorno'  => $request->fecha_retorno,
            'motivo_retorno' => $request->motivo_retorno,
        ]);

        // Volver a EN_PROCESO al retornar
        $this->otService->cambiarEstado(
            $entregaParcial->ot,
            'EN_PROCESO',
            'Vehículo retornó de entrega parcial: ' . $request->motivo_retorno,
            $request->fecha_retorno
        );

        return back()->with('success', 'Retorno registrado. OT vuelve a En Proceso.');
    }
}
