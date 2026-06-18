@extends('layouts.app')

@section('title', 'Marcas y Modelos')
@section('page_title', 'Marcas y Modelos de Vehículos')
@section('breadcrumb', 'Administración')

@section('content')

<div class="row g-3">

    {{-- Columna izquierda: tabla de marcas --}}
    <div class="col-12 col-lg-8">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Marcas registradas ({{ $marcas->count() }})</h3>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-sm mb-0">
                        <thead>
                            <tr>
                                <th>Marca</th>
                                <th class="text-center" style="width:80px">Modelos</th>
                                <th class="text-center" style="width:80px">Vehículos</th>
                                <th style="width:120px"></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($marcas as $marca)
                            <tr>
                                <td>
                                    <button class="btn btn-link p-0 text-start fw-medium text-decoration-none"
                                            onclick="toggleModelos({{ $marca->id }})"
                                            id="btn-marca-{{ $marca->id }}">
                                        <span id="ico-{{ $marca->id }}" class="toggle-ico collapsed"><x-icon name="chevron-down" /></span>
                                        {{ $marca->nombre }}
                                    </button>
                                </td>
                                <td class="text-center">
                                    <span class="badge bg-blue-lt">{{ $marca->modelos_count }}</span>
                                </td>
                                <td class="text-center">
                                    <span class="badge bg-secondary-lt">{{ $marca->vehiculos_count }}</span>
                                </td>
                                <td>
                                    <div class="d-flex gap-1">
                                        <button class="btn btn-xs btn-outline-primary"
                                                onclick="editarMarca({{ $marca->id }}, '{{ addslashes($marca->nombre) }}')">
                                            Editar
                                        </button>
                                        @if($marca->modelos_count == 0 && $marca->vehiculos_count == 0)
                                        <form method="POST" action="{{ route('admin.marcas.destroy', $marca) }}"
                                              data-confirm="¿Eliminar la marca {{ $marca->nombre }}?">
                                            @csrf @method('DELETE')
                                            <button class="btn btn-xs btn-ghost-danger"><x-icon name="x" /></button>
                                        </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                            {{-- Fila expandible con modelos --}}
                            <tr id="modelos-{{ $marca->id }}" class="d-none bg-light">
                                <td colspan="4" class="px-4 py-2">
                                    @php $modelos = $marca->modelos->sortBy('nombre'); @endphp
                                    @if($modelos->count() > 0)
                                    <div class="d-flex flex-wrap gap-2 mb-2">
                                        @foreach($modelos as $modelo)
                                        <div class="d-flex align-items-center gap-1 border rounded px-2 py-1 bg-white small">
                                            <span id="texto-modelo-{{ $modelo->id }}">{{ $modelo->nombre }}</span>
                                            <button class="btn btn-xs btn-ghost-secondary p-0 ms-1"
                                                    onclick="editarModelo({{ $modelo->id }}, '{{ addslashes($modelo->nombre) }}', {{ $marca->id }})"
                                                    title="Editar"><x-icon name="pencil" /></button>
                                            @if(!$modelo->vehiculos_count && !$modelo->catalogo_mo_count)
                                            <form method="POST" action="{{ route('admin.modelos.destroy', $modelo) }}"
                                                  data-confirm="¿Eliminar el modelo {{ $modelo->nombre }}?"
                                                  class="d-inline">
                                                @csrf @method('DELETE')
                                                <button class="btn btn-xs btn-ghost-danger p-0" title="Eliminar"><x-icon name="x" /></button>
                                            </form>
                                            @endif
                                        </div>
                                        @endforeach
                                    </div>
                                    @else
                                    <span class="text-muted small">Sin modelos registrados.</span>
                                    @endif
                                    {{-- Agregar modelo inline --}}
                                    <form method="POST" action="{{ route('admin.modelos.store', $marca) }}"
                                          class="d-flex gap-2 align-items-center mt-1">
                                        @csrf
                                        <input type="text" name="nombre" class="form-control form-control-sm"
                                               placeholder="Nuevo modelo..." maxlength="80" required
                                               style="max-width:200px">
                                        <button type="submit" class="btn btn-sm btn-primary">+ Agregar</button>
                                    </form>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    {{-- Columna derecha: formularios --}}
    <div class="col-12 col-lg-4">

        {{-- Crear nueva marca --}}
        <div class="card mb-3">
            <div class="card-header"><h3 class="card-title">Nueva Marca</h3></div>
            <div class="card-body">
                <form method="POST" action="{{ route('admin.marcas.store') }}" id="form-nueva-marca">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label small">Nombre de la marca</label>
                        <input type="text" name="nombre"
                               class="form-control @error('nombre') is-invalid @enderror"
                               value="{{ old('nombre') }}"
                               placeholder="Ej: VOLKSWAGEN" maxlength="50" required>
                        @error('nombre')
                        <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <button type="submit" class="btn btn-primary w-100">Crear Marca</button>
                </form>
            </div>
        </div>

        {{-- Editar marca (se muestra al dar clic en Editar) --}}
        <div class="card mb-3 d-none" id="card-editar-marca">
            <div class="card-header">
                <h3 class="card-title">Editar Marca</h3>
                <div class="card-options">
                    <button class="btn btn-sm btn-ghost-secondary" onclick="cancelarEditarMarca()"><x-icon name="x" /></button>
                </div>
            </div>
            <div class="card-body">
                <form method="POST" id="form-editar-marca">
                    @csrf @method('PUT')
                    <div class="mb-3">
                        <label class="form-label small">Nombre</label>
                        <input type="text" name="nombre" id="input-editar-marca"
                               class="form-control" maxlength="50" required>
                    </div>
                    <button type="submit" class="btn btn-warning w-100">Guardar cambios</button>
                </form>
            </div>
        </div>

        {{-- Editar modelo (se muestra al dar clic en editar modelo) --}}
        <div class="card d-none" id="card-editar-modelo">
            <div class="card-header">
                <h3 class="card-title">Editar Modelo</h3>
                <div class="card-options">
                    <button class="btn btn-sm btn-ghost-secondary" onclick="cancelarEditarModelo()"><x-icon name="x" /></button>
                </div>
            </div>
            <div class="card-body">
                <form method="POST" id="form-editar-modelo">
                    @csrf @method('PUT')
                    <div class="mb-3">
                        <label class="form-label small">Nombre del modelo</label>
                        <input type="text" name="nombre" id="input-editar-modelo"
                               class="form-control" maxlength="80" required>
                    </div>
                    <button type="submit" class="btn btn-warning w-100">Guardar cambios</button>
                </form>
            </div>
        </div>

    </div>

