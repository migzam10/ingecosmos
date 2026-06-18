@extends('layouts.app')
@section('title', 'Salidas de Inventario')
@section('page_title', 'Salidas de Inventario')
@section('breadcrumb', 'Almacén / Salidas')
@section('page_actions')
<div class="d-flex gap-2">
    <a href="{{ route('almacen.index') }}" class="btn btn-outline-secondary btn-sm"><x-icon name="arrow-left" /> Almacén</a>
    <a href="{{ route('almacen.salidas.create') }}" class="btn btn-warning btn-sm"><x-icon name="download" /> Registrar Salida</a>
</div>
@endsection

@section('content')
<div class="card">
    <div class="table-responsive">
        <table class="table table-sm table-hover mb-0">
            <thead>
                <tr>
                    <th>Fecha</th>
                    <th>Tipo</th>
                    <th>OT / Referencia</th>
                    <th>Entregado a</th>
                    <th>Ítems</th>
                    <th style="width:80px"></th>
                </tr>
            </thead>
            <tbody>
                @forelse($salidas as $salida)
                <tr>
                    <td>{{ $salida->fecha->format('d/m/Y') }}</td>
                    <td><span class="badge {{ $salida->tipo === 'COTIZACION' ? 'bg-blue-lt' : 'bg-secondary-lt' }}">{{ $salida->tipo }}</span></td>
                    <td>
                        @if($salida->ot)
                        <a href="{{ route('almacen.ots.show', $salida->ot) }}">#{{ $salida->ot->numero_ot }}</a>
                        @else
                        <span class="text-muted">—</span>
                        @endif
                    </td>
                    <td>{{ $salida->entregado_a }}</td>
                    <td class="text-muted small">
                        @foreach($salida->items->take(2) as $item)
                        {{ $item->descripcion }} ({{ number_format($item->cantidad, 2) }})@if(!$loop->last), @endif
                        @endforeach
                        @if($salida->items->count() > 2) +{{ $salida->items->count() - 2 }} más @endif
                    </td>
                    <td>
                        <a href="{{ route('almacen.salidas.show', $salida) }}" class="btn btn-sm btn-ghost-secondary">Ver</a>
                    </td>
                </tr>
                @empty
                <tr><td colspan="6" class="text-center text-muted py-4">Sin salidas registradas.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($salidas->hasPages())
    <div class="card-footer">{{ $salidas->links() }}</div>
    @endif
</div>
@endsection
