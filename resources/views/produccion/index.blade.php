@extends('layouts.app')

@section('title', 'Producción y KPIs')
@section('page_title', 'Producción y KPIs')
@section('breadcrumb', 'Administración')

@section('page_actions')
<a href="{{ route('produccion.exportar', request()->query()) }}"
   class="btn btn-success btn-sm">
    ↓ Exportar CSV
</a>
@endsection

@section('content')

{{-- 1. Filtros --}}
<div class="card mb-3">
    <div class="card-body py-2">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-auto">
                <label class="form-label small">Año</label>
                <select name="anio" class="form-select form-select-sm">
                    @foreach($aniosConDatos as $a)
                    <option value="{{ $a }}" {{ $anio == $a ? 'selected':'' }}>{{ $a }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-auto">
                <label class="form-label small">Mes</label>
                <select name="mes" class="form-select form-select-sm">
                    <option value="todos" {{ $mes === 'todos' ? 'selected':'' }}>Todo el año</option>
                    @foreach(['1'=>'Enero','2'=>'Febrero','3'=>'Marzo','4'=>'Abril','5'=>'Mayo','6'=>'Junio',
                              '7'=>'Julio','8'=>'Agosto','9'=>'Septiembre','10'=>'Octubre','11'=>'Noviembre','12'=>'Diciembre'] as $n => $nombre)
                    <option value="{{ $n }}" {{ $mes == $n ? 'selected':'' }}>{{ $nombre }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-auto">
                <label class="form-label small">Área</label>
                <select name="area" class="form-select form-select-sm">
                    <option value="todas"    {{ $area === 'todas'    ? 'selected':'' }}>Todas las áreas</option>
                    <option value="LYP"      {{ $area === 'LYP'      ? 'selected':'' }}>Latonería y Pintura</option>
                    <option value="MECANICA" {{ $area === 'MECANICA' ? 'selected':'' }}>Mecánica</option>
                </select>
            </div>
            <div class="col-auto">
                <button class="btn btn-secondary btn-sm">Aplicar</button>
                <a href="{{ route('produccion.index') }}" class="btn btn-outline-secondary btn-sm ms-1">Limpiar</a>
            </div>
        </form>
    </div>
</div>

{{-- 2. KPIs fila 1: Total · Entregadas · % Oportunas · Ticket promedio · TMP · TMR --}}
@php
function formatCOPView(float $v): string {
    if ($v >= 1000000) return '$' . number_format($v/1000000, 1) . 'M';
    if ($v >= 1000)    return '$' . number_format($v/1000, 0) . 'K';
    return '$' . number_format($v, 0, ',', '.');
}
@endphp
<div class="row g-3 mb-4">
    <div class="col-6 col-md-2">
        <div class="card kpi-card text-center">
            <div class="card-body py-3">
                <div class="kpi-value text-primary">{{ $totalOTs }}</div>
                <div class="kpi-label mt-1">Total órdenes</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-2">
        <div class="card kpi-card text-center">
            <div class="card-body py-3">
                <div class="kpi-value text-success">{{ $entregadas }}</div>
                <div class="kpi-label mt-1">Entregadas</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-2">
        <div class="card kpi-card text-center">
            <div class="card-body py-3">
                <div class="kpi-value {{ $pctOportuno >= 80 ? 'text-success' : ($pctOportuno >= 60 ? 'text-warning' : 'text-danger') }}">
                    {{ $pctOportuno }}%
                </div>
                <div class="kpi-label mt-1">Entregas oportunas</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-2">
        <div class="card kpi-card text-center">
            <div class="card-body py-3">
                <div class="kpi-value text-info" style="font-size:1.1rem">
                    {{ formatCOPView((float)$ticketPromedio) }}
                </div>
                <div class="kpi-label mt-1">Ticket promedio</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-2">
        <div class="card kpi-card text-center">
            <div class="card-body py-3">
                <div class="kpi-value text-muted">
                    {{ $promedios->tmp_prom ? round($promedios->tmp_prom) . 'd' : '—' }}
                </div>
                <div class="kpi-label mt-1">Tiempo prom. total</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-2">
        <div class="card kpi-card text-center">
            <div class="card-body py-3">
                <div class="kpi-value text-muted">
                    {{ $promedios->tmr_prom ? round($promedios->tmr_prom) . 'd' : '—' }}
                </div>
                <div class="kpi-label mt-1">Tiempo en taller</div>
            </div>
        </div>
    </div>
</div>

{{-- 3. KPIs fila 2: Tasa sin reparar + Distribución TG --}}
<div class="row g-3 mb-4">
    {{-- Tasa sin reparar --}}
    <div class="col-12 col-md-3">
        <div class="card h-100 text-center">
            <div class="card-body py-3">
                <div class="kpi-value {{ $pctSinReparar >= 20 ? 'text-danger' : ($pctSinReparar >= 10 ? 'text-warning' : 'text-success') }}"
                     style="font-size:1.8rem;font-weight:700">
                    {{ $pctSinReparar }}%
                </div>
                <div class="kpi-label mt-1">Cerradas sin reparar</div>
                <div class="text-muted small mt-1">{{ $sinReparar }} OTs (No aut. / Anuladas / Pérd. total)</div>
            </div>
        </div>
    </div>

    {{-- Distribución TG --}}
    @php
    $tgConfig = [
        'Leve'   => ['color' => 'primary',  'meta' => 5,  'badge' => 'bg-blue-lt'],
        'Medio'  => ['color' => 'warning',  'meta' => 10, 'badge' => 'bg-warning-lt'],
        'Fuerte' => ['color' => 'danger',   'meta' => 13, 'badge' => 'bg-danger-lt'],
    ];
    @endphp
    @foreach($tgConfig as $tgNombre => $cfg)
    @php
    $tgData  = $porTG->get($tgNombre);
    $tgTotal = $tgData?->total ?? 0;
    $tgBase  = $tgData?->base_tg ?? 0;
    $tgOpt   = $tgData?->oportunas_tg ?? 0;
    $tgPct   = $tgBase > 0 ? round($tgOpt / $tgBase * 100, 1) : 0;
    @endphp
    <div class="col-12 col-md-3">
        <div class="card h-100 text-center">
            <div class="card-body py-3">
                <span class="badge {{ $cfg['badge'] }} mb-1">{{ $tgNombre }}</span>
                <div class="kpi-value text-{{ $cfg['color'] }}" style="font-size:1.6rem;font-weight:700">
                    {{ $tgTotal }}
                </div>
                <div class="kpi-label mt-1">OTs · meta {{ $cfg['meta'] }} días CIA</div>
                @if($tgBase > 0)
                <div class="text-muted small mt-1">{{ $tgPct }}% oportuno</div>
                @else
                <div class="text-muted small mt-1">Sin datos</div>
                @endif
            </div>
        </div>
    </div>
    @endforeach
</div>

{{-- 4. Tiempos de proceso --}}
<div class="row g-3 mb-4">
    @foreach([
        ['label' => 'Promedio días hasta cotización',   'val' => $promedios->cot_prom, 'color' => 'blue'],
        ['label' => 'Promedio días hasta autorización', 'val' => $promedios->aut_prom, 'color' => 'orange'],
        ['label' => 'Promedio días llegada repuestos',  'val' => $promedios->rto_prom, 'color' => 'red'],
    ] as $kpi)
    <div class="col-12 col-md-4">
        <div class="card">
            <div class="card-body py-2 d-flex align-items-center gap-3">
                <div class="text-{{ $kpi['color'] }}" style="font-size:1.8rem;font-weight:700;line-height:1">
                    {{ $kpi['val'] ? round($kpi['val']) : '—' }}
                    @if($kpi['val'])<small style="font-size:0.9rem">días</small>@endif
                </div>
                <div class="text-muted small">{{ $kpi['label'] }}</div>
            </div>
        </div>
    </div>
    @endforeach
</div>

{{-- 5. Gráfica OTs por mes (col-8) | Cumplimiento meta CIA por TG (col-4) --}}
<div class="row g-3 mb-4">
    <div class="col-12 col-lg-8">
        <div class="card h-100">
            <div class="card-header"><h3 class="card-title">Órdenes por mes — {{ $anio }}</h3></div>
            <div class="card-body">
                <canvas id="chart-mes" height="100"></canvas>
            </div>
        </div>
    </div>

    <div class="col-12 col-lg-4">
        <div class="card h-100">
            <div class="card-header">
                <h3 class="card-title">Cumplimiento meta CIA por tipo de daño</h3>
            </div>
            <div class="card-body">
                @foreach(['Leve' => [5, 'primary'], 'Medio' => [10, 'warning'], 'Fuerte' => [13, 'danger']] as $tg => [$meta, $color])
                @php
                $td   = $porTG->get($tg);
                $base = $td?->base_tg ?? 0;
                $opt  = $td?->oportunas_tg ?? 0;
                $pct  = $base > 0 ? min(100, round($opt / $base * 100)) : 0;
                @endphp
                <div class="mb-3">
                    <div class="d-flex justify-content-between small mb-1">
                        <span>{{ $tg }} <span class="text-muted">(meta {{ $meta }} días CIA)</span></span>
                        <span class="fw-bold">{{ $pct }}%</span>
                    </div>
                    <div class="progress" style="height:12px">
                        <div class="progress-bar bg-{{ $color }}" style="width:{{ $pct }}%"></div>
                    </div>
                    <div class="text-muted" style="font-size:.75rem">
                        {{ $opt }}/{{ $base }} OTs dentro del plazo
                    </div>
                </div>
                @endforeach
            </div>
        </div>
    </div>
</div>

{{-- 6. Gráfica facturación mensual --}}
<div class="row g-3 mb-4">
    <div class="col-12">
        <div class="card">
            <div class="card-header"><h3 class="card-title">Facturación mensual — {{ $anio }}</h3></div>
            <div class="card-body">
                <canvas id="chart-fact-mensual" height="80"></canvas>
            </div>
        </div>
    </div>
</div>

{{-- 7. Gráfica órdenes por empresa --}}
<div class="row g-3 mb-4">
    <div class="col-12">
        <div class="card">
            <div class="card-header"><h3 class="card-title">Órdenes por empresa — Top 10</h3></div>
            <div class="card-body">
                <canvas id="chart-empresa" height="70"></canvas>
            </div>
        </div>
    </div>
</div>

{{-- 8. Top 10 clientes por facturación --}}
@php
$mesesLabelsShort = ['Ene','Feb','Mar','Abr','May','Jun','Jul','Ago','Sep','Oct','Nov','Dic'];
$nombreMes = $mes !== 'todos' ? $mesesLabelsShort[(int)$mes - 1] : null;
@endphp
@if($tablaTop10->count() > 0)
<div class="card mb-4">
    <div class="card-header d-flex align-items-center">
        <h3 class="card-title mb-0">
            Top 10 clientes por facturación — {{ $anio }}
            @if($nombreMes) — {{ $nombreMes }} @endif
        </h3>
        <a href="{{ route('produccion.excel-clientes', request()->only('anio','mes','area')) }}"
           class="btn btn-sm btn-outline-success ms-auto">
            ↓ Detalle mensual Excel
        </a>
    </div>
    <div class="table-responsive">
        <table class="table table-sm table-hover mb-0">
            <thead class="table-light">
                <tr>
                    <th>Empresa</th>
                    <th class="text-center">OTs</th>
                    <th class="text-end">Facturado</th>
                </tr>
            </thead>
            <tbody>
                @foreach($tablaTop10 as $fila)
                <tr>
                    <td>{{ $fila->nombre }}</td>
                    <td class="text-center text-muted">{{ $fila->total_ots }}</td>
                    <td class="text-end fw-medium">{{ formatCOPView((float)$fila->facturado_total) }}</td>
                </tr>
                @endforeach
            </tbody>
            <tfoot class="table-secondary fw-bold">
                <tr>
                    <td>TOTAL</td>
                    <td class="text-center">{{ $tablaTop10->sum('total_ots') }}</td>
                    <td class="text-end text-primary">{{ formatCOPView((float)$tablaTop10->sum('facturado_total')) }}</td>
                </tr>
            </tfoot>
        </table>
    </div>
</div>
@endif

{{-- 9. Indicadores por empresa/aseguradora --}}
@if($tiemposPorEmpresa->count() > 0)
<div class="card mb-4">
    <div class="card-header">
        <h3 class="card-title">Indicadores por empresa / aseguradora — {{ $anio }}</h3>
        <span class="card-subtitle ms-2 text-muted small">Solo empresas con 3+ OTs entregadas</span>
    </div>
    <div class="table-responsive">
        <table class="table table-sm table-hover mb-0">
            <thead class="table-light">
                <tr>
                    <th>Empresa</th>
                    <th class="text-center">OTs</th>
                    <th class="text-center">Días aut. CIA</th>
                    <th class="text-center">TMR prom</th>
                    <th class="text-center">TMP prom</th>
                    <th class="text-center">% Oportuno</th>
                </tr>
            </thead>
            <tbody>
                @foreach($tiemposPorEmpresa as $emp)
                @php
                $pctEmp = $emp->total > 0 ? round($emp->oportunas / $emp->total * 100) : 0;
                $colorPct = $pctEmp >= 80 ? 'success' : ($pctEmp >= 60 ? 'warning' : 'danger');
                @endphp
                <tr>
                    <td class="fw-medium">{{ $emp->nombre }}</td>
                    <td class="text-center">{{ $emp->total }}</td>
                    <td class="text-center small text-muted">{{ $emp->dias_aut !== null ? round($emp->dias_aut) . 'd' : '—' }}</td>
                    <td class="text-center small text-muted">{{ $emp->tmr !== null ? round($emp->tmr) . 'd' : '—' }}</td>
                    <td class="text-center small text-muted">{{ $emp->tmp !== null ? round($emp->tmp) . 'd' : '—' }}</td>
                    <td class="text-center">
                        <span class="badge bg-{{ $colorPct }}-lt text-{{ $colorPct }}">{{ $pctEmp }}%</span>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endif

{{-- 10. Rendimiento por técnico --}}
@if($rendimientoTecnicos->count() > 0)
@php
$nombresEsp = ['LAT'=>'Latonero','PREP'=>'Preparador','PINT'=>'Pintor','MEC'=>'Mecánico','ELEC'=>'Electricista','AA'=>'Aire Acond.','SCANNER'=>'Diagnóstico'];
@endphp
<div class="card mb-4">
    <div class="card-header">
        <h3 class="card-title">Rendimiento por técnico — {{ $anio }}</h3>
    </div>
    <div class="table-responsive">
        <table class="table table-sm table-hover mb-0">
            <thead class="table-light">
                <tr>
                    <th>Técnico</th>
                    <th>Especialidad</th>
                    <th class="text-center">Trabajos finalizados</th>
                    <th class="text-end">Valor liquidado</th>
                    <th class="text-center">Hrs prom/trabajo</th>
                </tr>
            </thead>
            <tbody>
                @foreach($rendimientoTecnicos as $tec)
                <tr>
                    <td class="fw-medium">{{ $tec->nombre }}</td>
                    <td><span class="badge bg-secondary-lt">{{ $nombresEsp[$tec->especialidad] ?? $tec->especialidad }}</span></td>
                    <td class="text-center">{{ $tec->trabajos }}</td>
                    <td class="text-end">{{ formatCOPView((float)$tec->valor_total) }}</td>
                    <td class="text-center text-muted">
                        {{ $tec->horas_prom !== null ? round($tec->horas_prom, 1) . 'h' : '—' }}
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endif

{{-- 11. Últimas 20 entregadas --}}
<div class="card">
    <div class="card-header">
        <h3 class="card-title">Últimas órdenes entregadas</h3>
    </div>
    <div class="table-responsive">
        <table class="table table-sm table-hover mb-0">
            <thead>
                <tr>
                    <th># Orden</th>
                    <th>Placa / Vehículo</th>
                    <th>Empresa</th>
                    <th>Daño</th>
                    <th class="text-center" title="DR — días hábiles estimados de reparación física, calculado desde la mano de obra de la cotización">Días reparación</th>
                    <th class="text-center">Tiempo total</th>
                    <th class="text-center">Oportuno</th>
                </tr>
            </thead>
            <tbody>
                @forelse($ultimasEntregadas as $ot)
                @php
                $tmp = ($ot->fecha_entrega_cliente && $ot->fecha_ingreso)
                    ? $ot->fecha_ingreso->diffInDays($ot->fecha_entrega_cliente) : null;
                $oportuno = ($ot->fecha_entrega_cliente && $ot->salida_estimada)
                    ? $ot->fecha_entrega_cliente->lte($ot->salida_estimada) : null;
                @endphp
                <tr>
                    <td class="fw-bold">{{ $ot->numero_ot }}</td>
                    <td>
                        <span class="badge bg-blue-lt">{{ $ot->vehiculo->placa }}</span>
                        <span class="text-muted small ms-1">{{ $ot->vehiculo->marca->nombre }}</span>
                    </td>
                    <td class="small">{{ $ot->empresaCliente->nombre }}</td>
                    <td><x-tg-badge :tg="$ot->tg" /></td>
                    <td class="text-center">{{ $ot->dr ?? '—' }}</td>
                    <td class="text-center {{ $tmp !== null && $tmp > ($ot->dr ?? 999) ? 'text-danger' : '' }}">
                        {{ $tmp ?? '—' }}
                    </td>
                    <td class="text-center">
                        @if($oportuno === null)
                        <span class="text-muted">—</span>
                        @elseif($oportuno)
                        <span class="badge bg-success-lt">Sí</span>
                        @else
                        <span class="badge bg-danger-lt">No</span>
                        @endif
                    </td>
                </tr>
                @empty
                <tr><td colspan="7" class="text-center text-muted py-4">Sin datos para el período seleccionado.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

@endsection

@push('scripts')
<script>
const MESES     = @json($mesesLabels);
const MES_TOT   = @json($dataMesTotal);
const MES_ENT   = @json($dataMesEntregada);
const EMPRESAS  = @json($porEmpresa->pluck('nombre'));
const EMP_TOT   = @json($porEmpresa->pluck('total'));
const FACT_MENS = @json($dataFactMensual);

const COL_AZUL   = 'rgba(66, 153, 225, 0.85)';
const COL_VERDE  = 'rgba(47, 179, 68, 0.85)';
const COL_MORADO = 'rgba(132, 99, 221, 0.85)';

// ── GRÁFICA 1: OTs por mes ────────────────────────────────────────────────
new Chart(document.getElementById('chart-mes'), {
    type: 'bar',
    data: {
        labels: MESES,
        datasets: [
            { label: 'Ingresadas', data: MES_TOT, backgroundColor: COL_AZUL },
            { label: 'Entregadas', data: MES_ENT, backgroundColor: COL_VERDE },
        ]
    },
    options: {
        responsive: true,
        plugins: { legend: { position: 'top' } },
        scales: { y: { beginAtZero: true, ticks: { stepSize: 1 } } }
    }
});

// ── GRÁFICA 2: Facturación mensual ────────────────────────────────────────
new Chart(document.getElementById('chart-fact-mensual'), {
    type: 'bar',
    data: {
        labels: MESES,
        datasets: [{
            label: 'Facturado ($)',
            data: FACT_MENS,
            backgroundColor: COL_MORADO,
            borderRadius: 4,
        }]
    },
    options: {
        responsive: true,
        plugins: {
            legend: { display: false },
            tooltip: {
                callbacks: {
                    label: ctx => ' $' + new Intl.NumberFormat('es-CO').format(ctx.parsed.y)
                }
            }
        },
        scales: {
            y: {
                beginAtZero: true,
                ticks: {
                    callback: val => '$' + (val >= 1000000
                        ? (val/1000000).toFixed(1) + 'M'
                        : (val/1000).toFixed(0) + 'K')
                }
            }
        }
    }
});

// ── GRÁFICA 3: Volumen por empresa ────────────────────────────────────────
const coloresEmp = EMPRESAS.map((_, i) => `hsl(${(i * 47) % 360}, 65%, 55%)`);
new Chart(document.getElementById('chart-empresa'), {
    type: 'bar',
    data: {
        labels: EMPRESAS,
        datasets: [{
            label: 'Órdenes',
            data: EMP_TOT,
            backgroundColor: coloresEmp,
            borderRadius: 4,
        }]
    },
    options: {
        indexAxis: 'y',
        responsive: true,
        plugins: {
            legend: { display: false },
            tooltip: { callbacks: { label: ctx => ` ${ctx.parsed.x} órdenes` } }
        },
        scales: { x: { beginAtZero: true, ticks: { stepSize: 1 } } }
    }
});
</script>
@endpush
