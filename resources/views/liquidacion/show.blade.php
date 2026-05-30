@extends('layouts.app')

@section('title', 'Liquidación — ' . $data['tecnico']->nombre)
@section('page_title', 'Liquidación: ' . $data['tecnico']->nombre)
@section('breadcrumb', 'Liquidación')

@section('page_actions')
<div class="d-flex gap-2 align-items-center">
    {{-- Selector de mes --}}
    <form method="GET" class="d-flex gap-2">
        <select name="mes" class="form-select form-select-sm" style="width:auto">
            @foreach(['1'=>'Enero','2'=>'Febrero','3'=>'Marzo','4'=>'Abril','5'=>'Mayo','6'=>'Junio',
                      '7'=>'Julio','8'=>'Agosto','9'=>'Septiembre','10'=>'Octubre','11'=>'Noviembre','12'=>'Diciembre'] as $n => $nombre)
            <option value="{{ $n }}" {{ $mes == $n ? 'selected' : '' }}>{{ $nombre }}</option>
            @endforeach
        </select>
        <select name="anio" class="form-select form-select-sm" style="width:auto">
            @foreach(range(now()->year, 2024, -1) as $a)
            <option value="{{ $a }}" {{ $anio == $a ? 'selected' : '' }}>{{ $a }}</option>
            @endforeach
        </select>
        <button class="btn btn-secondary btn-sm">Ver</button>
    </form>
    @if($data['total_ganado'] > 0)
    <a href="{{ route('liquidacion.pdf', [$data['tecnico'], 'mes' => $mes, 'anio' => $anio]) }}"
       class="btn btn-primary btn-sm" target="_blank">Generar Recibo PDF</a>
    @endif
    <a href="{{ route('liquidacion.index', ['mes' => $mes, 'anio' => $anio]) }}"
       class="btn btn-outline-secondary btn-sm">← Volver</a>
</div>
@endsection

