<?php

namespace App\Http\Controllers;

use App\Models\CatalogoInsumo;
use App\Models\EntradaAlmacen;
use App\Models\OrdenTrabajo;
use App\Models\SalidaAlmacen;
use App\Services\AlmacenService;
use Illuminate\Http\Request;

class AlmacenController extends Controller
{
    public function __construct(private AlmacenService $service) {}

    public function index()
    {
        $stockBajo       = $this->service->getAlertasStockBajo();
        $alertasDemanda  = $this->service->getAlertasDemanda()->take(10);
        $ultimasEntradas = EntradaAlmacen::with('creadoPor')->latest()->take(5)->get();
        $ultimasSalidas  = SalidaAlmacen::with('creadoPor', 'ot')->latest()->take(5)->get();
        $totalInsumos    = CatalogoInsumo::where('activo', true)->count();

        return view('almacen.index', compact(
            'stockBajo', 'alertasDemanda',
            'ultimasEntradas', 'ultimasSalidas', 'totalInsumos'
        ));
    }

    public function ots(Request $request)
    {
        $query = OrdenTrabajo::with(['vehiculo.marca', 'vehiculo.modelo'])
            ->orderByDesc('fecha_ingreso');

        if ($request->filled('buscar')) {
            $b = $request->buscar;
            $query->where(function ($q) use ($b) {
                $q->where('numero_ot', 'like', "%$b%")
                  ->orWhereHas('vehiculo', fn($q2) => $q2->where('placa', 'like', "%$b%"));
            });
        }

        $ordenes = $query->paginate(20)->withQueryString();

        return view('almacen.ots', compact('ordenes'));
    }

    public function otShow(OrdenTrabajo $orden)
    {
        $orden->load([
            'vehiculo.marca', 'vehiculo.modelo',
            'cotizaciones.itemsInsumo.insumo',
            'cotizaciones.itemsInsumo.salidasItems',
        ]);

        return view('almacen.ot-show', compact('orden'));
    }

    public function pendientes()
    {
        $pendientesPorOT = $this->service->getPendientesPorOT();
        $alertasDemanda  = $this->service->getAlertasDemanda();

        return view('almacen.pendientes', compact('pendientesPorOT', 'alertasDemanda'));
    }
}
