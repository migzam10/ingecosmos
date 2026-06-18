@extends('layouts.app')
@section('title','Empresas Cliente')
@section('page_title','Empresas / CIAs')
@section('breadcrumb','Administración')
@section('page_actions')
<a href="{{ route('admin.empresas.create') }}" class="btn btn-primary">+ Nueva Empresa</a>
@endsection
@section('content')
<div class="card">
    <div class="table-responsive">
        <table class="table table-vcenter table-hover card-table">
            <thead>
                <tr>
                    <th>Nombre</th>
                    <th>Tipo</th>
                    <th class="text-end">Tarifa/h</th>
                    <th>Metas (L/M/F)</th>
                    <th>Estado</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse($empresas as $empresa)
                <tr class="{{ !$empresa->activa ? 'opacity-50' : '' }}">
                    <td class="fw-medium">{{ $empresa->nombre }}</td>
                    <td>
                        <span class="badge {{ $empresa->tipo === 'A' ? 'bg-blue-lt' : 'bg-teal-lt' }}">
                            Tipo {{ $empresa->tipo }}
                        </span>
                        <small class="text-muted ms-1">
                            {{ $empresa->tipo === 'A' ? 'incluye RTO' : 'cliente pone RTO' }}
                        </small>
                    </td>
                    <td class="text-end fw-bold">$ {{ number_format($empresa->tarifa_hora, 0, ',', '.') }}</td>
                    <td class="small text-muted">
                        {{ $empresa->meta_dias_leve }}d /
                        {{ $empresa->meta_dias_medio }}d /
                        {{ $empresa->meta_dias_fuerte }}d
                    </td>
                    <td>
                        <span class="badge {{ $empresa->activa ? 'bg-success-lt' : 'bg-danger-lt' }}">
                            {{ $empresa->activa ? 'Activa' : 'Inactiva' }}
                        </span>
                    </td>
                    <td>
                        <div class="d-flex gap-1">
                            <a href="{{ route('admin.empresas.edit', $empresa) }}"
                               class="btn btn-sm btn-outline-secondary">Editar</a>
                            @if($empresa->activa)
                            <form method="POST" action="{{ route('admin.empresas.destroy', $empresa) }}"
                                  data-confirm="¿Desactivar {{ $empresa->nombre }}?">
                                @csrf @method('DELETE')
                                <button class="btn btn-sm btn-outline-danger"><x-icon name="x" /></button>
                            </form>
                            @else
                            <form method="POST" action="{{ route('admin.empresas.restaurar', $empresa) }}">
                                @csrf
                                <button class="btn btn-sm btn-outline-success"><x-icon name="arrow-back-up" /></button>
                            </form>
                            @endif
                        </div>
                    </td>
                </tr>
                @empty
                <tr><td colspan="6" class="text-center text-muted py-4">Sin empresas registradas.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