@section('content')
<div class="row g-3">

    {{-- Resumen --}}
    <div class="col-12">
        <div class="row g-3">
            <div class="col-6 col-md-3">
                <div class="card kpi-card text-center">
                    <div class="card-body py-3">
                        <div class="kpi-value text-primary">{{ $data['trabajos']->count() }}</div>
                        <div class="kpi-label mt-1">Órdenes trabajadas</div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="card kpi-card text-center">
                    <div class="card-body py-3">
                        <div class="kpi-value text-info">$ {{ number_format($data['total_ganado'], 0, ',', '.') }}</div>
                        <div class="kpi-label mt-1">Total a pagar</div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="card kpi-card text-center">
                    <div class="card-body py-3">
                        <div class="kpi-value text-warning">$ {{ number_format($data['total_avances'], 0, ',', '.') }}</div>
                        <div class="kpi-label mt-1">Avances pagados</div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="card kpi-card text-center">
                    <div class="card-body py-3">
                        <div class="kpi-value {{ $data['saldo'] > 0 ? 'text-danger' : 'text-success' }}">
                            $ {{ number_format($data['saldo'], 0, ',', '.') }}
                        </div>
                        <div class="kpi-label mt-1">Saldo pendiente</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Detalle por OT --}}
    <div class="col-12 col-lg-8">
        <div class="card">
            <div class="card-header"><h3 class="card-title">Órdenes del período</h3></div>
            <div class="table-responsive">
                <table class="table table-sm table-vcenter mb-0">
                    <thead>
                        <tr>
                            <th># Orden</th>
                            <th>Placa / Vehículo</th>
                            <th>Función</th>
                            <th>Estado trabajo</th>
                            <th class="text-end">Valor a liquidar</th>
                            <th style="width:130px"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @php
                        $nombresEsp = ['LAT'=>'Latonero','PREP'=>'Preparador','PINT'=>'Pintor',
                                       'MEC'=>'Mecánico','ELEC'=>'Electricista','AA'=>'Aire Acondicionado','SCANNER'=>'Diagnóstico'];
                        @endphp
                        @forelse($data['trabajos'] as $trabajo)
                        <tr>
                            <td class="fw-bold">{{ $trabajo->ot->numero_ot }}</td>
                            <td class="small">
                                <span class="badge bg-blue-lt">{{ $trabajo->ot->vehiculo->placa }}</span>
                                <span class="text-muted ms-1">{{ $trabajo->ot->vehiculo->marca->nombre }}</span>
                            </td>
                            <td class="small">{{ $nombresEsp[$trabajo->especialidad] ?? $trabajo->especialidad }}</td>
                            <td>
                                @if($trabajo->estado === 'FINALIZADO')
                                <span class="badge bg-success-lt">Finalizado</span>
                                @elseif($trabajo->estado === 'EN_PROCESO')
                                <span class="badge bg-warning-lt">En proceso</span>
                                @else
                                <span class="badge bg-secondary-lt">Pendiente</span>
                                @endif
                            </td>
                            <td class="text-end fw-bold">
                                $ {{ number_format($trabajo->valor_liquidar, 0, ',', '.') }}
                            </td>
                            <td>
                                <form method="POST" action="{{ route('trabajos.valor', $trabajo) }}"
                                      class="d-flex gap-1">
                                    @csrf
                                    <div class="input-group input-group-sm">
                                        <span class="input-group-text">$</span>
                                        <input type="number" name="valor_liquidar"
                                               class="form-control text-end"
                                               value="{{ $trabajo->valor_liquidar }}"
                                               min="0" step="1000" style="width:90px">
                                    </div>
                                    <button class="btn btn-sm btn-outline-secondary">✓</button>
                                </form>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6" class="text-center text-muted py-3">
                                Sin órdenes registradas para este período.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- Panel de pagos --}}
    <div class="col-12 col-lg-4">

        {{-- Registrar avance --}}
        <div class="card mb-3">
            <div class="card-header"><h3 class="card-title">Registrar Pago / Avance</h3></div>
            <div class="card-body">
                <form method="POST" action="{{ route('liquidacion.avance', $data['tecnico']) }}">
                    @csrf
                    <input type="hidden" name="mes"  value="{{ $mes }}">
                    <input type="hidden" name="anio" value="{{ $anio }}">

                    <div class="mb-2">
                        <label class="form-label small">Tipo de pago</label>
                        <select name="tipo" class="form-select form-select-sm">
                            <option value="ABONO">Abono</option>
                            <option value="ANTICIPO">Anticipo</option>
                            <option value="PAGO_FINAL">Pago final</option>
                        </select>
                    </div>
                    <div class="mb-2">
                        <label class="form-label small">Monto</label>
                        <div class="input-group input-group-sm">
                            <span class="input-group-text">$</span>
                            <input type="number" name="monto" class="form-control"
                                   min="1" step="1000" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small">Concepto (opcional)</label>
                        <input type="text" name="concepto" class="form-control form-control-sm"
                               placeholder="Ej: Quincena mayo...">
                    </div>
                    <button class="btn btn-success w-100 btn-sm">Registrar pago</button>
                </form>
            </div>
        </div>

        {{-- Historial de pagos --}}
        <div class="card">
            <div class="card-header"><h3 class="card-title">Pagos del mes</h3></div>
            <div class="card-body p-0">
                <table class="table table-sm mb-0">
                    <thead>
                        <tr><th>Tipo</th><th class="text-end">Monto</th><th>Fecha</th><th></th></tr>
                    </thead>
                    <tbody>
                        @forelse($data['avances'] as $pago)
                        <tr>
                            <td class="small">
                                {{ ['ABONO'=>'Abono','ANTICIPO'=>'Anticipo','PAGO_FINAL'=>'Pago final'][$pago->tipo] ?? $pago->tipo }}
                                @if($pago->concepto)
                                <br><span class="text-muted">{{ $pago->concepto }}</span>
                                @endif
                            </td>
                            <td class="text-end fw-bold">$ {{ number_format($pago->monto, 0, ',', '.') }}</td>
                            <td class="text-muted small">{{ $pago->created_at->format('d/m') }}</td>
                            <td>
                                <form method="POST" action="{{ route('pagos.eliminar', $pago) }}"
                                      data-confirm="¿Eliminar este pago de $ {{ number_format($pago->monto, 0, ',', '.') }}?">
                                    @csrf @method('DELETE')
                                    <button class="btn btn-sm btn-ghost-danger py-0 px-1">✕</button>
                                </form>
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="4" class="text-center text-muted py-2">Sin pagos registrados.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

    </div>
</div>
@endsection
