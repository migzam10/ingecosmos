<?php

namespace App\Http\Controllers;

use App\Http\Requests\GuardarProveedorRequest;
use App\Models\Proveedor;
use Illuminate\Http\Request;

class ProveedorController extends Controller
{
    public function index(Request $request)
    {
        $query = Proveedor::withCount('ordenesCompra')->orderBy('nombre');

        if ($request->filled('buscar')) {
            $b = trim($request->buscar);
            $query->where(function ($q) use ($b) {
                $q->where('nombre', 'like', "%$b%")->orWhere('nit', 'like', "%$b%");
            });
        }

        $proveedores = $query->paginate(20)->withQueryString();

        return view('proveedores.index', compact('proveedores'));
    }

    public function create()
    {
        return view('proveedores.form');
    }

    public function store(GuardarProveedorRequest $request)
    {
        $prov = Proveedor::create($this->limpiar($request->validated()));

        return redirect()->route('proveedores.index')
            ->with('success', "Proveedor «{$prov->nombre}» creado.");
    }

    public function edit(Proveedor $proveedor)
    {
        return view('proveedores.form', compact('proveedor'));
    }

    public function update(GuardarProveedorRequest $request, Proveedor $proveedor)
    {
        $proveedor->update($this->limpiar($request->validated()));

        return redirect()->route('proveedores.index')
            ->with('success', "Proveedor «{$proveedor->nombre}» actualizado.");
    }

    public function destroy(Proveedor $proveedor)
    {
        // Se conserva el historial: no se borra un proveedor con órdenes asociadas.
        if ($proveedor->ordenesCompra()->exists()) {
            return back()->with('error', 'No se puede eliminar: el proveedor tiene órdenes de compra asociadas.');
        }

        $nombre = $proveedor->nombre;
        $proveedor->delete();

        return redirect()->route('proveedores.index')
            ->with('success', "Proveedor «{$nombre}» eliminado.");
    }

    /** Normaliza los campos opcionales vacíos a null. */
    private function limpiar(array $data): array
    {
        $data['nit']      = ($data['nit'] ?? '') !== '' ? trim($data['nit']) : null;
        $data['telefono'] = ($data['telefono'] ?? '') !== '' ? trim($data['telefono']) : null;

        return $data;
    }
}
