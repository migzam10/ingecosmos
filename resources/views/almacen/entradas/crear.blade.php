@extends('layouts.app')
@section('title', 'Registrar Entrada')
@section('page_title', 'Registrar Entrada de Inventario')
@section('breadcrumb', 'Almacén / Entradas')
@section('page_actions')
<a href="{{ route('almacen.index') }}" class="btn btn-outline-secondary btn-sm"><x-icon name="arrow-left" /> Almacén</a>
@endsection

@section('content')
<form method="POST" action="{{ route('almacen.entradas.store') }}" id="form-entrada">
@csrf
<div class="row g-3">
    <div class="col-12 col-xl-8">
        <div class="card mb-3">
            <div class="card-header"><h3 class="card-title">Datos de la entrada</h3></div>
            <div class="card-body">
                <div class="row g-2">
                    <div class="col-6 col-md-3">
                        <label class="form-label">Fecha <span class="text-danger">*</span></label>
                        <input type="date" name="fecha" class="form-control @error('fecha') is-invalid @enderror"
                               value="{{ old('fecha', now()->toDateString()) }}" required max="{{ now()->toDateString() }}">
                        @error('fecha')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-12 col-md-9">
                        <label class="form-label">Observaciones</label>
                        <input type="text" name="observaciones" class="form-control" value="{{ old('observaciones') }}"
                               placeholder="Proveedor, número de factura, etc." maxlength="500">
                    </div>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h3 class="card-title mb-0">Insumos</h3>
                <button type="button" class="btn btn-sm btn-outline-primary" id="btn-agregar">+ Agregar ítem</button>
            </div>
            <div class="card-body border-bottom pb-2">
                <div class="input-group input-group-sm">
                    <span class="input-group-text"><x-icon name="search" /></span>
                    <input type="text" id="buscar-insumo" class="form-control" placeholder="Buscar insumo...">
                </div>
                <div id="resultados-busqueda" class="list-group mt-1" style="display:none; max-height:200px; overflow-y:auto;"></div>
            </div>
            <div class="card-body p-0">
                <table class="table table-sm mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Insumo</th>
                            <th style="width:100px" class="text-end">Cantidad</th>
                            <th style="width:80px" class="text-center">Unidad</th>
                            <th style="width:140px" class="text-end">P. Compra (opt.)</th>
                            <th style="width:40px"></th>
                        </tr>
                    </thead>
                    <tbody id="body-items"></tbody>
                </table>
                <div id="msg-vacio" class="text-center text-muted py-3 small">Agregue insumos usando la búsqueda o el botón.</div>
            </div>
        </div>
    </div>

    <div class="col-12 col-xl-4">
        <div class="card sticky-top" style="top:1rem">
            <div class="card-header bg-success text-white"><h3 class="card-title text-white">Entrada de Inventario</h3></div>
            <div class="card-body">
                <p class="text-muted small">Al guardar, el stock de cada insumo se incrementará con las cantidades indicadas.</p>
                <div id="resumen-items" class="small text-muted mb-2"></div>
            </div>
            <div class="card-footer">
                <button type="submit" class="btn btn-success w-100 btn-lg" data-confirm="¿Registrar esta entrada al inventario?">
                    Registrar Entrada
                </button>
            </div>
        </div>
    </div>
</div>
</form>
@endsection

@push('scripts')
<script>
const URL_INSUMOS = '{{ route("api.catalogo-insumos") }}';
let cItem = 0, tSearch = null;

document.getElementById('buscar-insumo').addEventListener('input', function () {
    clearTimeout(tSearch);
    const q = this.value.trim(), res = document.getElementById('resultados-busqueda');
    if (q.length < 2) { res.style.display = 'none'; return; }
    tSearch = setTimeout(() => {
        fetch(`${URL_INSUMOS}?buscar=${encodeURIComponent(q)}`)
            .then(r => r.json()).then(items => {
                res.innerHTML = '';
                if (!items.length) { res.innerHTML = '<div class="list-group-item text-muted small">Sin resultados</div>'; }
                else items.forEach(it => {
                    const a = document.createElement('button');
                    a.type = 'button'; a.className = 'list-group-item list-group-item-action py-1 px-2 small';
                    a.innerHTML = `<strong>${it.nombre}</strong> <span class="text-muted">(stock: ${it.stock_actual} ${it.unidad_medida})</span>`;
                    a.onclick = () => { agregarItem(it); res.style.display = 'none'; document.getElementById('buscar-insumo').value = ''; };
                    res.appendChild(a);
                });
                res.style.display = 'block';
            });
    }, 250);
});
document.addEventListener('click', e => {
    if (!e.target.closest('#buscar-insumo') && !e.target.closest('#resultados-busqueda'))
        document.getElementById('resultados-busqueda').style.display = 'none';
});

function agregarItem(insumo = null) {
    const i = cItem++;
    document.getElementById('msg-vacio').style.display = 'none';
    const tr = document.createElement('tr');
    tr.innerHTML = `
        <td>
            <input type="hidden" name="items[${i}][id_insumo]" value="${insumo?.id || ''}">
            <input type="text" class="form-control form-control-sm inp-nombre" value="${insumo?.nombre || ''}" placeholder="Nombre..." readonly style="background:transparent;border:none;padding-left:0">
            ${!insumo ? `<select name="items[${i}][id_insumo]" class="form-control form-control-sm mt-1" onchange="this.previousElementSibling.previousElementSibling.value=this.value"><option value="">Seleccionar insumo...</option>@foreach($insumos as $ins)<option value="{{ $ins->id }}">{{ $ins->nombre }}</option>@endforeach</select>` : ''}
        </td>
        <td><input type="number" name="items[${i}][cantidad]" class="form-control form-control-sm text-end" value="1" min="0.01" step="0.01" required oninput="actualizarResumen()"></td>
        <td class="text-center small text-muted">${insumo?.unidad_medida || '—'}</td>
        <td><div class="input-group input-group-sm"><span class="input-group-text">$</span><input type="number" name="items[${i}][precio_compra]" class="form-control text-end" min="0" step="1" placeholder="Opt."></div></td>
        <td><button type="button" class="btn btn-sm btn-ghost-danger" onclick="this.closest('tr').remove();actualizarResumen()"><x-icon name="x" /></button></td>`;
    document.getElementById('body-items').appendChild(tr);
    if (insumo) {
        const hiddenInput = tr.querySelector('input[name^="items["]');
        hiddenInput.value = insumo.id;
    }
    actualizarResumen();
}

function actualizarResumen() {
    const filas = document.querySelectorAll('#body-items tr');
    document.getElementById('msg-vacio').style.display = filas.length ? 'none' : '';
    const res = document.getElementById('resumen-items');
    if (!filas.length) { res.innerHTML = ''; return; }
    res.innerHTML = `<strong>${filas.length} ítem(s)</strong> listos para ingresar al inventario.`;
}

document.getElementById('btn-agregar').onclick = () => agregarItem(null);
</script>
@endpush
