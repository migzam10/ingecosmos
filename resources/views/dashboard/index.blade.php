@extends('layouts.app')

@section('title', 'Dashboard')

@section('page_title', 'Dashboard')
@section('breadcrumb', 'INGECOSMOS')

@section('content')
<div class="row g-3 mb-4">

    {{-- KPI: OTs Activas --}}
    <div class="col-6 col-md-4 col-lg-2">
        <div class="card kpi-card">
            <div class="card-body text-center">
                <div class="kpi-value text-primary">{{ $kpis['total_activas'] }}</div>
                <div class="kpi-label mt-1">OTs Activas</div>
            </div>
        </div>
    </div>

    {{-- KPI: Incumplidas --}}
    <div class="col-6 col-md-4 col-lg-2">
        <div class="card kpi-card {{ $kpis['incumplidas'] > 0 ? 'border-danger' : '' }}">
            <div class="card-body text-center">
                <div class="kpi-value {{ $kpis['incumplidas'] > 0 ? 'text-danger' : 'text-success' }}">
                    {{ $kpis['incumplidas'] }}
                </div>
                <div class="kpi-label mt-1">Incumplidas</div>
            </div>
        </div>
    </div>

    {{-- KPI: Entregar Hoy --}}
    <div class="col-6 col-md-4 col-lg-2">
        <div class="card kpi-card {{ $kpis['entregar_hoy'] > 0 ? 'border-warning' : '' }}">
            <div class="card-body text-center">
                <div class="kpi-value {{ $kpis['entregar_hoy'] > 0 ? 'text-warning' : '' }}">
                    {{ $kpis['entregar_hoy'] }}
                </div>
                <div class="kpi-label mt-1">Entregar Hoy</div>
            </div>
        </div>
    </div>

    {{-- KPI: Pte Cotización --}}
    <div class="col-6 col-md-4 col-lg-2">
        <div class="card kpi-card">
            <div class="card-body text-center">
                <div class="kpi-value text-secondary">{{ $kpis['pte_cotizacion'] }}</div>
                <div class="kpi-label mt-1">Pte. Cotización</div>
            </div>
        </div>
    </div>

    {{-- KPI: Pte Autorización --}}
    <div class="col-6 col-md-4 col-lg-2">
        <div class="card kpi-card">
            <div class="card-body text-center">
                <div class="kpi-value text-secondary">{{ $kpis['pte_autorizacion'] }}</div>
                <div class="kpi-label mt-1">Pte. Autorización</div>
            </div>
        </div>
    </div>

    {{-- KPI: En Proceso --}}
    <div class="col-6 col-md-4 col-lg-2">
        <div class="card kpi-card">
            <div class="card-body text-center">
                <div class="kpi-value text-info">{{ $kpis['en_proceso'] }}</div>
                <div class="kpi-label mt-1">En Proceso</div>
            </div>
        </div>
    </div>

</div>

{{-- Segunda fila --}}
<div class="row g-3">

    {{-- Card de bienvenida / estado del sistema --}}
    <div class="col-12 col-md-6">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Estado del Sistema</h3>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <div class="d-flex align-items-center mb-2">
                        <span class="badge semaforo-incumplido me-2">INCUMPLIDO</span>
                        <span class="text-muted small">Salida estimada ya venció</span>
                    </div>
                    <div class="d-flex align-items-center mb-2">
                        <span class="badge semaforo-entregar me-2">ENTREGAR HOY</span>
                        <span class="text-muted small">Debe entregarse hoy</span>
                    </div>
                    <div class="d-flex align-items-center mb-2">
                        <span class="badge semaforo-a-tiempo me-2">A TIEMPO</span>
                        <span class="text-muted small">Dentro del plazo</span>
                    </div>
                    <div class="d-flex align-items-center mb-2">
                        <span class="badge semaforo-ok me-2">OK</span>
                        <span class="text-muted small">Entregado / Cerrado</span>
                    </div>
                    <div class="d-flex align-items-center">
                        <span class="badge semaforo-sin-fecha me-2">SIN FECHA</span>
                        <span class="text-muted small">Sin salida estimada calculada</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Accesos rápidos --}}
    <div class="col-12 col-md-6">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Accesos Rápidos</h3>
            </div>
            <div class="card-body">
                @php $roles = Auth::user()->roles ?? []; @endphp

                @if(in_array('ADMIN', $roles) || in_array('RECEPCION', $roles))
                <a href="{{ route('ordenes.index') }}" class="btn btn-primary w-100 mb-2">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24"
                         fill="none" stroke="currentColor" stroke-width="2" class="me-1">
                        <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                        <line x1="12" y1="5" x2="12" y2="19"/>
                        <line x1="5" y1="12" x2="19" y2="12"/>
                    </svg>
                    Nueva Orden de Trabajo
                </a>
                @endif

                @if(in_array('ADMIN', $roles) || in_array('COORDINADOR', $roles))
                <a href="{{ route('torre.index') }}" class="btn btn-outline-primary w-100 mb-2">
                    Torre de Control
                </a>
                @endif

                @if(in_array('TECNICO', $roles))
                <a href="{{ route('mis-tareas.index') }}" class="btn btn-outline-secondary w-100 mb-2">
                    Ver Mis Tareas
                </a>
                @endif

                @if(in_array('ADMIN', $roles) || in_array('COTIZADOR', $roles))
                <a href="{{ route('cotizaciones.index') }}" class="btn btn-outline-secondary w-100">
                    Cotizaciones Pendientes
                </a>
                @endif
            </div>
        </div>
    </div>

</div>
@endsection
