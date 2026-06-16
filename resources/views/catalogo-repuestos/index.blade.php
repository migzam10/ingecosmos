@extends('layouts.app')

@section('title', 'Catálogo de Repuestos')
@section('page_title', 'Catálogo de Repuestos')
@section('breadcrumb', 'Catálogo Repuestos')

@section('page_actions')
<button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#modal-nuevo">
    + Nuevo repuesto
</button>
@endsection

@section('content')

@if(session('success'))
<div class="alert alert-success alert-dismissible" role="alert">
    {{ session('success') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif
@if(session('error'))
<div class="alert alert-danger alert-dismissible" role="alert">
    {{ session('error') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif

<div class="card">
    <div class="card-header">
        <form method="GET" class="d-flex gap-2 flex-wrap align-items-center">
            <input type="text" name="buscar" value="{{ request('buscar') }}"
                   class="form-control form-control-sm" style="max-width:300px"
                   placeholder="Buscar descripción...">
            <select name="activo" class="form-select form-select-sm" style="width:auto">
                <option value="">Todos</option>
                <option value="1" {{ request('activo') === '1' ? 'selected' : '' }}>Solo activos</option>
                <option value="0" {{ request('activo') === '0' ? 'selected' : '' }}>Solo inactivos</option>
            </select>
            <button type="submit" class="btn btn-sm btn-outline-secondary">Filtrar</button>
            @if(request()->hasAny(['buscar','activo']))
            <a href="{{ route('catalogo-repuestos.index') }}" class="btn btn-sm btn-ghost-secondary">Limpiar</a>
            @endif
        </form>
    </div>

    <div class="table-responsive">
        <table class="table table-sm table-hover mb-0">
            <thead>
                <tr>
                    <th>Descripción</th>
                    <th class="text-end" style="width:140px">Precio Referencia</th>
                    <th class="text-center" style="width:80px">Estado</th>
                    <th style="width:100px"></th>
                </tr>
            </thead>
            <tbody>
                @forelse($catalogo as $item)
                <tr class="{{ $item->activo ? '' : 'table-secondary text-muted' }}" id="fila-{{ $item->id }}">
                    <td>
                        <span class="nombre-display-{{ $item->id }}">{{ $item->descripcion }}</span>
                        <span class="nombre-edit-{{ $item->id }} d-none">
                            <input type="text" class="form-control form-control-sm" id="inp-desc-{{ $item->id }}" value="{{ $item->descripcion }}">
                        </span>
                    </td>
                    <td class="text-end">
                        <span class="precio-display-{{ $item->id }}">
                            {{ $item->precio_referencia ? '$ ' . number_format($item->precio_referencia, 0, ',', '.') : '—' }}
                        </span>
                        <span class="precio-edit-{{ $item->id }} d-none">
                            <input type="number" class="form-control form-control-sm text-end" id="inp-precio-{{ $item->id }}"
                                   value="{{ $item->precio_referencia ?? '' }}" min="0" step="1" placeholder="0">
                        </span>
                    </td>
                    <td class="text-center">
                        @if($item->activo)
                        <span class="badge bg-success">Activo</span>
                        @else
                        <span class="badge bg-secondary">Inactivo</span>
                        @endif
                    </td>
                    <td class="text-end">
                        <div class="btn-group btn-group-sm acciones-display-{{ $item->id }}">
                            <button type="button" class="btn btn-outline-secondary"
                                    onclick="editarFila({{ $item->id }})">✏️</button>
                            @if($item->activo)
                            <form method="POST" action="{{ route('catalogo-repuestos.destroy', $item) }}"
                                  data-confirm="¿Desactivar '{{ $item->descripcion }}'?">
                                @csrf @method('DELETE')
                                <button type="submit" class="btn btn-outline-danger">⊘</button>
                            </form>
                            @else
                            <form method="POST" action="{{ route('catalogo-repuestos.restaurar', $item) }}"
                                  data-confirm="¿Reactivar '{{ $item->descripcion }}'?">
                                @csrf
                                <button type="submit" class="btn btn-outline-success">↺</button>
                            </form>
                            @endif
                        </div>
                        <div class="btn-group btn-group-sm acciones-edit-{{ $item->id }} d-none">
                            <form method="POST" action="{{ route('catalogo-repuestos.update', $item) }}"
                                  id="form-edit-{{ $item->id }}">
                                @csrf @method('PUT')
                                <button type="submit" class="btn btn-success">✓</button>
                            </form>
                            <button type="button" class="btn btn-outline-secondary"
                                    onclick="cancelarEdicion({{ $item->id }}, '{{ addslashes($item->descripcion) }}', '{{ $item->precio_referencia ?? '' }}')">✕</button>
                        </div>
                    </td>
                </tr>
                @empty
                <tr><td colspan="4" class="text-center text-muted py-4">Sin registros.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($catalogo->hasPages())
    <div class="card-footer">{{ $catalogo->withQueryString()->links() }}</div>
    @endif
</div>

{{-- Modal nuevo repuesto --}}
<div class="modal modal-blur fade" id="modal-nuevo" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-sm modal-dialog-centered" role="document">
        <div class="modal-content">
            <form method="POST" action="{{ route('catalogo-repuestos.store') }}">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">Nuevo repuesto</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Descripción <span class="text-danger">*</span></label>
                        <input type="text" name="descripcion" class="form-control @error('descripcion') is-invalid @enderror"
                               value="{{ old('descripcion') }}" required maxlength="200" autofocus>
                        @error('descripcion')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Precio referencia</label>
                        <div class="input-group">
                            <span class="input-group-text">$</span>
                            <input type="number" name="precio_referencia" class="form-control text-end"
                                   value="{{ old('precio_referencia') }}" min="0" step="1" placeholder="0">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Guardar</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function editarFila(id) {
    document.querySelector(`.nombre-display-${id}`).classList.add('d-none');
    document.querySelector(`.nombre-edit-${id}`).classList.remove('d-none');
    document.querySelector(`.precio-display-${id}`).classList.add('d-none');
    document.querySelector(`.precio-edit-${id}`).classList.remove('d-none');
    document.querySelector(`.acciones-display-${id}`).classList.add('d-none');
    document.querySelector(`.acciones-edit-${id}`).classList.remove('d-none');
}
function cancelarEdicion(id, desc, precio) {
    document.getElementById(`inp-desc-${id}`).value = desc;
    document.getElementById(`inp-precio-${id}`).value = precio;
    document.querySelector(`.nombre-display-${id}`).classList.remove('d-none');
    document.querySelector(`.nombre-edit-${id}`).classList.add('d-none');
    document.querySelector(`.precio-display-${id}`).classList.remove('d-none');
    document.querySelector(`.precio-edit-${id}`).classList.add('d-none');
    document.querySelector(`.acciones-display-${id}`).classList.remove('d-none');
    document.querySelector(`.acciones-edit-${id}`).classList.add('d-none');
}
document.querySelectorAll('[id^="form-edit-"]').forEach(form => {
    form.addEventListener('submit', function (e) {
        const id = this.id.replace('form-edit-', '');
        const desc = document.getElementById(`inp-desc-${id}`).value;
        const precio = document.getElementById(`inp-precio-${id}`).value;
        const hidden1 = document.createElement('input');
        hidden1.type = 'hidden'; hidden1.name = 'descripcion'; hidden1.value = desc;
        const hidden2 = document.createElement('input');
        hidden2.type = 'hidden'; hidden2.name = 'precio_referencia'; hidden2.value = precio;
        this.appendChild(hidden1);
        this.appendChild(hidden2);
    });
});
@if($errors->any())
var modal = new bootstrap.Modal(document.getElementById('modal-nuevo'));
modal.show();
@endif
</script>
@endpush
