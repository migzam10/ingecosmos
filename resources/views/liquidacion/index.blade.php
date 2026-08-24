@extends('layouts.app')
@section('title','Liquidación de Técnicos')
@section('page_title','Liquidación de Técnicos')
@section('breadcrumb','Administración')

@section('page_actions')
<a href="{{ route('liquidacion.planilla', ['mes' => $mes, 'anio' => $anio]) }}" class="btn btn-primary btn-sm">
    Planilla del mes
</a>
@endsection

@section('content')

{{-- Selector de mes --}}
<div class="card mb-3">
    <div class="card-body py-2">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-auto">
                <label class="form-label small">Mes</label>
                <select name="mes" class="form-select form-select-sm">
                    @foreach(['1'=>'Enero','2'=>'Febrero','3'=>'Marzo','4'=>'Abril','5'=>'Mayo','6'=>'Junio',
                              '7'=>'Julio','8'=>'Agosto','9'=>'Septiembre','10'=>'Octubre','11'=>'Noviembre','12'=>'Diciembre'] as $n => $nombre)
                    <option value="{{ $n }}" {{ $mes == $n ? 'selected' : '' }}>{{ $nombre }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-auto">
                <label class="form-label small">Año</label>
                <select name="anio" class="form-select form-select-sm">
                    @foreach(range(now()->year, 2024, -1) as $a)
                    <option value="{{ $a }}" {{ $anio == $a ? 'selected' : '' }}>{{ $a }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-auto">
                <button class="btn btn-secondary btn-sm">Ver</button>
            </div>
        </form>
    </div>
</div>

{{-- Tabla de técnicos --}}
<div class="card">
    <div class="table-responsive">
        <table class="table table-vcenter table-hover card-table">
            <thead>
                <tr>
                    <th>Técnico</th>
                    <th>Especialidades</th>
                    <th class="text-end">Órdenes trabajadas</th>
                    <th class="text-end">Total a pagar</th>
                    <th class="text-end">Avances pagados</th>
                    <th class="text-end">Saldo pendiente</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @php
                $nombresEsp = ['LAT'=>'Latonero','PREP'=>'Preparador','PINT'=>'Pintor',
                               'MEC'=>'Mecánico','ELEC'=>'Electricista','AA'=>'Aire Acondicionado','SCANNER'=>'Diagnóstico'];
                @endphp
                @forelse($resumen as $r)
                <tr>
                    <td class="fw-medium">{{ $r['tecnico']->nombre }}</td>
                    <td class="small text-muted">
                        {{ implode(', ', array_map(fn($e) => $nombresEsp[$e] ?? $e, $r['tecnico']->especialidades ?: [])) }}
                    </td>
                    <td class="text-end">{{ $r['trabajos']->count() }}</td>
                    <td class="text-end fw-bold">
                        $ {{ number_format($r['total_ganado'], 0, ',', '.') }}
                    </td>
                    <td class="text-end text-muted">
                        $ {{ number_format($r['total_avances'], 0, ',', '.') }}
                    </td>
                    <td class="text-end fw-bold {{ $r['saldo'] > 0 ? 'text-danger' : 'text-success' }}">
                        $ {{ number_format($r['saldo'], 0, ',', '.') }}
                    </td>
                    <td>
                        <div class="d-flex gap-1">
                            <a href="{{ route('liquidacion.show', [$r['tecnico'], 'mes' => $mes, 'anio' => $anio]) }}"
                               class="btn btn-sm btn-outline-primary">Detalle</a>
                            @if($r['total_ganado'] > 0)
                            <a href="{{ route('liquidacion.pdf', [$r['tecnico'], 'mes' => $mes, 'anio' => $anio]) }}"
                               class="btn btn-sm btn-outline-secondary" target="_blank">PDF</a>
                            @endif
                        </div>
                    </td>
                </tr>
                @empty
                <tr><td colspan="7" class="text-center text-muted py-4">Sin técnicos activos.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

@endsection
