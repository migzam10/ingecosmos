<?php

namespace App\Http\Controllers;

use App\Models\CatalogoRepuesto;
use App\Models\MarcaVehiculo;
use App\Models\ModeloVehiculo;
use Illuminate\Http\Request;

class CatalogoRepuestosController extends Controller
{
    public function index(Request $request)
    {
        $query = CatalogoRepuesto::with(['marca', 'modelo']);

        if ($request->filled('buscar')) {
            $query->where('descripcion', 'like', '%' . $request->buscar . '%');
        }

        if ($request->filled('nivel')) {
            $query->where('nivel', $request->nivel);
        }

        if ($request->filled('marca')) {
            $query->where('id_marca', $request->marca);
        }

        if ($request->filled('activo')) {
            $query->where('activo', $request->activo === '1');
        }

        $items  = $query->orderBy('nivel')->orderBy('descripcion')->paginate(30)->withQueryString();
        $marcas = MarcaVehiculo::orderBy('nombre')->get();

        return view('catalogo-repuestos.index', compact('items', 'marcas'));
    }

    public function create()
    {
        $marcas = MarcaVehiculo::orderBy('nombre')->get();
        return view('catalogo-repuestos.form', compact('marcas'));
    }

    public function store(Request $request)
    {
        CatalogoRepuesto::create($this->validar($request));

        return redirect()->route('catalogo-repuestos.index')
            ->with('success', 'Repuesto agregado al catálogo.');
    }

    public function edit(CatalogoRepuesto $catalogoRepuesto)
    {
        $marcas  = MarcaVehiculo::orderBy('nombre')->get();
        $modelos = $catalogoRepuesto->id_marca
            ? ModeloVehiculo::where('id_marca', $catalogoRepuesto->id_marca)->orderBy('nombre')->get()
            : collect();

        return view('catalogo-repuestos.form', compact('catalogoRepuesto', 'marcas', 'modelos'));
    }

    public function update(Request $request, CatalogoRepuesto $catalogoRepuesto)
    {
        $catalogoRepuesto->update($this->validar($request));

        return redirect()->route('catalogo-repuestos.index')
            ->with('success', 'Repuesto actualizado.');
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

    /** AJAX — filtra por marca/modelo, del más específico al más genérico */
    public function porVehiculo(Request $request)
    {
        $idMarca  = $request->id_marca;
        $idModelo = $request->id_modelo;
        $buscar   = $request->buscar ?? '';

        $items = CatalogoRepuesto::where('activo', true)
            ->where(function ($q) use ($idMarca, $idModelo) {
                $q->where('nivel', 1)
                  ->orWhere(fn($q2) => $q2->where('nivel', 2)->where('id_marca', $idMarca))
                  ->orWhere(fn($q3) => $q3->where('nivel', 3)
                      ->where('id_marca', $idMarca)
                      ->where('id_modelo', $idModelo));
            })
            ->when($buscar, fn($q) => $q->where('descripcion', 'like', "%$buscar%"))
            ->orderByDesc('nivel')
            ->orderBy('descripcion')
            ->limit(30)
            ->get(['id', 'nivel', 'descripcion', 'precio_referencia']);

        return response()->json($items);
    }

    private function validar(Request $request): array
    {
        $data = $request->validate([
            'nivel'             => 'required|in:1,2,3',
            'descripcion'       => 'required|string|max:200',
            'precio_referencia' => 'required|numeric|min:0',
            'activo'            => 'boolean',
            'id_marca'          => 'nullable|exists:marcas_vehiculo,id',
            'id_modelo'         => 'nullable|exists:modelos_vehiculo,id',
        ]);

        if ($data['nivel'] == 1) {
            $data['id_marca']  = null;
            $data['id_modelo'] = null;
        }
        if ($data['nivel'] == 2) {
            $data['id_modelo'] = null;
        }

        $data['activo'] = $request->boolean('activo', true);

        return $data;
    }
}
