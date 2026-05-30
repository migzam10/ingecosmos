<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class UsuarioController extends Controller
{
    const ROLES_DISPONIBLES = ['ADMIN', 'COORDINADOR', 'COTIZADOR', 'RECEPCION', 'TECNICO'];

    public function index()
    {
        $usuarios = User::orderBy('name')->get();
        return view('admin.usuarios.index', compact('usuarios'));
    }

    public function create()
    {
        $roles = self::ROLES_DISPONIBLES;
        return view('admin.usuarios.form', compact('roles'));
    }

    public function store(Request $request)
    {
        $data = $this->validar($request, null);

        // COORDINADOR no puede asignar el rol ADMIN
        $roles = $data['roles'] ?? [];
        if (!auth()->user()->hasRole('ADMIN')) {
            $roles = array_values(array_filter($roles, fn($r) => $r !== 'ADMIN'));
        }

        User::create([
            'name'     => $data['name'],
            'email'    => $data['email'],
            'password' => Hash::make($data['password']),
            'roles'    => $roles,
            'activo'   => true,
        ]);

        return redirect()->route('admin.usuarios.index')
            ->with('success', 'Usuario creado correctamente.');
    }

    public function edit(User $usuario)
    {
        $roles = self::ROLES_DISPONIBLES;
        return view('admin.usuarios.form', compact('usuario', 'roles'));
    }

    public function update(Request $request, User $usuario)
    {
        $data = $this->validar($request, $usuario);

        // COORDINADOR no puede asignar ni quitar el rol ADMIN
        $roles = $data['roles'] ?? [];
        if (!auth()->user()->hasRole('ADMIN')) {
            // Preservar el rol ADMIN si ya lo tenía, no permitir agregarlo
            $yaEraAdmin = in_array('ADMIN', $usuario->roles ?? []);
            $roles = array_values(array_filter($roles, fn($r) => $r !== 'ADMIN'));
            if ($yaEraAdmin) $roles[] = 'ADMIN';
        }

        $update = [
            'name'   => $data['name'],
            'email'  => $data['email'],
            'roles'  => $roles,
            'activo' => $request->boolean('activo', true),
        ];

        if (!empty($data['password'])) {
            $update['password'] = Hash::make($data['password']);
        }

        $usuario->update($update);

        return redirect()->route('admin.usuarios.index')
            ->with('success', 'Usuario actualizado.');
    }

    public function destroy(User $usuario)
    {
        abort_if($usuario->id === auth()->id(), 403, 'No puedes desactivar tu propio usuario.');
        $usuario->update(['activo' => false]);
        return back()->with('success', 'Usuario desactivado.');
    }

    private function validar(Request $request, ?User $usuario): array
    {
        $emailRule = 'required|email|unique:users,email' . ($usuario ? ",{$usuario->id}" : '');

        $rules = [
            'name'     => 'required|string|max:150',
            'email'    => $emailRule,
            'roles'    => 'nullable|array',
            'roles.*'  => 'in:' . implode(',', self::ROLES_DISPONIBLES),
            'password' => $usuario
                ? ['nullable', Password::min(8)]
                : ['required', Password::min(8)],
        ];

        return $request->validate($rules, [
            'name.required'     => 'El nombre es obligatorio.',
            'email.required'    => 'El correo es obligatorio.',
            'email.unique'      => 'Este correo ya está registrado.',
            'password.required' => 'La contraseña es obligatoria.',
            'password.min'      => 'La contraseña debe tener al menos 8 caracteres.',
        ]);
    }
}
