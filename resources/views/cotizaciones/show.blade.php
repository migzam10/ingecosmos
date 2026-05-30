@extends('layouts.app')

@section('title', 'Cotización #' . $cotizacion->numero_cot)
@section('page_title', 'Cotización #' . $cotizacion->numero_cot)
@section('breadcrumb', 'Cotizaciones')

@section('page_actions')
<div class="d-flex gap-2">
    <a href="{{ route('cotizaciones.pdf', $cotizacion) }}" class="btn btn-primary btn-sm" target="_blank">
        Descargar PDF
    </a>
    <a href="{{ route('ordenes.show', $cotizacion->ot) }}" class="btn btn-outline-secondary btn-sm">
        Ver OT #{{ $cotizacion->ot->numero_ot }}
    </a>
</div>
@endsection

@section('content')
<div class="row g-3">

    <div class="col-12 col-md-8">

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

        {{-- Suministros --}}
        @if($cotizacion->itemsSuministro->count())
        <div class="card mb-3">
            <div class="card-header"><h3 class="card-title">Insumos de Pintura</h3></div>
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
        <div class="card">
            <div class="card-header"><h3 class="card-title">Resumen</h3></div>
            <div class="card-body">
                <table class="table table-sm mb-0">
                    <tr><td class="text-muted">Mano de Obra</td><td class="text-end">$ {{ number_format($cotizacion->subtotal_mo, 0, ',', '.') }}</td></tr>
                    <tr><td class="text-muted">Insumos Pintura</td><td class="text-end">$ {{ number_format($cotizacion->subtotal_suministros, 0, ',', '.') }}</td></tr>
                    @if($cotizacion->subtotal_rto > 0)
                    <tr><td class="text-muted">Repuestos</td><td class="text-end">$ {{ number_format($cotizacion->subtotal_rto, 0, ',', '.') }}</td></tr>
                    @endif
                    @if($cotizacion->subtotal_terceros > 0)
                    <tr><td class="text-muted">Trabajos subcontratados</td><td class="text-end">$ {{ number_format($cotizacion->subtotal_terceros, 0, ',', '.') }}</td></tr>
                    @endif
                    @if($cotizacion->subtotal_op > 0)
                    <tr><td class="text-muted">Otros gastos</td><td class="text-end">$ {{ number_format($cotizacion->subtotal_op, 0, ',', '.') }}</td></tr>
                    @endif
                    <tr class="table-active"><td class="fw-bold">TOTAL</td><td class="text-end fw-bold fs-5">$ {{ number_format($cotizacion->total, 0, ',', '.') }}</td></tr>
                </table>
                <hr>
                <div class="row text-center">
                    <div class="col"><div class="text-muted small">Horas artesano</div><strong>{{ $cotizacion->ot->ha ?? '—' }}</strong></div>
                    <div class="col"><div class="text-muted small">Días estimados</div><strong>{{ $cotizacion->ot->dr ?? '—' }}</strong></div>
                    <div class="col"><div class="text-muted small">Tamaño daño</div><x-tg-badge :tg="$cotizacion->ot->tg" /></div>
                </div>
                @if($cotizacion->observaciones)
                <div class="mt-3 small text-muted">{{ $cotizacion->observaciones }}</div>
                @endif
            </div>
        </div>
    </div>

</div>
@endsection
