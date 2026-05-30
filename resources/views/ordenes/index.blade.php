@extends('layouts.app')

@section('title', 'Órdenes de Trabajo')
@section('page_title', 'Órdenes de Trabajo')
@section('breadcrumb', 'Recepción')

@section('page_actions')
<a href="{{ route('ordenes.create') }}" class="btn btn-primary">
    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24"
         fill="none" stroke="currentColor" stroke-width="2" class="me-1">
        <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
        <line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/>
    </svg>
    Nueva OT
</a>
@endsection

@section('content')

{{-- Filtros --}}
<div class="card mb-3">
    <div class="card-body py-2">
        <form method="GET" action="{{ route('ordenes.index') }}" class="row g-2 align-items-end">
            <div class="col-12 col-md-4">
                <input type="text" name="buscar" class="form-control"
                       placeholder="Buscar por placa o # OT..."
                       value="{{ request('buscar') }}">
            </div>
            <div class="col-6 col-md-3">
                <select name="estado" class="form-select">
                    <option value="">Todos los estados</option>
                    <option value="PTE_COTIZACION"     {{ request('estado')=='PTE_COTIZACION'     ? 'selected' : '' }}>Pte. Cotización</option>
                    <option value="PTE_AUTORIZACION"   {{ request('estado')=='PTE_AUTORIZACION'   ? 'selected' : '' }}>Pte. Autorización</option>
                    <option value="PTE_REPUESTOS"      {{ request('estado')=='PTE_REPUESTOS'      ? 'selected' : '' }}>Pte. Repuestos</option>
                    <option value="EN_PROCESO"         {{ request('estado')=='EN_PROCESO'         ? 'selected' : '' }}>En Proceso</option>
                    <option value="PROGRAMADO_ENTREGA" {{ request('estado')=='PROGRAMADO_ENTREGA' ? 'selected' : '' }}>Prog. Entrega</option>
                </select>
            </div>
            <div class="col-6 col-md-2">
                <select name="area" class="form-select">
                    <option value="">Todas las áreas</option>
                    <option value="LYP"      {{ request('area')=='LYP'      ? 'selected' : '' }}>LYP</option>
                    <option value="MECANICA" {{ request('area')=='MECANICA' ? 'selected' : '' }}>Mecánica</option>
                </select>
            </div>
            <div class="col-12 col-md-3 d-flex gap-2">
                <button type="submit" class="btn btn-secondary flex-grow-1">Filtrar</button>
                <a href="{{ route('ordenes.index') }}" class="btn btn-outline-secondary">Limpiar</a>
            </div>
        </form>
    </div>
</div>

{{-- Tabla PC --}}
<div class="card d-none d-md-block">
    <div class="table-responsive">
        <table class="table table-vcenter table-hover card-table">
            <thead>
                <tr>
                    <th># OT</th>
                    <th>Placa</th>
                    <th>Vehículo</th>
                    <th>Empresa</th>
                    <th>Área</th>
                    <th>Estado</th>
                    <th>Semáforo</th>
                    <th>Ingreso</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse($ordenes as $ot)
                <tr>
                    <td class="fw-bold">{{ $ot->numero_ot }}</td>
                    <td>
                        <span class="badge bg-blue-lt fw-bold">{{ $ot->vehiculo->placa }}</span>
                    </td>
                    <td>
                        {{ $ot->vehiculo->marca->nombre }}
                        {{ $ot->vehiculo->modelo?->nombre }}
                        @if($ot->vehiculo->color)
                        <small class="text-muted">· {{ $ot->vehiculo->color }}</small>
                        @endif
                    </td>
                    <td>{{ $ot->empresaCliente->nombre }}</td>
                    <td><span class="badge bg-secondary-lt">{{ $ot->area }}</span></td>
                    <td><x-estado-badge :estado="$ot->estado_proceso" /></td>
                    <td><x-semaforo :estado="$ot->estado_semaforo" /></td>
                    <td class="text-muted small">{{ $ot->fecha_ingreso->format('d/m/Y') }}</td>
                    <td>
                        <a href="{{ route('ordenes.show', $ot) }}" class="btn btn-sm btn-outline-primary">
                            Ver
                        </a>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="9" class="text-center text-muted py-4">
                        No hay órdenes de trabajo activas.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($ordenes->hasPages())
    <div class="card-footer d-flex align-items-center">
        {{ $ordenes->links() }}
    </div>
    @endif
</div>

{{-- Cards móvil --}}
<div class="d-md-none">
    @forelse($ordenes as $ot)
    <div class="card mb-2">
        <div class="card-body p-3">
            <div class="d-flex justify-content-between align-items-start mb-2">
                <div>
                    <span class="fw-bold text-primary"># {{ $ot->numero_ot }}</span>
                    <span class="badge bg-blue-lt ms-1">{{ $ot->vehiculo->placa }}</span>
                </div>
                <x-semaforo :estado="$ot->estado_semaforo" />
            </div>
            <div class="fw-medium">
                {{ $ot->vehiculo->marca->nombre }} {{ $ot->vehiculo->modelo?->nombre }}
            </div>
            <div class="text-muted small mb-2">
                {{ $ot->empresaCliente->nombre }} · {{ $ot->area }}
                · {{ $ot->fecha_ingreso->format('d/m/Y') }}
            </div>
            <div class="d-flex justify-content-between align-items-center">
                <x-estado-badge :estado="$ot->estado_proceso" />
                <a href="{{ route('ordenes.show', $ot) }}" class="btn btn-sm btn-primary">Ver OT</a>
            </div>
        </div>
    </div>
    @empty
    <div class="text-center text-muted py-4">No hay órdenes activas.</div>
    @endforelse

    @if($ordenes->hasPages())
    <div class="mt-2">{{ $ordenes->links() }}</div>
    @endif
</div>

@endsection
