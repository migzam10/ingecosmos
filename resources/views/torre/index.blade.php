@extends('layouts.app')

@section('title', 'Torre de Control')
@section('page_title', 'Torre de Control')
@section('breadcrumb', 'Operación Diaria')

@section('content')

{{-- TABS DE SEMÁFORO --}}
<div class="card mb-3">
    <div class="card-body py-2 px-3">
        <div class="nav nav-tabs card-header-tabs flex-nowrap overflow-auto">

            <a class="nav-link text-nowrap {{ $tab === 'todas' ? 'active' : '' }}"
               href="{{ request()->fullUrlWithQuery(['tab' => 'todas', 'page' => null]) }}">
                Todas
                <span class="badge bg-secondary ms-1">{{ $conteos['todas'] }}</span>
            </a>

            <a class="nav-link text-nowrap {{ $tab === 'incumplidas' ? 'active' : '' }}"
               href="{{ request()->fullUrlWithQuery(['tab' => 'incumplidas', 'page' => null]) }}">
                <span class="text-danger">●</span> Incumplidas
                @if($conteos['incumplidas'] > 0)
                <span class="badge bg-danger ms-1">{{ $conteos['incumplidas'] }}</span>
                @else
                <span class="badge bg-secondary-lt ms-1">0</span>
                @endif
            </a>

            <a class="nav-link text-nowrap {{ $tab === 'entregar_hoy' ? 'active' : '' }}"
               href="{{ request()->fullUrlWithQuery(['tab' => 'entregar_hoy', 'page' => null]) }}">
                <span class="text-warning">●</span> Entregar Hoy
                @if($conteos['entregar_hoy'] > 0)
                <span class="badge bg-warning ms-1">{{ $conteos['entregar_hoy'] }}</span>
                @else
                <span class="badge bg-secondary-lt ms-1">0</span>
                @endif
            </a>

            <a class="nav-link text-nowrap {{ $tab === 'a_tiempo' ? 'active' : '' }}"
               href="{{ request()->fullUrlWithQuery(['tab' => 'a_tiempo', 'page' => null]) }}">
                <span class="text-success">●</span> A Tiempo
                <span class="badge bg-secondary-lt ms-1">{{ $conteos['a_tiempo'] }}</span>
            </a>

            <a class="nav-link text-nowrap {{ $tab === 'sin_fecha' ? 'active' : '' }}"
               href="{{ request()->fullUrlWithQuery(['tab' => 'sin_fecha', 'page' => null]) }}">
                <span class="text-muted">●</span> Sin Fecha
                <span class="badge bg-secondary-lt ms-1">{{ $conteos['sin_fecha'] }}</span>
            </a>

        </div>
    </div>
</div>

{{-- FILTROS --}}
<div class="card mb-3">
    <div class="card-body py-2">
        <form method="GET" action="{{ route('torre.index') }}" class="row g-2 align-items-end">
            <input type="hidden" name="tab" value="{{ $tab }}">

            <div class="col-12 col-md-3">
                <input type="text" name="buscar" class="form-control form-control-sm"
                       placeholder="Placa, # OT o cliente..."
                       value="{{ request('buscar') }}">
            </div>

            <div class="col-6 col-md-2">
                <select name="area" class="form-select form-select-sm">
                    <option value="">Todas las áreas</option>
                    <option value="LYP"      {{ request('area')=='LYP'      ? 'selected' : '' }}>Latonería y Pintura</option>
                    <option value="MECANICA" {{ request('area')=='MECANICA' ? 'selected' : '' }}>Mecánica</option>
                </select>
            </div>

            <div class="col-6 col-md-2">
                <select name="estado" class="form-select form-select-sm">
                    <option value="">Todos los estados</option>
                    <option value="PTE_COTIZACION"     {{ request('estado')=='PTE_COTIZACION'     ? 'selected':'' }}>Pendiente cotización</option>
                    <option value="PTE_AUTORIZACION"   {{ request('estado')=='PTE_AUTORIZACION'   ? 'selected':'' }}>Pendiente autorización</option>
                    <option value="PTE_ORDEN"          {{ request('estado')=='PTE_ORDEN'          ? 'selected':'' }}>Pendiente orden repuestos</option>
                    <option value="PTE_REPUESTOS"      {{ request('estado')=='PTE_REPUESTOS'      ? 'selected':'' }}>Esperando repuestos</option>
                    <option value="RTO_INSTALADO"      {{ request('estado')=='RTO_INSTALADO'      ? 'selected':'' }}>Repuestos instalados</option>
                    <option value="EN_PROCESO"         {{ request('estado')=='EN_PROCESO'         ? 'selected':'' }}>En proceso</option>
                    <option value="PROGRAMADO_ENTREGA" {{ request('estado')=='PROGRAMADO_ENTREGA' ? 'selected':'' }}>Programado para entrega</option>
                </select>
            </div>

            <div class="col-6 col-md-2">
                <select name="empresa" class="form-select form-select-sm">
                    <option value="">Todas las empresas</option>
                    @foreach($empresas as $emp)
                    <option value="{{ $emp->id }}" {{ request('empresa')==$emp->id ? 'selected':'' }}>
                        {{ $emp->nombre }}
                    </option>
                    @endforeach
                </select>
            </div>

            <div class="col-6 col-md-2">
                <select name="tecnico" class="form-select form-select-sm">
                    <option value="">Todos los técnicos</option>
                    @foreach($tecnicos as $tec)
                    <option value="{{ $tec->id }}" {{ request('tecnico')==$tec->id ? 'selected':'' }}>
                        {{ $tec->nombre }}
                    </option>
                    @endforeach
                </select>
            </div>

            <div class="col-12 col-md-1 d-flex gap-1">
                <button type="submit" class="btn btn-sm btn-secondary flex-grow-1">Filtrar</button>
                <a href="{{ route('torre.index') }}" class="btn btn-sm btn-outline-secondary" title="Limpiar filtros">✕</a>
            </div>
        </form>
    </div>
