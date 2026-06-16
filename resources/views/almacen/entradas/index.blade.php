@extends('layouts.app')
@section('title', 'Entradas de Inventario')
@section('page_title', 'Entradas de Inventario')
@section('breadcrumb', 'Almacén / Entradas')
@section('page_actions')
<a href="{{ route('almacen.entradas.create') }}" class="btn btn-success btn-sm">+ Registrar Entrada</a>
@endsection

@section('content')
<div class="card">
    <div class="table-responsive">
        <table class="table table-sm table-hover mb-0">
            <thead>
                <tr>
                    <th>Fecha</th>
                    <th>Insumos</th>
                    <th>Observaciones</th>
                    <th>Registrado por</th>
                    <th style="width:80px"></th>
                </tr>
            </thead>
            <tbody>
                @forelse($entradas as $entrada)
                <tr>
                    <td>{{ $entrada->fecha->format('d/m/Y') }}</td>
                    <td>
                        @foreach($entrada->items->take(3) as $item)
                        <span class="badge bg-secondary-lt me-1">{{ $item->insumo?->nombre ?? 'N/A' }}: {{ number_format($item->cantidad, 2) }}</span>
                        @endforeach
                        @if($entrada->items->count() > 3)
                        <span class="text-muted small">+{{ $entrada->items->count() - 3 }} más</span>
                        @endif
                    </td>
                    <td class="text-muted small">{{ Str::limit($entrada->observaciones, 50) }}</td>
                    <td class="text-muted small">{{ $entrada->creadoPor->name }}</td>
                    <td>
                        <a href="{{ route('almacen.entradas.show', $entrada) }}" class="btn btn-sm btn-ghost-secondary">Ver</a>
                    </td>
                </tr>
                @empty
                <tr><td colspan="5" class="text-center text-muted py-4">Sin entradas registradas.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($entradas->hasPages())
    <div class="card-footer">{{ $entradas->links() }}</div>
    @endif
</div>
@endsection
