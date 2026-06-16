<?php

namespace App\Http\Controllers;

use App\Models\CatalogoRepuesto;
use Illuminate\Http\Request;

class CatalogoRepuestosController extends Controller
{
    public function index(Request $request)
    {
        $query = CatalogoRepuesto::orderBy('descripcion');

        if ($request->filled('buscar')) {
            $query->where('descripcion', 'like', '%' . $request->buscar . '%');
        }

        if ($request->filled('activo')) {
            $query->where('activo', $request->activo === '1');
        }

        $catalogo = $query->paginate(30)->withQueryString();

        return view('catalogo-repuestos.index', compact('catalogo'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'descripcion'       => 'required|string|max:200',
            'precio_referencia' => 'nullable|numeric|min:0',
        ]);

        CatalogoRepuesto::create($data + ['activo' => true]);

        return back()->with('success', 'Repuesto creado correctamente.');
    }

    public function update(Request $request, CatalogoRepuesto $catalogoRepuesto)
    {
        $data = $request->validate([
            'descripcion'       => 'required|string|max:200',
            'precio_referencia' => 'nullable|numeric|min:0',
        ]);

        $catalogoRepuesto->update($data);

        return back()->with('success', 'Repuesto actualizado.');
    }

    public function destroy(CatalogoRepuesto $catalogoRepuesto)
    {
        $catalogoRepuesto->update(['activo' => false]);

        return back()->with('success', 'Repuesto desactivado.');
    }

    public function restaurar(CatalogoRepuesto $catalogoRepuesto)
    {
        $catalogoRepuesto->update(['activo' => true]);

        return back()->with('success', 'Repuesto reactivado.');
    }

    public function buscar(Request $request)
    {
        $buscar = $request->buscar ?? '';

        $items = CatalogoRepuesto::where('activo', true)
            ->when($buscar, fn($q) => $q->where('descripcion', 'like', "%$buscar%"))
            ->orderBy('descripcion')
            ->limit(20)
            ->get(['id', 'descripcion', 'precio_referencia']);

        return response()->json($items);
    }
}
