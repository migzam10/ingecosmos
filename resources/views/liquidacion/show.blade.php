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
                            <th style="width:36px" class="text-center">
                                <input type="checkbox" id="chk-sel-todas" class="form-check-input" title="Seleccionar todas las OTs">
                            </th>
                            <th># Orden</th>
                            <th>Placa / Vehículo</th>
                            <th>Función</th>
                            <th>Inició el</th>
                            <th>{{ ($anio < 2026 || ($anio == 2026 && $mes < 9)) ? 'Asignado el' : 'Terminado el' }}</th>
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
                            <td class="text-center">
                                @if(!$trabajo->liquidado)
                                <input type="checkbox" class="form-check-input chk-ot"
                                       value="{{ $trabajo->ot->numero_ot }}"
                                       title="Seleccionar OT {{ $trabajo->ot->numero_ot }}">
                                @endif
                            </td>
                            <td class="fw-bold">{{ $trabajo->ot->numero_ot }}</td>
                            <td class="small">
                                <span class="badge bg-blue-lt">{{ $trabajo->ot->vehiculo->placa }}</span>
                                <span class="text-muted ms-1">{{ $trabajo->ot->vehiculo->marca->nombre }}</span>
                            </td>
                            <td class="small">{{ $nombresEsp[$trabajo->especialidad] ?? $trabajo->especialidad }}</td>
                            <td class="small text-muted">
                                {{ $trabajo->inicio_en ? $trabajo->inicio_en->format('d/m/Y') : '—' }}
                            </td>
                            <td class="small text-muted">
                                @if($anio < 2026 || ($anio == 2026 && $mes < 9))
                                    {{ $trabajo->fecha_asignacion ? $trabajo->fecha_asignacion->format('d/m/Y') : '—' }}
                                @else
                                    {{ $trabajo->fin_en ? $trabajo->fin_en->format('d/m/Y') : '—' }}
                                @endif
                            </td>
                            <td>
                                @if($trabajo->liquidado)
                                <span class="badge bg-purple-lt">Liquidado</span>
                                @elseif($trabajo->estado === 'FINALIZADO')
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
                                @if($trabajo->liquidado)
                                <span class="text-muted small fst-italic">Cerrado</span>
                                @else
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
                                @endif
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="8" class="text-center text-muted py-3">
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
                        <div class="border-top mt-2 pt-2">
                            <div class="d-flex justify-content-between small mb-1">
                                <span class="text-muted">Monto del pago:</span>
                                <span class="fw-medium" id="res-monto">$ 0</span>
                            </div>
                            <div class="d-flex justify-content-between small mb-1">
                                <span class="text-muted">Valor deducciones:</span>
                                <span class="text-danger fw-medium" id="res-ded">$ 0</span>
                            </div>
                            <div class="d-flex justify-content-between small pt-1 border-top">
                                <span class="fw-bold text-dark">Recibe el técnico en sí:</span>
                                <span class="fw-bold text-success fs-5" id="res-neto">$ 0</span>
                            </div>
                        </div>
                        <div class="small text-danger mt-2 alert alert-danger py-1 px-2 mb-0" id="msg-ded" style="display:none">
                            La suma de deducciones no puede superar el monto.
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small">Concepto (opcional)</label>
                        <input type="text" name="concepto" id="inp-concepto" class="form-control form-control-sm"
                               value="{{ old('concepto') }}" placeholder="Ej: Quincena mayo o selecciona OTs...">
                        <div class="form-text small text-muted">Puedes escribir texto libre o marcar las casillas de las OTs en la tabla.</div>
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
                        <td class="small ps-4 fst-italic">Valor pago</td>
                        <td class="text-end small fst-italic">$ {{ number_format($data['total_neto'], 0, ',', '.') }}</td>
                    </tr>
                    @endif
                    <tr class="table-active">
                        <td class="fw-bold">Saldo pendiente</td>
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
(function () {
    const monto    = document.getElementById('inp-monto');
    const deds     = document.querySelectorAll('.inp-ded');
    const resMonto = document.getElementById('res-monto');
    const resDed   = document.getElementById('res-ded');
    const resNeto  = document.getElementById('res-neto');
    const msgEl    = document.getElementById('msg-ded');
    const btn      = document.getElementById('btn-avance');
    const ahorro1  = document.querySelector('[name="ded_ahorro_1"]');
    const concepto = document.getElementById('inp-concepto');
    const chkOts   = document.querySelectorAll('.chk-ot');
    const chkTodas = document.getElementById('chk-sel-todas');

    if (!monto || !btn) return;

    // Ahorro 1 se autocalcula (10% del monto) mientras el usuario no lo edite manualmente
    let ahorro1Auto = {{ old('monto') === null ? 'true' : 'false' }};
    if (ahorro1) ahorro1.addEventListener('input', () => { ahorro1Auto = false; });

    function aplicarAhorro10() {
        if (!ahorro1Auto || !ahorro1) return;
        ahorro1.value = Math.round((parseFloat(monto.value) || 0) * 0.10);
    }

    const cop = n => new Intl.NumberFormat('es-CO', {style:'currency',currency:'COP',minimumFractionDigits:0,maximumFractionDigits:0}).format(n||0);

    function recalc() {
        let totalDed = 0;
        deds.forEach(i => totalDed += parseFloat(i.value) || 0);
        const m = parseFloat(monto.value) || 0;
        const neto = Math.max(0, m - totalDed);

        if (resMonto) resMonto.textContent = cop(m);
        if (resDed)   resDed.textContent   = '- ' + cop(totalDed);
        if (resNeto)  resNeto.textContent  = cop(neto);

        const excede = totalDed > m;
        if (msgEl) msgEl.style.display = excede ? '' : 'none';
        btn.disabled = excede;
    }

    deds.forEach(i => i.addEventListener('input', recalc));
    monto.addEventListener('input', () => { aplicarAhorro10(); recalc(); });

    aplicarAhorro10();
    recalc();

    // ── Selección de OTs para el campo Concepto ──
    let lastOtString = '';

    function actualizarConceptoDesdeOts() {
        if (!concepto) return;
        const seleccionadas = Array.from(chkOts)
            .filter(c => c.checked)
            .map(c => c.value);

        const nuevaCadenaOts = seleccionadas.join(' - ');

        let actual = concepto.value;
        if (lastOtString && actual.includes(lastOtString)) {
            // Reemplazar la cadena de OTs anterior manteniendo cualquier texto extra escrito por el usuario
            concepto.value = actual.replace(lastOtString, nuevaCadenaOts).trim();
            if (concepto.value === '-') concepto.value = '';
        } else if (nuevaCadenaOts) {
            if (!actual.trim()) {
                concepto.value = nuevaCadenaOts;
            } else {
                concepto.value = actual.trim() + ' - ' + nuevaCadenaOts;
            }
        }

        lastOtString = nuevaCadenaOts;

        // Actualizar estado de "seleccionar todas"
        if (chkTodas) {
            chkTodas.checked = chkOts.length > 0 && seleccionadas.length === chkOts.length;
            chkTodas.indeterminate = seleccionadas.length > 0 && seleccionadas.length < chkOts.length;
        }
    }

    chkOts.forEach(c => c.addEventListener('change', actualizarConceptoDesdeOts));

    if (chkTodas) {
        chkTodas.addEventListener('change', function () {
            chkOts.forEach(c => c.checked = chkTodas.checked);
            actualizarConceptoDesdeOts();
        });
    }
})();
</script>
@endpush
