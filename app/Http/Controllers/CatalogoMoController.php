<?php

namespace App\Http\Controllers;

use App\Models\CatalogoMo;
use App\Models\MarcaVehiculo;
use App\Models\ModeloVehiculo;
use Illuminate\Http\Request;

class CatalogoMoController extends Controller
{
    public function index(Request $request)
    {
        $query = CatalogoMo::with(['marca', 'modelo']);

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

        $items = $query
            ->orderBy('nivel')
            ->orderBy('descripcion')
            ->paginate(30)
            ->withQueryString();

        $marcas = MarcaVehiculo::orderBy('nombre')->get();

        return view('catalogo.index', compact('items', 'marcas'));
    }

    public function create()
    {
        $marcas = MarcaVehiculo::orderBy('nombre')->get();
        return view('catalogo.form', compact('marcas'));
    }

    public function store(Request $request)
    {
        $data = $this->validar($request);

        CatalogoMo::create($data);

        return redirect()->route('catalogo.index')
            ->with('success', 'Ítem agregado al catálogo.');
    }

    public function edit(CatalogoMo $catalogo)
    {
        $marcas  = MarcaVehiculo::orderBy('nombre')->get();
        $modelos = $catalogo->id_marca
            ? ModeloVehiculo::where('id_marca', $catalogo->id_marca)->orderBy('nombre')->get()
            : collect();

        return view('catalogo.form', compact('catalogo', 'marcas', 'modelos'));
    }

    public function update(Request $request, CatalogoMo $catalogo)
    {
        $data = $this->validar($request);

        $catalogo->update($data);

        return redirect()->route('catalogo.index')
            ->with('success', 'Ítem actualizado.');
    }

    public function destroy(CatalogoMo $catalogo)
    {
        // Desactivar en lugar de eliminar para no romper cotizaciones existentes
        $catalogo->update(['activo' => false]);

        return back()->with('success', 'Ítem desactivado del catálogo.');
    }

    public function restaurar(CatalogoMo $catalogo)
    {
        $catalogo->update(['activo' => true]);

        return back()->with('success', 'Ítem restaurado.');
    }

    // AJAX — usado por cotización (Fase 6)
    // Retorna ítems filtrados por marca/modelo, del más específico al más genérico
    public function porVehiculo(Request $request)
    {
        $idMarca  = $request->id_marca;
        $idModelo = $request->id_modelo;

        $buscar = $request->buscar ?? '';

        $items = CatalogoMo::where('activo', true)
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
        $rules = [
            'nivel'             => 'required|in:1,2,3',
            'descripcion'       => 'required|string|max:200',
            'precio_referencia' => 'required|numeric|min:0',
            'activo'            => 'boolean',
            'id_marca'          => 'nullable|exists:marcas_vehiculo,id',
            'id_modelo'         => 'nullable|exists:modelos_vehiculo,id',
        ];

        $data = $request->validate($rules);

        // Nivel 1 no tiene marca ni modelo
        if ($data['nivel'] == 1) {
            $data['id_marca']  = null;
            $data['id_modelo'] = null;
        }
        // Nivel 2 solo tiene marca
        if ($data['nivel'] == 2) {
            $data['id_modelo'] = null;
        }

        $data['activo'] = $request->boolean('activo', true);

        return $data;
    }
}
