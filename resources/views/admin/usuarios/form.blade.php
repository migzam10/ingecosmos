@extends('layouts.app')
@section('title', isset($usuario) ? 'Editar Usuario' : 'Nuevo Usuario')
@section('page_title', isset($usuario) ? 'Editar: ' . $usuario->name : 'Nuevo Usuario')
@section('breadcrumb','Usuarios')
@section('content')
<div class="row justify-content-center">
<div class="col-12 col-lg-7">
<form method="POST" action="{{ isset($usuario) ? route('admin.usuarios.update', $usuario) : route('admin.usuarios.store') }}">
    @csrf @if(isset($usuario)) @method('PUT') @endif
    <x-errores />
    <div class="card">
        <div class="card-body">

            <div class="mb-3">
                <label class="form-label">Nombre completo <span class="text-danger">*</span></label>
                <input type="text" name="name" class="form-control @error('name') is-invalid @enderror"
                       value="{{ old('name', $usuario->name ?? '') }}" required>
                @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            <div class="mb-3">
                <label class="form-label">Correo electrónico <span class="text-danger">*</span></label>
                <input type="email" name="email" class="form-control @error('email') is-invalid @enderror"
                       value="{{ old('email', $usuario->email ?? '') }}" required>
                @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            <div class="mb-3">
                <label class="form-label">
                    Contraseña
                    @if(isset($usuario))<small class="text-muted">(dejar vacío para no cambiar)</small>@else<span class="text-danger">*</span>@endif
                </label>
                <input type="password" name="password" class="form-control @error('password') is-invalid @enderror"
                       autocomplete="new-password" {{ isset($usuario) ? '' : 'required' }}>
                @error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            <div class="mb-3">
                <label class="form-label">Roles <span class="text-danger">*</span></label>
                <div class="row g-2">
                    @foreach($roles as $rol)
                    @php
                    $desc = [
                        'ADMIN'       => 'Acceso total al sistema',
                        'COORDINADOR' => 'Torre, técnicos, autorización, producción, almacén',
                        'COTIZADOR'   => 'Crear y editar cotizaciones',
                        'RECEPCION'   => 'Crear órdenes de trabajo',
                        'TECNICO'     => 'Ver y gestionar sus propias tareas',
                        'ALMACEN'     => 'Entradas, salidas e inventario de insumos',
                    ][$rol];
                    $checked = in_array($rol, old('roles', $usuario->roles ?? []));
                    @endphp
                    <div class="col-12 col-md-6">
                        <label class="form-check">
                            <input class="form-check-input" type="checkbox"
                                   name="roles[]" value="{{ $rol }}"
                                   {{ $checked ? 'checked' : '' }}>
                            <span class="form-check-label">
                                <strong>{{ $rol }}</strong><br>
                                <small class="text-muted">{{ $desc }}</small>
                            </span>
                        </label>
                    </div>
                    @endforeach
                </div>
                @error('roles')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
            </div>

            @isset($usuario)
            <div class="mb-0">
                <label class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" name="activo" value="1"
                           {{ old('activo', $usuario->activo) ? 'checked' : '' }}>
                    <span class="form-check-label">Usuario activo</span>
                </label>
            </div>
            @endisset

        </div>
        <div class="card-footer d-flex gap-2">
            <button type="submit" class="btn btn-primary">
                {{ isset($usuario) ? 'Guardar Cambios' : 'Crear Usuario' }}
            </button>
            <a href="{{ route('admin.usuarios.index') }}" class="btn btn-outline-secondary">Cancelar</a>
        </div>
    </div>
</form>
</div></div>
@endsection
