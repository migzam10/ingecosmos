@extends('layouts.app')

@section('title', isset($orden) ? 'Editar Orden de Compra #' . $orden->numero : 'Nueva Orden de Compra')
@section('page_title', isset($orden) ? 'Editar Orden de Compra #' . $orden->numero : 'Nueva Orden de Compra')
@section('breadcrumb', 'Compras')

@section('page_actions')
<a href="{{ route('ordenes-compra.index') }}" class="btn btn-outline-secondary btn-sm"><x-icon name="arrow-left" /> Volver</a>
@endsection

@section('content')

@isset($orden)
<form method="POST" action="{{ route('ordenes-compra.update', $orden) }}" id="form-oc">
@csrf @method('PUT')
@else
<form method="POST" action="{{ route('ordenes-compra.store') }}" id="form-oc">
@csrf
@endisset

<x-errores />

<div class="row g-3">
    <div class="col-12 col-xl-8">

        {{-- Datos del vehículo --}}
        <div class="card mb-3">
            <div class="card-header"><h3 class="card-title">Datos del Vehículo</h3></div>
            <div class="card-body">
                <div class="row g-2">
                    <div class="col-6 col-md-3">
                        <label class="form-label">Placa</label>
                        <input type="text" name="placa" id="inp-placa"
                               class="form-control text-uppercase @error('placa') is-invalid @enderror"
                               value="{{ old('placa', $orden->placa ?? '') }}" maxlength="10"
                               style="text-transform:uppercase" placeholder="Buscar...">
                        @error('placa')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        <div class="form-hint small" id="placa-hint"></div>
                    </div>
                    <div class="col-6 col-md-3">
                        <label class="form-label">Marca</label>
                        <select name="id_marca" id="sel-marca" class="form-select">
                            <option value="">Seleccionar...</option>
                            @foreach($marcas as $m)
                            <option value="{{ $m->id }}" {{ old('id_marca', $orden->id_marca ?? '') == $m->id ? 'selected' : '' }}>
                                {{ $m->nombre }}
                            </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-6 col-md-3">
                        <label class="form-label">Modelo</label>
                        <select name="id_modelo" id="sel-modelo" class="form-select">
                            <option value="">Seleccionar...</option>
                        </select>
                    </div>
                    <div class="col-6 col-md-3">
                        <label class="form-label">OT <small class="text-muted">(opcional)</small></label>
                        <input type="text" name="numero_ot" class="form-control @error('numero_ot') is-invalid @enderror"
                               value="{{ old('numero_ot', $orden->numero_ot ?? '') }}" maxlength="50" placeholder="# OT">
                        @error('numero_ot')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>
            </div>
        </div>

        {{-- Datos del proveedor --}}
        <div class="card mb-3">
            <div class="card-header"><h3 class="card-title">Datos del Proveedor</h3></div>
            <div class="card-body">
                <div class="row g-2">
                    <div class="col-12 col-md-6 position-relative">
                        <label class="form-label">Nombre <span class="text-danger">*</span></label>
                        <input type="text" name="proveedor_nombre" id="inp-prov-nombre" autocomplete="off"
                               class="form-control @error('proveedor_nombre') is-invalid @enderror"
                               value="{{ old('proveedor_nombre', $orden->proveedor->nombre ?? '') }}" maxlength="150"
                               required placeholder="Escribe para buscar...">
                        @error('proveedor_nombre')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        <div id="prov-resultados" class="list-group position-absolute w-100 shadow-sm"
                             style="display:none; z-index:20; max-height:220px; overflow-y:auto;"></div>
                        <div class="form-hint small" id="prov-hint"></div>
                    </div>
                    <div class="col-6 col-md-3">
                        <label class="form-label">Cédula / NIT</label>
                        <input type="text" name="proveedor_nit" id="inp-nit"
                               class="form-control @error('proveedor_nit') is-invalid @enderror"
                               value="{{ old('proveedor_nit', $orden->proveedor->nit ?? '') }}" maxlength="30" placeholder="Buscar...">
                        @error('proveedor_nit')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        <div class="form-hint small" id="nit-hint"></div>
                    </div>
                    <div class="col-6 col-md-3">
                        <label class="form-label">Teléfono</label>
                        <input type="text" name="proveedor_telefono" id="inp-prov-tel"
                               class="form-control @error('proveedor_telefono') is-invalid @enderror"
                               value="{{ old('proveedor_telefono', $orden->proveedor->telefono ?? '') }}" maxlength="25">
                        @error('proveedor_telefono')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>
            </div>
        </div>

        {{-- Productos --}}
        <div class="card mb-3">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h3 class="card-title mb-0">Productos</h3>
                <button type="button" class="btn btn-sm btn-outline-primary" id="btn-agregar">+ Agregar producto</button>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-sm mb-0" style="min-width:640px">
                        <thead class="table-light">
                            <tr>
                                <th style="width:90px" class="text-end">Cantidad</th>
                                <th style="width:80px">Und</th>
                                <th>Descripción</th>
                                <th style="width:140px" class="text-end">Vr. Unitario</th>
                                <th style="width:140px" class="text-end">Valor Total</th>
                                <th style="width:40px"></th>
                            </tr>
                        </thead>
                        <tbody id="body-items"></tbody>
                        <tfoot>
                            <tr class="table-light">
                                <td colspan="4" class="text-end fw-bold">Subtotal</td>
                                <td class="text-end fw-bold" id="subtotal-display">$ 0</td>
                                <td></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
                <div id="msg-vacio" class="text-center text-muted py-3 small">Agregue productos con el botón «+ Agregar producto».</div>
            </div>
        </div>

        <div class="card mb-3">
            <div class="card-body">
                <label class="form-label">Observaciones</label>
                <textarea name="observaciones" class="form-control" rows="2"
                          maxlength="1000" placeholder="Notas...">{{ old('observaciones', $orden->observaciones ?? '') }}</textarea>
            </div>
        </div>

    </div>

    {{-- SIDEBAR --}}
    <div class="col-12 col-xl-4">
        <div class="card mb-3 sticky-top" style="top: 1rem;">
            <div class="card-header bg-primary text-white">
                <h3 class="card-title text-white">Orden de Compra</h3>
            </div>
            <div class="card-body">
                <div class="row g-2 mb-3">
                    <div class="col-6">
                        <label class="form-label small fw-bold"># Orden <small class="text-muted">(editable)</small></label>
                        <input type="text" inputmode="numeric" pattern="[0-9]+" name="numero"
                               class="form-control form-control-sm @error('numero') is-invalid @enderror"
                               value="{{ old('numero', $orden->numero ?? ($siguienteNumero ?? '')) }}" required>
                        @error('numero')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-6">
                        <label class="form-label small fw-bold">Fecha <span class="text-danger">*</span></label>
                        <input type="date" name="fecha"
                               class="form-control form-control-sm @error('fecha') is-invalid @enderror"
                               value="{{ old('fecha', isset($orden) ? $orden->fecha?->format('Y-m-d') : now()->toDateString()) }}"
                               max="{{ now()->toDateString() }}" required>
                        @error('fecha')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-12">
                        <label class="form-label small fw-bold">Forma de pago <span class="text-danger">*</span></label>
                        @php $_fp = old('forma_pago', $orden->forma_pago ?? 'CONTADO'); @endphp
                        <select name="forma_pago" class="form-select form-select-sm @error('forma_pago') is-invalid @enderror" required>
                            <option value="CONTADO" {{ $_fp === 'CONTADO' ? 'selected' : '' }}>Contado</option>
                            <option value="CREDITO" {{ $_fp === 'CREDITO' ? 'selected' : '' }}>Crédito</option>
                        </select>
                        @error('forma_pago')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>

                <table class="table table-sm mb-0">
                    <tr class="table-light">
                        <td class="fw-bold small">Subtotal</td>
                        <td class="text-end fw-bold" id="res-subtotal">$ 0</td>
                    </tr>
                    <tr>
                        <td class="small">Descuento
                            <input type="number" id="inp-desc-pct" step="0.01" min="0" max="100"
                                   class="form-control form-control-sm d-inline-block text-end" style="width:56px" value="0"> %
                        </td>
                        <td class="text-end">
                            <input type="number" name="descuento_valor" id="inp-desc-val" step="1" min="0"
                                   class="form-control form-control-sm text-end" value="0" style="width:110px; margin-left:auto">
                        </td>
                    </tr>
                    <tr>
                        <td class="fw-bold small">Base gravable</td>
                        <td class="text-end fw-bold" id="res-base">$ 0</td>
                    </tr>
                    <tr>
                        <td class="small">IVA
                            <input type="number" id="inp-iva-pct" step="0.01" min="0" max="100"
                                   class="form-control form-control-sm d-inline-block text-end" style="width:56px" value="0"> %
                        </td>
                        <td class="text-end">
                            <input type="number" name="iva_valor" id="inp-iva-val" step="1" min="0"
                                   class="form-control form-control-sm text-end" value="0" style="width:110px; margin-left:auto">
                        </td>
                    </tr>
                    <tr class="table-active">
                        <td class="fw-bold">TOTAL</td>
                        <td class="text-end fw-bold fs-5" id="res-total">$ 0</td>
                    </tr>
                </table>
                
            </div>
            <div class="card-footer">
                <button type="submit" class="btn btn-primary w-100 btn-lg"
                        data-confirm="¿Guardar la orden de compra?">
                    {{ isset($orden) ? 'Actualizar Orden de Compra' : 'Guardar Orden de Compra' }}
                </button>
            </div>
        </div>
    </div>
