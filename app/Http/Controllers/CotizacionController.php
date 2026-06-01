<?php

namespace App\Http\Controllers;

use App\Models\Cotizacion;
use App\Models\OrdenTrabajo;
use App\Services\CotizacionService;
use App\Services\OTService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;

class CotizacionController extends Controller
{
    public function __construct(private CotizacionService $service, private OTService $otService) {}

    public function index(Request $request)
    {
        $query = Cotizacion::with(['ot.vehiculo.marca', 'ot.empresaCliente', 'creadaPor'])
            ->orderByDesc('created_at');

        if ($request->filled('buscar')) {
            $b = $request->buscar;
            $query->where('numero_cot', 'like', "%$b%")
                  ->orWhereHas('ot.vehiculo', fn($q) => $q->where('placa', 'like', "%$b%"));
        }

        $cotizaciones = $query->paginate(20)->withQueryString();

        return view('cotizaciones.index', compact('cotizaciones'));
    }

    public function create(OrdenTrabajo $orden)
    {
        // Solo se puede cotizar si está en PTE_COTIZACION
        if (!in_array($orden->estado_proceso, ['PTE_COTIZACION', 'PTE_AUTORIZACION'])) {
            return redirect()->route('ordenes.show', $orden)
                ->with('error', 'Esta OT no está en estado de cotización.');
        }

        $orden->load(['vehiculo.marca', 'vehiculo.modelo', 'empresaCliente', 'cotizaciones.itemsMo', 'cotizaciones.itemsSuministro']);

        return view('cotizaciones.crear', compact('orden'));
    }

    public function store(Request $request, OrdenTrabajo $orden)
    {
        $request->validate([
            'fecha_cotizacion'      => 'required|date|before_or_equal:today',
            'items_mo'              => 'nullable|array',
            'items_mo.*.descripcion'=> 'required_with:items_mo.*.precio|string|max:200',
            'items_mo.*.precio'     => 'required_with:items_mo.*.descripcion|numeric|min:0',
            'items_suministro'      => 'nullable|array',
            'subtotal_rto'          => 'nullable|numeric|min:0',
            'subtotal_terceros'     => 'nullable|numeric|min:0',
            'subtotal_op'           => 'nullable|numeric|min:0',
        ]);

        $cot = $this->service->crear($orden, $request->all());

        return redirect()->route('cotizaciones.show', $cot)
            ->with('success', "Cotización #{$cot->numero_cot} guardada. OT pasó a PTE_AUTORIZACION.");
    }

    public function show(Cotizacion $cotizacion)
    {
        $cotizacion->load(['ot.vehiculo.marca', 'ot.vehiculo.modelo', 'ot.clientePersona',
                           'ot.empresaCliente', 'itemsMo', 'itemsSuministro', 'creadaPor']);

        return view('cotizaciones.show', compact('cotizacion'));
    }

    public function edit(Cotizacion $cotizacion)
    {
        abort_if(
            $cotizacion->estado !== 'BORRADOR',
            403,
            'Solo se puede editar una cotización en estado Borrador.'
        );

        $cotizacion->load(['ot.vehiculo.marca', 'ot.vehiculo.modelo', 'ot.empresaCliente', 'itemsMo', 'itemsSuministro']);

        return view('cotizaciones.crear', ['orden' => $cotizacion->ot, 'cotizacion' => $cotizacion]);
    }

    public function update(Request $request, Cotizacion $cotizacion)
    {
        abort_if(
            $cotizacion->estado !== 'BORRADOR',
            403,
            'Solo se puede editar una cotización en estado Borrador.'
        );

        $request->validate([
            'fecha_cotizacion'       => 'required|date|before_or_equal:today',
            'items_mo'               => 'nullable|array',
            'items_mo.*.descripcion' => 'required_with:items_mo.*.precio|string|max:200',
            'items_mo.*.precio'      => 'required_with:items_mo.*.descripcion|numeric|min:0',
            'items_suministro'       => 'nullable|array',
            'subtotal_rto'           => 'nullable|numeric|min:0',
            'subtotal_terceros'      => 'nullable|numeric|min:0',
            'subtotal_op'            => 'nullable|numeric|min:0',
        ]);

        $cot = $this->service->actualizar($cotizacion, $request->all());

        return redirect()->route('cotizaciones.show', $cot)
            ->with('success', "Cotización #{$cot->numero_cot} actualizada.");
    }

    public function destroy(Cotizacion $cotizacion)
    {
        abort_if(
            $cotizacion->estado !== 'BORRADOR',
            403,
            'Solo se puede eliminar una cotización en estado Borrador.'
        );

        $ot = $cotizacion->ot;

        \Illuminate\Support\Facades\DB::transaction(function () use ($cotizacion, $ot) {
            $cotizacion->itemsMo()->delete();
            $cotizacion->itemsSuministro()->delete();
            $cotizacion->delete();

            // Si la OT no tiene más cotizaciones, vuelve a PTE_COTIZACION
            if (!$ot->cotizaciones()->exists()) {
                $ot->update([
                    'valor_mo' => 0, 'valor_rto' => 0, 'valor_insumos_pint' => 0,
                    'valor_terceros' => 0, 'valor_op' => 0, 'total' => 0,
                    'ha' => null, 'dr' => null, 'tg' => null, 'salida_estimada' => null,
                    'fecha_cotizacion' => null,
                ]);
                $this->otService->cambiarEstado($ot, 'PTE_COTIZACION', 'Cotización eliminada — OT regresa a Pte. Cotización');
            }
        });

        return redirect()->route('ordenes.show', $ot)
            ->with('success', 'Cotización eliminada. La OT regresó a Pendiente de Cotización.');
    }

    public function pdf(Cotizacion $cotizacion)
    {
        $cotizacion->load(['ot.vehiculo.marca', 'ot.vehiculo.modelo', 'ot.clientePersona',
                           'ot.empresaCliente', 'itemsMo', 'itemsSuministro', 'creadaPor']);

        $pdf = Pdf::loadView('cotizaciones.pdf', compact('cotizacion'))
            ->setPaper('letter', 'portrait');

        return $pdf->stream("cotizacion-{$cotizacion->numero_cot}.pdf");
    }
}
