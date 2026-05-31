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

{{-- Filtros --}}
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

{{-- KPIs principales --}}
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
                <div class="kpi-value text-info">{{ $activas }}</div>
                <div class="kpi-label mt-1">En proceso</div>
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

{{-- Tiempos de proceso --}}
<div class="row g-3 mb-4">
    @foreach([
        ['label' => 'Promedio días hasta cotización',   'val' => $promedios->cot_prom, 'color' => 'blue'],
        ['label' => 'Promedio días hasta autorización', 'val' => $promedios->aut_prom, 'color' => 'orange'],
        ['label' => 'Promedio días llegada repuestos',  'val' => $promedios->rto_prom, 'color' => 'red'],
    ] as $kpi)
    <div class="col-12 col-md-4">
        <div class="card">
            <div class="card-body py-2 d-flex align-items-center gap-3">
                <div class="text-{{ $kpi['color'] }}" style="font-size:1.8rem; font-weight:700; line-height:1;">
                    {{ $kpi['val'] ? round($kpi['val']) : '—' }}
                    @if($kpi['val'])<small style="font-size:0.9rem">días</small>@endif
                </div>
                <div class="text-muted small">{{ $kpi['label'] }}</div>
            </div>
        </div>
    </div>
    @endforeach
</div>

{{-- Gráficas fila 1 --}}
<div class="row g-3 mb-4">

    {{-- Gráfica 1: OTs por mes --}}
    <div class="col-12 col-lg-8">
        <div class="card h-100">
            <div class="card-header"><h3 class="card-title">Órdenes por mes — {{ $anio }}</h3></div>
            <div class="card-body">
                <canvas id="chart-mes" height="100"></canvas>
            </div>
        </div>
    </div>

    {{-- Gráfica 2: Oportuno vs tardío --}}
    <div class="col-12 col-lg-4">
        <div class="card h-100">
            <div class="card-header">
                <h3 class="card-title">Cumplimiento de entrega</h3>
                @if($baseOportuno > 0)
                <span class="card-subtitle ms-2 text-muted small">sobre {{ $baseOportuno }} OTs con datos</span>
                @endif
            </div>
            <div class="card-body d-flex align-items-center justify-content-center">
                @if($baseOportuno > 0)
                <canvas id="chart-oportuno" style="max-height:220px"></canvas>
                @else
                <div class="text-center text-muted py-4">
                    <div style="font-size:2rem">📊</div>
                    <div class="small mt-2">Sin datos suficientes para calcular<br>oportuno (se requiere fecha entrega + salida estimada)</div>
                </div>
                @endif
            </div>
        </div>
    </div>

</div>

{{-- Gráfica 3: Facturación mensual --}}
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

{{-- Gráfica 4: Volumen por empresa --}}
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

{{-- Tablas top 7 por área --}}
@foreach([['LYP','Latonería y Pintura',$tablaLYP], ['MECANICA','Mecánica',$tablaMEC]] as [$areaKey, $areaLabel, $tabla])
@if(count($tabla) > 1)
<div class="card mb-4">
    <div class="card-header">
        <h3 class="card-title">Top 7 clientes — {{ $areaLabel }} — {{ $anio }}</h3>
        <span class="card-subtitle ms-2 text-muted small">Facturación por mes (COP)</span>
    </div>
    <div class="table-responsive">
        <table class="table table-sm table-hover mb-0" style="font-size:0.8rem">
            <thead class="table-light">
                <tr>
                    <th style="min-width:130px">Empresa</th>
                    <th class="text-center" style="width:40px">OTs</th>
                    @foreach(['Ene','Feb','Mar','Abr','May','Jun','Jul','Ago','Sep','Oct','Nov','Dic'] as $ml)
                    <th class="text-end" style="min-width:75px">{{ $ml }}</th>
                    @endforeach
                    <th class="text-end" style="min-width:90px">Total</th>
                </tr>
            </thead>
            <tbody>
                @foreach($tabla as $i => $fila)
                @php $esTotal = $fila['empresa'] === 'TOTAL'; @endphp
                <tr class="{{ $esTotal ? 'fw-bold table-secondary' : '' }}">
                    <td class="{{ $esTotal ? 'text-uppercase' : '' }}">{{ $fila['empresa'] }}</td>
                    <td class="text-center text-muted">{{ $fila['ots'] }}</td>
                    @foreach(range(1,12) as $m)
                    @php $v = $fila['meses'][$m] ?? 0; @endphp
                    <td class="text-end {{ $v > 0 ? '' : 'text-muted' }}">
                        {{ $v > 0 ? '$' . number_format($v/1000000, 1) . 'M' : '—' }}
                    </td>
                    @endforeach
                    <td class="text-end {{ $esTotal ? 'text-primary' : '' }}">
                        ${{ number_format($fila['total']/1000000, 1) }}M
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endif
@endforeach

{{-- Tabla últimas entregadas --}}
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
                    <th class="text-center">Días est.</th>
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
const MESES       = @json($mesesLabels);
const MES_TOT     = @json($dataMesTotal);
const MES_ENT     = @json($dataMesEntregada);
const OPORTUNO    = {{ $oportunas }};
const TARDIAS     = {{ $tardias }};
const BASE_OPT    = {{ $baseOportuno }};
const EMPRESAS    = @json($porEmpresa->pluck('nombre'));
const EMP_TOT     = @json($porEmpresa->pluck('total'));
const FACT_MENS   = @json($dataFactMensual);

const COL_AZUL    = 'rgba(66, 153, 225, 0.85)';
const COL_VERDE   = 'rgba(47, 179, 68, 0.85)';
const COL_ROJO    = 'rgba(214, 57, 57, 0.85)';
const COL_NARANJ  = 'rgba(247, 103, 7, 0.85)';
const COL_GRIS    = 'rgba(134, 142, 150, 0.6)';
const COL_MORADO  = 'rgba(132, 99, 221, 0.85)';

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

// ── GRÁFICA 2: Oportuno vs tardío ─────────────────────────────────────────
if (BASE_OPT > 0 && document.getElementById('chart-oportuno')) {
    new Chart(document.getElementById('chart-oportuno'), {
        type: 'doughnut',
        data: {
            labels: ['Entrega oportuna', 'Entrega tardía'],
            datasets: [{
                data: [OPORTUNO, TARDIAS],
                backgroundColor: [COL_VERDE, COL_ROJO],
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: { position: 'bottom' },
                tooltip: {
                    callbacks: {
                        label: ctx => {
                            const total = ctx.dataset.data.reduce((a, b) => a + b, 0);
                            const pct   = total > 0 ? Math.round(ctx.parsed / total * 100) : 0;
                            return ` ${ctx.label}: ${ctx.parsed} (${pct}%)`;
                        }
                    }
                }
            }
        }
    });
}

// ── GRÁFICA 3: Facturación mensual ────────────────────────────────────────
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

// ── GRÁFICA 4: Volumen por empresa ────────────────────────────────────────
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
            tooltip: {
                callbacks: {
                    label: ctx => ` ${ctx.parsed.x} órdenes`
                }
            }
        },
        scales: {
            x: { beginAtZero: true, ticks: { stepSize: 1 } }
        }
    }
});
</script>
@endpush
