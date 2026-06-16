@extends('layouts.app')

@section('title', isset($cotizacion) ? 'Editar Cotización #' . $cotizacion->numero_cot : 'Nueva Cotización — OT #' . $orden->numero_ot)
@section('page_title', isset($cotizacion) ? 'Editar Cotización #' . $cotizacion->numero_cot : 'Cotización OT #' . $orden->numero_ot)
@section('breadcrumb', 'Cotizaciones')

@section('content')

@if(isset($cotizacion))
<form method="POST" action="{{ route('cotizaciones.update', $cotizacion) }}" id="form-cot">
@csrf @method('PUT')
@else
<form method="POST" action="{{ route('cotizaciones.store', $orden) }}" id="form-cot">
@csrf
@endif

<div class="row g-3">

    {{-- ========== COLUMNA PRINCIPAL ========== --}}
    <div class="col-12 col-xl-8">

        {{-- Info OT --}}
        <div class="card mb-3">
            <div class="card-body py-2">
                <div class="row align-items-center">
                    <div class="col">
                        <span class="fw-bold">{{ $orden->vehiculo->placa }}</span>
                        <span class="text-muted mx-1">·</span>
                        {{ $orden->vehiculo->marca->nombre }} {{ $orden->vehiculo->modelo?->nombre }}
                        <span class="text-muted mx-1">·</span>
                        {{ $orden->empresaCliente->nombre }}
                        <span class="badge bg-secondary-lt ms-1">Tipo {{ $orden->empresaCliente->tipo }}</span>
                    </div>
                    <div class="col-auto text-muted small">
                        Tarifa: <strong>$ {{ number_format($orden->empresaCliente->tarifa_hora, 0, ',', '.') }}/h</strong>
                    </div>
                </div>
            </div>
        </div>

        {{-- MANO DE OBRA --}}
        <div class="card mb-3">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h3 class="card-title mb-0">Mano de Obra</h3>
                <button type="button" class="btn btn-sm btn-outline-primary" id="btn-agregar-mo">
                    + Agregar ítem manual
                </button>
            </div>

            {{-- Catálogo sugerido --}}
            <div class="card-body border-bottom pb-2">
                <p class="text-muted small mb-2">
                    Ítems del catálogo para <strong>{{ $orden->vehiculo->marca->nombre }}
                    {{ $orden->vehiculo->modelo?->nombre ?? '' }}</strong>
                    — haz clic para agregar:
                </p>
                <div id="lista-catalogo" class="d-flex flex-wrap gap-1">
                    <span class="text-muted small">Cargando catálogo...</span>
                </div>
            </div>

            {{-- Tabla de ítems MO --}}
            <div class="card-body p-0">
                <table class="table table-sm mb-0" id="tabla-mo">
                    <thead class="table-light">
                        <tr>
                            <th>Descripción</th>
                            <th style="width:150px" class="text-end">Precio</th>
                            <th style="width:40px"></th>
                        </tr>
                    </thead>
                    <tbody id="body-mo">
                        {{-- filas dinámicas --}}
                    </tbody>
                    <tfoot>
                        <tr class="table-light">
                            <td class="text-end fw-bold">Subtotal MO</td>
                            <td class="text-end fw-bold" id="subtotal-mo-display">$ 0</td>
                            <td></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>

        {{-- SUMINISTROS DE PINTURA --}}
        <div class="card mb-3">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h3 class="card-title mb-0">
                    Insumos de Pintura <small class="text-muted fw-normal">(precio al cliente: costo + 25%)</small>
                </h3>
                <button type="button" class="btn btn-sm btn-outline-primary" id="btn-agregar-sum">
                    + Agregar insumo
                </button>
            </div>
            <div class="card-body p-0">
                <table class="table table-sm mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Descripción</th>
                            <th style="width:130px" class="text-end">Costo</th>
                            <th style="width:130px" class="text-end">Precio (×1.25)</th>
                            <th style="width:40px"></th>
                        </tr>
                    </thead>
                    <tbody id="body-sum">
                        {{-- filas dinámicas --}}
                    </tbody>
                    <tfoot>
                        <tr class="table-light">
                            <td colspan="2" class="text-end fw-bold">Subtotal Insumos</td>
                            <td class="text-end fw-bold" id="subtotal-sum-display">$ 0</td>
                            <td></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>

        {{-- OTROS VALORES --}}
        <div class="card mb-3">
            <div class="card-header"><h3 class="card-title">Otros Valores</h3></div>
            <div class="card-body">
                <div class="row g-2">
                    <div class="col-12 col-md-4">
                        <label class="form-label small">
                            Repuestos
                            @if($orden->empresaCliente->tipo === 'B')
                            <span class="badge bg-warning-lt ms-1">Cliente pone sus repuestos</span>
                            @endif
                        </label>
                        <div class="input-group input-group-sm">
                            <span class="input-group-text">$</span>
                            <input type="number" name="subtotal_rto" id="inp-rto"
                                   class="form-control text-end"
                                   value="{{ isset($cotizacion) ? $cotizacion->subtotal_rto : 0 }}"
                                   min="0" step="1" oninput="recalcularTotal()">
                        </div>
                    </div>
                    <div class="col-12 col-md-4">
                        <label class="form-label small">Trabajos subcontratados</label>
                        <div class="input-group input-group-sm">
                            <span class="input-group-text">$</span>
                            <input type="number" name="subtotal_terceros" id="inp-terceros"
                                   class="form-control text-end"
                                   value="{{ isset($cotizacion) ? $cotizacion->subtotal_terceros : 0 }}"
                                   min="0" step="1" oninput="recalcularTotal()">
                        </div>
                    </div>
                    <div class="col-12 col-md-4">
                        <label class="form-label small">Otros gastos</label>
                        <div class="input-group input-group-sm">
                            <span class="input-group-text">$</span>
                            <input type="number" name="subtotal_op" id="inp-op"
                                   class="form-control text-end"
                                   value="{{ isset($cotizacion) ? $cotizacion->subtotal_op : 0 }}"
                                   min="0" step="1" oninput="recalcularTotal()">
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Observaciones --}}
        <div class="card mb-3">
            <div class="card-body">
                <label class="form-label">Observaciones de la cotización</label>
                <textarea name="observaciones" class="form-control" rows="2"
                          placeholder="Notas para la CIA o el cliente...">{{ isset($cotizacion) ? $cotizacion->observaciones : '' }}</textarea>
            </div>
        </div>

    </div>

    {{-- ========== COLUMNA LATERAL ========== --}}
    <div class="col-12 col-xl-4">

        {{-- Panel de cálculo en tiempo real --}}
        <div class="card mb-3 sticky-top" style="top: 1rem;">
            <div class="card-header bg-primary text-white">
                <h3 class="card-title text-white">Resumen</h3>
            </div>
            <div class="card-body">

                <table class="table table-sm mb-3">
                    <tr>
                        <td class="text-muted">MO</td>
                        <td class="text-end fw-bold" id="res-mo">$ 0</td>
                    </tr>
                    <tr>
                        <td class="text-muted">Insumos Pintura</td>
                        <td class="text-end fw-bold" id="res-sum">$ 0</td>
                    </tr>
                    <tr>
                        <td class="text-muted">Repuestos</td>
                        <td class="text-end" id="res-rto">$ 0</td>
                    </tr>
                    <tr>
                        <td class="text-muted">Terceros</td>
                        <td class="text-end" id="res-ter">$ 0</td>
                    </tr>
                    <tr>
                        <td class="text-muted">Otros</td>
                        <td class="text-end" id="res-op">$ 0</td>
                    </tr>
                    <tr class="table-active">
                        <td class="fw-bold">TOTAL</td>
                        <td class="text-end fw-bold fs-5" id="res-total">$ 0</td>
                    </tr>
                </table>

                {{-- Número de cotización y fecha --}}
                <div class="row g-2 mb-3">
                    @if(!isset($cotizacion))
                    <div class="col-6 col-sm-4">
                        <label class="form-label small fw-bold">
                            # COT <small class="text-muted fw-normal">(editable)</small>
                        </label>
                        <input type="text" inputmode="numeric" pattern="[0-9]+" name="numero_cot" class="form-control form-control-sm @error('numero_cot') is-invalid @enderror"
                               value="{{ old('numero_cot', $siguienteCOT ?? '') }}">
                        @error('numero_cot')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    @elseif($cotizacion->estado === 'BORRADOR')
                    <div class="col-6 col-sm-4">
                        <label class="form-label small fw-bold">
                            # COT <small class="text-muted fw-normal">(editable)</small>
                        </label>
                        <input type="text" inputmode="numeric" pattern="[0-9]+" name="numero_cot" class="form-control form-control-sm @error('numero_cot') is-invalid @enderror"
                               value="{{ old('numero_cot', $cotizacion->numero_cot) }}">
                        @error('numero_cot')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    @endif
                    <div class="col-6 col-sm-4">
                        <label class="form-label small fw-bold">
                            Fecha de cotización <span class="text-danger">*</span>
                        </label>
                        <input type="date" name="fecha_cotizacion" class="form-control form-control-sm"
                               value="{{ isset($cotizacion) ? $cotizacion->ot->fecha_cotizacion?->format('Y-m-d') : now()->toDateString() }}"
                               max="{{ now()->toDateString() }}" required>
                    </div>
                </div>

                <hr>

                {{-- HA / DR / TG calculados --}}
                <div class="row g-2 text-center mb-3">
                    <div class="col-4">
                        <div class="text-muted small">Horas artesano</div>
                        <div class="fw-bold fs-5" id="res-ha">—</div>
                    </div>
                    <div class="col-4">
                        <div class="text-muted small">Días estimados</div>
                        <div class="fw-bold fs-5" id="res-dr">—</div>
                    </div>
                    <div class="col-4">
                        <div class="text-muted small">Tamaño del daño</div>
                        <div id="res-tg">—</div>
                    </div>
                </div>

                <div class="text-center mb-3">
                    <div class="text-muted small">Salida estimada</div>
                    <div class="fw-bold" id="res-salida">— (sin fecha inicio proceso)</div>
                </div>

            </div>
            <div class="card-footer">
                <button type="submit" class="btn btn-success w-100 btn-lg"
                        data-confirm="¿Guardar cotización y cambiar estado OT a PTE_AUTORIZACION?">
                    Guardar y Generar PDF
                </button>
            </div>
        </div>

    </div>
