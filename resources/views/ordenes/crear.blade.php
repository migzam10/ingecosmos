@extends('layouts.app')

@section('title', 'Nueva Orden de Trabajo')
@section('page_title', 'Nueva Orden de Trabajo')
@section('breadcrumb', 'Órdenes de Trabajo')

@section('content')
<form method="POST" action="{{ route('ordenes.store') }}" id="form-ot">
@csrf

<div class="row g-3">

    {{-- ============ COLUMNA PRINCIPAL ============ --}}
    <div class="col-12 col-lg-8">

        {{-- BÚSQUEDA DE PLACA --}}
        <div class="card mb-3">
            <div class="card-header"><h3 class="card-title">Vehículo</h3></div>
            <div class="card-body">

                <div class="row g-2 mb-3">
                    <div class="col-8 col-md-4">
                        <label class="form-label fw-bold">Placa</label>
                        <div class="input-group">
                            <input type="text" id="input-placa" name="placa"
                                   class="form-control text-uppercase fw-bold @error('placa') is-invalid @enderror"
                                   value="{{ old('placa') }}"
                                   placeholder="Ej: ABC123" maxlength="10" autocomplete="off">
                            <button type="button" id="btn-buscar-placa" class="btn btn-outline-secondary">
                                Buscar
                            </button>
                        </div>
                        @error('placa')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                        <div id="msg-placa" class="form-text"></div>
                    </div>
                </div>

                <div class="row g-2">
                    <div class="col-12 col-md-4">
                        <label class="form-label">Marca <span class="text-danger">*</span></label>
                        <select name="id_marca" id="select-marca"
                                class="form-select @error('id_marca') is-invalid @enderror" required>
                            <option value="">Seleccionar...</option>
                            @foreach($marcas as $marca)
                            <option value="{{ $marca->id }}" {{ old('id_marca') == $marca->id ? 'selected' : '' }}>
                                {{ $marca->nombre }}
                            </option>
                            @endforeach
                        </select>
                        @error('id_marca')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-12 col-md-4">
                        <label class="form-label">Modelo</label>
                        <select name="id_modelo" id="select-modelo" class="form-select">
                            <option value="">Seleccionar modelo...</option>
                        </select>
                    </div>
                    <div class="col-6 col-md-2">
                        <label class="form-label">Color</label>
                        <input type="text" name="color" id="input-color" class="form-control"
                               value="{{ old('color') }}" placeholder="Blanco">
                    </div>
                    <div class="col-6 col-md-2">
                        <label class="form-label">Año</label>
                        <input type="number" name="anio" id="input-anio" class="form-control"
                               value="{{ old('anio') }}" min="1980" max="{{ date('Y') + 1 }}" placeholder="{{ date('Y') }}">
                    </div>
                </div>

            </div>
        </div>

        {{-- DATOS DEL PROPIETARIO --}}
        <div class="card mb-3">
            <div class="card-header"><h3 class="card-title">Propietario / Cliente</h3></div>
            <div class="card-body">
                <div class="row g-2">
                    <div class="col-12 col-md-6">
                        <label class="form-label">Nombre completo <span class="text-danger">*</span></label>
                        <input type="text" name="nombre_cliente" id="input-nombre"
                               class="form-control @error('nombre_cliente') is-invalid @enderror"
                               value="{{ old('nombre_cliente') }}" required>
                        @error('nombre_cliente')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-12 col-md-3">
                        <label class="form-label">Cédula / NIT</label>
                        <input type="text" name="cedula_cliente" id="input-cedula"
                               class="form-control" value="{{ old('cedula_cliente') }}">
                    </div>
                    <div class="col-12 col-md-3">
                        <label class="form-label">Teléfono</label>
                        <input type="text" name="telefono_cliente" id="input-telefono"
                               class="form-control" value="{{ old('telefono_cliente') }}">
                    </div>
                    <div class="col-12 col-md-5">
                        <label class="form-label">Email</label>
                        <input type="email" name="email_cliente" id="input-email"
                               class="form-control" value="{{ old('email_cliente') }}">
                    </div>
                    <div class="col-12 col-md-5">
                        <label class="form-label">Dirección</label>
                        <input type="text" name="direccion_cliente" id="input-direccion"
                               class="form-control" value="{{ old('direccion_cliente') }}"
                               placeholder="Calle, barrio, ciudad">
                    </div>
                    <div class="col-12 col-md-3">
                        <label class="form-label">Fecha de cumpleaños</label>
                        <input type="date" name="fecha_cumpleanos_cliente" id="input-cumpleanos"
                               class="form-control" value="{{ old('fecha_cumpleanos_cliente') }}">
                    </div>
                </div>
            </div>
        </div>

        {{-- DATOS DE LA OT --}}
        <div class="card mb-3">
            <div class="card-header"><h3 class="card-title">Datos de Ingreso</h3></div>
            <div class="card-body">
                <div class="row g-2">
                    <div class="col-12 col-md-5">
                        <label class="form-label">Empresa / CIA <span class="text-danger">*</span></label>
                        <select name="id_empresa_cliente"
                                class="form-select @error('id_empresa_cliente') is-invalid @enderror" required>
                            <option value="">Seleccionar...</option>
                            @foreach($empresas as $empresa)
                            <option value="{{ $empresa->id }}"
                                {{ old('id_empresa_cliente') == $empresa->id ? 'selected' : '' }}>
                                {{ $empresa->nombre }} (Tipo {{ $empresa->tipo }})
                            </option>
                            @endforeach
                        </select>
                        @error('id_empresa_cliente')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-6 col-md-3">
                        <label class="form-label">Área <span class="text-danger">*</span></label>
                        <select name="area" class="form-select @error('area') is-invalid @enderror" required>
                            <option value="LYP"      {{ old('area','LYP')=='LYP'      ? 'selected' : '' }}>Latonería y Pintura</option>
                            <option value="MECANICA" {{ old('area')=='MECANICA' ? 'selected' : '' }}>Mecánica</option>
                        </select>
                        @error('area')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-6 col-md-2">
                        <label class="form-label">Fecha ingreso <span class="text-danger">*</span></label>
                        <input type="date" name="fecha_ingreso"
                               class="form-control @error('fecha_ingreso') is-invalid @enderror"
                               value="{{ old('fecha_ingreso', date('Y-m-d')) }}" required>
                        @error('fecha_ingreso')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-6 col-md-2">
                        <label class="form-label">KM ingreso <span class="text-danger">*</span></label>
                        <input type="number" name="km_ingreso"
                               class="form-control @error('km_ingreso') is-invalid @enderror"
                               value="{{ old('km_ingreso', 0) }}" min="0" required>
                        @error('km_ingreso')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-12 col-md-4">
                        <label class="form-label">Número de caso <small class="text-muted">(asignado por la aseguradora)</small></label>
                        <input type="text" name="referencia_forc" class="form-control"
                               value="{{ old('referencia_forc') }}" placeholder="Ej: SURA-2026-00123">
                    </div>
                </div>
            </div>
        </div>

        {{-- INVENTARIO B/R/G --}}
        <div class="card mb-3">
            <div class="card-header">
                <h3 class="card-title">Inventario del Vehículo</h3>
                <div class="card-options">
                    <small class="text-muted">
                        <span class="badge bg-success-lt me-1">B</span>Bueno
                        <span class="badge bg-warning-lt me-1 ms-1">R</span>Regular
                        <span class="badge bg-danger-lt me-1 ms-1">M</span>Malo
                    </small>
                </div>
            </div>
            <div class="card-body">

                @php
                $invSimples = [
                    'retrovisores'     => 'Retrovisores',
                    'retrovisor_interno'=> 'Retrovisor Interno',
                    'radio'            => 'Radio',
                    'encendedor'       => 'Encendedor',
                    'pito'             => 'Pito',
                    'tapizado'         => 'Tapizado',
                    'luz_techo'        => 'Luz techo',
                    'tapa_gasolina'    => 'Tapa gasolina',
                    'llave_pernos'     => 'Llave Pernos',
                    'herramientas'     => 'Herramientas',
                    'kit_carretera'    => 'Kit de Carretera',
                    'gato'             => 'Gato',
                    'extintor'         => 'Extintor',
                    'sensores'         => 'Sensores',
                    'camara_reversa'   => 'Cámara reversa',
                    'control_alarma'   => 'Control alarma',
                    'bateria'          => 'Batería',
                    'comando_ptas'     => 'Comando ptas',
                ];
                $invCantidad = [
                    'panoramicos' => 'Panorámicos',
                    'parlantes'   => 'Parlantes',
                    'rejillas_aa' => 'Rejillas A/A',
                    'plumillas'   => 'Plumillas',
                    'cinturones'  => 'Cinturones',
                    'manijas'     => 'Manijas',
                    'tapa_soles'  => 'Tapa soles',
                    'tapetes'     => 'Tapetes',
                ];
                @endphp

                {{-- Ítems sin cantidad --}}
                <div class="row g-2 mb-3">
                    @foreach($invSimples as $campo => $etiqueta)
                    <div class="col-6 col-md-4 col-lg-3">
                        <label class="form-label small mb-1">{{ $etiqueta }}</label>
                        <div class="btn-group w-100" role="group">
                            @foreach(['B'=>'success','R'=>'warning','M'=>'danger'] as $val => $color)
                            <input type="radio" class="btn-check"
                                   name="inv_{{ $campo }}" id="inv_{{ $campo }}_{{ $val }}_c"
                                   value="{{ $val }}"
                                   {{ old("inv_{$campo}") == $val ? 'checked' : '' }}>
                            <label class="btn btn-sm btn-outline-{{ $color }}"
                                   for="inv_{{ $campo }}_{{ $val }}_c">{{ $val }}</label>
                            @endforeach
                        </div>
                    </div>
                    @endforeach
                </div>

                {{-- Ítems con cantidad --}}
                <div class="border-top pt-3">
                    <div class="text-muted small mb-2">Ítems con cantidad</div>
                    <div class="row g-2">
                        @foreach($invCantidad as $campo => $etiqueta)
                        <div class="col-6 col-md-4 col-lg-3">
                            <label class="form-label small mb-1">{{ $etiqueta }}</label>
                            <div class="d-flex gap-1 align-items-center">
                                <input type="number" name="inv_{{ $campo }}_qty"
                                       class="form-control form-control-sm text-center"
                                       style="max-width:60px"
                                       value="{{ old("inv_{$campo}_qty") }}"
                                       min="0" max="99" placeholder="#">
                                <div class="btn-group flex-grow-1" role="group">
                                    @foreach(['B'=>'success','R'=>'warning','M'=>'danger'] as $val => $color)
                                    <input type="radio" class="btn-check"
                                           name="inv_{{ $campo }}" id="inv_{{ $campo }}_{{ $val }}_c"
                                           value="{{ $val }}"
                                           {{ old("inv_{$campo}") == $val ? 'checked' : '' }}>
                                    <label class="btn btn-sm btn-outline-{{ $color }}"
                                           for="inv_{{ $campo }}_{{ $val }}_c">{{ $val }}</label>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>

                <div class="mt-3">
                    <label class="form-label">Observaciones del inventario</label>
                    <textarea name="inv_observaciones" class="form-control" rows="2"
                              placeholder="Rayones, abolladuras, accesorios especiales...">{{ old('inv_observaciones') }}</textarea>
                </div>

            </div>
        </div>

        {{-- OBSERVACIONES OT --}}
        <div class="card mb-3">
            <div class="card-body">
                <label class="form-label">Observaciones generales de la OT</label>
                <textarea name="observaciones" class="form-control" rows="3"
                          placeholder="Descripción del daño, solicitud del cliente...">{{ old('observaciones') }}</textarea>
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
                        <input class="form-check-input" type="checkbox"
                               name="llaves_entregadas" value="1"
                               {{ old('llaves_entregadas') ? 'checked' : '' }}>
                        <span class="form-check-label">Llaves entregadas</span>
                    </label>
                </div>
                <div class="mb-2">
                    <label class="form-check form-switch">
                        <input class="form-check-input" type="checkbox"
                               name="documentos_entregados" value="1"
                               {{ old('documentos_entregados') ? 'checked' : '' }}>
                        <span class="form-check-label">Documentos entregados</span>
                    </label>
                </div>
                <div>
                    <label class="form-check form-switch">
                        <input class="form-check-input" type="checkbox"
                               name="ingreso_grua" value="1"
                               {{ old('ingreso_grua') ? 'checked' : '' }}>
                        <span class="form-check-label">Ingresó en grúa</span>
                    </label>
                </div>
            </div>
        </div>

        {{-- NIVEL DE COMBUSTIBLE --}}
        <div class="card mb-3">
            <div class="card-header"><h3 class="card-title">Nivel de Combustible</h3></div>
            <div class="card-body">
                <div class="d-flex align-items-center gap-3 mb-2">
                    <span class="text-muted small">E</span>
                    <input type="range" class="form-range flex-grow-1"
                           name="nivel_combustible" id="range-combustible"
                           min="0" max="10" value="{{ old('nivel_combustible', 5) }}"
                           oninput="document.getElementById('val-combustible').textContent = this.value">
                    <span class="text-muted small">F</span>
                </div>
                <div class="text-center">
                    <span class="fw-bold" id="val-combustible">{{ old('nivel_combustible', 5) }}</span>
                    <span class="text-muted">/10</span>
                </div>
            </div>
        </div>

        {{-- BOTÓN GUARDAR --}}
        <div class="card">
            <div class="card-body">
                <button type="submit" class="btn btn-primary w-100 btn-lg mb-2">
                    Crear Orden de Trabajo
                </button>
                <a href="{{ route('ordenes.index') }}" class="btn btn-outline-secondary w-100">
                    Cancelar
                </a>
            </div>
        </div>

    </div>
