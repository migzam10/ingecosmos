@extends('layouts.app')
@section('title', 'Órdenes de Trabajo — Almacén')
@section('page_title', 'Órdenes de Trabajo')
@section('breadcrumb', 'Almacén')

@section('content')
<div class="card">
    <div class="card-header">
        <form method="GET" class="d-flex gap-2 align-items-center flex-wrap">
            <input type="text" name="buscar" value="{{ request('buscar') }}"
                   class="form-control form-control-sm" style="max-width:260px"
                   placeholder="# OT o placa...">
            <button type="submit" class="btn btn-sm btn-outline-secondary">Buscar</button>
            @if(request('buscar'))
            <a href="{{ route('almacen.ots.index') }}" class="btn btn-sm btn-ghost-secondary">Limpiar</a>
            @endif
        </form>
    </div>
    <div class="table-responsive">
        <table class="table table-sm table-hover mb-0">
            <thead>
                <tr>
                    <th># OT</th>
                    <th>Placa</th>
                    <th>Vehículo</th>
                    <th>Estado</th>
                    <th>Ingreso</th>
                    <th style="width:80px"></th>
                </tr>
            </thead>
            <tbody>
                @forelse($ordenes as $orden)
                <tr>
                    <td class="fw-bold">{{ $orden->numero_ot }}</td>
                    <td>{{ $orden->vehiculo->placa }}</td>
                    <td class="text-muted">{{ $orden->vehiculo->marca->nombre }} {{ $orden->vehiculo->modelo?->nombre }}</td>
                    <td><span class="badge bg-secondary-lt">{{ $orden->estado_proceso }}</span></td>
                    <td class="text-muted small">{{ $orden->fecha_ingreso ? \Carbon\Carbon::parse($orden->fecha_ingreso)->format('d/m/Y') : '—' }}</td>
                    <td>
                        <a href="{{ route('almacen.ots.show', $orden) }}" class="btn btn-sm btn-ghost-secondary">Ver</a>
                    </td>
                </tr>
                @empty
                <tr><td colspan="6" class="text-center text-muted py-4">Sin órdenes.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($ordenes->hasPages())
    <div class="card-footer">{{ $ordenes->withQueryString()->links() }}</div>
    @endif
</div>
@endsection
