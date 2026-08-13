@extends('layouts.app')

@section('title', 'Días Festivos')
@section('page_title', 'Días Festivos Colombia')
@section('breadcrumb', 'Administración')

@section('page_actions')
<div class="d-flex gap-2 flex-wrap">
    <a href="{{ route('admin.festivos.plantilla') }}" class="btn btn-outline-success btn-sm">
        <x-icon name="download" /> Descargar plantilla Excel
    </a>
</div>
@endsection

@section('content')

{{-- Alertas de importación con errores detallados --}}
@if(session('error_importacion'))
<div class="alert alert-danger alert-dismissible mb-3" role="alert">
    <div class="fw-bold mb-2">El archivo fue rechazado — corrige los siguientes errores:</div>
    <ul class="mb-0 ps-3">
        @foreach(session('error_importacion') as $err)
        <li class="small">{{ $err }}</li>
        @endforeach
    </ul>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif

<div class="row g-3">

    {{-- Columna izquierda: tabla --}}
    <div class="col-12 col-lg-8">

        {{-- Filtro de año --}}
        <div class="card mb-3">
            <div class="card-body py-2">
                <form method="GET" action="{{ route('admin.festivos.index') }}"
                      class="d-flex gap-2 align-items-center">
                    <label class="form-label mb-0 small fw-medium">Año:</label>
                    <select name="anio" class="form-select form-select-sm" style="width:100px"
                            onchange="this.form.submit()">
                        @foreach($aniosDisponibles as $a)
                        <option value="{{ $a }}" {{ $a == $anio ? 'selected' : '' }}>{{ $a }}</option>
                        @endforeach
                    </select>
                    <span class="text-muted small ms-2">
                        {{ $festivos->count() }} festivo(s) registrado(s)
                    </span>
                </form>
            </div>
        </div>

        {{-- Tabla --}}
        <div class="card">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-sm table-hover mb-0">
                        <thead>
                            <tr>
                                <th style="width:120px">Fecha</th>
                                <th style="width:80px">Día</th>
                                <th>Nombre</th>
                                <th style="width:60px"></th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($festivos as $f)
                            <tr>
                                <td class="fw-medium">{{ $f->fecha->format('d/m/Y') }}</td>
                                <td class="small text-muted">
                                    {{ ['Mon'=>'Lunes','Tue'=>'Martes','Wed'=>'Miércoles','Thu'=>'Jueves',
                                        'Fri'=>'Viernes','Sat'=>'Sábado','Sun'=>'Domingo'][$f->fecha->format('D')] }}
                                </td>
                                <td>{{ $f->nombre }}</td>
                                <td>
                                    <form method="POST" action="{{ route('admin.festivos.destroy', $f) }}"
                                          data-confirm="¿Eliminar el festivo \"{{ $f->nombre }}\" ({{ $f->fecha->format('d/m/Y') }})?">
                                        @csrf @method('DELETE')
                                        <button class="btn btn-sm btn-ghost-danger py-0 px-1"><x-icon name="x" /></button>
                                    </form>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="4" class="text-center text-muted py-4">
                                    No hay festivos registrados para {{ $anio }}.
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    </div>

    {{-- Columna derecha: formularios --}}
    <div class="col-12 col-lg-4">

        {{-- Agregar festivo individual --}}
        <div class="card mb-3">
            <div class="card-header">
                <h3 class="card-title">Agregar festivo</h3>
            </div>
            <div class="card-body">
                <form method="POST" action="{{ route('admin.festivos.store') }}">
                    @csrf
                    <x-errores />
                    <div class="mb-2">
                        <label class="form-label small">Fecha</label>
                        <input type="date" name="fecha"
                               class="form-control form-control-sm @error('fecha') is-invalid @enderror"
                               value="{{ old('fecha') }}" required>
                        @error('fecha')
                        <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="mb-3">
                        <label class="form-label small">Nombre del festivo</label>
                        <input type="text" name="nombre"
                               class="form-control form-control-sm @error('nombre') is-invalid @enderror"
                               value="{{ old('nombre') }}"
                               placeholder="Ej: Día de la Independencia" maxlength="100" required>
                        @error('nombre')
                        <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <button type="submit" class="btn btn-primary btn-sm w-100">Agregar</button>
                </form>
            </div>
        </div>

        {{-- Importar desde Excel --}}
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Importar desde Excel</h3>
            </div>
            <div class="card-body">
                <div class="alert alert-info py-2 mb-3 small">
                    <strong>Formato requerido (.xlsx / .xls):</strong>
                    <ul class="mb-0 mt-1 ps-3">
                        <li>Fila 1: encabezados <code>FECHA</code> y <code>NOMBRE</code> (exactos)</li>
                        <li>Columna A: fecha en formato <code>YYYY-MM-DD</code></li>
                        <li>Columna B: nombre del festivo</li>
                        <li>Máx. 200 festivos por importación</li>
                        <li>Las fechas ya existentes se omiten automáticamente</li>
                    </ul>
                    <div class="mt-2">
                        <a href="{{ route('admin.festivos.plantilla') }}"
                           class="text-success fw-medium"><x-icon name="download" /> Descargar plantilla oficial</a>
                    </div>
                </div>

                <form method="POST" action="{{ route('admin.festivos.importar') }}"
                      enctype="multipart/form-data">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label small">Archivo Excel</label>
                        <input type="file" name="archivo" accept=".xlsx,.xls"
                               class="form-control form-control-sm @error('archivo') is-invalid @enderror"
                               required>
                        @error('archivo')
                        <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <button type="submit" class="btn btn-success btn-sm w-100">
                        <x-icon name="upload" /> Importar festivos
                    </button>
                </form>
            </div>
        </div>

    </div>

</div>
@endsection
