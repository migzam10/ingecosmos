<?php

namespace App\Http\Controllers;

use App\Models\CatalogoInsumo;
use App\Models\Cotizacion;
use App\Models\ItemCotizacionInsumo;
use App\Models\OrdenTrabajo;
use App\Models\SalidaAlmacen;
use App\Services\AlmacenService;
use Illuminate\Http\Request;

class SalidaAlmacenController extends Controller
{
    public function __construct(private AlmacenService $service) {}

    public function index()
    {
        $salidas = SalidaAlmacen::with(['creadoPor', 'ot', 'cotizacion', 'items.insumo'])
            ->orderByDesc('fecha')
            ->orderByDesc('created_at')
            ->paginate(20);

        return view('almacen.salidas.index', compact('salidas'));
    }

    public function create(Request $request)
    {
        $tipo         = $request->tipo ?? 'DIRECTA';
        $insumos      = CatalogoInsumo::where('activo', true)->orderBy('nombre')->get();
        $pendientes   = collect();
        $cotizacion   = null;
        $ot           = null;

        if ($tipo === 'COTIZACION' && $request->filled('numero_ot')) {
            $ot = OrdenTrabajo::where('numero_ot', $request->numero_ot)
                ->with(['vehiculo.marca', 'cotizaciones'])
                ->first();

            if ($ot) {
                $cot = $ot->cotizaciones()
                    ->whereIn('estado', ['BORRADOR', 'AUTORIZADA'])
                    ->latest()
                    ->first();

                if ($cot) {
                    $cotizacion = $cot;
                    $pendientes = $this->service->getPendientesCotizacion($cot);
                }
            }
        }

        return view('almacen.salidas.crear', compact('tipo', 'insumos', 'pendientes', 'cotizacion', 'ot'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'tipo'                   => 'required|in:COTIZACION,DIRECTA',
            'entregado_a'            => 'required|string|max:150',
            'fecha'                  => 'required|date|before_or_equal:today',
            'items'                  => 'required|array|min:1',
            'items.*.id_insumo'      => 'required|exists:catalogo_insumos,id',
            'items.*.cantidad'       => 'required|numeric|min:0.01',
            'items.*.descripcion'    => 'required|string|max:200',
            'observaciones'          => 'nullable|string|max:500',
        ]);

        if ($request->tipo === 'COTIZACION') {
            $request->validate(['id_cotizacion' => 'required|exists:cotizaciones,id']);
        }

        try {
            $data = $request->all();

            if ($request->tipo === 'COTIZACION' && $request->filled('id_cotizacion')) {
                $cot = Cotizacion::find($request->id_cotizacion);
                $data['id_ot'] = $cot?->id_ot;
            }

            $salida = $this->service->registrarSalida($data);

            return redirect()->route('almacen.salidas.show', $salida)
                ->with('success', "Salida registrada. {$salida->items->count()} ítem(s) entregado(s) a {$salida->entregado_a}.");
        } catch (\RuntimeException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }
    }

    public function show(SalidaAlmacen $salida)
    {
        $salida->load(['items.insumo', 'cotizacion.ot.vehiculo.marca', 'ot.vehiculo.marca', 'creadoPor']);
        return view('almacen.salidas.show', compact('salida'));
    }

    /** API: insumos pendientes de una cotización para el form de salida */
    public function pendientesCotizacion(Cotizacion $cotizacion)
    {
        $pendientes = $this->service->getPendientesCotizacion($cotizacion)
            ->map(fn($item) => [
                'id'                        => $item->id,
                'id_insumo'                 => $item->id_insumo,
                'descripcion'               => $item->descripcion,
                'cantidad_solicitada'        => (float)$item->cantidad_solicitada,
                'cantidad_entregada'         => $item->cantidad_entregada,
                'cantidad_pendiente'         => $item->cantidad_pendiente,
                'unidad_medida'             => $item->insumo?->unidad_medida ?? '',
                'precio_venta'              => (float)$item->precio_venta,
                'stock_actual'              => (float)($item->insumo?->stock_actual ?? 0),
            ])
            ->values();

        return response()->json($pendientes);
    }
}
