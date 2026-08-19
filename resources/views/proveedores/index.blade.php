@extends('layouts.app')

@section('title', 'Proveedores')
@section('page_title', 'Proveedores')
@section('breadcrumb', 'Compras')

@section('page_actions')
<a href="{{ route('proveedores.create') }}" class="btn btn-primary btn-sm">+ Nuevo Proveedor</a>
@endsection

@section('content')
<div class="card">
    <div class="card-header">
        <form method="GET" class="d-flex gap-2">
            <input type="text" name="buscar" class="form-control form-control-sm"
                   placeholder="Nombre o Cédula/NIT..." value="{{ request('buscar') }}" style="max-width:260px">
            <button class="btn btn-sm btn-secondary">Buscar</button>
            <a href="{{ route('proveedores.index') }}" class="btn btn-sm btn-outline-secondary"><x-icon name="x" /></a>
        </form>
    </div>
    <div class="table-responsive">
        <table class="table table-vcenter table-hover card-table">
            <thead>
                <tr>
                    <th>Nombre</th>
                    <th>Cédula / NIT</th>
                    <th>Teléfono</th>
                    <th class="text-center">Órdenes</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse($proveedores as $prov)
                <tr>
                    <td class="fw-medium">{{ $prov->nombre }}</td>
                    <td>{{ $prov->nit ?? '—' }}</td>
                    <td>{{ $prov->telefono ?? '—' }}</td>
                    <td class="text-center">
                        <span class="badge bg-secondary-lt">{{ $prov->ordenes_compra_count }}</span>
                    </td>
                    <td>
                        <div class="d-flex gap-1 justify-content-end">
                            <a href="{{ route('proveedores.edit', $prov) }}" class="btn btn-sm btn-outline-secondary">Editar</a>
                            @if(in_array('ADMIN', Auth::user()->roles ?? []))
                            <form method="POST" action="{{ route('proveedores.destroy', $prov) }}"
                                  data-confirm="¿Eliminar al proveedor «{{ $prov->nombre }}»?">
                                @csrf @method('DELETE')
                                <button class="btn btn-sm btn-outline-danger" {{ $prov->ordenes_compra_count > 0 ? 'disabled title=Tiene órdenes asociadas' : '' }}>
                                    <x-icon name="trash" />
                                </button>
                            </form>
                            @endif
                        </div>
                    </td>
                </tr>
                @empty
                <tr><td colspan="5" class="text-center text-muted py-4">No hay proveedores registrados.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($proveedores->hasPages())
    <div class="card-footer">{{ $proveedores->links() }}</div>
    @endif
</div>
@endsection