</div>

{{-- TABLA PC --}}
<div class="card d-none d-lg-block">
    <div class="table-responsive">
        <table class="table table-vcenter table-hover card-table table-sm">
            <thead>
                <tr>
                    <th style="width:60px"># Orden</th>
                    <th style="width:80px">Placa</th>
                    <th>Vehículo</th>
                    <th style="width:110px">Empresa</th>
                    <th style="width:70px">Área</th>
                    <th>Estado</th>
                    <th style="width:90px">Semáforo</th>
                    <th style="width:85px">Días faltantes</th>
                    <th style="width:95px">Salida estimada</th>
                    <th style="width:110px">Técnicos</th>
                    <th style="width:80px">Días / Daño</th>
                    <th style="width:60px"></th>
                </tr>
            </thead>
            <tbody>
                @forelse($ordenes as $ot)
                @php
                $filaClase = match($ot->estado_semaforo) {
                    'INCUMPLIDO'   => 'fila-incumplido',
                    'ENTREGAR_HOY' => 'fila-entregar',
                    'A_TIEMPO'     => 'fila-a-tiempo',
                    default        => '',
                };
                $dfal = $ot->salida_estimada
                    ? (int) now()->startOfDay()->diffInDays($ot->salida_estimada, false)
                    : null;
                @endphp
                <tr class="{{ $filaClase }}">
                    <td class="fw-bold small">{{ $ot->numero_ot }}</td>
                    <td>
                        <span class="badge bg-blue-lt fw-bold">{{ $ot->vehiculo->placa }}</span>
                    </td>
                    <td class="small">
                        <div class="fw-medium">{{ $ot->vehiculo->marca->nombre }} {{ $ot->vehiculo->modelo?->nombre }}</div>
                        @if($ot->clientePersona)
                        <div class="text-muted">{{ $ot->clientePersona->nombre }}</div>
                        @endif
                    </td>
                    <td class="small">{{ $ot->empresaCliente->nombre }}</td>
                    <td><x-area-badge :area="$ot->area" /></td>
                    <td><x-estado-badge :estado="$ot->estado_proceso" /></td>
                    <td><x-semaforo :estado="$ot->estado_semaforo" /></td>
                    <td class="text-center">
                        @if($dfal !== null)
                            @if($dfal > 0)
                            <span class="dfal-positivo">+{{ $dfal }}</span>
                            @elseif($dfal === 0)
                            <span class="dfal-hoy">HOY</span>
                            @else
                            <span class="dfal-negativo">{{ $dfal }}</span>
                            @endif
                        @else
                        <span class="text-muted">—</span>
                        @endif
                    </td>
                    <td class="small text-muted">
                        {{ $ot->salida_estimada?->format('d/m/Y') ?? '—' }}
                    </td>
                    <td class="small">
                        @foreach([
                            [$ot->tecnicoLat,  'Latonero'],
                            [$ot->tecnicoPrep, 'Preparador'],
                            [$ot->tecnicoPint, 'Pintor'],
                            [$ot->tecnicoMec,  'Mecánico'],
                        ] as [$tec, $esp])
                        @if($tec)
                        <div class="text-nowrap small">
                            <span class="text-muted">{{ $esp }}:</span>
                            {{ explode(' ', $tec->nombre)[0] }}
                        </div>
                        @endif
                        @endforeach
                    </td>
                    <td class="small text-center">
                        @if($ot->dr)
                        <div><span class="text-muted">Días:</span> <strong>{{ $ot->dr }}</strong></div>
                        <x-tg-badge :tg="$ot->tg" />
                        @else
                        <span class="text-muted">—</span>
                        @endif
                    </td>
                    <td>
                        <a href="{{ route('ordenes.show', $ot) }}"
                           class="btn btn-sm btn-outline-primary">Ver</a>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="12" class="text-center text-muted py-4">
                        No hay órdenes en esta vista.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($ordenes->hasPages())
    <div class="card-footer">{{ $ordenes->links() }}</div>
    @endif
