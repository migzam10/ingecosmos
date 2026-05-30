<?php

namespace App\Http\Controllers;

use App\Models\Cotizacion;
use App\Models\OrdenTrabajo;
use App\Services\CotizacionService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;

class CotizacionController extends Controller
{
    public function __construct(private CotizacionService $service) {}

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
            'items_mo'              => 'nullable|array',
            'items_mo.*.descripcion'=> 'required_with:items_mo.*.precio|string|max:200',
            'items_mo.*.precio'     => 'required_with:items_mo.*.descripcion|numeric|min:0',
            'items_suministro'      => 'nullable|array',
            'subtotal_rto'          => 'nullable|numeric|min:0',
            'subtotal_terceros'     => 'nullable|numeric|min:0',
            'subtotal_op'           => 'nullable|numeric|min:0',
        ]);

        $cot = $this->service->crear($orden, $request->all());

        return redirect()->route('cotizaciones.pdf', $cot)
            ->with('success', "Cotización #{$cot->numero_cot} guardada. OT pasó a PTE_AUTORIZACION.");
    }

    public function show(Cotizacion $cotizacion)
    {
        $cotizacion->load(['ot.vehiculo.marca', 'ot.vehiculo.modelo', 'ot.clientePersona',
                           'ot.empresaCliente', 'itemsMo', 'itemsSuministro', 'creadaPor']);

        return view('cotizaciones.show', compact('cotizacion'));
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