</div>
</form>
@endsection

@push('scripts')
<script>
const URL_PLACA = '{{ route("api.placa") }}';
const URL_MOD   = '{{ route("api.modelos") }}';
const URL_PROV  = '{{ route("api.proveedor") }}';
const URL_PROV_LIST = '{{ route("api.proveedores") }}';

@php
    // Ítems a pintar: old() si rebotó la validación; el modelo si es edición; vacío si es nuevo.
    if (old('items') !== null) {
        $_itemsInit = collect(old('items', []))->map(fn ($i) => [
            'cantidad'       => $i['cantidad'] ?? 1,
            'unidad'         => $i['unidad'] ?? '',
            'descripcion'    => $i['descripcion'] ?? '',
            'valor_unitario' => $i['valor_unitario'] ?? 0,
            'valor_total'    => $i['valor_total'] ?? 0,
        ])->values()->toArray();
        $_descInit  = old('descuento_valor') !== null ? (float) old('descuento_valor') : 0;
        $_ivaInit   = old('iva_valor') !== null ? (float) old('iva_valor') : 0;
        $_marcaInit = old('id_marca');
        $_modeloInit= old('id_modelo');
    } elseif (isset($orden)) {
        $_itemsInit = $orden->items->map(fn ($i) => [
            'cantidad'       => (float) $i->cantidad,
            'unidad'         => $i->unidad ?? '',
            'descripcion'    => $i->descripcion,
            'valor_unitario' => (float) $i->valor_unitario,
            'valor_total'    => (float) $i->valor_total,
        ])->values()->toArray();
        $_descInit  = (float) $orden->descuento_valor;
        $_ivaInit   = (float) $orden->iva_valor;
        $_marcaInit = $orden->id_marca;
        $_modeloInit= $orden->id_modelo;
    } else {
        $_itemsInit = []; $_descInit = 0; $_ivaInit = 0; $_marcaInit = null; $_modeloInit = null;
    }
