@extends('layouts.app')
@section('title', 'Almacén')
@section('page_title', 'Almacén')
@section('breadcrumb', 'Almacén')
@section('page_actions')
<div class="d-flex gap-2 flex-wrap">
    <a href="{{ route('almacen.entradas.index') }}" class="btn btn-success btn-sm">Entradas</a>
    <a href="{{ route('almacen.salidas.index') }}" class="btn btn-warning btn-sm">Salidas</a>
</div>
@endsection

@section('content')
<div class="row g-3 mb-3">
    <div class="col-6 col-md-3">
        <div class="card text-center">
            <div class="card-body py-3">
                <div class="h1 text-primary">{{ $totalInsumos }}</div>
                <div class="text-muted small">Insumos activos</div>
            </div>
            <a href="{{ route('almacen.catalogo.index') }}" class="card-footer text-muted small">Ver catálogo →</a>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card text-center {{ $pendientesCount ? 'border-info' : '' }}">
            <div class="card-body py-3">
                <div class="h1 {{ $pendientesCount ? 'text-info' : 'text-success' }}">{{ $pendientesCount }}</div>
                <div class="text-muted small">OTs con entregas pendientes</div>
            </div>
            <a href="{{ route('almacen.pendientes') }}" class="card-footer text-muted small">Ver pendientes →</a>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card text-center {{ $stockBajo->count() ? 'border-warning' : '' }}">
            <div class="card-body py-3">
                <div class="h1 {{ $stockBajo->count() ? 'text-warning' : 'text-success' }}">{{ $stockBajo->count() }}</div>
                <div class="text-muted small">Stock bajo / agotado</div>
            </div>
            <span class="card-footer text-muted small">{{ $stockBajo->count() ? 'Ver detalle abajo ↓' : 'Todo en orden' }}</span>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card text-center {{ $alertasDemanda->count() ? 'border-danger' : '' }}">
            <div class="card-body py-3">
                <div class="h1 {{ $alertasDemanda->count() ? 'text-danger' : 'text-success' }}">{{ $alertasDemanda->count() }}</div>
                <div class="text-muted small">Alertas de demanda</div>
            </div>
            <a href="{{ route('almacen.pendientes') }}" class="card-footer text-muted small">Ver alertas →</a>
        </div>
    </div>
</div>

<div class="row g-3">
    {{-- Stock bajo --}}
    @if($stockBajo->count())
    <div class="col-12 col-md-6">
        <div class="card border-warning">
            <div class="card-header bg-warning-lt">
                <h3 class="card-title text-warning">⚠ Insumos con stock bajo</h3>
            </div>
            <div class="table-responsive">
                <table class="table table-sm mb-0">
                    <thead><tr><th>Insumo</th><th class="text-end">Stock</th><th class="text-end">Mínimo</th><th class="text-end">Unidad</th></tr></thead>
                    <tbody>
                        @foreach($stockBajo as $ins)
                        <tr class="{{ (float)$ins->stock_actual <= 0 ? 'table-danger' : 'table-warning' }}">
                            <td>{{ $ins->nombre }}</td>
                            <td class="text-end fw-bold">{{ number_format($ins->stock_actual, 2) }}</td>
                            <td class="text-end text-muted">{{ number_format($ins->stock_minimo, 2) }}</td>
                            <td class="text-end text-muted">{{ $ins->unidad_medida }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    @endif

    {{-- Últimas entradas --}}
    <div class="col-12 col-md-6">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h3 class="card-title">Últimas entradas</h3>
                <a href="{{ route('almacen.entradas.index') }}" class="text-muted small">Ver todas →</a>
            </div>
            <div class="table-responsive">
                <table class="table table-sm mb-0">
                    <thead><tr><th>Fecha</th><th>Ítems</th><th>Registrado por</th></tr></thead>
                    <tbody>
                        @forelse($ultimasEntradas as $e)
                        <tr>
                            <td><a href="{{ route('almacen.entradas.show', $e) }}">{{ $e->fecha->format('d/m/Y') }}</a></td>
                            <td>{{ $e->items->count() }} insumo(s)</td>
                            <td class="text-muted small">{{ $e->creadoPor->name }}</td>
                        </tr>
                        @empty
                        <tr><td colspan="3" class="text-muted text-center py-2">Sin entradas.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- Últimas salidas --}}
    <div class="col-12 col-md-6">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h3 class="card-title">Últimas salidas</h3>
                <a href="{{ route('almacen.salidas.index') }}" class="text-muted small">Ver todas →</a>
            </div>
            <div class="table-responsive">
                <table class="table table-sm mb-0">
                    <thead><tr><th>Fecha</th><th>Tipo</th><th>Entregado a</th><th>OT</th></tr></thead>
                    <tbody>
                        @forelse($ultimasSalidas as $s)
                        <tr>
                            <td><a href="{{ route('almacen.salidas.show', $s) }}">{{ $s->fecha->format('d/m/Y') }}</a></td>
                            <td><span class="badge {{ $s->tipo === 'COTIZACION' ? 'bg-blue-lt' : 'bg-secondary-lt' }}">{{ $s->tipo }}</span></td>
                            <td>{{ $s->entregado_a }}</td>
                            <td class="text-muted small">{{ $s->ot ? '#' . $s->ot->numero_ot : '—' }}</td>
                        </tr>
                        @empty
                        <tr><td colspan="4" class="text-muted text-center py-2">Sin salidas.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
