@extends('layouts.app')

@section('title', isset($catalogoRepuesto) ? 'Editar Repuesto' : 'Nuevo Repuesto')
@section('page_title', isset($catalogoRepuesto) ? 'Editar Repuesto' : 'Nuevo Repuesto')
@section('breadcrumb', 'Catálogo Repuestos')

@section('content')
<div class="row justify-content-center">
<div class="col-12 col-lg-7">

<form method="POST"
      action="{{ isset($catalogoRepuesto) ? route('catalogo-repuestos.update', $catalogoRepuesto) : route('catalogo-repuestos.store') }}">
    @csrf
    @if(isset($catalogoRepuesto)) @method('PUT') @endif
    <x-errores />

    <div class="card">
        <div class="card-header">
            <h3 class="card-title">
                {{ isset($catalogoRepuesto) ? 'Editando: ' . $catalogoRepuesto->descripcion : 'Nuevo repuesto al catálogo' }}
            </h3>
        </div>
        <div class="card-body">

            {{-- NIVEL --}}
            <div class="mb-3">
                <label class="form-label">Nivel de aplicación <span class="text-danger">*</span></label>
                <div class="row g-2">
                    @foreach([
                        1 => ['Genérico',   'Aplica a todos los vehículos',              'bg-blue-lt'],
                        2 => ['Por Marca',  'Aplica a una marca específica',             'bg-teal-lt'],
                        3 => ['Por Modelo', 'Aplica a una marca y modelo específico',    'bg-purple-lt'],
                    ] as $n => [$label, $desc, $badge])
                    <div class="col-12 col-md-4">
                        <label class="form-check form-check-card">
                            <input class="form-check-input" type="radio" name="nivel"
                                   value="{{ $n }}" id="nivel{{ $n }}"
                                   {{ old('nivel', $catalogoRepuesto->nivel ?? 1) == $n ? 'checked' : '' }}
                                   onchange="actualizarNivel({{ $n }})">
                            <div class="form-check-label">
                                <div class="fw-bold">
                                    <span class="badge {{ $badge }} me-1">N{{ $n }}</span>
                                    {{ $label }}
                                </div>
                                <div class="text-muted small mt-1">{{ $desc }}</div>
                            </div>
                        </label>
                    </div>
                    @endforeach
                </div>
                @error('nivel')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
            </div>

            {{-- MARCA --}}
            <div id="campo-marca" class="mb-3"
                 style="{{ old('nivel', $catalogoRepuesto->nivel ?? 1) == 1 ? 'display:none' : '' }}">
                <label class="form-label">Marca <span class="text-danger">*</span></label>
                <select name="id_marca" id="select-marca-cat" class="form-select"
                        onchange="cargarModelosCat(this.value)">
                    <option value="">Seleccionar marca...</option>
                    @foreach($marcas as $marca)
                    <option value="{{ $marca->id }}"
                        {{ old('id_marca', $catalogoRepuesto->id_marca ?? '') == $marca->id ? 'selected' : '' }}>
                        {{ $marca->nombre }}
                    </option>
                    @endforeach
                </select>
                @error('id_marca')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
            </div>

            {{-- MODELO --}}
            <div id="campo-modelo" class="mb-3"
                 style="{{ old('nivel', $catalogoRepuesto->nivel ?? 1) != 3 ? 'display:none' : '' }}">
                <label class="form-label">Modelo <span class="text-danger">*</span></label>
                <select name="id_modelo" id="select-modelo-cat" class="form-select">
                    <option value="">Seleccionar modelo...</option>
                    @isset($modelos)
                    @foreach($modelos as $modelo)
                    <option value="{{ $modelo->id }}"
                        {{ old('id_modelo', $catalogoRepuesto->id_modelo ?? '') == $modelo->id ? 'selected' : '' }}>
                        {{ $modelo->nombre }}
                    </option>
                    @endforeach
                    @endisset
                </select>
                @error('id_modelo')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
            </div>

            {{-- DESCRIPCIÓN --}}
            <div class="mb-3">
                <label class="form-label">Descripción <span class="text-danger">*</span></label>
                <input type="text" name="descripcion" class="form-control @error('descripcion') is-invalid @enderror"
                       value="{{ old('descripcion', $catalogoRepuesto->descripcion ?? '') }}"
                       placeholder="Ej: Pastillas de freno delanteras"
                       required maxlength="200">
                @error('descripcion')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            {{-- PRECIO DE REFERENCIA --}}
            <div class="mb-3">
                <label class="form-label">Precio de referencia (COP) <span class="text-danger">*</span></label>
                <div class="input-group">
                    <span class="input-group-text">$</span>
                    <input type="number" name="precio_referencia"
                           class="form-control @error('precio_referencia') is-invalid @enderror"
                           value="{{ old('precio_referencia', $catalogoRepuesto->precio_referencia ?? 0) }}"
                           min="0" step="1" required>
                </div>
                <div class="form-text">El cotizador puede cambiar este precio en la cotización — es solo referencia.</div>
                @error('precio_referencia')<div class="text-danger small">{{ $message }}</div>@enderror
            </div>

            {{-- ACTIVO --}}
            <div class="mb-0">
                <label class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" name="activo" value="1"
                           {{ old('activo', $catalogoRepuesto->activo ?? true) ? 'checked' : '' }}>
                    <span class="form-check-label">Repuesto activo en el catálogo</span>
                </label>
            </div>

        </div>
        <div class="card-footer d-flex gap-2">
            <button type="submit" class="btn btn-primary">
                {{ isset($catalogoRepuesto) ? 'Guardar Cambios' : 'Agregar al Catálogo' }}
            </button>
            <a href="{{ route('catalogo-repuestos.index') }}" class="btn btn-outline-secondary">Cancelar</a>
        </div>
    </div>

</form>
</div>
</div>
@endsection

@push('scripts')
<script>
const MODELOS_URL = '{{ route("api.modelos") }}';

function actualizarNivel(nivel) {
    document.getElementById('campo-marca').style.display  = nivel > 1 ? '' : 'none';
    document.getElementById('campo-modelo').style.display = nivel == 3 ? '' : 'none';
}

function cargarModelosCat(idMarca) {
    const sel = document.getElementById('select-modelo-cat');
    sel.innerHTML = '<option value="">Cargando...</option>';

    if (!idMarca) {
        sel.innerHTML = '<option value="">Seleccionar modelo...</option>';
        return;
    }

    fetch(`${MODELOS_URL}?id_marca=${idMarca}`)
        .then(r => r.json())
        .then(data => {
            sel.innerHTML = '<option value="">Seleccionar modelo...</option>';
            data.forEach(m => {
                sel.innerHTML += `<option value="${m.id}">${m.nombre}</option>`;
            });
        });
}

document.addEventListener('DOMContentLoaded', function () {
    const nivelActual = document.querySelector('input[name="nivel"]:checked')?.value;
    if (nivelActual) actualizarNivel(parseInt(nivelActual));
});
</script>
@endpush