@endphp
const ITEMS_INIT   = @json($_itemsInit);
const DESC_INIT    = @json((float) $_descInit);
const IVA_INIT     = @json((float) $_ivaInit);
const ID_MARCA     = @json($_marcaInit !== null && $_marcaInit !== '' ? (int) $_marcaInit : null);
const ID_MODELO    = @json($_modeloInit !== null && $_modeloInit !== '' ? (int) $_modeloInit : null);

let cItem = 0, tPlaca = null, tNit = null;

function cop(n) {
    return new Intl.NumberFormat('es-CO', {style:'currency',currency:'COP',minimumFractionDigits:0,maximumFractionDigits:0}).format(n||0);
}

function subtotalItems() {
    let t = 0;
    document.querySelectorAll('#body-items .inp-total').forEach(i => t += parseFloat(i.value) || 0);
    return t;
}

function recalcular() {
    const sub = subtotalItems();
    document.getElementById('subtotal-display').textContent = cop(sub);
    document.getElementById('res-subtotal').textContent = cop(sub);

    // Descuento: si el % > 0 manda; si no, respeta el valor escrito.
    const descPct = parseFloat(document.getElementById('inp-desc-pct').value) || 0;
    let descVal = parseFloat(document.getElementById('inp-desc-val').value) || 0;
    if (descPct > 0) { descVal = Math.round(sub * descPct / 100); document.getElementById('inp-desc-val').value = descVal; }
    descVal = Math.min(Math.max(0, descVal), sub);

    const base = sub - descVal;
    document.getElementById('res-base').textContent = cop(base);

    const ivaPct = parseFloat(document.getElementById('inp-iva-pct').value) || 0;
    let ivaVal = parseFloat(document.getElementById('inp-iva-val').value) || 0;
    if (ivaPct > 0) { ivaVal = Math.round(base * ivaPct / 100); document.getElementById('inp-iva-val').value = ivaVal; }
    ivaVal = Math.max(0, ivaVal);

    document.getElementById('res-total').textContent = cop(base + ivaVal);
}

