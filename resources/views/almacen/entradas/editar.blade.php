@extends('layouts.app')
@section('title', 'Editar Entrada')
@section('page_title', 'Editar Entrada del ' . $entrada->fecha->format('d/m/Y'))
@section('breadcrumb', 'Almacén / Entradas')
@section('page_actions')
<a href="{{ route('almacen.entradas.show', $entrada) }}" class="btn btn-outline-secondary btn-sm">← Volver</a>
@endsection

@section('content')

@if(session('error'))
<div class="alert alert-danger alert-dismissible" role="alert">
    {{ session('error') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif

<form method="POST" action="{{ route('almacen.entradas.update', $entrada) }}">
@csrf @method('PUT')

<div class="row g-3">
    <div class="col-12 col-xl-8">

        <div class="card mb-3">
            <div class="card-header"><h3 class="card-title">Datos de la entrada</h3></div>
            <div class="card-body">
                <div class="row g-2">
                    <div class="col-6 col-md-3">
                        <label class="form-label">Fecha <span class="text-danger">*</span></label>
                        <input type="date" name="fecha" class="form-control @error('fecha') is-invalid @enderror"
                               value="{{ old('fecha', $entrada->fecha->toDateString()) }}"
                               required max="{{ now()->toDateString() }}">
                        @error('fecha')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-12 col-md-9">
                        <label class="form-label">Observaciones</label>
                        <input type="text" name="observaciones" class="form-control"
                               value="{{ old('observaciones', $entrada->observaciones) }}" maxlength="500">
                    </div>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h3 class="card-title mb-0">Insumos ingresados</h3>
            </div>
            <div class="card-body p-0">
                <div class="alert alert-info mx-3 mt-3 mb-2 small">
                    Al guardar, el stock se revertirá al estado anterior y se aplicarán las nuevas cantidades.
                </div>
                <table class="table table-sm mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Insumo</th>
                            <th style="width:120px" class="text-end">Cantidad <span class="text-danger">*</span></th>
                            <th style="width:80px" class="text-center">Unidad</th>
                            <th style="width:140px" class="text-end">P. Compra</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($entrada->items as $i => $item)
                        <input type="hidden" name="items[{{ $i }}][id_insumo]" value="{{ $item->id_insumo }}">
                        <tr>
                            <td class="align-middle">{{ $item->insumo?->nombre ?? 'N/A' }}</td>
                            <td>
                                <input type="number" name="items[{{ $i }}][cantidad]"
                                       class="form-control form-control-sm text-end"
                                       value="{{ old("items.$i.cantidad", $item->cantidad) }}"
                                       min="0.01" step="0.01" required>
                            </td>
                            <td class="text-center text-muted small align-middle">{{ $item->insumo?->unidad_medida }}</td>
                            <td>
                                <input type="number" name="items[{{ $i }}][precio_compra]"
                                       class="form-control form-control-sm text-end"
                                       value="{{ old("items.$i.precio_compra", $item->precio_compra) }}"
                                       min="0" step="1" placeholder="Opcional">
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="col-12 col-xl-4">
        <div class="card sticky-top" style="top:1rem">
            <div class="card-header bg-primary-lt"><h3 class="card-title text-primary">Editar Entrada</h3></div>
            <div class="card-body small text-muted">
                <p>El stock se ajustará automáticamente: se revertirán las cantidades anteriores y se aplicarán las nuevas.</p>
                <p class="text-warning"><strong>No se pueden agregar ni quitar insumos. Solo modificar cantidades.</strong></p>
            </div>
            <div class="card-footer">
                <button type="submit" class="btn btn-primary w-100"
                        data-confirm="¿Confirmar edición de esta entrada? El stock se recalculará.">
                    Guardar Cambios
                </button>
            </div>
        </div>
    </div>
</div>
</form>
@endsection
