<?php

namespace App\Http\Controllers;

use App\Models\CatalogoInsumo;
use App\Models\EntradaAlmacen;
use App\Services\AlmacenService;
use Illuminate\Http\Request;

class EntradaAlmacenController extends Controller
{
    public function __construct(private AlmacenService $service) {}

    public function index()
    {
        $entradas = EntradaAlmacen::with(['creadoPor', 'items.insumo'])
            ->orderByDesc('fecha')
            ->orderByDesc('created_at')
            ->paginate(20);

        return view('almacen.entradas.index', compact('entradas'));
    }

    public function create()
    {
        $insumos = CatalogoInsumo::where('activo', true)->orderBy('nombre')->get();
        return view('almacen.entradas.crear', compact('insumos'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'fecha'                  => 'required|date|before_or_equal:today',
            'items'                  => 'required|array|min:1',
            'items.*.id_insumo'      => 'required|exists:catalogo_insumos,id',
            'items.*.cantidad'       => 'required|numeric|min:0.01',
            'items.*.precio_compra'  => 'nullable|numeric|min:0',
            'observaciones'          => 'nullable|string|max:500',
        ]);

        $entrada = $this->service->registrarEntrada($request->all());

        return redirect()->route('almacen.entradas.show', $entrada)
            ->with('success', "Entrada registrada. Se actualizó el stock de {$entrada->items->count()} insumo(s).");
    }

    public function show(EntradaAlmacen $entrada)
    {
        $entrada->load(['items.insumo', 'creadoPor']);
        return view('almacen.entradas.show', compact('entrada'));
    }
}
