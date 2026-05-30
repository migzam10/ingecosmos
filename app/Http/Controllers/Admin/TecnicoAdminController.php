<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Tecnico;
use App\Models\User;
use Illuminate\Http\Request;

class TecnicoAdminController extends Controller
{
    const ESPECIALIDADES = ['LAT', 'PREP', 'PINT', 'MEC', 'ELEC', 'AA', 'SCANNER'];

    public function index()
    {
        $tecnicos = Tecnico::with('user')->orderBy('nombre')->get();
        return view('admin.tecnicos.index', compact('tecnicos'));
    }

    public function create()
    {
        $especialidades = self::ESPECIALIDADES;
        // Solo usuarios con rol TECNICO que no tengan técnico asignado aún
        $usuarios = User::where('activo', true)
            ->whereJsonContains('roles', 'TECNICO')
            ->whereNotIn('id', Tecnico::whereNotNull('id_user')->pluck('id_user'))
            ->orderBy('name')
            ->get();

        return view('admin.tecnicos.form', compact('especialidades', 'usuarios'));
    }

    public function store(Request $request)
    {
        $data = $this->validar($request);

        Tecnico::create([
            'nombre'        => $data['nombre'],
            'id_user'       => $data['id_user'] ?: null,
            'especialidades'=> $data['especialidades'] ?? [],
            'activo'        => true,
        ]);

        return redirect()->route('admin.tecnicos.index')
            ->with('success', 'Técnico creado correctamente.');
    }

    public function edit(Tecnico $tecnico)
    {
        $especialidades = self::ESPECIALIDADES;
        $usuarios = User::where('activo', true)
            ->whereJsonContains('roles', 'TECNICO')
            ->where(function ($q) use ($tecnico) {
                $q->whereNotIn('id', Tecnico::whereNotNull('id_user')
                    ->where('id', '!=', $tecnico->id)
                    ->pluck('id_user'))
                  ->orWhere('id', $tecnico->id_user);
            })
            ->orderBy('name')
            ->get();

        return view('admin.tecnicos.form', compact('tecnico', 'especialidades', 'usuarios'));
    }

    public function update(Request $request, Tecnico $tecnico)
    {
        $data = $this->validar($request);

        $tecnico->update([
            'nombre'        => $data['nombre'],
            'id_user'       => $data['id_user'] ?: null,
            'especialidades'=> $data['especialidades'] ?? [],
            'activo'        => $request->boolean('activo', true),
        ]);

        return redirect()->route('admin.tecnicos.index')
            ->with('success', 'Técnico actualizado.');
    }

    public function destroy(Tecnico $tecnico)
    {
        $tecnico->update(['activo' => false]);
        return back()->with('success', 'Técnico desactivado.');
    }

    private function validar(Request $request): array
    {
        return $request->validate([
            'nombre'          => 'required|string|max:100',
            'id_user'         => 'nullable|exists:users,id',
            'especialidades'  => 'required|array|min:1',
            'especialidades.*'=> 'in:' . implode(',', self::ESPECIALIDADES),
        ], [
            'nombre.required'          => 'El nombre del técnico es obligatorio.',
            'especialidades.required'  => 'Seleccione al menos una especialidad.',
            'especialidades.min'       => 'Seleccione al menos una especialidad.',
        ]);
    }
}
