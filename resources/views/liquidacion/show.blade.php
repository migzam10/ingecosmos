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
       class="btn btn-outline-secondary btn-sm"><x-icon name="arrow-left" /> Volver</a>
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
                                               min="0" step="1" style="width:90px">
                                    </div>
                                    <button class="btn btn-sm btn-outline-secondary"><x-icon name="check" /></button>
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
                @error('deducciones')
                <div class="alert alert-danger py-2 small mb-2">{{ $message }}</div>
                @enderror
                <form method="POST" action="{{ route('liquidacion.avance', $data['tecnico']) }}" id="form-avance">
                    @csrf
                    <input type="hidden" name="mes"  value="{{ $mes }}">
                    <input type="hidden" name="anio" value="{{ $anio }}">

                    <div class="mb-2">
                        <label class="form-label small">Tipo de pago</label>
                        <select name="tipo" class="form-select form-select-sm">
                            <option value="ABONO" {{ old('tipo') === 'ABONO' ? 'selected' : '' }}>Abono inicial</option>
                            <option value="PAGO_FINAL" {{ old('tipo') === 'PAGO_FINAL' ? 'selected' : '' }}>Pago final</option>
                        </select>
                    </div>
                    <div class="mb-2">
                        <label class="form-label small">Fecha del pago</label>
                        <input type="date" name="fecha_pago" class="form-control form-control-sm"
                               value="{{ old('fecha_pago', now()->toDateString()) }}"
                               max="{{ now()->toDateString() }}" required>
                    </div>
                    <div class="mb-2">
                        <label class="form-label small">Monto</label>
                        <div class="input-group input-group-sm">
                            <span class="input-group-text">$</span>
                            <input type="number" name="monto" id="inp-monto" class="form-control"
                                   min="1" step="1"
                                   value="{{ old('monto', $data['saldo'] > 0 ? (int) $data['saldo'] : '') }}"
                                   placeholder="Monto a pagar" required>
                        </div>
                    </div>

                    {{-- Deducciones (se restan del valor de liquidación) --}}
                    <div class="border rounded p-2 mb-3 bg-light-subtle">
                        <div class="small text-muted mb-2">Estos valores se deben restar del valor de liquidación</div>
                        @foreach(\App\Models\PagoTecnico::DEDUCCIONES as $col => $label)
                        <div class="row g-1 align-items-center mb-1">
                            <label class="col-6 form-label small mb-0">{{ $label }}</label>
                            <div class="col-6">
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text">$</span>
                                    <input type="number" name="{{ $col }}" class="form-control text-end inp-ded"
                                           min="0" step="1" value="{{ old($col, 0) }}">
                                </div>
                            </div>
                        </div>
                        @endforeach
                        <div class="d-flex justify-content-between small mt-2 pt-1 border-top">
                            <span class="text-muted">Total deducciones</span>
                            <span class="fw-bold" id="res-ded">$ 0</span>
                        </div>
                        <div class="small text-danger mt-1" id="msg-ded" style="display:none">
                            La suma de deducciones no puede superar el monto.
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small">Concepto (opcional)</label>
                        <input type="text" name="concepto" class="form-control form-control-sm"
                               value="{{ old('concepto') }}" placeholder="Ej: Quincena mayo...">
                    </div>
                    <button class="btn btn-success w-100 btn-sm" id="btn-avance">Registrar pago</button>
                </form>
            </div>
        </div>

        {{-- Resumen de liquidación (con deducciones) --}}
        <div class="card mb-3">
            <div class="card-header"><h3 class="card-title">Resumen del mes</h3></div>
            <div class="card-body p-0">
                <table class="table table-sm mb-0">
                    <tr>
                        <td class="text-muted">Total mano de obra</td>
                        <td class="text-end fw-bold">$ {{ number_format($data['total_ganado'], 0, ',', '.') }}</td>
                    </tr>
                    <tr>
                        <td class="text-muted">Pagos (bruto)</td>
                        <td class="text-end">- $ {{ number_format($data['total_avances'], 0, ',', '.') }}</td>
                    </tr>
                    @if($data['total_deducciones'] > 0)
                    @foreach($data['deducciones'] as $col => $monto)
                    @if($monto > 0)
                    <tr>
                        <td class="text-muted small ps-4">{{ \App\Models\PagoTecnico::DEDUCCIONES[$col] }}</td>
                        <td class="text-end small text-muted">- $ {{ number_format($monto, 0, ',', '.') }}</td>
                    </tr>
                    @endif
                    @endforeach
                    <tr>
                        <td class="small ps-4 fst-italic">Neto entregado al técnico</td>
                        <td class="text-end small fst-italic">$ {{ number_format($data['total_neto'], 0, ',', '.') }}</td>
                    </tr>
                    @endif
                    <tr class="table-active">
                        <td class="fw-bold">Saldo a pagar</td>
                        <td class="text-end fw-bold {{ $data['saldo'] > 0 ? 'text-danger' : 'text-success' }}">
                            $ {{ number_format($data['saldo'], 0, ',', '.') }}
                        </td>
                    </tr>
                </table>
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
                            <td class="text-muted small">
                                {{ ($pago->fecha_pago ?? $pago->created_at)->format('d/m/Y') }}
                            </td>
                            <td>
                                <div class="d-flex gap-1">
                                <a href="{{ route('pagos.pdf', $pago) }}" target="_blank"
                                   class="btn btn-xs btn-outline-secondary py-0 px-1" title="Recibo PDF">PDF</a>
                                <form method="POST" action="{{ route('pagos.eliminar', $pago) }}"
                                      data-confirm="¿Eliminar este pago de $ {{ number_format($pago->monto, 0, ',', '.') }}?">
                                    @csrf @method('DELETE')
                                    <button class="btn btn-sm btn-ghost-danger py-0 px-1"><x-icon name="x" /></button>
                                </form>
                                </div>
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

@push('scripts')
<script>
// Deducciones: sumar en vivo, autocalcular Ahorro 1 (10% del monto) y no permitir que superen el pago.
(function () {
    const monto = document.getElementById('inp-monto');
    const deds  = document.querySelectorAll('.inp-ded');
    const resEl = document.getElementById('res-ded');
    const msgEl = document.getElementById('msg-ded');
    const btn   = document.getElementById('btn-avance');
    const ahorro1 = document.querySelector('[name="ded_ahorro_1"]');
    if (!monto || !btn) return;

    // Ahorro 1 se autocalcula (10% del monto) mientras el usuario no lo edite.
    // En un reenvío fallido se respeta lo que ya venía escrito.
    let ahorro1Auto = {{ old('monto') === null ? 'true' : 'false' }};
    if (ahorro1) ahorro1.addEventListener('input', () => { ahorro1Auto = false; });

    function aplicarAhorro10() {
        if (!ahorro1Auto || !ahorro1) return;
        ahorro1.value = Math.round((parseFloat(monto.value) || 0) * 0.10);
    }

    const cop = n => new Intl.NumberFormat('es-CO', {style:'currency',currency:'COP',minimumFractionDigits:0,maximumFractionDigits:0}).format(n||0);

    function recalc() {
        let total = 0;
        deds.forEach(i => total += parseFloat(i.value) || 0);
        resEl.textContent = cop(total);
        const m = parseFloat(monto.value) || 0;
        const excede = total > m;
        msgEl.style.display = excede ? '' : 'none';
        btn.disabled = excede;
    }
    deds.forEach(i => i.addEventListener('input', recalc));
    monto.addEventListener('input', () => { aplicarAhorro10(); recalc(); });

    aplicarAhorro10(); // 10% inicial sobre el monto precargado
    recalc();
})();
</script>
@endpush
