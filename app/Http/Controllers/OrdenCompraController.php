<?php

namespace App\Http\Controllers;

use App\Http\Requests\GuardarOrdenCompraRequest;
use App\Models\ConfiguracionEmpresa;
use App\Models\MarcaVehiculo;
use App\Models\OrdenCompra;
use App\Models\Proveedor;
use App\Services\OrdenCompraService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;

class OrdenCompraController extends Controller
{
    public function __construct(private OrdenCompraService $service) {}

    public function index(Request $request)
    {
        $query = OrdenCompra::with(['proveedor', 'creadoPor'])->orderByDesc('fecha')->orderByDesc('numero');

        if ($request->filled('buscar')) {
            $b = trim($request->buscar);
            $query->where(function ($q) use ($b) {
                $q->where('numero', 'like', "%$b%")
                    ->orWhere('placa', 'like', "%$b%")
                    ->orWhere('numero_ot', 'like', "%$b%")
                    ->orWhereHas('proveedor', fn ($q2) => $q2->where('nombre', 'like', "%$b%")->orWhere('nit', 'like', "%$b%"));
            });
        }

        $ordenes = $query->paginate(20)->withQueryString();

        return view('ordenes-compra.index', compact('ordenes'));
    }

    public function create()
    {
        $marcas          = MarcaVehiculo::orderBy('nombre')->get();
        $siguienteNumero = $this->service->sugerirNumero();

        return view('ordenes-compra.crear', compact('marcas', 'siguienteNumero'));
    }

    public function store(GuardarOrdenCompraRequest $request)
    {
        $orden = $this->service->crear($request->validated(), $request->user()->id);

        return redirect()->route('ordenes-compra.show', $orden)
            ->with('success', "Orden de compra #{$orden->numero} creada.");
    }

    public function show(OrdenCompra $ordenCompra)
    {
        $ordenCompra->load(['proveedor', 'marca', 'modelo', 'items', 'creadoPor', 'actualizadoPor']);

        return view('ordenes-compra.show', ['orden' => $ordenCompra]);
    }

    public function edit(OrdenCompra $ordenCompra)
    {
        $ordenCompra->load(['proveedor', 'items']);
        $marcas = MarcaVehiculo::orderBy('nombre')->get();

        return view('ordenes-compra.crear', ['orden' => $ordenCompra, 'marcas' => $marcas]);
    }

    public function update(GuardarOrdenCompraRequest $request, OrdenCompra $ordenCompra)
    {
        $this->service->actualizar($ordenCompra, $request->validated(), $request->user()->id);

        return redirect()->route('ordenes-compra.show', $ordenCompra)
            ->with('success', "Orden de compra #{$ordenCompra->numero} actualizada.");
    }

    public function destroy(OrdenCompra $ordenCompra)
    {
        $numero = $ordenCompra->numero;
        $ordenCompra->delete(); // los ítems caen por cascade

        return redirect()->route('ordenes-compra.index')
            ->with('success', "Orden de compra #{$numero} eliminada.");
    }

    public function pdf(OrdenCompra $ordenCompra)
    {
        $ordenCompra->load(['proveedor', 'marca', 'modelo', 'items', 'creadoPor']);
        $config = ConfiguracionEmpresa::getActual();

        $pdf = Pdf::loadView('ordenes-compra.pdf', ['orden' => $ordenCompra, 'config' => $config])
            ->setPaper('letter', 'portrait');

        return $pdf->stream("orden-compra-{$ordenCompra->numero}.pdf");
    }

    /** Autollenado del proveedor por Cédula / NIT (igual que el buscador de cédula del cliente). */
    public function buscarProveedor(Request $request)
    {
        $nit  = trim($request->nit);
        $prov = Proveedor::where('nit', $nit)->first();

        if (!$prov) {
            return response()->json(['encontrado' => false]);
        }

        return response()->json([
            'encontrado'         => true,
            'proveedor_nombre'   => $prov->nombre,
            'proveedor_nit'      => $prov->nit,
            'proveedor_telefono' => $prov->telefono,
        ]);
    }
}
