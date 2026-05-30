@extends('layouts.app')

@section('title', 'Editar OT #' . $orden->numero_ot)
@section('page_title', 'Editar OT #' . $orden->numero_ot)
@section('breadcrumb', 'Órdenes de Trabajo')

@section('content')
<form method="POST" action="{{ route('ordenes.update', $orden) }}" id="form-editar-ot">
@csrf
@method('PUT')

<div class="row g-3">

    {{-- ============ COLUMNA PRINCIPAL ============ --}}
    <div class="col-12 col-lg-8">

        {{-- VEHÍCULO (placa no editable) --}}
        <div class="card mb-3">
            <div class="card-header"><h3 class="card-title">Vehículo</h3></div>
            <div class="card-body">
                <div class="alert alert-info py-2 mb-3">
                    <strong>Placa:</strong>
                    <span class="badge bg-blue-lt fw-bold fs-5 ms-1">{{ $orden->vehiculo->placa }}</span>
                    <span class="text-muted small ms-2">— La placa no se puede modificar. Para cambiarla, elimine esta OT y cree una nueva.</span>
                </div>

                <div class="row g-2">
                    <div class="col-12 col-md-4">
                        <label class="form-label">Marca <span class="text-danger">*</span></label>
                        <select name="id_marca" id="select-marca" class="form-select" required>
                            <option value="">Seleccionar...</option>
                            @foreach($marcas as $marca)
                            <option value="{{ $marca->id }}"
                                {{ old('id_marca', $orden->vehiculo->id_marca) == $marca->id ? 'selected' : '' }}>
                                {{ $marca->nombre }}
                            </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-12 col-md-4">
                        <label class="form-label">Modelo</label>
                        <select name="id_modelo" id="select-modelo" class="form-select">
                            <option value="">Sin modelo específico</option>
                            @foreach($modelos as $modelo)
                            <option value="{{ $modelo->id }}"
                                {{ old('id_modelo', $orden->vehiculo->id_modelo) == $modelo->id ? 'selected' : '' }}>
                                {{ $modelo->nombre }}
                            </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-6 col-md-2">
                        <label class="form-label">Color</label>
                        <input type="text" name="color" class="form-control"
                               value="{{ old('color', $orden->vehiculo->color) }}" placeholder="Blanco">
                    </div>
                    <div class="col-6 col-md-2">
                        <label class="form-label">Año</label>
                        <input type="number" name="anio" class="form-control"
                               value="{{ old('anio', $orden->vehiculo->anio) }}"
                               min="1980" max="{{ date('Y') + 1 }}">
                    </div>
                </div>
            </div>
        </div>

        {{-- PROPIETARIO --}}
        <div class="card mb-3">
            <div class="card-header"><h3 class="card-title">Propietario / Cliente</h3></div>
            <div class="card-body">
                <div class="row g-2">
                    <div class="col-12 col-md-6">
                        <label class="form-label">Nombre completo <span class="text-danger">*</span></label>
                        <input type="text" name="nombre_cliente" class="form-control" required
                               value="{{ old('nombre_cliente', $orden->clientePersona?->nombre) }}">
                    </div>
                    <div class="col-12 col-md-3">
                        <label class="form-label">Cédula / NIT</label>
                        <input type="text" name="cedula_cliente" class="form-control"
                               value="{{ old('cedula_cliente', $orden->clientePersona?->cedula) }}">
                    </div>
                    <div class="col-12 col-md-3">
                        <label class="form-label">Teléfono</label>
                        <input type="text" name="telefono_cliente" class="form-control"
                               value="{{ old('telefono_cliente', $orden->clientePersona?->telefono) }}">
                    </div>
                    <div class="col-12 col-md-5">
                        <label class="form-label">Email</label>
                        <input type="email" name="email_cliente" class="form-control"
                               value="{{ old('email_cliente', $orden->clientePersona?->email) }}">
                    </div>
                </div>
            </div>
        </div>

        {{-- DATOS DE INGRESO --}}
        <div class="card mb-3">
            <div class="card-header"><h3 class="card-title">Datos de Ingreso</h3></div>
            <div class="card-body">
                <div class="row g-2">
                    <div class="col-12 col-md-5">
                        <label class="form-label">Empresa / CIA <span class="text-danger">*</span></label>
                        <select name="id_empresa_cliente" class="form-select" required>
                            <option value="">Seleccionar...</option>
                            @foreach($empresas as $empresa)
                            <option value="{{ $empresa->id }}"
                                {{ old('id_empresa_cliente', $orden->id_empresa_cliente) == $empresa->id ? 'selected' : '' }}>
                                {{ $empresa->nombre }} (Tipo {{ $empresa->tipo }})
                            </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-6 col-md-3">
                        <label class="form-label">Área <span class="text-danger">*</span></label>
                        <select name="area" class="form-select" required>
                            <option value="LYP"      {{ old('area', $orden->area) == 'LYP'      ? 'selected' : '' }}>Latonería y Pintura</option>
                            <option value="MECANICA" {{ old('area', $orden->area) == 'MECANICA' ? 'selected' : '' }}>Mecánica</option>
                        </select>
                    </div>
                    <div class="col-6 col-md-2">
                        <label class="form-label">Fecha ingreso <span class="text-danger">*</span></label>
                        <input type="date" name="fecha_ingreso" class="form-control" required
                               value="{{ old('fecha_ingreso', $orden->fecha_ingreso->format('Y-m-d')) }}">
                    </div>
                    <div class="col-6 col-md-2">
                        <label class="form-label">KM ingreso <span class="text-danger">*</span></label>
                        <input type="number" name="km_ingreso" class="form-control" required min="0"
                               value="{{ old('km_ingreso', $orden->km_ingreso) }}">
                    </div>
                    <div class="col-12 col-md-5">
                        <label class="form-label">Número de caso <small class="text-muted">(aseguradora)</small></label>
                        <input type="text" name="referencia_forc" class="form-control"
                               value="{{ old('referencia_forc', $orden->referencia_forc) }}"
                               placeholder="Ej: SURA-2026-00123">
                    </div>
                </div>
            </div>
        </div>

        {{-- OBSERVACIONES --}}
        <div class="card mb-3">
            <div class="card-body">
                <label class="form-label">Observaciones generales</label>
                <textarea name="observaciones" class="form-control" rows="3">{{ old('observaciones', $orden->observaciones) }}</textarea>
            </div>
        </div>

    </div>

    {{-- ============ COLUMNA LATERAL ============ --}}
    <div class="col-12 col-lg-4">

        {{-- CHECKBOXES --}}
        <div class="card mb-3">
            <div class="card-header"><h3 class="card-title">Entrega de Documentos</h3></div>
            <div class="card-body">
                <div class="mb-2">
                    <label class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" name="llaves_entregadas" value="1"
                               {{ old('llaves_entregadas', $orden->llaves_entregadas) ? 'checked' : '' }}>
                        <span class="form-check-label">Llaves entregadas</span>
                    </label>
                </div>
                <div class="mb-2">
                    <label class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" name="documentos_entregados" value="1"
                               {{ old('documentos_entregados', $orden->documentos_entregados) ? 'checked' : '' }}>
                        <span class="form-check-label">Documentos entregados</span>
                    </label>
                </div>
                <div>
                    <label class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" name="ingreso_grua" value="1"
                               {{ old('ingreso_grua', $orden->ingreso_grua) ? 'checked' : '' }}>
                        <span class="form-check-label">Ingresó en grúa</span>
                    </label>
                </div>
            </div>
        </div>

        {{-- COMBUSTIBLE --}}
        <div class="card mb-3">
            <div class="card-header"><h3 class="card-title">Nivel de Combustible</h3></div>
            <div class="card-body">
                <div class="d-flex align-items-center gap-3 mb-2">
                    <span class="text-muted small">E</span>
                    <input type="range" class="form-range flex-grow-1" name="nivel_combustible"
                           id="range-combustible" min="0" max="10"
                           value="{{ old('nivel_combustible', $orden->nivel_combustible) }}"
                           oninput="document.getElementById('val-combustible').textContent = this.value">
                    <span class="text-muted small">F</span>
                </div>
                <div class="text-center">
                    <span class="fw-bold" id="val-combustible">{{ old('nivel_combustible', $orden->nivel_combustible) }}</span>
                    <span class="text-muted">/10</span>
                </div>
            </div>
        </div>

        {{-- BOTONES --}}
        <div class="card">
            <div class="card-body d-grid gap-2">
                <button type="submit" class="btn btn-primary btn-lg">Guardar cambios</button>
                <a href="{{ route('ordenes.show', $orden) }}" class="btn btn-outline-secondary">Cancelar</a>
            </div>
        </div>

    </div>
</div>
</form>
@endsection

@push('scripts')
<script>
const MODELOS_URL = '{{ route("api.modelos") }}';

document.getElementById('select-marca').addEventListener('change', function () {
    cargarModelos(this.value, null);
});

function cargarModelos(idMarca, idModeloSeleccionado) {
    const sel = document.getElementById('select-modelo');
    if (!idMarca) { sel.innerHTML = '<option value="">Sin modelo específico</option>'; return; }
    sel.innerHTML = '<option value="">Cargando...</option>';
    fetch(`${MODELOS_URL}?id_marca=${idMarca}`)
        .then(r => r.json())
        .then(data => {
            sel.innerHTML = '<option value="">Sin modelo específico</option>';
            data.forEach(m => {
                const opt = document.createElement('option');
                opt.value = m.id;
                opt.textContent = m.nombre;
                if (idModeloSeleccionado && m.id == idModeloSeleccionado) opt.selected = true;
                sel.appendChild(opt);
            });
        });
}
</script>
@endpush
