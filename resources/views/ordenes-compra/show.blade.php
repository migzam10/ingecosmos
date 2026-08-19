@extends('layouts.app')

@section('title', 'Orden de Compra #' . $orden->numero)
@section('page_title', 'Orden de Compra #' . $orden->numero)
@section('breadcrumb', 'Compras')

@section('page_actions')
<div class="d-flex gap-2">
    <a href="{{ route('ordenes-compra.index') }}" class="btn btn-outline-secondary btn-sm"><x-icon name="arrow-left" /> Volver</a>
    <a href="{{ route('ordenes-compra.pdf', $orden) }}" class="btn btn-outline-primary btn-sm" target="_blank">PDF</a>
    <a href="{{ route('ordenes-compra.edit', $orden) }}" class="btn btn-primary btn-sm">Editar</a>
    @if(in_array('ADMIN', Auth::user()->roles ?? []))
    <form method="POST" action="{{ route('ordenes-compra.destroy', $orden) }}"
          data-confirm="¿Eliminar la orden de compra #{{ $orden->numero }}? Esta acción no se puede deshacer.">
        @csrf @method('DELETE')
        <button class="btn btn-outline-danger btn-sm"><x-icon name="trash" /> Eliminar</button>
    </form>
    @endif
</div>
@endsection

@section('content')
<div class="row g-3">
    <div class="col-12 col-lg-8">
        <div class="card mb-3">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h3 class="card-title mb-0">Productos</h3>
                <span class="badge bg-{{ $orden->forma_pago === 'CONTADO' ? 'green' : 'yellow' }}-lt">
                    {{ ucfirst(strtolower($orden->forma_pago)) }}
                </span>
            </div>
            <div class="table-responsive">
                <table class="table table-vcenter card-table">
                    <thead>
                        <tr>
                            <th class="text-end">Cant.</th>
                            <th>Und</th>
                            <th>Descripción</th>
                            <th class="text-end">Vr. Unitario</th>
                            <th class="text-end">Valor Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($orden->items as $it)
                        <tr>
                            <td class="text-end">{{ rtrim(rtrim(number_format($it->cantidad, 2, ',', '.'), '0'), ',') }}</td>
                            <td class="text-muted">{{ $it->unidad ?? '—' }}</td>
                            <td>{{ $it->descripcion }}</td>
                            <td class="text-end">$ {{ number_format($it->valor_unitario, 0, ',', '.') }}</td>
                            <td class="text-end fw-medium">$ {{ number_format($it->valor_total, 0, ',', '.') }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr>
                            <td colspan="4" class="text-end fw-bold">Subtotal</td>
                            <td class="text-end fw-bold">$ {{ number_format($orden->subtotal, 0, ',', '.') }}</td>
                        </tr>
                        @if($orden->descuento_valor > 0)
                        <tr class="text-muted">
                            <td colspan="4" class="text-end">Descuento{{ $orden->descuento_porcentaje > 0 ? ' ' . number_format($orden->descuento_porcentaje, 0) . '%' : '' }}</td>
                            <td class="text-end">- $ {{ number_format($orden->descuento_valor, 0, ',', '.') }}</td>
                        </tr>
                        @endif
                        @if($orden->iva_valor > 0)
                        <tr class="text-muted">
                            <td colspan="4" class="text-end">IVA{{ $orden->iva_porcentaje > 0 ? ' ' . number_format($orden->iva_porcentaje, 0) . '%' : '' }}</td>
                            <td class="text-end">$ {{ number_format($orden->iva_valor, 0, ',', '.') }}</td>
                        </tr>
                        @endif
                        <tr>
                            <td colspan="4" class="text-end fw-bold fs-4">TOTAL</td>
                            <td class="text-end fw-bold fs-4 text-primary">$ {{ number_format($orden->total, 0, ',', '.') }}</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>

        @if($orden->observaciones)
        <div class="card mb-3">
            <div class="card-header"><h3 class="card-title">Observaciones</h3></div>
            <div class="card-body">{{ $orden->observaciones }}</div>
        </div>
        @endif
    </div>

    <div class="col-12 col-lg-4">
        <div class="card mb-3">
            <div class="card-header"><h3 class="card-title">Proveedor</h3></div>
            <div class="card-body">
                <div class="fw-bold">{{ $orden->proveedor?->nombre ?? '—' }}</div>
                @if($orden->proveedor?->nit)<div class="text-muted small">NIT {{ $orden->proveedor->nit }}</div>@endif
                @if($orden->proveedor?->telefono)<div class="text-muted small">Tel. {{ $orden->proveedor->telefono }}</div>@endif
            </div>
        </div>

        <div class="card mb-3">
            <div class="card-header"><h3 class="card-title">Detalles</h3></div>
            <div class="card-body">
                <dl class="row mb-0 small">
                    <dt class="col-5 text-muted"># Orden</dt><dd class="col-7 fw-bold">{{ $orden->numero }}</dd>
                    <dt class="col-5 text-muted">Fecha</dt><dd class="col-7">{{ $orden->fecha?->format('d/m/Y') }}</dd>
                    <dt class="col-5 text-muted">Forma de pago</dt><dd class="col-7">{{ ucfirst(strtolower($orden->forma_pago)) }}</dd>
                    @if($orden->numero_ot)<dt class="col-5 text-muted">OT</dt><dd class="col-7">{{ $orden->numero_ot }}</dd>@endif
                    @if($orden->placa)<dt class="col-5 text-muted">Placa</dt><dd class="col-7">{{ $orden->placa }}</dd>@endif
                    @if($orden->marca)<dt class="col-5 text-muted">Vehículo</dt><dd class="col-7">{{ $orden->marca->nombre }} {{ $orden->modelo?->nombre }}</dd>@endif
                    <dt class="col-5 text-muted">Elaboró</dt><dd class="col-7">{{ $orden->creadoPor?->name ?? '—' }}</dd>
                </dl>
            </div>
        </div>
    </div>
</div>
@endsection
