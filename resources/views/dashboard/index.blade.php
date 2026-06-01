@extends('layouts.app')

@section('title', 'Panel de Control')

@section('page_title', 'Panel de Control')
@section('breadcrumb', 'INGECOSMOS')

@section('content')
@php $roles = $roles ?? (Auth::user()->roles ?? []); @endphp
<div class="row g-3 mb-4">

    @if(!($esSoloCotizador ?? false))
    {{-- KPI: OTs Activas --}}
    <div class="col-6 col-md-4 col-lg-2">
        <div class="card kpi-card">
            <div class="card-body text-center">
                <div class="kpi-value text-primary">{{ $kpis['total_activas'] }}</div>
                <div class="kpi-label mt-1">Órdenes activas</div>
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
    @endif

    {{-- KPI: Pte Cotización (visible a todos) --}}
    <div class="col-6 col-md-4 col-lg-2">
        <div class="card kpi-card">
            <div class="card-body text-center">
                <div class="kpi-value text-secondary">{{ $kpis['pte_cotizacion'] }}</div>
                <div class="kpi-label mt-1">Sin cotizar</div>
            </div>
        </div>
    </div>

    {{-- KPI: Pte Autorización (visible a todos) --}}
    <div class="col-6 col-md-4 col-lg-2">
        <div class="card kpi-card">
            <div class="card-body text-center">
                <div class="kpi-value text-secondary">{{ $kpis['pte_autorizacion'] }}</div>
                <div class="kpi-label mt-1">Sin autorizar</div>
            </div>
        </div>
    </div>

    @if(!($esSoloCotizador ?? false))
    {{-- KPI: En Proceso --}}
    <div class="col-6 col-md-4 col-lg-2">
        <div class="card kpi-card">
            <div class="card-body text-center">
                <div class="kpi-value text-info">{{ $kpis['en_proceso'] }}</div>
                <div class="kpi-label mt-1">En Proceso</div>
            </div>
        </div>
    </div>
    @endif

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

                @if(in_array('ADMIN', $roles) || in_array('COORDINADOR', $roles) || in_array('COTIZADOR', $roles))
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

{{-- Panel de alertas --}}
@if($totalAlertas > 0)
<div class="row g-3 mt-1">
    <div class="col-12">
        <div class="card border-0">
            <div class="card-header">
                <h3 class="card-title">
                    Alertas activas
                    <span class="badge bg-danger ms-2">{{ $totalAlertas }}</span>
                </h3>
            </div>
            <div class="card-body p-0">

                @php
                $tiposAlerta = [
                    'incumplida'           => 'Entrega vencida',
                    'entregar_hoy'         => 'Entregar hoy',
                    'sin_cotizacion'       => 'Sin cotizar',
                    'sin_autorizacion'     => 'Sin autorización',
                    'autorizada_sin_iniciar' => 'Autorizada sin iniciar',
                    'repuestos_demorados'  => 'Repuestos demorados',
                    'entrega_parcial'      => 'Entrega parcial sin retorno',
                ];
                $grupos = [
                    'roja'    => ['color' => 'danger', 'icono' => '🔴'],
                    'naranja' => ['color' => 'warning', 'icono' => '🟠'],
                    'amarilla'=> ['color' => 'yellow',  'icono' => '🟡'],
                ];
                @endphp

                @foreach($grupos as $nivel => $cfg)
                @if(count($alertas[$nivel]))
                <div class="p-3 border-bottom">
                    <div class="fw-bold text-{{ $cfg['color'] }} mb-2">
                        {{ $cfg['icono'] }}
                        {{ ['roja'=>'Urgente','naranja'=>'Atención requerida','amarilla'=>'Aviso'][$nivel] }}
                        <span class="badge bg-{{ $cfg['color'] }}-lt text-{{ $cfg['color'] }} ms-1">
                            {{ count($alertas[$nivel]) }}
                        </span>
                    </div>
                    <div class="row g-2">
                        @foreach($alertas[$nivel] as $alerta)
                        <div class="col-12 col-md-6 col-lg-4">
                            <div class="d-flex align-items-start gap-2 p-2 rounded border">
                                <div class="flex-grow-1">
                                    <div class="d-flex gap-2 align-items-center">
                                        <span class="fw-bold text-primary small">
                                            # {{ $alerta['ot']->numero_ot }}
                                        </span>
                                        <span class="badge bg-blue-lt small">
                                            {{ $alerta['ot']->vehiculo->placa }}
                                        </span>
                                    </div>
                                    <div class="small text-muted">
                                        {{ $alerta['ot']->vehiculo->marca->nombre }}
                                        · {{ $alerta['ot']->empresaCliente->nombre }}
                                    </div>
                                    <div class="small mt-1">
                                        <span class="badge bg-{{ $cfg['color'] }}-lt">
                                            {{ $tiposAlerta[$alerta['tipo']] ?? $alerta['tipo'] }}
                                        </span>
                                        <span class="text-muted ms-1">{{ $alerta['mensaje'] }}</span>
                                    </div>
                                </div>
                                <a href="{{ route('ordenes.show', $alerta['ot']) }}"
                                   class="btn btn-sm btn-outline-{{ $cfg['color'] }}">Ver</a>
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
                @endif
                @endforeach

            </div>
        </div>
    </div>
</div>
@endif

@endsection
