@extends('layouts.app')
@section('title', isset($tecnico) ? 'Editar Técnico' : 'Nuevo Técnico')
@section('page_title', isset($tecnico) ? 'Editar: ' . $tecnico->nombre : 'Nuevo Técnico')
@section('breadcrumb','Técnicos')
@section('content')
<div class="row justify-content-center">
<div class="col-12 col-lg-7">
<form method="POST" action="{{ isset($tecnico) ? route('admin.tecnicos.update', $tecnico) : route('admin.tecnicos.store') }}">
    @csrf @if(isset($tecnico)) @method('PUT') @endif
    <div class="card">
        <div class="card-body">

            <div class="mb-3">
                <label class="form-label">Nombre del técnico <span class="text-danger">*</span></label>
                <input type="text" name="nombre" class="form-control @error('nombre') is-invalid @enderror"
                       value="{{ old('nombre', $tecnico->nombre ?? '') }}" required maxlength="100">
                @error('nombre')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            <div class="mb-3">
                <label class="form-label">
                    Especialidades <span class="text-danger">*</span>
                    <small class="text-muted">(puede tener más de una)</small>
                </label>
                @php
                $descs = [
                    'LAT'     => 'Latonero — trabaja el metal',
                    'PREP'    => 'Preparador — masilla y lijado',
                    'PINT'    => 'Pintor',
                    'MEC'     => 'Mecánico',
                    'ELEC'    => 'Electricista del vehículo',
                    'AA'      => 'Aire Acondicionado',
                    'SCANNER' => 'Diagnóstico electrónico',
                    'TAP'     => 'Tapicero',
                ];
                $seleccionadas = old('especialidades', $tecnico->especialidades ?? []);
                @endphp
                <div class="row g-2">
                    @foreach($especialidades as $esp)
                    <div class="col-6 col-md-4">
                        <label class="form-check">
                            <input class="form-check-input" type="checkbox"
                                   name="especialidades[]" value="{{ $esp }}"
                                   {{ in_array($esp, $seleccionadas) ? 'checked' : '' }}>
                            <span class="form-check-label">
                                <strong>{{ $esp }}</strong><br>
                                <small class="text-muted">{{ $descs[$esp] }}</small>
                            </span>
                        </label>
                    </div>
                    @endforeach
                </div>
                @error('especialidades')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
            </div>

            <div class="mb-3">
                <label class="form-label">
                    Usuario del sistema
                    <small class="text-muted">(opcional — para que pueda iniciar sesión)</small>
                </label>
                <select name="id_user" class="form-select">
                    <option value="">Sin usuario asignado</option>
                    @foreach($usuarios as $u)
                    <option value="{{ $u->id }}"
                        {{ old('id_user', $tecnico->id_user ?? '') == $u->id ? 'selected' : '' }}>
                        {{ $u->name }} — {{ $u->email }}
                    </option>
                    @endforeach
                </select>
                <div class="form-text">
                    Solo aparecen usuarios con rol TECNICO sin técnico asignado.
                    Crea primero el usuario en
                    <a href="{{ route('admin.usuarios.create') }}" target="_blank">Administración <x-icon name="arrow-right" /> Usuarios</a>.
                </div>
            </div>

            @isset($tecnico)
            <div class="mb-0">
                <label class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" name="activo" value="1"
                           {{ old('activo', $tecnico->activo) ? 'checked' : '' }}>
                    <span class="form-check-label">Técnico activo</span>
                </label>
            </div>
            @endisset

        </div>
        <div class="card-footer d-flex gap-2">
            <button type="submit" class="btn btn-primary">
                {{ isset($tecnico) ? 'Guardar Cambios' : 'Crear Técnico' }}
            </button>
            <a href="{{ route('admin.tecnicos.index') }}" class="btn btn-outline-secondary">Cancelar</a>
        </div>
    </div>
</form>
</div></div>
@endsection