</div>

</form>
@endsection

@push('scripts')
<script>
const TARIFA_HORA   = {{ $orden->empresaCliente->tarifa_hora }};
const TIPO_CLIENTE  = '{{ $orden->empresaCliente->tipo }}';
const ID_MARCA      = {{ $orden->vehiculo->id_marca }};
const ID_MODELO     = {{ $orden->vehiculo->id_modelo ?? 'null' }};
const CATALOGO_URL  = '{{ route("api.catalogo-mo") }}';
const FECHA_INICIO  = '{{ $orden->fecha_inicio_proceso ?? "" }}';

@if(isset($cotizacion))
const ITEMS_MO_EDIT  = @json($cotizacion->itemsMo->map(fn($i) => ['descripcion' => $i->descripcion, 'precio' => (float)$i->precio, 'id_catalogo_mo' => $i->id_catalogo_mo]));
const ITEMS_SUM_EDIT = @json($cotizacion->itemsSuministro->map(fn($i) => ['descripcion' => $i->descripcion, 'costo' => (float)$i->costo, 'precio' => (float)$i->precio]));
@else
const ITEMS_MO_EDIT  = [];
const ITEMS_SUM_EDIT = [];
@endif

let contadorMo  = 0;
let contadorSum = 0;

// ── UTILIDADES ──────────────────────────────────────────────────────────────
function cop(n) {
    return new Intl.NumberFormat('es-CO', {
        style: 'currency', currency: 'COP',
        minimumFractionDigits: 0, maximumFractionDigits: 0
    }).format(n || 0);
}

