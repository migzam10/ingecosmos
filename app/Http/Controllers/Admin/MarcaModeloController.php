<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MarcaVehiculo;
use App\Models\ModeloVehiculo;
use Illuminate\Http\Request;

class MarcaModeloController extends Controller
{
    public function index()
    {
        $marcas = MarcaVehiculo::withCount(['modelos', 'vehiculos'])
            ->with(['modelos' => fn($q) => $q->withCount(['vehiculos', 'catalogoMo'])->orderBy('nombre')])
            ->orderBy('nombre')
            ->get();

        return view('admin.marcas.index', compact('marcas'));
    }

    // ── Marcas ─────────────────────────────────────────────────────────────────

    public function storeMarca(Request $request)
    {
        $request->validate([
            'nombre' => 'required|string|max:50|unique:marcas_vehiculo,nombre',
        ], [
            'nombre.unique' => 'Ya existe una marca con ese nombre.',
        ]);

        MarcaVehiculo::create(['nombre' => strtoupper(trim($request->nombre))]);

        return back()->with('success', 'Marca "' . strtoupper($request->nombre) . '" creada.');
    }

    public function updateMarca(Request $request, MarcaVehiculo $marca)
    {
        $request->validate([
            'nombre' => 'required|string|max:50|unique:marcas_vehiculo,nombre,' . $marca->id,
        ], [
            'nombre.unique' => 'Ya existe una marca con ese nombre.',
        ]);

        $marca->update(['nombre' => strtoupper(trim($request->nombre))]);

        return back()->with('success', 'Marca actualizada correctamente.');
    }

    public function destroyMarca(MarcaVehiculo $marca)
    {
        if ($marca->vehiculos()->exists()) {
            return back()->with('error', "No se puede eliminar \"{$marca->nombre}\": tiene vehículos asociados.");
        }
        if ($marca->modelos()->exists()) {
            return back()->with('error', "No se puede eliminar \"{$marca->nombre}\": tiene modelos asociados. Elimínalos primero.");
        }
        if ($marca->catalogoMo()->exists()) {
            return back()->with('error', "No se puede eliminar \"{$marca->nombre}\": tiene ítems en el catálogo de MO.");
        }

        $nombre = $marca->nombre;
        $marca->delete();

        return back()->with('success', "Marca \"{$nombre}\" eliminada.");
    }

    // ── Modelos ────────────────────────────────────────────────────────────────

    public function storeModelo(Request $request, MarcaVehiculo $marca)
    {
        $request->validate([
            'nombre' => [
                'required', 'string', 'max:80',
                \Illuminate\Validation\Rule::unique('modelos_vehiculo')
                    ->where('id_marca', $marca->id),
            ],
        ], [
            'nombre.unique' => "Ya existe el modelo \"{$request->nombre}\" para {$marca->nombre}.",
        ]);

        ModeloVehiculo::create([
            'id_marca' => $marca->id,
            'nombre'   => strtoupper(trim($request->nombre)),
        ]);

        return back()->with('success', 'Modelo "' . strtoupper($request->nombre) . '" agregado a ' . $marca->nombre . '.');
    }

    public function updateModelo(Request $request, ModeloVehiculo $modelo)
    {
        $request->validate([
            'nombre' => [
                'required', 'string', 'max:80',
                \Illuminate\Validation\Rule::unique('modelos_vehiculo')
                    ->where('id_marca', $modelo->id_marca)
                    ->ignore($modelo->id),
            ],
        ], [
            'nombre.unique' => 'Ya existe ese modelo para esta marca.',
        ]);

        $modelo->update(['nombre' => strtoupper(trim($request->nombre))]);

        return back()->with('success', 'Modelo actualizado correctamente.');
    }

    public function destroyModelo(ModeloVehiculo $modelo)
    {
        if ($modelo->vehiculos()->exists()) {
            return back()->with('error', "No se puede eliminar \"{$modelo->nombre}\": tiene vehículos asociados.");
        }
        if ($modelo->catalogoMo()->exists()) {
            return back()->with('error', "No se puede eliminar \"{$modelo->nombre}\": tiene ítems en el catálogo de MO.");
        }

        $nombre = $modelo->nombre;
        $modelo->delete();

        return back()->with('success', "Modelo \"{$nombre}\" eliminado.");
    }
}
