@extends('layouts.app')

@section('title', 'Planilla de Contratistas')
@section('page_title', 'Planilla de Contratistas')
@section('breadcrumb', 'Liquidación')

@section('page_actions')
<div class="d-flex gap-2 align-items-center">
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
    <a href="{{ route('liquidacion.planilla.pdf', ['mes' => $mes, 'anio' => $anio]) }}"
       class="btn btn-primary btn-sm" target="_blank">Descargar PDF</a>
    <a href="{{ route('liquidacion.index', ['mes' => $mes, 'anio' => $anio]) }}"
       class="btn btn-outline-secondary btn-sm"><x-icon name="arrow-left" /> Volver</a>
</div>
@endsection

@section('content')
@php
    // Acumuladores para la fila TOTAL
    $tDev = 0; $tNeto = 0; $tSaldo = 0;
    $tDed = array_fill_keys(array_keys($deducciones), 0);
@endphp
<div class="card">
    <div class="card-header">
        <h3 class="card-title mb-0">Contratistas independientes — {{ ['','Enero','Febrero','Marzo','Abril','Mayo','Junio','Julio','Agosto','Septiembre','Octubre','Noviembre','Diciembre'][$mes] }} {{ $anio }}</h3>
    </div>
    <div class="table-responsive">
        <table class="table table-sm table-bordered table-vcenter mb-0" style="min-width:1000px">
            <thead class="table-light">
                <tr>
                    <th rowspan="2" class="align-middle">Empleado</th>
                    <th rowspan="2" class="align-middle text-end">Devengado</th>
                    <th colspan="{{ count($deducciones) }}" class="text-center">Deducciones</th>
                    <th rowspan="2" class="align-middle text-end">Neto entregado</th>
                    <th rowspan="2" class="align-middle text-end">Saldo</th>
                </tr>
                <tr>
                    @foreach($deducciones as $label)
                    <th class="text-end small">{{ $label }}</th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @forelse($resumen as $r)
                @php
                    $tDev += $r['total_ganado']; $tNeto += $r['total_neto']; $tSaldo += $r['saldo'];
                    foreach ($deducciones as $col => $lbl) { $tDed[$col] += $r['deducciones'][$col] ?? 0; }
                @endphp
                <tr>
                    <td class="fw-medium">{{ $r['tecnico']->nombre }}</td>
                    <td class="text-end">{{ number_format($r['total_ganado'], 0, ',', '.') }}</td>
                    @foreach($deducciones as $col => $lbl)
                    <td class="text-end small">{{ ($r['deducciones'][$col] ?? 0) > 0 ? number_format($r['deducciones'][$col], 0, ',', '.') : '—' }}</td>
                    @endforeach
                    <td class="text-end">{{ $r['total_neto'] > 0 ? number_format($r['total_neto'], 0, ',', '.') : '—' }}</td>
                    <td class="text-end fw-bold">{{ number_format($r['saldo'], 0, ',', '.') }}</td>
                </tr>
                @empty
                <tr><td colspan="{{ count($deducciones) + 4 }}" class="text-center text-muted py-3">Sin técnicos activos.</td></tr>
                @endforelse
            </tbody>
            <tfoot class="table-light">
                <tr class="fw-bold">
                    <td>TOTAL</td>
                    <td class="text-end">{{ number_format($tDev, 0, ',', '.') }}</td>
                    @foreach($deducciones as $col => $lbl)
                    <td class="text-end small">{{ number_format($tDed[$col], 0, ',', '.') }}</td>
                    @endforeach
                    <td class="text-end">{{ number_format($tNeto, 0, ',', '.') }}</td>
                    <td class="text-end">{{ number_format($tSaldo, 0, ',', '.') }}</td>
                </tr>
            </tfoot>
        </table>
    </div>
</div>
@endsection