function sumTabla(bodyId, selector) {
    let total = 0;
    document.querySelectorAll(`#${bodyId} ${selector}`).forEach(inp => {
        total += parseFloat(inp.value) || 0;
    });
    return total;
}

// ── CÁLCULO EN TIEMPO REAL ───────────────────────────────────────────────────
function recalcularTotal() {
    const mo  = sumTabla('body-mo',  'input.inp-precio-mo');
    const sum = sumTabla('body-sum', 'input.inp-precio-sum');
    const rto = parseFloat(document.getElementById('inp-rto').value)      || 0;
    const ter = parseFloat(document.getElementById('inp-terceros').value)  || 0;
    const op  = parseFloat(document.getElementById('inp-op').value)        || 0;

    // Subtotales en tabla
    document.getElementById('subtotal-mo-display').textContent  = cop(mo);
    document.getElementById('subtotal-sum-display').textContent = cop(sum);

    // Resumen lateral
    document.getElementById('res-mo').textContent  = cop(mo);
    document.getElementById('res-sum').textContent = cop(sum);
    document.getElementById('res-rto').textContent = cop(rto);
    document.getElementById('res-ter').textContent = cop(ter);
    document.getElementById('res-op').textContent  = cop(op);

    const total = mo + sum + ter + op + (TIPO_CLIENTE === 'A' ? rto : 0);
    document.getElementById('res-total').textContent = cop(total);

    // HA / DR / TG
    const ha = TARIFA_HORA > 0 ? mo / TARIFA_HORA : 0;
    const dr = ha > 0 ? Math.ceil(ha / 8 * 1.5) : 0;
    const tg = dr <= 5 ? 'Leve' : dr <= 10 ? 'Medio' : 'Fuerte';
    const tgColor = {'Leve':'success','Medio':'warning','Fuerte':'danger'}[tg];

    document.getElementById('res-ha').textContent = ha > 0 ? ha.toFixed(1) : '—';
    document.getElementById('res-dr').textContent = dr > 0 ? dr : '—';
    document.getElementById('res-tg').innerHTML   = dr > 0
        ? `<span class="badge tg-${tg.toLowerCase()}">${tg}</span>` : '—';

    if (FECHA_INICIO && dr > 0) {
        document.getElementById('res-salida').textContent = 'Se calculará al guardar';
    } else if (dr > 0) {
        document.getElementById('res-salida').textContent = 'Sin fecha inicio proceso';
    } else {
        document.getElementById('res-salida').textContent = '—';
    }
}

