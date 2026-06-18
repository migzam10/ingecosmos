@extends('layouts.app')
@section('title','Técnicos')
@section('page_title','Técnicos del Taller')
@section('breadcrumb','Administración')
@section('page_actions')
<a href="{{ route('admin.tecnicos.create') }}" class="btn btn-primary">+ Nuevo Técnico</a>
@endsection
@section('content')
<div class="card">
    <div class="table-responsive">
        <table class="table table-vcenter table-hover card-table">
            <thead>
                <tr><th>Nombre</th><th>Especialidades</th><th>Usuario</th><th>Estado</th><th></th></tr>
            </thead>
            <tbody>
                @forelse($tecnicos as $tecnico)
                <tr class="{{ !$tecnico->activo ? 'opacity-50' : '' }}">
                    <td class="fw-medium">{{ $tecnico->nombre }}</td>
                    <td>
                        @foreach($tecnico->especialidades ?: [] as $esp)
                        <span class="badge bg-secondary-lt me-1">{{ $esp }}</span>
                        @endforeach
                    </td>
                    <td class="text-muted small">
                        {{ $tecnico->user?->name ?? '— sin usuario —' }}
                    </td>
                    <td>
                        <span class="badge {{ $tecnico->activo ? 'bg-success-lt' : 'bg-danger-lt' }}">
                            {{ $tecnico->activo ? 'Activo' : 'Inactivo' }}
                        </span>
                    </td>
                    <td>
                        <div class="d-flex gap-1">
                            <a href="{{ route('admin.tecnicos.edit', $tecnico) }}"
                               class="btn btn-sm btn-outline-secondary">Editar</a>
                            <form method="POST" action="{{ route('admin.tecnicos.destroy', $tecnico) }}"
                                  data-confirm="¿Desactivar a {{ $tecnico->nombre }}?">
                                @csrf @method('DELETE')
                                <button class="btn btn-sm btn-outline-danger"><x-icon name="x" /></button>
                            </form>
                        </div>
                    </td>
                </tr>
                @empty
                <tr><td colspan="5" class="text-center text-muted py-4">Sin técnicos registrados.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
