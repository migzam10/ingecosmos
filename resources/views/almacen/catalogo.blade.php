@extends('layouts.app')
@section('title', 'Catálogo de Insumos')
@section('page_title', 'Catálogo de Insumos')
@section('breadcrumb', 'Almacén')
@section('page_actions')
<button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#modal-nuevo">+ Nuevo insumo</button>
@endsection

@section('content')

@if(session('success'))<div class="alert alert-success alert-dismissible" role="alert">{{ session('success') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>@endif
@if(session('error'))<div class="alert alert-danger alert-dismissible" role="alert">{{ session('error') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>@endif

<div class="card">
    <div class="card-header">
        <form method="GET" class="d-flex gap-2 flex-wrap align-items-center">
            <input type="text" name="buscar" value="{{ request('buscar') }}"
                   class="form-control form-control-sm" style="max-width:280px" placeholder="Buscar nombre...">
            <select name="activo" class="form-select form-select-sm" style="width:auto">
                <option value="">Todos</option>
                <option value="1" {{ request('activo') === '1' ? 'selected' : '' }}>Solo activos</option>
                <option value="0" {{ request('activo') === '0' ? 'selected' : '' }}>Solo inactivos</option>
            </select>
            <button type="submit" class="btn btn-sm btn-outline-secondary">Filtrar</button>
            @if(request()->hasAny(['buscar','activo']))
            <a href="{{ route('almacen.catalogo.index') }}" class="btn btn-sm btn-ghost-secondary">Limpiar</a>
            @endif
        </form>
    </div>
    <div class="table-responsive">
        <table class="table table-sm table-hover mb-0">
            <thead>
                <tr>
                    <th>Nombre</th>
                    <th style="width:90px" class="text-center">Unidad</th>
                    <th style="width:120px" class="text-end">P. Venta</th>
                    <th style="width:80px" class="text-end">Stock mín.</th>
                    <th style="width:90px" class="text-end">Stock actual</th>
                    <th style="width:80px" class="text-center">Estado</th>
                    <th style="width:110px"></th>
                </tr>
            </thead>
            <tbody>
                @forelse($catalogo as $item)
                <tr class="{{ !$item->activo ? 'table-secondary text-muted' : ($item->stock_bajo ? 'table-warning' : '') }}">
                    <td>
                        <span class="nombre-display-{{ $item->id }}">{{ $item->nombre }}</span>
                        <span class="nombre-edit-{{ $item->id }} d-none">
                            <input type="text" class="form-control form-control-sm" id="inp-nombre-{{ $item->id }}" value="{{ $item->nombre }}">
                        </span>
                    </td>
                    <td class="text-center">
                        <span class="um-display-{{ $item->id }}">{{ $item->unidad_medida }}</span>
                        <span class="um-edit-{{ $item->id }} d-none">
                            <input type="text" class="form-control form-control-sm text-center" id="inp-um-{{ $item->id }}" value="{{ $item->unidad_medida }}" maxlength="30">
                        </span>
                    </td>
                    <td class="text-end">
                        <span class="pv-display-{{ $item->id }}">$ {{ number_format($item->precio_venta, 0, ',', '.') }}</span>
                        <span class="pv-edit-{{ $item->id }} d-none">
                            <input type="number" class="form-control form-control-sm text-end" id="inp-pv-{{ $item->id }}" value="{{ $item->precio_venta }}" min="0" step="1">
                        </span>
                    </td>
                    <td class="text-end">
                        <span class="sm-display-{{ $item->id }}">{{ number_format($item->stock_minimo, 2) }}</span>
                        <span class="sm-edit-{{ $item->id }} d-none">
                            <input type="number" class="form-control form-control-sm text-end" id="inp-sm-{{ $item->id }}" value="{{ $item->stock_minimo }}" min="0" step="0.01">
                        </span>
                    </td>
                    <td class="text-end fw-bold {{ $item->stock_bajo ? 'text-warning' : '' }}">
                        {{ number_format($item->stock_actual, 2) }} <small class="text-muted">{{ $item->unidad_medida }}</small>
                    </td>
                    <td class="text-center">
                        @if($item->activo)
                        <span class="badge {{ $item->stock_bajo ? 'bg-warning text-dark' : 'bg-success' }}">
                            {{ $item->stock_bajo ? 'Stock bajo' : 'Activo' }}
                        </span>
                        @else
                        <span class="badge bg-secondary">Inactivo</span>
                        @endif
                    </td>
                    <td class="text-end">
                        <div class="btn-group btn-group-sm acciones-display-{{ $item->id }}">
                            <button type="button" class="btn btn-outline-secondary" onclick="editarFila({{ $item->id }})">✏️</button>
                            @if($item->activo)
                            <form method="POST" action="{{ route('almacen.catalogo.destroy', $item) }}"
                                  data-confirm="¿Desactivar «{{ $item->nombre }}»?">
                                @csrf @method('DELETE')
                                <button type="submit" class="btn btn-outline-danger">⊘</button>
                            </form>
                            @else
                            <form method="POST" action="{{ route('almacen.catalogo.restaurar', $item) }}"
                                  data-confirm="¿Reactivar «{{ $item->nombre }}»?">
                                @csrf
                                <button type="submit" class="btn btn-outline-success">↺</button>
                            </form>
                            @endif
                        </div>
                        <div class="btn-group btn-group-sm acciones-edit-{{ $item->id }} d-none">
                            <form method="POST" action="{{ route('almacen.catalogo.update', $item) }}" id="form-edit-{{ $item->id }}">
                                @csrf @method('PUT')
                                <button type="submit" class="btn btn-success">✓</button>
                            </form>
                            <button type="button" class="btn btn-outline-secondary"
                                    onclick="cancelarEdicion({{ $item->id }}, @json($item->nombre), '{{ $item->unidad_medida }}', {{ $item->precio_venta }}, {{ $item->stock_minimo }})">✕</button>
                        </div>
                    </td>
                </tr>
                @empty
                <tr><td colspan="7" class="text-center text-muted py-4">Sin insumos en el catálogo.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($catalogo->hasPages())
    <div class="card-footer">{{ $catalogo->withQueryString()->links() }}</div>
    @endif
</div>

{{-- Modal nuevo insumo --}}
<div class="modal modal-blur fade" id="modal-nuevo" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-sm modal-dialog-centered" role="document">
        <div class="modal-content">
            <form method="POST" action="{{ route('almacen.catalogo.store') }}">
                @csrf
                <div class="modal-header"><h5 class="modal-title">Nuevo insumo</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-2">
                        <label class="form-label">Nombre <span class="text-danger">*</span></label>
                        <input type="text" name="nombre" class="form-control @error('nombre') is-invalid @enderror"
                               value="{{ old('nombre') }}" required maxlength="200" autofocus>
                        @error('nombre')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="mb-2">
                        <label class="form-label">Unidad de medida <span class="text-danger">*</span></label>
                        <input type="text" name="unidad_medida" class="form-control" value="{{ old('unidad_medida', 'unidad') }}" required maxlength="30" placeholder="litro, kg, unidad, metro...">
                    </div>
                    <div class="mb-2">
                        <label class="form-label">Precio venta (referencia)</label>
                        <div class="input-group">
                            <span class="input-group-text">$</span>
                            <input type="number" name="precio_venta" class="form-control text-end" value="{{ old('precio_venta', 0) }}" min="0" step="1">
                        </div>
                    </div>
                    <div class="mb-2">
                        <label class="form-label">Stock mínimo</label>
                        <input type="number" name="stock_minimo" class="form-control text-end" value="{{ old('stock_minimo', 0) }}" min="0" step="0.01">
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
    ['nombre','um','pv','sm'].forEach(k => {
        document.querySelector(`.${k}-display-${id}`).classList.add('d-none');
        document.querySelector(`.${k}-edit-${id}`).classList.remove('d-none');
    });
    document.querySelector(`.acciones-display-${id}`).classList.add('d-none');
    document.querySelector(`.acciones-edit-${id}`).classList.remove('d-none');
}
function cancelarEdicion(id, nombre, um, pv, sm) {
    document.getElementById(`inp-nombre-${id}`).value = nombre;
    document.getElementById(`inp-um-${id}`).value = um;
    document.getElementById(`inp-pv-${id}`).value = pv;
    document.getElementById(`inp-sm-${id}`).value = sm;
    ['nombre','um','pv','sm'].forEach(k => {
        document.querySelector(`.${k}-display-${id}`).classList.remove('d-none');
        document.querySelector(`.${k}-edit-${id}`).classList.add('d-none');
    });
    document.querySelector(`.acciones-display-${id}`).classList.remove('d-none');
    document.querySelector(`.acciones-edit-${id}`).classList.add('d-none');
}
document.querySelectorAll('[id^="form-edit-"]').forEach(form => {
    form.addEventListener('submit', function () {
        const id = this.id.replace('form-edit-', '');
        [
            ['nombre', 'inp-nombre'], ['unidad_medida', 'inp-um'],
            ['precio_venta', 'inp-pv'], ['stock_minimo', 'inp-sm']
        ].forEach(([name, inpId]) => {
            const h = document.createElement('input');
            h.type = 'hidden'; h.name = name;
            h.value = document.getElementById(`${inpId}-${id}`).value;
            this.appendChild(h);
        });
    });
});
@if($errors->any())
new bootstrap.Modal(document.getElementById('modal-nuevo')).show();
@endif
</script>
@endpush