// ── CATÁLOGO SUGERIDO ────────────────────────────────────────────────────────
function cargarCatalogo() {
    const url = `${CATALOGO_URL}?id_marca=${ID_MARCA}&id_modelo=${ID_MODELO || ''}`;
    fetch(url)
        .then(r => r.json())
        .then(items => {
            const cont = document.getElementById('lista-catalogo');
            if (!items.length) {
                cont.innerHTML = '<span class="text-muted small">Sin ítems en el catálogo para esta marca/modelo.</span>';
                return;
            }
            cont.innerHTML = '';
            items.forEach(item => {
                const btn = document.createElement('button');
                btn.type = 'button';
                btn.className = 'btn btn-sm btn-outline-secondary';
                btn.textContent = item.descripcion;
                btn.title = `Precio ref: ${cop(item.precio_referencia)}`;
                btn.onclick = () => agregarFilaMO(item.descripcion, item.precio_referencia, item.id);
                cont.appendChild(btn);
            });
        });
}

// ── FILAS MANO DE OBRA ───────────────────────────────────────────────────────
function agregarFilaMO(descripcion = '', precio = 0, idCatalogo = null) {
    const i = contadorMo++;
    const tr = document.createElement('tr');
    tr.innerHTML = `
        <td>
            <input type="hidden" name="items_mo[${i}][id_catalogo_mo]" value="${idCatalogo || ''}">
            <input type="text" name="items_mo[${i}][descripcion]"
                   class="form-control form-control-sm"
                   value="${descripcion}" placeholder="Descripción..." required>
        </td>
        <td>
            <div class="input-group input-group-sm">
                <span class="input-group-text">$</span>
                <input type="number" name="items_mo[${i}][precio]"
                       class="form-control text-end inp-precio-mo"
                       value="${precio}" min="0" step="1"
                       oninput="recalcularTotal()" required>
            </div>
        </td>
        <td>
            <button type="button" class="btn btn-sm btn-ghost-danger"
                    onclick="this.closest('tr').remove(); recalcularTotal()">✕</button>
        </td>`;
    document.getElementById('body-mo').appendChild(tr);
    recalcularTotal();
}

document.getElementById('btn-agregar-mo').onclick = () => agregarFilaMO();

// ── FILAS SUMINISTROS ────────────────────────────────────────────────────────
function agregarFilaSum(descripcion = '', costo = 0, precioFijo = null) {
    const i = contadorSum++;
    const precio = precioFijo !== null ? precioFijo : Math.round(costo * 1.25);
    const tr = document.createElement('tr');
    tr.innerHTML = `
        <td>
            <input type="text" name="items_suministro[${i}][descripcion]"
                   class="form-control form-control-sm"
                   value="${descripcion}" placeholder="Ej: Lija 120..." required>
        </td>
        <td>
            <div class="input-group input-group-sm">
                <span class="input-group-text">$</span>
                <input type="number" name="items_suministro[${i}][costo]"
                       class="form-control text-end inp-costo-sum"
                       value="${costo}" min="0" step="1"
                       oninput="actualizarPrecioSum(this)">
            </div>
        </td>
        <td>
            <div class="input-group input-group-sm">
                <span class="input-group-text">$</span>
                <input type="number" name="items_suministro[${i}][precio]"
                       class="form-control text-end inp-precio-sum"
                       value="${precio}" min="0" step="1"
                       oninput="recalcularTotal()">
            </div>
        </td>
        <td>
            <button type="button" class="btn btn-sm btn-ghost-danger"
                    onclick="this.closest('tr').remove(); recalcularTotal()">✕</button>
        </td>`;
    document.getElementById('body-sum').appendChild(tr);
    recalcularTotal();
}

function actualizarPrecioSum(inputCosto) {
    const costo  = parseFloat(inputCosto.value) || 0;
    const precio = Math.round(costo * 1.25);
    const fila   = inputCosto.closest('tr');
    fila.querySelector('.inp-precio-sum').value = precio;
    recalcularTotal();
}

document.getElementById('btn-agregar-sum').onclick = () => agregarFilaSum();

// ── INIT ─────────────────────────────────────────────────────────────────────
document.addEventListener('DOMContentLoaded', function () {
    cargarCatalogo();

    // Pre-cargar ítems existentes en modo edición
    ITEMS_MO_EDIT.forEach(item  => agregarFilaMO(item.descripcion, item.precio, item.id_catalogo_mo));
    ITEMS_SUM_EDIT.forEach(item => agregarFilaSum(item.descripcion, item.costo, item.precio));

    recalcularTotal();
});
</script>
@endpush
