@extends('layouts.app')

@section('title', 'Cotización #' . $cotizacion->numero_cot)
@php
$badgeEstadoCot = match($cotizacion->estado) {
    'AUTORIZADA' => '<span class="badge bg-success ms-2">Autorizada</span>',
    'RECHAZADA'  => '<span class="badge bg-danger ms-2">Rechazada</span>',
    default      => '<span class="badge bg-secondary ms-2">Borrador</span>',
};
$esPrevia = $cotizacion->es_previa;
@endphp
@section('page_title')
Cotización #{{ $cotizacion->numero_cot }} {!! $badgeEstadoCot !!}
@if($esPrevia) <span class="badge bg-warning text-dark ms-2">Previa (sin OT)</span> @endif
@endsection
@section('breadcrumb', 'Cotizaciones')

@section('page_actions')
<div class="d-flex gap-2 flex-wrap">
    <a href="{{ route('cotizaciones.pdf', $cotizacion) }}" class="btn btn-primary btn-sm" target="_blank">
        Descargar PDF
    </a>
    @if($cotizacion->estado === 'BORRADOR')
    <a href="{{ route('cotizaciones.edit', $cotizacion) }}" class="btn btn-outline-warning btn-sm">
        Editar
    </a>
    <form method="POST"
          action="{{ route('cotizaciones.destroy', $cotizacion) }}"
          data-confirm="¿Eliminar la cotización #{{ $cotizacion->numero_cot }}?{{ $esPrevia ? '' : ' La OT regresará a Pendiente de Cotización.' }}">
        @csrf @method('DELETE')
        <button class="btn btn-outline-danger btn-sm">Eliminar</button>
    </form>
    @endif
    @if(!$esPrevia && $cotizacion->ot)
    <a href="{{ route('ordenes.show', $cotizacion->ot) }}" class="btn btn-outline-secondary btn-sm">
        Ver OT #{{ $cotizacion->ot->numero_ot }}
    </a>
    @endif
</div>
@endsection

@section('content')

{{-- Banner de vincular OT (solo previa BORRADOR) --}}
@if($esPrevia && $cotizacion->estado === 'BORRADOR')
<div class="alert alert-warning mb-3">
    <div class="d-flex flex-wrap align-items-center gap-3">
        <div class="flex-fill">
            <strong>Cotización previa sin OT.</strong>
            Cuando el vehículo ingrese al taller, vincúlala a la OT correspondiente.
        </div>
        <form method="POST" action="{{ route('cotizaciones.vincular-ot', $cotizacion) }}" class="d-flex gap-2 align-items-center">
            @csrf
            <input type="text" name="numero_ot" inputmode="numeric"
                   class="form-control form-control-sm @error('numero_ot') is-invalid @enderror"
                   style="width:120px" placeholder="# OT" required>
            <button type="submit" class="btn btn-warning btn-sm">Vincular OT</button>
        </form>
    </div>
    @error('numero_ot')<div class="mt-1 small text-danger">{{ $message }}</div>@enderror
</div>
@endif