// Recalcular IVA/descuento cuando el usuario edita los porcentajes o valores.
['inp-desc-pct','inp-desc-val','inp-iva-pct','inp-iva-val'].forEach(id => {
    document.getElementById(id).addEventListener('input', recalcular);
});

function agregarItem(it = null) {
    const i = cItem++;
    document.getElementById('msg-vacio').style.display = 'none';
    const tr = document.createElement('tr');
    tr.innerHTML = `
        <td><input type="number" name="items[${i}][cantidad]" class="form-control form-control-sm text-end inp-cant"
                   value="${it?.cantidad ?? 1}" min="0.01" step="0.01" oninput="actualizarTotal(this)" required></td>
        <td><input type="text" name="items[${i}][unidad]" class="form-control form-control-sm"
                   value="${it?.unidad ?? ''}" maxlength="20" placeholder="und"></td>
        <td><input type="text" name="items[${i}][descripcion]" class="form-control form-control-sm"
                   value="${it ? String(it.descripcion).replace(/"/g,'&quot;') : ''}" placeholder="Descripción..." required></td>
        <td><div class="input-group input-group-sm"><span class="input-group-text">$</span>
            <input type="number" name="items[${i}][valor_unitario]" class="form-control text-end inp-unit"
                   value="${it?.valor_unitario ?? 0}" min="0" step="1" oninput="actualizarTotal(this)"></div></td>
        <td><div class="input-group input-group-sm"><span class="input-group-text">$</span>
            <input type="number" name="items[${i}][valor_total]" class="form-control text-end inp-total"
                   value="${it?.valor_total ?? 0}" min="0" step="1" oninput="recalcular()"></div></td>
        <td><button type="button" class="btn btn-sm btn-ghost-danger" onclick="eliminarItem(this)"><x-icon name="x" /></button></td>`;
    document.getElementById('body-items').appendChild(tr);
    recalcular();
}

function actualizarTotal(input) {
    const fila = input.closest('tr');
    const cant = parseFloat(fila.querySelector('.inp-cant').value) || 0;
    const unit = parseFloat(fila.querySelector('.inp-unit').value) || 0;
    fila.querySelector('.inp-total').value = Math.round(cant * unit);
    recalcular();
}

function eliminarItem(btn) {
    btn.closest('tr').remove();
    if (!document.querySelectorAll('#body-items tr').length) {
        document.getElementById('msg-vacio').style.display = '';
    }
    recalcular();
}

document.getElementById('btn-agregar').onclick = () => agregarItem();

// Buscar placa → autollenar marca/modelo
document.getElementById('inp-placa').addEventListener('input', function () {
    clearTimeout(tPlaca);
    const placa = this.value.trim().toUpperCase();
    const hint = document.getElementById('placa-hint');
    if (placa.length < 3) { hint.textContent = ''; return; }
    tPlaca = setTimeout(() => {
        fetch(`${URL_PLACA}?placa=${encodeURIComponent(placa)}`)
            .then(r => r.json()).then(d => {
                if (!d.encontrado) { hint.textContent = 'Placa nueva'; hint.className = 'form-hint small text-muted'; return; }
                hint.textContent = '✓ Vehículo encontrado'; hint.className = 'form-hint small text-success';
                if (d.id_marca) {
                    const selM = document.getElementById('sel-marca');
                    selM.value = d.id_marca;
                    cargarModelos(d.id_marca, d.id_modelo);
                }
            });
    }, 350);
});