</div>

@push('scripts')
<script>
function toggleModelos(idMarca) {
    const fila = document.getElementById('modelos-' + idMarca);
    const ico  = document.getElementById('ico-' + idMarca);
    if (fila.classList.contains('d-none')) {
        fila.classList.remove('d-none');
        ico.classList.remove('collapsed');
    } else {
        fila.classList.add('d-none');
        ico.classList.add('collapsed');
    }
}

function editarMarca(id, nombre) {
    document.getElementById('card-editar-marca').classList.remove('d-none');
    document.getElementById('input-editar-marca').value = nombre;
    document.getElementById('form-editar-marca').action = '/admin/marcas/' + id;
    document.getElementById('card-editar-marca').scrollIntoView({behavior:'smooth'});
}

function cancelarEditarMarca() {
    document.getElementById('card-editar-marca').classList.add('d-none');
}

function editarModelo(id, nombre, idMarca) {
    // Expand the brand row
    const fila = document.getElementById('modelos-' + idMarca);
    if (fila.classList.contains('d-none')) toggleModelos(idMarca);

    document.getElementById('card-editar-modelo').classList.remove('d-none');
    document.getElementById('input-editar-modelo').value = nombre;
    document.getElementById('form-editar-modelo').action = '/admin/modelos/' + id;
    document.getElementById('card-editar-modelo').scrollIntoView({behavior:'smooth'});
}

function cancelarEditarModelo() {
    document.getElementById('card-editar-modelo').classList.add('d-none');
}

// Auto-expandir la marca si viene error de validación de modelo
@if(session('_old_input'))
// No es necesario — los errores se muestran con el formulario del nivel correcto
@endif
</script>
@endpush

@endsection
