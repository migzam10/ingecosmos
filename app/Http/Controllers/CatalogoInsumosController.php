<?php

namespace App\Http\Controllers;

use App\Models\CatalogoInsumo;
use Illuminate\Http\Request;

class CatalogoInsumosController extends Controller
{
    public function index(Request $request)
    {
        $query = CatalogoInsumo::orderBy('nombre');

        if ($request->filled('buscar')) {
            $query->where('nombre', 'like', '%' . $request->buscar . '%');
        }

        if ($request->filled('activo')) {
            $query->where('activo', $request->activo === '1');
        }

        $catalogo = $query->paginate(30)->withQueryString();

        return view('almacen.catalogo', compact('catalogo'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'nombre'        => 'required|string|max:200',
            'unidad_medida' => 'required|string|max:30',
            'precio_venta'  => 'nullable|numeric|min:0',
            'stock_minimo'  => 'nullable|numeric|min:0',
        ]);

        CatalogoInsumo::create($data + ['activo' => true, 'stock_actual' => 0]);

        return back()->with('success', 'Insumo creado correctamente.');
    }

    public function update(Request $request, CatalogoInsumo $catalogoInsumo)
    {
        $data = $request->validate([
            'nombre'        => 'required|string|max:200',
            'unidad_medida' => 'required|string|max:30',
            'precio_venta'  => 'nullable|numeric|min:0',
            'stock_minimo'  => 'nullable|numeric|min:0',
        ]);

        $catalogoInsumo->update($data);

        return back()->with('success', 'Insumo actualizado.');
    }

    public function destroy(CatalogoInsumo $catalogoInsumo)
    {
        $catalogoInsumo->update(['activo' => false]);
        return back()->with('success', 'Insumo desactivado.');
    }

    public function restaurar(CatalogoInsumo $catalogoInsumo)
    {
        $catalogoInsumo->update(['activo' => true]);
        return back()->with('success', 'Insumo reactivado.');
    }

    /** API: buscar insumos para el form de cotización */
    public function buscar(Request $request)
    {
        $buscar = $request->buscar ?? '';

        $items = CatalogoInsumo::where('activo', true)
            ->when($buscar, fn($q) => $q->where('nombre', 'like', "%$buscar%"))
            ->orderBy('nombre')
            ->limit(20)
            ->get(['id', 'nombre', 'unidad_medida', 'precio_venta', 'stock_actual']);

        return response()->json($items);
    }
}
