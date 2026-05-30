@extends('layouts.app')
@section('title', isset($empresa) ? 'Editar Empresa' : 'Nueva Empresa')
@section('page_title', isset($empresa) ? 'Editar: ' . $empresa->nombre : 'Nueva Empresa / CIA')
@section('breadcrumb', 'Empresas')
@section('content')
<div class="row justify-content-center">
<div class="col-12 col-lg-7">
<form method="POST" action="{{ isset($empresa) ? route('admin.empresas.update', $empresa) : route('admin.empresas.store') }}">
    @csrf @if(isset($empresa)) @method('PUT') @endif
    <div class="card">
        <div class="card-body">

            <div class="mb-3">
                <label class="form-label">Nombre <span class="text-danger">*</span></label>
                <input type="text" name="nombre" class="form-control @error('nombre') is-invalid @enderror"
                       value="{{ old('nombre', $empresa->nombre ?? '') }}" required maxlength="100">
                @error('nombre')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            <div class="row g-2 mb-3">
                <div class="col-12 col-md-6">
                    <label class="form-label">Tipo <span class="text-danger">*</span></label>
                    <div class="d-flex gap-3">
                        @foreach(['A' => 'Tipo A — incluye repuestos en el total', 'B' => 'Tipo B — cliente pone sus repuestos'] as $val => $desc)
                        <label class="form-check">
                            <input class="form-check-input" type="radio" name="tipo" value="{{ $val }}"
                                   {{ old('tipo', $empresa->tipo ?? 'A') === $val ? 'checked' : '' }} required>
                            <span class="form-check-label">
                                <strong>{{ $val }}</strong><br>
                                <small class="text-muted">{{ $desc }}</small>
                            </span>
                        </label>
                        @endforeach
                    </div>
                    @error('tipo')<div class="text-danger small">{{ $message }}</div>@enderror
                </div>
                <div class="col-12 col-md-6">
                    <label class="form-label">Tarifa hora (COP) <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <span class="input-group-text">$</span>
                        <input type="number" name="tarifa_hora" class="form-control @error('tarifa_hora') is-invalid @enderror"
                               value="{{ old('tarifa_hora', $empresa->tarifa_hora ?? 50000) }}"
                               min="0" step="1" required>
                    </div>
                    @error('tarifa_hora')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
            </div>

            <div class="mb-3">
                <label class="form-label">Metas de días según TG</label>
                <div class="row g-2">
                    @foreach(['leve' => 'Leve', 'medio' => 'Medio', 'fuerte' => 'Fuerte'] as $campo => $label)
                    <div class="col-4">
                        <label class="form-label small">{{ $label }}</label>
                        <div class="input-group input-group-sm">
                            <input type="number" name="meta_dias_{{ $campo }}"
                                   class="form-control"
                                   value="{{ old("meta_dias_{$campo}", $empresa->{"meta_dias_{$campo}"} ?? ($campo === 'leve' ? 5 : ($campo === 'medio' ? 10 : 13))) }}"
                                   min="1" max="30" required>
                            <span class="input-group-text">días</span>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>

            @isset($empresa)
            <div class="mb-0">
                <label class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" name="activa" value="1"
                           {{ old('activa', $empresa->activa) ? 'checked' : '' }}>
                    <span class="form-check-label">Empresa activa</span>
                </label>
            </div>
            @endisset

        </div>
        <div class="card-footer d-flex gap-2">
            <button type="submit" class="btn btn-primary">
                {{ isset($empresa) ? 'Guardar Cambios' : 'Crear Empresa' }}
            </button>
            <a href="{{ route('admin.empresas.index') }}" class="btn btn-outline-secondary">Cancelar</a>
        </div>
    </div>
</form>
</div></div>
@endsection
