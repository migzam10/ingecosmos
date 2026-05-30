@extends('layouts.app')

@section('title', 'Producción y KPIs')
@section('page_title', 'Producción y KPIs')
@section('breadcrumb', 'Administración')

@section('page_actions')
<a href="{{ route('produccion.exportar', request()->query()) }}"
   class="btn btn-success btn-sm">
    ↓ Exportar Excel (CSV)
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
                    @foreach(range(now()->year, 2023, -1) as $a)
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
                    <option value="todas"   {{ $area === 'todas'   ? 'selected':'' }}>Todas las áreas</option>
                    <option value="LYP"     {{ $area === 'LYP'     ? 'selected':'' }}>Latonería y Pintura</option>
                    <option value="MECANICA"{{ $area === 'MECANICA'? 'selected':'' }}>Mecánica</option>
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
                <div class="kpi-label mt-1">Tiempo promedio total</div>
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

{{-- Gráficas --}}
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
            <div class="card-header"><h3 class="card-title">Cumplimiento de entrega</h3></div>
            <div class="card-body d-flex align-items-center justify-content-center">
                <canvas id="chart-oportuno" style="max-height:220px"></canvas>
            </div>
        </div>
    </div>

    {{-- Gráfica 3: TMR real vs meta por TG --}}
    <div class="col-12 col-lg-6">
        <div class="card h-100">
            <div class="card-header"><h3 class="card-title">Días reales vs meta por tamaño del daño</h3></div>
            <div class="card-body">
                <canvas id="chart-tg" height="120"></canvas>
            </div>
        </div>
    </div>

    {{-- Gráfica 4: Volumen por empresa --}}
    <div class="col-12 col-lg-6">
        <div class="card h-100">
            <div class="card-header"><h3 class="card-title">Órdenes por empresa (top 10)</h3></div>
            <div class="card-body">
                <canvas id="chart-empresa" height="120"></canvas>
            </div>
        </div>
    </div>

</div>

{{-- Tabla detallada --}}
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
                    <th>Tamaño daño</th>
                    <th class="text-center">Días estimados</th>
                    <th class="text-center">Tiempo total</th>
                    <th class="text-center">Tiempo en taller</th>
                    <th class="text-center">Oportuno</th>
                </tr>
            </thead>
            <tbody>
                @forelse($ultimasEntregadas as $ot)
                @php
                $tmp = ($ot->fecha_entrega_cliente && $ot->fecha_ingreso)
                    ? $ot->fecha_ingreso->diffInDays($ot->fecha_entrega_cliente) : null;
                $tmr = ($ot->fecha_terminacion && $ot->fecha_inicio_proceso)
                    ? \Carbon\Carbon::parse($ot->fecha_inicio_proceso)->diffInDays($ot->fecha_terminacion) : null;
                $oportuno = ($ot->fecha_terminacion && $ot->salida_estimada)
                    ? \Carbon\Carbon::parse($ot->fecha_terminacion)->lte($ot->salida_estimada) : null;
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
                    <td class="text-center {{ $tmp > ($ot->dr ?? 999) ? 'text-danger' : '' }}">
                        {{ $tmp ?? '—' }}
                    </td>
                    <td class="text-center">{{ $tmr ?? '—' }}</td>
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
                <tr><td colspan="8" class="text-center text-muted py-4">Sin datos para el período seleccionado.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

@endsection

@push('scripts')
<script>
// Datos del servidor
const MESES    = @json($mesesLabels);
const MES_TOT  = @json($dataMesTotal);
const MES_ENT  = @json($dataMesEntregada);
const OPORTUNO = {{ $oportunas }};
const TARDIAS  = {{ $tardias }};
const TG_DATA  = @json($tgData);
const EMPRESAS = @json($porEmpresa->pluck('nombre'));
const EMP_TOT  = @json($porEmpresa->pluck('total'));

// Colores base
const COL_AZUL   = 'rgba(66, 153, 225, 0.8)';
const COL_VERDE  = 'rgba(47, 179, 68, 0.8)';
const COL_ROJO   = 'rgba(214, 57, 57, 0.8)';
const COL_NARANJ = 'rgba(247, 103, 7, 0.8)';
const COL_GRIS   = 'rgba(134, 142, 150, 0.8)';

// ── GRÁFICA 1: OTs por mes ────────────────────────────────────────────────
new Chart(document.getElementById('chart-mes'), {
    type: 'bar',
    data: {
        labels: MESES,
        datasets: [
            { label: 'Ingresadas', data: MES_TOT,  backgroundColor: COL_AZUL },
            { label: 'Entregadas', data: MES_ENT,  backgroundColor: COL_VERDE },
        ]
    },
    options: {
        responsive: true,
        plugins: { legend: { position: 'top' } },
        scales: { y: { beginAtZero: true, ticks: { stepSize: 1 } } }
    }
});

// ── GRÁFICA 2: Oportuno vs tardío ─────────────────────────────────────────
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

// ── GRÁFICA 3: TMR real vs meta por TG ───────────────────────────────────
new Chart(document.getElementById('chart-tg'), {
    type: 'bar',
    data: {
        labels: Object.keys(TG_DATA),
        datasets: [
            {
                label: 'Días reales promedio',
                data: Object.values(TG_DATA).map(d => d.tmr),
                backgroundColor: [COL_VERDE, COL_NARANJ, COL_ROJO],
            },
            {
                label: 'Meta CIA (días)',
                data: Object.values(TG_DATA).map(d => d.meta),
                backgroundColor: 'transparent',
                borderColor: 'rgba(26, 86, 219, 0.8)',
                borderWidth: 2,
                type: 'line',
                pointRadius: 5,
            }
        ]
    },
    options: {
        responsive: true,
        plugins: { legend: { position: 'top' } },
        scales: { y: { beginAtZero: true } }
    }
});

// ── GRÁFICA 4: Volumen por empresa ────────────────────────────────────────
new Chart(document.getElementById('chart-empresa'), {
    type: 'bar',
    data: {
        labels: EMPRESAS,
        datasets: [{
            label: 'Órdenes',
            data: EMP_TOT,
            backgroundColor: COL_AZUL,
        }]
    },
    options: {
        indexAxis: 'y',
        responsive: true,
        plugins: { legend: { display: false } },
        scales: { x: { beginAtZero: true, ticks: { stepSize: 1 } } }
    }
});
</script>
@endpush