</div>

{{-- CARDS MÓVIL / TABLET --}}
<div class="d-lg-none">
    @forelse($ordenes as $ot)
    @php
    $dfal = $ot->salida_estimada
        ? (int) now()->startOfDay()->diffInDays($ot->salida_estimada, false)
        : null;
    $bordeClase = match($ot->estado_semaforo) {
        'INCUMPLIDO'   => 'border-danger',
        'ENTREGAR_HOY' => 'border-warning',
        'A_TIEMPO'     => 'border-primary',
        default        => '',
    };
    @endphp
    <div class="card mb-2 border-start border-4 {{ $bordeClase }}">
        <div class="card-body p-3">

            {{-- Fila 1: OT, placa, semáforo --}}
            <div class="d-flex justify-content-between align-items-start mb-1">
                <div>
                    <span class="fw-bold text-primary me-1">#{{ $ot->numero_ot }}</span>
                    <span class="badge bg-blue-lt fw-bold">{{ $ot->vehiculo->placa }}</span>
                    <x-area-badge :area="$ot->area" />
                </div>
                <x-semaforo :estado="$ot->estado_semaforo" />
            </div>

            {{-- Fila 2: Vehículo --}}
            <div class="fw-medium small mb-1">
                {{ $ot->vehiculo->marca->nombre }} {{ $ot->vehiculo->modelo?->nombre }}
                @if($ot->clientePersona)
                <span class="text-muted">— {{ $ot->clientePersona->nombre }}</span>
                @endif
            </div>

            {{-- Fila 3: Estado + empresa --}}
            <div class="d-flex flex-wrap gap-1 align-items-center mb-2">
                <x-estado-badge :estado="$ot->estado_proceso" />
                <span class="text-muted small">{{ $ot->empresaCliente->nombre }}</span>
                @if($ot->tg)
                <x-tg-badge :tg="$ot->tg" />
                @endif
            </div>

            {{-- Fila 4: Fechas y D_FAL --}}
            <div class="d-flex justify-content-between align-items-center">
                <div class="small text-muted">
                    @if($ot->salida_estimada)
                    Entrega: {{ $ot->salida_estimada->format('d/m/Y') }}
                    @if($dfal !== null)
                        @if($dfal > 0)
                        <span class="dfal-positivo ms-1">(+{{ $dfal }}d)</span>
                        @elseif($dfal === 0)
                        <span class="dfal-hoy ms-1">(HOY)</span>
                        @else
                        <span class="dfal-negativo ms-1">({{ $dfal }}d)</span>
                        @endif
                    @endif
                    @else
                    <span class="text-muted">Sin fecha estimada</span>
                    @endif
                </div>
                <a href="{{ route('ordenes.show', $ot) }}" class="btn btn-sm btn-primary">Ver OT</a>
            </div>

        </div>
    </div>
    @empty
    <div class="text-center text-muted py-4">No hay órdenes en esta vista.</div>
    @endforelse

    @if($ordenes->hasPages())
    <div class="mt-2">{{ $ordenes->links() }}</div>
    @endif
</div>

@endsection