// Buscar NIT → autollenar proveedor
document.getElementById('inp-nit').addEventListener('input', function () {
    clearTimeout(tNit);
    const nit = this.value.trim();
    const hint = document.getElementById('nit-hint');
    if (nit.length < 3) { hint.textContent = ''; return; }
    tNit = setTimeout(() => {
        fetch(`${URL_PROV}?nit=${encodeURIComponent(nit)}`)
            .then(r => r.json()).then(d => {
                if (!d.encontrado) { hint.textContent = 'Proveedor nuevo'; hint.className = 'form-hint small text-muted'; return; }
                hint.textContent = '✓ Proveedor encontrado'; hint.className = 'form-hint small text-success';
                document.getElementById('inp-prov-nombre').value = d.proveedor_nombre ?? '';
                document.getElementById('inp-prov-tel').value = d.proveedor_telefono ?? '';
            });
    }, 350);
});

// Buscar proveedor por NOMBRE (lista tipo autocompletar) → al seleccionar trae NIT y teléfono
let tProvNom = null;
const inpProvNombre = document.getElementById('inp-prov-nombre');
const boxProvRes    = document.getElementById('prov-resultados');

inpProvNombre.addEventListener('input', function () {
    clearTimeout(tProvNom);
    const q = this.value.trim();
    document.getElementById('prov-hint').textContent = '';
    if (q.length < 2) { boxProvRes.style.display = 'none'; return; }
    tProvNom = setTimeout(() => {
        fetch(`${URL_PROV_LIST}?q=${encodeURIComponent(q)}`)
            .then(r => r.json()).then(items => {
                boxProvRes.innerHTML = '';
                if (!items.length) { boxProvRes.style.display = 'none'; return; }
                items.forEach(p => {
                    const a = document.createElement('button');
                    a.type = 'button';
                    a.className = 'list-group-item list-group-item-action py-1 px-2 small';
                    a.innerHTML = `<strong>${p.nombre}</strong>` + (p.nit ? ` <span class="text-muted">· NIT ${p.nit}</span>` : '');
                    a.onclick = () => {
                        inpProvNombre.value = p.nombre || '';
                        document.getElementById('inp-nit').value = p.nit || '';
                        document.getElementById('inp-prov-tel').value = p.telefono || '';
                        boxProvRes.style.display = 'none';
                        document.getElementById('prov-hint').textContent = '✓ Proveedor seleccionado';
                        document.getElementById('prov-hint').className = 'form-hint small text-success';
                    };
                    boxProvRes.appendChild(a);
                });
                boxProvRes.style.display = 'block';
            });
    }, 250);
});
// Cerrar la lista al hacer clic fuera
document.addEventListener('click', e => {
    if (!e.target.closest('#inp-prov-nombre') && !e.target.closest('#prov-resultados')) {
        boxProvRes.style.display = 'none';
    }
});

function cargarModelos(idMarca, idModeloSel = null) {
    const sel = document.getElementById('sel-modelo');
    sel.innerHTML = '<option value="">Cargando...</option>';
    if (!idMarca) { sel.innerHTML = '<option value="">Seleccionar...</option>'; return; }
    fetch(`${URL_MOD}?id_marca=${idMarca}`)
        .then(r => r.json()).then(mods => {
            sel.innerHTML = '<option value="">Seleccionar...</option>';
            mods.forEach(m => {
                const opt = document.createElement('option');
                opt.value = m.id; opt.textContent = m.nombre;
                if (idModeloSel && m.id == idModeloSel) opt.selected = true;
                sel.appendChild(opt);
            });
        });
}

document.getElementById('sel-marca').addEventListener('change', function () {
    cargarModelos(this.value);
});

document.addEventListener('DOMContentLoaded', function () {
    ITEMS_INIT.forEach(it => agregarItem(it));

    document.getElementById('inp-desc-val').value = DESC_INIT;
    document.getElementById('inp-iva-val').value = IVA_INIT;

    if (ID_MARCA) {
        document.getElementById('sel-marca').value = ID_MARCA;
        cargarModelos(ID_MARCA, ID_MODELO);
    }
    recalcular();
});
</script>
@endpush
