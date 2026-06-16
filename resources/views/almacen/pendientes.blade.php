@extends('layouts.app')
@section('title', 'Pendientes por OT')
@section('page_title', 'Pendientes por OT')
@section('breadcrumb', 'Almacén')

@section('content')

{{-- Alertas de demanda --}}
@if($alertasDemanda->count())
<div class="card border-danger mb-3">
    <div class="card-header bg-danger-lt">
        <h3 class="card-title text-danger">⚠ Insumos con stock insuficiente para cubrir demanda</h3>
    </div>
    <div class="table-responsive">
        <table class="table table-sm mb-0">
            <thead>
                <tr>
                    <th>Insumo</th>
                    <th class="text-end">Stock actual</th>
                    <th class="text-end">Demanda pendiente</th>
                    <th class="text-end text-danger">Déficit</th>
                    <th class="text-center">Unidad</th>
                </tr>
            </thead>
            <tbody>
                @foreach($alertasDemanda as $alerta)
                <tr class="table-danger">
                    <td>{{ $alerta['insumo']?->nombre ?? 'N/A' }}</td>
                    <td class="text-end">{{ number_format($alerta['stock_actual'], 2) }}</td>
                    <td class="text-end">{{ number_format($alerta['total_pendiente'], 2) }}</td>
                    <td class="text-end fw-bold text-danger">{{ number_format($alerta['deficit'], 2) }}</td>
                    <td class="text-center text-muted">{{ $alerta['insumo']?->unidad_medida }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    <div class="card-footer">
        <a href="{{ route('almacen.entradas.create') }}" class="btn btn-sm btn-success">+ Registrar Entrada para cubrir déficit</a>
    </div>
</div>
@endif

{{-- Pendientes por OT --}}
@if($pendientesPorOT->isEmpty())
<div class="alert alert-success">
    <strong>¡Sin pendientes!</strong> Todas las entregas de insumos están al día.
</div>
@else
@foreach($pendientesPorOT as $idOt => $items)
@php
    $ot = $items->first()->cotizacion?->ot;
    $cot = $items->first()->cotizacion;
@endphp
<div class="card mb-3">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h3 class="card-title mb-0">
            @if($ot)
            <a href="{{ route('almacen.ots.show', $ot) }}" class="text-reset">
                OT #{{ $ot->numero_ot }} — {{ $ot->vehiculo?->placa }}
                <span class="text-muted fw-normal small ms-1">
                    {{ $ot->vehiculo?->marca?->nombre }} {{ $ot->vehiculo?->modelo?->nombre }}
                </span>
            </a>
            @else
            Sin OT asignada
            @endif
        </h3>
        <div class="d-flex gap-2 align-items-center">
            @if($cot)
            <span class="text-muted small">COT #{{ $cot->numero_cot }}</span>
            @endif
            @if($ot)
            <a href="{{ route('almacen.salidas.create') }}?tipo=COTIZACION&numero_ot={{ $ot->numero_ot }}"
               class="btn btn-sm btn-warning">↓ Registrar Salida</a>
            @endif
        </div>
    </div>
    <div class="table-responsive">
        <table class="table table-sm mb-0">
            <thead class="table-light">
                <tr>
                    <th>Insumo</th>
                    <th class="text-end" style="width:110px">Solicitado</th>
                    <th class="text-end" style="width:110px">Entregado</th>
                    <th class="text-end" style="width:110px">Pendiente</th>
                    <th class="text-center" style="width:80px">Unidad</th>
                    <th class="text-center" style="width:90px">Stock disp.</th>
                </tr>
            </thead>
            <tbody>
                @foreach($items as $item)
                @php
                    $stock    = (float)($item->insumo?->stock_actual ?? 0);
                    $sinStock = $stock < $item->cantidad_pendiente;
                @endphp
                <tr class="{{ $sinStock ? 'table-danger' : 'table-warning' }}">
                    <td>
                        {{ $item->descripcion }}
                        @if($sinStock)
                        <span class="badge bg-danger ms-1">Sin stock suficiente</span>
                        @endif
                    </td>
                    <td class="text-end text-muted">{{ number_format($item->cantidad_solicitada, 2) }}</td>
                    <td class="text-end text-success">{{ number_format($item->cantidad_entregada, 2) }}</td>
                    <td class="text-end fw-bold">{{ number_format($item->cantidad_pendiente, 2) }}</td>
                    <td class="text-center text-muted small">{{ $item->insumo?->unidad_medida }}</td>
                    <td class="text-center {{ $sinStock ? 'text-danger fw-bold' : 'text-success' }}">
                        {{ number_format($stock, 2) }}
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endforeach
@endif

@endsection
