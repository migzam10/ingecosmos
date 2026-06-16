@extends('layouts.app')

@section('title', 'Cotizaciones')
@section('page_title', 'Cotizaciones')
@section('breadcrumb', 'Administración')

@section('page_actions')
<a href="{{ route('cotizaciones.previa.create') }}" class="btn btn-warning btn-sm">
    + Cotización Previa <span class="text-dark-50 ms-1 small">(sin OT)</span>
</a>
@endsection

@section('content')
<div class="card">
    {{-- Búsqueda --}}
    <div class="card-header">
        <form method="GET" class="d-flex gap-2">
            <input type="text" name="buscar" class="form-control form-control-sm"
                   placeholder="# COT o placa..." value="{{ request('buscar') }}" style="max-width:220px">
            <button class="btn btn-sm btn-secondary">Buscar</button>
            <a href="{{ route('cotizaciones.index') }}" class="btn btn-sm btn-outline-secondary">✕</a>
        </form>
    </div>
    <div class="table-responsive">
        <table class="table table-vcenter table-hover card-table">
            <thead>
                <tr>
                    <th># COT</th>
                    <th># OT</th>
                    <th>Placa / Vehículo</th>
                    <th>Empresa</th>
                    <th class="text-end">Total</th>
                    <th>Estado</th>
                    <th>Creada</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse($cotizaciones as $cot)
                <tr>
                    <td class="fw-bold text-primary">{{ $cot->numero_cot }}</td>
                    <td class="fw-bold">{{ $cot->ot->numero_ot }}</td>
                    <td>
                        <span class="badge bg-blue-lt">{{ $cot->ot->vehiculo->placa }}</span>
                        <span class="text-muted small ms-1">
                            {{ $cot->ot->vehiculo->marca->nombre }}
                        </span>
                    </td>
                    <td class="small">{{ $cot->ot->empresaCliente->nombre }}</td>
                    <td class="text-end fw-bold">$ {{ number_format($cot->total, 0, ',', '.') }}</td>
                    <td>
                        <span class="badge bg-{{ $cot->estado === 'AUTORIZADA' ? 'success' : ($cot->estado === 'RECHAZADA' ? 'danger' : 'secondary') }}-lt">
                            {{ $cot->estado }}
                        </span>
                    </td>
                    <td class="text-muted small">{{ $cot->created_at->format('d/m/Y') }}</td>
                    <td>
                        <div class="d-flex gap-1">
                            <a href="{{ route('cotizaciones.show', $cot) }}"
                               class="btn btn-sm btn-outline-secondary">Ver</a>
                            <a href="{{ route('cotizaciones.pdf', $cot) }}"
                               class="btn btn-sm btn-outline-primary" target="_blank">PDF</a>
                        </div>
                    </td>
                </tr>
                @empty
                <tr><td colspan="8" class="text-center text-muted py-4">No hay cotizaciones.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($cotizaciones->hasPages())
    <div class="card-footer">{{ $cotizaciones->links() }}</div>
    @endif
</div>
@endsection
