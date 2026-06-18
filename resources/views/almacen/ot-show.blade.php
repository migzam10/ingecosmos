@extends('layouts.app')
@section('title', 'OT #' . $orden->numero_ot . ' — Almacén')
@section('page_title', 'OT #' . $orden->numero_ot)
@section('breadcrumb', 'Almacén / OTs')
@section('page_actions')
<div class="d-flex gap-2">
    <a href="{{ route('almacen.ots.index') }}" class="btn btn-outline-secondary btn-sm"><x-icon name="arrow-left" /> Volver</a>
    <a href="{{ route('almacen.salidas.create') }}?tipo=COTIZACION&numero_ot={{ $orden->numero_ot }}"
       class="btn btn-warning btn-sm"><x-icon name="download" /> Registrar Salida</a>
</div>
@endsection

@section('content')

{{-- Info básica del vehículo --}}
<div class="card mb-3">
    <div class="card-body">
        <div class="row g-2 align-items-center">
            <div class="col">
                <div class="fw-bold fs-4">{{ $orden->vehiculo->placa }}</div>
                <div class="text-muted">{{ $orden->vehiculo->marca->nombre }} {{ $orden->vehiculo->modelo?->nombre }}</div>
            </div>
            <div class="col-auto">
                <span class="badge bg-secondary fs-6">{{ $orden->estado_proceso }}</span>
            </div>
        </div>
    </div>
</div>

{{-- Insumos solicitados en cotizaciones --}}
@php $todasCots = $orden->cotizaciones->filter(fn($c) => $c->itemsInsumo->isNotEmpty()); @endphp

@if($todasCots->isEmpty())
<div class="alert alert-info">Esta OT no tiene insumos solicitados en sus cotizaciones.</div>
@else
@foreach($todasCots as $cot)
<div class="card mb-3">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h3 class="card-title mb-0">Cotización #{{ $cot->numero_cot }}</h3>
        <span class="badge {{ match($cot->estado) { 'AUTORIZADA' => 'bg-success', 'RECHAZADA' => 'bg-danger', default => 'bg-secondary' } }}">
            {{ $cot->estado }}
        </span>
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
                    <th class="text-center" style="width:80px">Stock</th>
                </tr>
            </thead>
            <tbody>
                @foreach($cot->itemsInsumo as $item)
                @php
                    $entregado = $item->salidasItems->sum('cantidad');
                    $pendiente = max(0, (float)$item->cantidad_solicitada - $entregado);
                    $stock     = (float)($item->insumo?->stock_actual ?? 0);
                @endphp
                <tr class="{{ $pendiente > 0 && $stock < $pendiente ? 'table-danger' : ($pendiente > 0 ? 'table-warning' : '') }}">
                    <td>{{ $item->descripcion }}</td>
                    <td class="text-end">{{ number_format($item->cantidad_solicitada, 2) }}</td>
                    <td class="text-end text-success">{{ number_format($entregado, 2) }}</td>
                    <td class="text-end fw-bold {{ $pendiente > 0 ? 'text-warning' : 'text-success' }}">
                        @if($pendiente > 0){{ number_format($pendiente, 2) }}@else<x-icon name="check" />@endif
                    </td>
                    <td class="text-center text-muted small">{{ $item->insumo?->unidad_medida }}</td>
                    <td class="text-center small {{ $stock < $pendiente && $pendiente > 0 ? 'text-danger fw-bold' : 'text-muted' }}">
                        {{ number_format($stock, 2) }}
                        @if($stock < $pendiente && $pendiente > 0)
                        <div class="text-danger" style="font-size:10px"><x-icon name="alert-triangle" size="13" /> Insuficiente</div>
                        @endif
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
