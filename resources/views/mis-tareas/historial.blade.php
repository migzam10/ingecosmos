@extends('layouts.app')

@section('title', 'Historial de Trabajos')
@section('page_title', 'Historial de Trabajos')
@section('breadcrumb', 'Mis Tareas')

@section('page_actions')
<a href="{{ route('mis-tareas.index') }}" class="btn btn-outline-secondary btn-sm">
    <x-icon name="arrow-left" /> Tareas activas
</a>
@endsection

@section('content')

@if(!$tecnico)
<div class="alert alert-warning">
    Tu usuario no tiene un técnico asociado. Contacta al administrador.
</div>
@else

{{-- Filtros --}}
<div class="card mb-3">
    <div class="card-body py-2">
        <form method="GET" action="{{ route('mis-tareas.historial') }}" class="row g-2 align-items-end">
            <div class="col-auto">
                <label class="form-label mb-1 small">Mes</label>
                <select name="mes" class="form-select form-select-sm">
                    <option value="">Todos</option>
                    @foreach(range(1,12) as $m)
                    <option value="{{ $m }}" {{ request('mes') == $m ? 'selected' : '' }}>
                        {{ \Carbon\Carbon::create(null,$m)->locale('es')->isoFormat('MMMM') }}
                    </option>
                    @endforeach
                </select>
            </div>
            <div class="col-auto">
                <label class="form-label mb-1 small">Año</label>
                <select name="anio" class="form-select form-select-sm">
                    <option value="">Todos</option>
                    @foreach(range(now()->year, now()->year - 3) as $a)
                    <option value="{{ $a }}" {{ request('anio') == $a ? 'selected' : '' }}>{{ $a }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-auto">
                <label class="form-label mb-1 small">Especialidad</label>
                <select name="especialidad" class="form-select form-select-sm">
                    <option value="">Todas</option>
                    @foreach(['LAT'=>'Latonero','PREP'=>'Preparador','PINT'=>'Pintor','MEC'=>'Mecánico','ELEC'=>'Electricista','AA'=>'Aire Acondicionado','SCANNER'=>'Diagnóstico'] as $k => $v)
                    <option value="{{ $k }}" {{ request('especialidad') === $k ? 'selected' : '' }}>{{ $v }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-auto">
                <button type="submit" class="btn btn-sm btn-primary">Filtrar</button>
                <a href="{{ route('mis-tareas.historial') }}" class="btn btn-sm btn-outline-secondary ms-1">Limpiar</a>
            </div>
        </form>
    </div>
</div>

@if($trabajos->count() > 0)
<div class="card">
    <div class="table-responsive">
        <table class="table table-sm table-hover mb-0">
            <thead>
                <tr>
                    <th># OT</th>
                    <th>Placa</th>
                    <th>Vehículo</th>
                    <th>Cliente</th>
                    <th>Función</th>
                    <th>Inicio</th>
                    <th>Fin</th>
                    <th class="text-end">Valor</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @php $nombresEsp = ['LAT'=>'Latonero','PREP'=>'Preparador','PINT'=>'Pintor','MEC'=>'Mecánico','ELEC'=>'Electricista','AA'=>'Aire Acondicionado','SCANNER'=>'Diagnóstico']; @endphp
                @foreach($trabajos as $t)
                <tr>
                    <td class="fw-bold text-primary">{{ $t->ot->numero_ot }}</td>
                    <td><span class="badge bg-blue-lt">{{ $t->ot->vehiculo->placa }}</span></td>
                    <td class="small">{{ $t->ot->vehiculo->marca->nombre }} {{ $t->ot->vehiculo->modelo?->nombre }}</td>
                    <td class="small">{{ $t->ot->empresaCliente->nombre }}</td>
                    <td><span class="badge bg-secondary-lt">{{ $nombresEsp[$t->especialidad] ?? $t->especialidad }}</span></td>
                    <td class="small text-muted">{{ $t->inicio_en?->format('d/m/Y H:i') ?? '—' }}</td>
                    <td class="small text-muted">{{ $t->fin_en?->format('d/m/Y H:i') ?? '—' }}</td>
                    <td class="text-end small">
                        @if($t->valor_liquidar > 0)
                            <span class="text-success">${{ number_format($t->valor_liquidar, 0, ',', '.') }}</span>
                        @else
                            <span class="text-muted">—</span>
                        @endif
                    </td>
                    <td>
                        <a href="{{ route('mis-tareas.detalle', $t) }}"
                           class="btn btn-xs btn-outline-primary">Ver</a>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>

{{-- Paginación --}}
<div class="mt-3 d-flex justify-content-center">
    {{ $trabajos->links() }}
</div>

@else
<div class="card">
    <div class="card-body text-center text-muted py-5">
        No hay trabajos finalizados con los filtros seleccionados.
    </div>
</div>
@endif

@endif
@endsection