<div class="row g-3">
    <div class="col-12 col-md-8">

        {{-- Datos vehículo (previa) --}}
        @if($esPrevia)
        <div class="card mb-3">
            <div class="card-header"><h3 class="card-title">Datos del Vehículo / Cliente</h3></div>
            <div class="card-body">
                <div class="row g-2">
                    <div class="col-6 col-md-3">
                        <div class="text-muted small">Placa</div>
                        <strong>{{ $cotizacion->placa_previa ?? '—' }}</strong>
                    </div>
                    @if($cotizacion->marcaPrevia)
                    <div class="col-6 col-md-3">
                        <div class="text-muted small">Marca</div>
                        <strong>{{ $cotizacion->marcaPrevia->nombre }}</strong>
                    </div>
                    @endif
                    @if($cotizacion->modeloPrevia)
                    <div class="col-6 col-md-3">
                        <div class="text-muted small">Modelo</div>
                        <strong>{{ $cotizacion->modeloPrevia->nombre }}</strong>
                    </div>
                    @endif
                    @if($cotizacion->clientePrevia)
                    <div class="col-12 col-md-6">
                        <div class="text-muted small">Cliente</div>
                        <strong>{{ $cotizacion->clientePrevia->nombre }}</strong>
                        @if($cotizacion->clientePrevia->cedula)
                        <span class="text-muted small"> — {{ $cotizacion->clientePrevia->cedula }}</span>
                        @endif
                        @if($cotizacion->clientePrevia->telefono)
                        <span class="text-muted small"> · {{ $cotizacion->clientePrevia->telefono }}</span>
                        @endif
                    </div>
                    @endif
                    @if($cotizacion->descripcion_previa)
                    <div class="col-12">
                        <div class="text-muted small">Descripción</div>
                        <span>{{ $cotizacion->descripcion_previa }}</span>
                    </div>
                    @endif
                </div>
            </div>
        </div>
        @endif

        {{-- MO --}}
        @if($cotizacion->itemsMo->count())
        <div class="card mb-3">
            <div class="card-header"><h3 class="card-title">Mano de Obra</h3></div>
            <div class="table-responsive">
                <table class="table table-sm mb-0">
                    <thead><tr><th>Descripción</th><th class="text-end">Precio</th></tr></thead>
                    <tbody>
                        @foreach($cotizacion->itemsMo as $item)
                        <tr>
                            <td>{{ $item->descripcion }}</td>
                            <td class="text-end">$ {{ number_format($item->precio, 0, ',', '.') }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                    <tfoot class="table-light">
                        <tr>
                            <td class="text-end fw-bold">Subtotal MO</td>
                            <td class="text-end fw-bold">$ {{ number_format($cotizacion->subtotal_mo, 0, ',', '.') }}</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
        @endif

        {{-- Repuestos --}}
        @if($cotizacion->itemsRepuesto->count())
        <div class="card mb-3">
            <div class="card-header"><h3 class="card-title">Repuestos</h3></div>
            <div class="table-responsive">
                <table class="table table-sm mb-0">
                    <thead>
                        <tr>
                            <th>Descripción</th>
                            <th class="text-end" style="width:80px">Und</th>
                            <th class="text-end" style="width:120px">P. Unitario</th>
                            <th class="text-end" style="width:120px">Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($cotizacion->itemsRepuesto as $item)
                        <tr>
                            <td>{{ $item->descripcion }}</td>
                            <td class="text-end">{{ number_format($item->unidades, 2) }}</td>
                            <td class="text-end">$ {{ number_format($item->precio_unitario, 0, ',', '.') }}</td>
                            <td class="text-end">$ {{ number_format($item->precio_total, 0, ',', '.') }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                    <tfoot class="table-light">
                        <tr>
                            <td colspan="3" class="text-end fw-bold">Subtotal Repuestos</td>
                            <td class="text-end fw-bold">$ {{ number_format($cotizacion->subtotal_rto, 0, ',', '.') }}</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
        @endif

        {{-- Insumos de Pintura (Fase 3) --}}
        @if($cotizacion->itemsInsumo->count())
        <div class="card mb-3">
            <div class="card-header"><h3 class="card-title">Insumos de Pintura</h3></div>
            <div class="table-responsive">
                <table class="table table-sm mb-0">
                    <thead>
                        <tr>
                            <th>Descripción</th>
                            <th class="text-end" style="width:80px">Cantidad</th>
                            <th class="text-center" style="width:80px">Unidad</th>
                            <th class="text-end" style="width:120px">P. Venta</th>
                            <th class="text-end" style="width:120px">Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($cotizacion->itemsInsumo as $item)
                        <tr>
                            <td>{{ $item->descripcion }}</td>
                            <td class="text-end">{{ number_format($item->cantidad_solicitada, 2) }}</td>
                            <td class="text-center text-muted small">{{ $item->insumo?->unidad_medida }}</td>
                            <td class="text-end">$ {{ number_format($item->precio_venta, 0, ',', '.') }}</td>
                            <td class="text-end">$ {{ number_format($item->precio_total, 0, ',', '.') }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                    <tfoot class="table-light">
                        <tr>
                            <td colspan="4" class="text-end fw-bold">Subtotal Insumos</td>
                            <td class="text-end fw-bold">$ {{ number_format($cotizacion->subtotal_insumos, 0, ',', '.') }}</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
        @endif

        {{-- Suministros (legado) --}}
        @if($cotizacion->itemsSuministro->count())
        <div class="card mb-3">
            <div class="card-header"><h3 class="card-title">Insumos de Pintura <span class="badge bg-secondary ms-1 fs-6">Histórico</span></h3></div>
            <div class="table-responsive">
                <table class="table table-sm mb-0">
                    <thead><tr><th>Descripción</th><th class="text-end">Costo</th><th class="text-end">Precio</th></tr></thead>
                    <tbody>
                        @foreach($cotizacion->itemsSuministro as $item)
                        <tr>
                            <td>{{ $item->descripcion }}</td>
                            <td class="text-end text-muted">$ {{ number_format($item->costo, 0, ',', '.') }}</td>
                            <td class="text-end">$ {{ number_format($item->precio, 0, ',', '.') }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                    <tfoot class="table-light">
                        <tr>
                            <td colspan="2" class="text-end fw-bold">Subtotal Insumos</td>
                            <td class="text-end fw-bold">$ {{ number_format($cotizacion->subtotal_suministros, 0, ',', '.') }}</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
        @endif

    </div>

    <div class="col-12 col-md-4">
        <div class="card mb-3">
            <div class="card-header"><h3 class="card-title">Resumen</h3></div>
            <div class="card-body">
                @php
                    $subtotalNeto = $cotizacion->subtotal_mo + $cotizacion->subtotal_rto
                                 + $cotizacion->subtotal_insumos + $cotizacion->subtotal_suministros;
                    $ivaVal = $cotizacion->iva_valor ?? 0;
                @endphp
                <table class="table table-sm mb-0">
                    @if($cotizacion->subtotal_mo > 0)
                    <tr><td class="text-muted">Mano de Obra</td><td class="text-end">$ {{ number_format($cotizacion->subtotal_mo, 0, ',', '.') }}</td></tr>
                    @endif
                    @if($cotizacion->subtotal_rto > 0)
                    <tr><td class="text-muted">Repuestos</td><td class="text-end">$ {{ number_format($cotizacion->subtotal_rto, 0, ',', '.') }}</td></tr>
                    @endif
                    @if($cotizacion->subtotal_insumos > 0)
                    <tr><td class="text-muted">Insumos Pintura</td><td class="text-end">$ {{ number_format($cotizacion->subtotal_insumos, 0, ',', '.') }}</td></tr>
                    @endif
                    @if($cotizacion->subtotal_suministros > 0)
                    <tr><td class="text-muted">Insumos (legado)</td><td class="text-end">$ {{ number_format($cotizacion->subtotal_suministros, 0, ',', '.') }}</td></tr>
                    @endif
                    @if($cotizacion->subtotal_terceros > 0)
                    <tr><td class="text-muted">Subcontratados</td><td class="text-end">$ {{ number_format($cotizacion->subtotal_terceros, 0, ',', '.') }}</td></tr>
                    @endif
                    @if($cotizacion->subtotal_op > 0)
                    <tr><td class="text-muted">Otros gastos</td><td class="text-end">$ {{ number_format($cotizacion->subtotal_op, 0, ',', '.') }}</td></tr>
                    @endif
                    <tr class="table-light">
                        <td class="fw-bold small">Subtotal neto</td>
                        <td class="text-end fw-bold">$ {{ number_format($subtotalNeto, 0, ',', '.') }}</td>
                    </tr>
                    @if($ivaVal > 0)
                    <tr>
                        <td class="text-muted">IVA{{ $cotizacion->iva_porcentaje > 0 ? ' ' . number_format($cotizacion->iva_porcentaje, 0) . '%' : '' }}</td>
                        <td class="text-end">$ {{ number_format($ivaVal, 0, ',', '.') }}</td>
                    </tr>
                    @endif
                    <tr class="table-active">
                        <td class="fw-bold">TOTAL</td>
                        <td class="text-end fw-bold fs-5">$ {{ number_format($cotizacion->total, 0, ',', '.') }}</td>
                    </tr>
                </table>

                @if(!$esPrevia && $cotizacion->ot)
                <hr>
                <div class="row text-center">
                    <div class="col"><div class="text-muted small">Horas artesano</div><strong>{{ $cotizacion->ot->ha ?? '—' }}</strong></div>
                    <div class="col"><div class="text-muted small">Días estimados</div><strong>{{ $cotizacion->ot->dr ?? '—' }}</strong></div>
                    <div class="col"><div class="text-muted small">Tamaño daño</div><x-tg-badge :tg="$cotizacion->ot->tg" /></div>
                </div>
                @endif

                @if($cotizacion->observaciones)
                <div class="mt-3 small text-muted border-top pt-2">{{ $cotizacion->observaciones }}</div>
                @endif
            </div>
        </div>

        @if(!$esPrevia && $cotizacion->ot)
        <div class="card">
            <div class="card-header"><h3 class="card-title">Vehículo</h3></div>
            <div class="card-body small">
                <div><span class="text-muted">Placa:</span> <strong>{{ $cotizacion->ot->vehiculo->placa }}</strong></div>
                <div><span class="text-muted">Marca/Modelo:</span> {{ $cotizacion->ot->vehiculo->marca->nombre }} {{ $cotizacion->ot->vehiculo->modelo?->nombre }}</div>
                @if($cotizacion->ot->empresaCliente)
                <div><span class="text-muted">CIA/Empresa:</span> {{ $cotizacion->ot->empresaCliente->nombre }}</div>
                @endif
                @if($cotizacion->ot->clientePersona)
                <div><span class="text-muted">Propietario:</span> {{ $cotizacion->ot->clientePersona->nombre }}</div>
                @endif
            </div>
        </div>
        @endif
    </div>
</div>
@endsection