</div>

</form>
@endsection

@push('scripts')
<script>
const MODELOS_URL = '{{ route("api.modelos") }}';
const PLACA_URL   = '{{ route("api.placa") }}';

// Cargar modelos al cambiar la marca
document.getElementById('select-marca').addEventListener('change', function () {
    cargarModelos(this.value, null);
});

function cargarModelos(idMarca, idModeloSeleccionado) {
    const sel = document.getElementById('select-modelo');
    sel.innerHTML = '<option value="">Cargando...</option>';

    if (!idMarca) {
        sel.innerHTML = '<option value="">Seleccionar modelo...</option>';
        return;
    }

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

// Búsqueda por placa
document.getElementById('btn-buscar-placa').addEventListener('click', buscarPlaca);
document.getElementById('input-placa').addEventListener('keydown', function (e) {
    if (e.key === 'Enter') { e.preventDefault(); buscarPlaca(); }
});

function buscarPlaca() {
    const placa = document.getElementById('input-placa').value.trim().toUpperCase();
    const msg   = document.getElementById('msg-placa');

    if (!placa) return;

    document.getElementById('input-placa').value = placa;
    msg.textContent = 'Buscando...';
    msg.className   = 'form-text text-muted';

    fetch(`${PLACA_URL}?placa=${encodeURIComponent(placa)}`)
        .then(r => r.json())
        .then(data => {
            if (data.encontrado) {
                msg.textContent = '✓ Vehículo encontrado — datos precargados';
                msg.className   = 'form-text text-success';

                // Precargar marca y modelos
                const selMarca = document.getElementById('select-marca');
                selMarca.value = data.id_marca;
                cargarModelos(data.id_marca, data.id_modelo);

                // Precargar resto del vehículo
                setValue('input-color', data.color);
                setValue('input-anio',  data.anio);

                // Precargar cliente
                setValue('input-nombre',      data.nombre_cliente);
                setValue('input-cedula',      data.cedula_cliente);
                setValue('input-telefono',    data.telefono_cliente);
                setValue('input-email',       data.email_cliente);
                setValue('input-direccion',   data.direccion_cliente);
                setValue('input-cumpleanos',  data.fecha_cumpleanos_cliente);
            } else {
                msg.textContent = 'Placa nueva — complete los datos del vehículo';
                msg.className   = 'form-text text-info';
            }
        })
        .catch(() => {
            msg.textContent = 'Error al buscar la placa';
            msg.className   = 'form-text text-danger';
        });
}

function setValue(id, val) {
    const el = document.getElementById(id);
    if (el && val) el.value = val;
}

// Precargar modelos si hay old() values
document.addEventListener('DOMContentLoaded', function () {
    const idMarca  = document.getElementById('select-marca').value;
    const idModelo = '{{ old("id_modelo") }}';
    if (idMarca) cargarModelos(idMarca, idModelo);
});
</script>
@endpush
