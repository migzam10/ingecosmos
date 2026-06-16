@extends('layouts.app')
@section('title', 'Salida del ' . $salida->fecha->format('d/m/Y'))
@section('page_title', 'Salida del ' . $salida->fecha->format('d/m/Y'))
@section('breadcrumb', 'Almacén / Salidas')
@section('page_actions')
<a href="{{ route('almacen.salidas.index') }}" class="btn btn-outline-secondary btn-sm">← Volver</a>
@endsection

@section('content')
<div class="row g-3">
    <div class="col-12 col-md-8">
        <div class="card">
            <div class="card-header"><h3 class="card-title">Insumos entregados</h3></div>
            <div class="table-responsive">
                <table class="table table-sm mb-0">
                    <thead>
                        <tr>
                            <th>Insumo</th>
                            <th class="text-end" style="width:100px">Cantidad</th>
                            <th class="text-center" style="width:80px">Unidad</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($salida->items as $item)
                        <tr>
                            <td>{{ $item->descripcion }}</td>
                            <td class="text-end fw-bold">{{ number_format($item->cantidad, 2) }}</td>
                            <td class="text-center text-muted">{{ $item->insumo?->unidad_medida }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="col-12 col-md-4">
        <div class="card">
            <div class="card-header"><h3 class="card-title">Detalle</h3></div>
            <div class="card-body small">
                <div class="mb-1"><span class="text-muted">Fecha:</span> <strong>{{ $salida->fecha->format('d/m/Y') }}</strong></div>
                <div class="mb-1"><span class="text-muted">Tipo:</span>
                    <span class="badge {{ $salida->tipo === 'COTIZACION' ? 'bg-blue-lt' : 'bg-secondary-lt' }}">{{ $salida->tipo }}</span>
                </div>
                <div class="mb-1"><span class="text-muted">Entregado a:</span> <strong>{{ $salida->entregado_a }}</strong></div>
                @if($salida->ot)
                <div class="mb-1"><span class="text-muted">OT:</span>
                    <a href="{{ route('almacen.ots.show', $salida->ot) }}">#{{ $salida->ot->numero_ot }}</a>
                    — {{ $salida->ot->vehiculo->placa }}
                </div>
                @endif
                @if($salida->cotizacion)
                <div class="mb-1"><span class="text-muted">Cotización:</span> #{{ $salida->cotizacion->numero_cot }}</div>
                @endif
                <div class="mb-1"><span class="text-muted">Registrado por:</span> {{ $salida->creadoPor->name }}</div>
                @if($salida->observaciones)
                <div class="mt-2 p-2 bg-light rounded">{{ $salida->observaciones }}</div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
