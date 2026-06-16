@extends('layouts.app')
@section('title', 'Editar Salida')
@section('page_title', 'Editar Salida del ' . $salida->fecha->format('d/m/Y'))
@section('breadcrumb', 'Almacén / Salidas')
@section('page_actions')
<a href="{{ route('almacen.salidas.show', $salida) }}" class="btn btn-outline-secondary btn-sm">← Volver</a>
@endsection

@section('content')

@if(session('error'))
<div class="alert alert-danger alert-dismissible" role="alert">
    {{ session('error') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif

<form method="POST" action="{{ route('almacen.salidas.update', $salida) }}">
@csrf @method('PUT')

<div class="row g-3">
    <div class="col-12 col-xl-8">

        <div class="card mb-3">
            <div class="card-header"><h3 class="card-title">Datos de la salida</h3></div>
            <div class="card-body">
                @if($salida->tipo === 'COTIZACION' && $salida->cotizacion)
                <div class="alert alert-info mb-3 small">
                    <strong>Salida de cotización</strong> —
                    OT #{{ $salida->cotizacion->ot?->numero_ot }} /
                    COT #{{ $salida->cotizacion->numero_cot }}
                    {{ $salida->cotizacion->ot?->vehiculo?->placa }}
                </div>
                @endif
                <div class="row g-2">
                    <div class="col-6 col-md-3">
                        <label class="form-label">Fecha <span class="text-danger">*</span></label>
                        <input type="date" name="fecha" class="form-control @error('fecha') is-invalid @enderror"
                               value="{{ old('fecha', $salida->fecha->toDateString()) }}"
                               required max="{{ now()->toDateString() }}">
                        @error('fecha')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-12 col-md-5">
                        <label class="form-label">Entregado a <span class="text-danger">*</span></label>
                        <input type="text" name="entregado_a" class="form-control @error('entregado_a') is-invalid @enderror"
                               value="{{ old('entregado_a', $salida->entregado_a) }}" required maxlength="150">
                        @error('entregado_a')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-12 col-md-4">
                        <label class="form-label">Observaciones</label>
                        <input type="text" name="observaciones" class="form-control"
                               value="{{ old('observaciones', $salida->observaciones) }}" maxlength="500">
                    </div>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h3 class="card-title mb-0">Insumos entregados</h3>
            </div>
            <div class="card-body p-0">
                <div class="alert alert-info mx-3 mt-3 mb-2 small">
                    Al guardar, el stock se revertirá y se aplicarán las nuevas cantidades.
                    @if($salida->tipo === 'COTIZACION')
                    Los insumos vinculados a la cotización se recalcularán como pendientes si reduces la cantidad.
                    @endif
                </div>
                <table class="table table-sm mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Insumo</th>
                            <th style="width:120px" class="text-end">Cantidad <span class="text-danger">*</span></th>
                            <th style="width:70px" class="text-center">Unidad</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($salida->items as $i => $item)
                        @php
                            $cotItem = $item->itemCotizacion;
                            // Max entregable = lo que esta salida tenía + pendiente actual de la cotización
                            $maxEntregable = $cotItem
                                ? (float)$item->cantidad + $cotItem->cantidad_pendiente
                                : (float)($item->insumo?->stock_actual ?? 0) + (float)$item->cantidad;
                        @endphp
                        <input type="hidden" name="items[{{ $i }}][id_insumo]" value="{{ $item->id_insumo }}">
                        <input type="hidden" name="items[{{ $i }}][id_item_cotizacion_insumo]" value="{{ $item->id_item_cotizacion_insumo }}">
                        <input type="hidden" name="items[{{ $i }}][descripcion]" value="{{ $item->descripcion }}">
                        <input type="hidden" name="items[{{ $i }}][precio_venta]" value="{{ $item->precio_venta }}">
                        <tr>
                            <td class="align-middle">
                                {{ $item->descripcion }}
                                @if($cotItem)
                                <div class="small text-muted">
                                    Cotización solicita: {{ number_format($cotItem->cantidad_solicitada, 2) }}
                                    | Esta salida: {{ number_format($item->cantidad, 2) }}
                                    | Pendiente actual (sin esta salida): {{ number_format($cotItem->cantidad_pendiente, 2) }}
                                </div>
                                @else
                                <div class="small text-muted">Stock actual: {{ $item->insumo?->stock_actual ?? '—' }} {{ $item->insumo?->unidad_medida }}</div>
                                @endif
                            </td>
                            <td>
                                <input type="number" name="items[{{ $i }}][cantidad]"
                                       class="form-control form-control-sm text-end"
                                       value="{{ old("items.$i.cantidad", $item->cantidad) }}"
                                       min="0.01" step="0.01"
                                       max="{{ $maxEntregable }}"
                                       required>
                            </td>
                            <td class="text-center text-muted small align-middle">{{ $item->insumo?->unidad_medida }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="col-12 col-xl-4">
        <div class="card sticky-top" style="top:1rem">
            <div class="card-header bg-warning"><h3 class="card-title text-dark">Editar Salida</h3></div>
            <div class="card-body small text-muted">
                <p>El stock se revertirá y se aplicarán las nuevas cantidades.</p>
                <p class="text-danger"><strong>No se puede superar el stock disponible ni las cantidades pendientes de cotización.</strong></p>
                <p>No se pueden agregar ni quitar insumos. Solo modificar cantidades o datos de entrega.</p>
            </div>
            <div class="card-footer">
                <button type="submit" class="btn btn-warning w-100"
                        data-confirm="¿Confirmar edición de esta salida? El stock se recalculará.">
                    Guardar Cambios
                </button>
            </div>
        </div>
    </div>
</div>
</form>
@endsection
