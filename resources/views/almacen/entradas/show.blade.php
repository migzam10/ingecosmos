@extends('layouts.app')
@section('title', 'Entrada del ' . $entrada->fecha->format('d/m/Y'))
@section('page_title', 'Entrada del ' . $entrada->fecha->format('d/m/Y'))
@section('breadcrumb', 'Almacén / Entradas')
@section('page_actions')
<div class="d-flex gap-2 flex-wrap">
    <a href="{{ route('almacen.entradas.index') }}" class="btn btn-outline-secondary btn-sm">← Volver</a>
    @if(auth()->user()->hasAnyRole(['ADMIN','COORDINADOR']))
    <a href="{{ route('almacen.entradas.edit', $entrada) }}" class="btn btn-outline-primary btn-sm">Editar</a>
    <form method="POST" action="{{ route('almacen.entradas.destroy', $entrada) }}" class="d-inline">
        @csrf @method('DELETE')
        <button type="submit" class="btn btn-outline-danger btn-sm"
                data-confirm="¿Eliminar esta entrada? El stock se descontará automáticamente.">
            Eliminar
        </button>
    </form>
    @endif
</div>
@endsection

@section('content')
<div class="row g-3">
    <div class="col-12 col-md-8">
        <div class="card">
            <div class="card-header"><h3 class="card-title">Insumos ingresados</h3></div>
            <div class="table-responsive">
                <table class="table table-sm mb-0">
                    <thead>
                        <tr>
                            <th>Insumo</th>
                            <th class="text-end" style="width:100px">Cantidad</th>
                            <th class="text-center" style="width:80px">Unidad</th>
                            <th class="text-end" style="width:130px">P. Compra</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($entrada->items as $item)
                        <tr>
                            <td>{{ $item->insumo?->nombre ?? 'N/A' }}</td>
                            <td class="text-end fw-bold">{{ number_format($item->cantidad, 2) }}</td>
                            <td class="text-center text-muted">{{ $item->insumo?->unidad_medida }}</td>
                            <td class="text-end text-muted">
                                {{ $item->precio_compra ? '$ ' . number_format($item->precio_compra, 0, ',', '.') : '—' }}
                            </td>
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
                <div class="mb-1"><span class="text-muted">Fecha:</span> <strong>{{ $entrada->fecha->format('d/m/Y') }}</strong></div>
                <div class="mb-1"><span class="text-muted">Ítems:</span> {{ $entrada->items->count() }}</div>
                <div class="mb-1"><span class="text-muted">Registrado por:</span> {{ $entrada->creadoPor->name }}</div>
                @if($entrada->observaciones)
                <div class="mt-2 p-2 bg-light rounded">{{ $entrada->observaciones }}</div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
