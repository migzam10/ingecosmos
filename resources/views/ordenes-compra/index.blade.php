@extends('layouts.app')

@section('title', 'Órdenes de Compra')
@section('page_title', 'Órdenes de Compra')
@section('breadcrumb', 'Compras')

@section('page_actions')
<a href="{{ route('ordenes-compra.create') }}" class="btn btn-primary btn-sm">
    + Nueva Orden de Compra
</a>
@endsection

@section('content')
<div class="card">
    <div class="card-header">
        <form method="GET" class="d-flex gap-2">
            <input type="text" name="buscar" class="form-control form-control-sm"
                   placeholder="# orden, proveedor, placa u OT..." value="{{ request('buscar') }}" style="max-width:260px">
            <button class="btn btn-sm btn-secondary">Buscar</button>
            <a href="{{ route('ordenes-compra.index') }}" class="btn btn-sm btn-outline-secondary"><x-icon name="x" /></a>
        </form>
    </div>
    <div class="table-responsive">
        <table class="table table-vcenter table-hover card-table">
            <thead>
                <tr>
                    <th># Orden</th>
                    <th>Fecha</th>
                    <th>Proveedor</th>
                    <th>Placa / OT</th>
                    <th>Pago</th>
                    <th class="text-end">Total</th>
                    <th>Elaboró</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse($ordenes as $orden)
                <tr>
                    <td class="fw-bold text-primary">{{ $orden->numero }}</td>
                    <td class="text-muted small">{{ $orden->fecha?->format('d/m/Y') }}</td>
                    <td>
                        <div class="fw-medium">{{ $orden->proveedor?->nombre ?? '—' }}</div>
                        @if($orden->proveedor?->nit)
                        <div class="text-muted small">NIT {{ $orden->proveedor->nit }}</div>
                        @endif
                    </td>
                    <td class="small">
                        @if($orden->placa)<span class="badge bg-blue-lt">{{ $orden->placa }}</span>@endif
                        @if($orden->numero_ot)<span class="text-muted ms-1">OT {{ $orden->numero_ot }}</span>@endif
                        @if(!$orden->placa && !$orden->numero_ot)<span class="text-muted">—</span>@endif
                    </td>
                    <td>
                        <span class="badge bg-{{ $orden->forma_pago === 'CONTADO' ? 'green' : 'yellow' }}-lt">
                            {{ ucfirst(strtolower($orden->forma_pago)) }}
                        </span>
                    </td>
                    <td class="text-end fw-bold">$ {{ number_format($orden->total, 0, ',', '.') }}</td>
                    <td class="text-muted small">{{ $orden->creadoPor?->name ?? '—' }}</td>
                    <td>
                        <div class="d-flex gap-1">
                            <a href="{{ route('ordenes-compra.show', $orden) }}" class="btn btn-sm btn-outline-secondary">Ver</a>
                            <a href="{{ route('ordenes-compra.pdf', $orden) }}" class="btn btn-sm btn-outline-primary" target="_blank">PDF</a>
                            <a href="{{ route('ordenes-compra.edit', $orden) }}" class="btn btn-sm btn-outline-secondary">Editar</a>
                            @if(in_array('ADMIN', Auth::user()->roles ?? []))
                            <form method="POST" action="{{ route('ordenes-compra.destroy', $orden) }}"
                                  data-confirm="¿Eliminar la orden de compra #{{ $orden->numero }}? Esta acción no se puede deshacer.">
                                @csrf @method('DELETE')
                                <button class="btn btn-sm btn-outline-danger"><x-icon name="trash" /></button>
                            </form>
                            @endif
                        </div>
                    </td>
                </tr>
                @empty
                <tr><td colspan="8" class="text-center text-muted py-4">No hay órdenes de compra.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($ordenes->hasPages())
    <div class="card-footer">{{ $ordenes->links() }}</div>
    @endif
</div>
@endsection
