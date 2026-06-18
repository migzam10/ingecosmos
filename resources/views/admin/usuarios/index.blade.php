@extends('layouts.app')
@section('title','Usuarios')
@section('page_title','Usuarios del Sistema')
@section('breadcrumb','Administración')
@section('page_actions')
<a href="{{ route('admin.usuarios.create') }}" class="btn btn-primary">+ Nuevo Usuario</a>
@endsection
@section('content')
<div class="card">
    <div class="table-responsive">
        <table class="table table-vcenter table-hover card-table">
            <thead>
                <tr><th>Nombre</th><th>Email</th><th>Roles</th><th>Estado</th><th></th></tr>
            </thead>
            <tbody>
                @forelse($usuarios as $usuario)
                <tr class="{{ !$usuario->activo ? 'opacity-50' : '' }}">
                    <td class="fw-medium">{{ $usuario->name }}</td>
                    <td class="text-muted small">{{ $usuario->email }}</td>
                    <td>
                        @foreach($usuario->roles ?: [] as $rol)
                        <span class="badge bg-blue-lt me-1">{{ $rol }}</span>
                        @endforeach
                    </td>
                    <td>
                        <span class="badge {{ $usuario->activo ? 'bg-success-lt' : 'bg-danger-lt' }}">
                            {{ $usuario->activo ? 'Activo' : 'Inactivo' }}
                        </span>
                    </td>
                    <td>
                        <div class="d-flex gap-1">
                            <a href="{{ route('admin.usuarios.edit', $usuario) }}"
                               class="btn btn-sm btn-outline-secondary">Editar</a>
                            @if($usuario->id !== Auth::id())
                            <form method="POST" action="{{ route('admin.usuarios.destroy', $usuario) }}"
                                  data-confirm="¿Desactivar a {{ $usuario->name }}?">
                                @csrf @method('DELETE')
                                <button class="btn btn-sm btn-outline-danger"><x-icon name="x" /></button>
                            </form>
                            @endif
                        </div>
                    </td>
                </tr>
                @empty
                <tr><td colspan="5" class="text-center text-muted py-4">Sin usuarios.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
