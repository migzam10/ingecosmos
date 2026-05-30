<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\EmpresaCliente;
use Illuminate\Http\Request;

class EmpresaClienteController extends Controller
{
    public function index()
    {
        $empresas = EmpresaCliente::orderBy('nombre')->get();
        return view('admin.empresas.index', compact('empresas'));
    }

    public function create()
    {
        return view('admin.empresas.form');
    }

    public function store(Request $request)
    {
        $data = $this->validar($request);
        EmpresaCliente::create($data);
        return redirect()->route('admin.empresas.index')
            ->with('success', 'Empresa creada correctamente.');
    }

    public function edit(EmpresaCliente $empresa)
    {
        return view('admin.empresas.form', compact('empresa'));
    }

    public function update(Request $request, EmpresaCliente $empresa)
    {
        $data = $this->validar($request);
        $empresa->update($data);
        return redirect()->route('admin.empresas.index')
            ->with('success', 'Empresa actualizada.');
    }

    public function destroy(EmpresaCliente $empresa)
    {
        $empresa->update(['activa' => false]);
        return back()->with('success', 'Empresa desactivada.');
    }

    public function restaurar(EmpresaCliente $empresa)
    {
        $empresa->update(['activa' => true]);
        return back()->with('success', 'Empresa activada.');
    }

    private function validar(Request $request): array
    {
        return $request->validate([
            'nombre'            => 'required|string|max:100',
            'tipo'              => 'required|in:A,B',
            'tarifa_hora'       => 'required|numeric|min:0',
            'meta_dias_leve'    => 'required|integer|min:1|max:30',
            'meta_dias_medio'   => 'required|integer|min:1|max:30',
            'meta_dias_fuerte'  => 'required|integer|min:1|max:30',
            'activa'            => 'boolean',
        ], [
            'nombre.required'   => 'El nombre de la empresa es obligatorio.',
            'tipo.required'     => 'Seleccione el tipo (A o B).',
            'tarifa_hora.required' => 'La tarifa por hora es obligatoria.',
        ]);
    }
}
