@extends('layouts.app')

@section('title', 'Órdenes de Trabajo')
@section('page_title', 'Órdenes de Trabajo')
@section('breadcrumb', $verHistoricas ? 'Historial completo' : 'Activas')

@section('page_actions')
@if(Auth::user()->hasAnyRole(['ADMIN', 'COORDINADOR', 'RECEPCION']))
<a href="{{ route('ordenes.create') }}" class="btn btn-primary">
    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24"
         fill="none" stroke="currentColor" stroke-width="2" class="me-1">
        <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
        <line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/>
    </svg>
    Nueva OT
</a>
@endif
@endsection

@section('content')

{{-- Toggle activas / históricas --}}
<div class="d-flex gap-2 mb-3">
    <a href="{{ route('ordenes.index', request()->except('historicas', 'page')) }}"
       class="btn {{ !$verHistoricas ? 'btn-primary' : 'btn-outline-secondary' }}">
        Activas
        <span class="badge bg-white text-dark ms-1">{{ $totalActivas }}</span>
    </a>
    <a href="{{ route('ordenes.index', array_merge(request()->except('page'), ['historicas' => 1])) }}"
       class="btn {{ $verHistoricas ? 'btn-secondary' : 'btn-outline-secondary' }}">
        Historial completo
        <span class="badge bg-white text-dark ms-1">{{ $totalActivas + $totalHistoricas }}</span>
    </a>
</div>

{{-- Filtros --}}
<div class="card mb-3">
    <div class="card-body py-2">
        <form method="GET" action="{{ route('ordenes.index') }}" class="row g-2 align-items-end">
            @if($verHistoricas)
            <input type="hidden" name="historicas" value="1">
            @endif
            <div class="col-12 col-md-3">
                <input type="text" name="buscar" class="form-control"
                       placeholder="Buscar por placa o # OT..."
                       value="{{ request('buscar') }}">
            </div>
            <div class="col-6 col-md-3">
                <select name="empresa" class="form-select">
                    <option value="">Todas las empresas</option>
                    @foreach($empresas as $emp)
                    <option value="{{ $emp->id }}" {{ request('empresa') == $emp->id ? 'selected' : '' }}>{{ $emp->nombre }}</option>
                    @endforeach
                </select>
            </div>
            @php
                $mesesNombres = [1=>'enero',2=>'febrero',3=>'marzo',4=>'abril',5=>'mayo',6=>'junio',
                                 7=>'julio',8=>'agosto',9=>'septiembre',10=>'octubre',11=>'noviembre',12=>'diciembre'];
                $fechasSel = (array) request('fechas', []);
            @endphp
            <div class="col-6 col-md-2">
                <div class="dropdown w-100" id="dd-fechas">
                    <button type="button" class="btn btn-outline-secondary dropdown-toggle w-100 text-truncate"
                            data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-expanded="false" title="Fecha de ingreso">
                        <span id="lbl-fechas">{{ count($fechasSel) ? count($fechasSel).' fecha'.(count($fechasSel)>1?'s':'') : 'Fecha' }}</span>
                    </button>
                    <div class="dropdown-menu p-0 filtro-fechas-menu">
                        <div class="p-2 border-bottom">
                            <input type="text" class="form-control form-control-sm" id="buscar-fecha" placeholder="Buscar fecha..." autocomplete="off">
                        </div>
                        <label class="d-flex align-items-center gap-2 fecha-row fw-medium mx-2 mt-2 mb-1">
                            <input type="checkbox" class="form-check-input m-0" id="chk-todas">
                            <span>(Seleccionar todo)</span>
                        </label>
                        <div class="filtro-fechas-tree px-2 pb-2" id="tree-fechas">
                            @forelse($arbolFechas as $anio => $mesesArbol)
                            @php $anioSel = collect($mesesArbol)->flatten()->intersect($fechasSel)->isNotEmpty(); @endphp
                            <div class="fecha-nodo" data-nivel="anio" data-anio="{{ $anio }}">
                                <div class="d-flex align-items-center gap-1 fecha-row">
                                    <button type="button" class="btn-caret {{ $anioSel ? 'abierto' : '' }}" aria-label="Expandir">▸</button>
                                    <label class="d-flex align-items-center gap-2 flex-grow-1 mb-0">
                                        <input type="checkbox" class="form-check-input m-0 chk-anio">
                                        <span class="fecha-txt">{{ $anio }}</span>
                                    </label>
                                </div>
                                <div class="fecha-hijos" style="{{ $anioSel ? '' : 'display:none' }}">
                                    @foreach($mesesArbol as $mes => $dias)
                                    @php $mesSel = collect($dias)->intersect($fechasSel)->isNotEmpty(); @endphp
                                    <div class="fecha-nodo ms-3" data-nivel="mes" data-mes="{{ $mesesNombres[(int)$mes] }}">
                                        <div class="d-flex align-items-center gap-1 fecha-row">
                                            <button type="button" class="btn-caret {{ $mesSel ? 'abierto' : '' }}" aria-label="Expandir">▸</button>
                                            <label class="d-flex align-items-center gap-2 flex-grow-1 mb-0">
                                                <input type="checkbox" class="form-check-input m-0 chk-mes">
                                                <span class="fecha-txt text-capitalize">{{ $mesesNombres[(int)$mes] }}</span>
                                            </label>
                                        </div>
                                        <div class="fecha-hijos" style="{{ $mesSel ? '' : 'display:none' }}">
                                            @foreach($dias as $dia)
                                            <div class="fecha-nodo ms-4" data-nivel="dia" data-fecha="{{ $dia }}">
                                                <label class="d-flex align-items-center gap-2 fecha-row mb-0">
                                                    <input type="checkbox" class="form-check-input m-0 chk-dia" name="fechas[]"
                                                           value="{{ $dia }}" {{ in_array($dia, $fechasSel) ? 'checked' : '' }}>
                                                    <span class="fecha-txt">{{ \Illuminate\Support\Str::afterLast($dia, '-') }}</span>
                                                </label>
                                            </div>
                                            @endforeach
                                        </div>
                                    </div>
                                    @endforeach
                                </div>
                            </div>
                            @empty
                            <div class="text-muted small p-2">Sin fechas disponibles.</div>
                            @endforelse
                        </div>
                        <div class="d-flex gap-2 p-2 border-top">
                            <button type="submit" class="btn btn-sm btn-secondary flex-grow-1">Aplicar</button>
                            <button type="button" class="btn btn-sm btn-outline-secondary" id="btn-limpiar-fechas">Limpiar</button>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-2">
                <select name="estado" class="form-select">
                    <option value="">Todos los estados</option>
                    @if(!$verHistoricas)
                    <option value="PTE_COTIZACION"     {{ request('estado')=='PTE_COTIZACION'     ? 'selected' : '' }}>Pte. cotización</option>
                    <option value="PTE_AUTORIZACION"   {{ request('estado')=='PTE_AUTORIZACION'   ? 'selected' : '' }}>Pte. autorización</option>
                    <option value="PTE_ORDEN"          {{ request('estado')=='PTE_ORDEN'          ? 'selected' : '' }}>Solicitud de repuesto</option>
                    <option value="RTO_INSTALADO"      {{ request('estado')=='RTO_INSTALADO'      ? 'selected' : '' }}>Llegada de repuesto</option>
                    <option value="EN_PROCESO"         {{ request('estado')=='EN_PROCESO'         ? 'selected' : '' }}>En proceso</option>
                    <option value="PROGRAMADO_ENTREGA" {{ request('estado')=='PROGRAMADO_ENTREGA' ? 'selected' : '' }}>Programado entrega</option>
                    <option value="REPUESTOS_INSTALADOS" {{ request('estado')=='REPUESTOS_INSTALADOS' ? 'selected' : '' }}>Repuestos instalados</option>
                    <option value="GARANTIA"           {{ request('estado')=='GARANTIA'           ? 'selected' : '' }}>Garantía</option>
                    <option value="ENTREGA_PARCIAL"    {{ request('estado')=='ENTREGA_PARCIAL'    ? 'selected' : '' }}>Entrega parcial</option>
                    @else
                    <option value="ENTREGADO"          {{ request('estado')=='ENTREGADO'          ? 'selected' : '' }}>Entregado</option>
                    <option value="NO_AUTORIZADO"      {{ request('estado')=='NO_AUTORIZADO'      ? 'selected' : '' }}>No autorizado</option>
                    <option value="ORDEN_ANULADA"      {{ request('estado')=='ORDEN_ANULADA'      ? 'selected' : '' }}>Orden anulada</option>
                    <option value="PERDIDA_TOTAL"      {{ request('estado')=='PERDIDA_TOTAL'      ? 'selected' : '' }}>Pérdida total</option>
                    <option value="GARANTIA"           {{ request('estado')=='GARANTIA'           ? 'selected' : '' }}>Garantía</option>
                    <option value="ARREGLO_DIRECTO"    {{ request('estado')=='ARREGLO_DIRECTO'    ? 'selected' : '' }}>Arreglo directo</option>
                    <option value="VFT"                {{ request('estado')=='VFT'                ? 'selected' : '' }}>Vehículo fuera taller</option>
                    @endif
                </select>
            </div>
            <div class="col-6 col-md-2">
                <select name="area" class="form-select">
                    <option value="">Todas las áreas</option>
                    <option value="LYP"      {{ request('area')=='LYP'      ? 'selected' : '' }}>Latonería y Pintura</option>
                    <option value="MECANICA" {{ request('area')=='MECANICA' ? 'selected' : '' }}>Mecánica</option>
                </select>
            </div>
            <div class="col-12 col-md-3 d-flex gap-2">
                <button type="submit" class="btn btn-secondary flex-grow-1">Filtrar</button>
                <a href="{{ route('ordenes.index', $verHistoricas ? ['historicas'=>1] : []) }}"
                   class="btn btn-outline-secondary">Limpiar</a>
            </div>
        </form>
    </div>
</div>

{{-- Tabla PC --}}
<div class="card d-none d-md-block">
    <div class="table-responsive">
        <table class="table table-vcenter table-hover card-table">
            <thead>
                <tr>
                    <th># OT</th>
                    <th>Placa</th>
                    <th>Vehículo</th>
                    <th>Empresa</th>
                    <th>Área</th>
                    <th>Estado</th>
                    <th>Semáforo</th>
                    <th>Ingreso</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse($ordenes as $ot)
                <tr>
                    <td class="fw-bold">{{ $ot->numero_ot }}</td>
                    <td>
                        <span class="badge bg-blue-lt fw-bold">{{ $ot->vehiculo->placa }}</span>
                    </td>
                    <td>
                        {{ $ot->vehiculo->marca->nombre }}
                        {{ $ot->vehiculo->modelo?->nombre }}
                        @if($ot->vehiculo->color)
                        <small class="text-muted">· {{ $ot->vehiculo->color }}</small>
                        @endif
                    </td>
                    <td>{{ $ot->empresaCliente->nombre }}</td>
                    <td><x-area-badge :area="$ot->area" /></td>
                    <td>
                        <x-estado-badge :estado="$ot->estado_proceso" />
                        @if($ot->estado_proceso === 'ENTREGADO' && $ot->fecha_entrega_cliente && $ot->salida_estimada &&
                            $ot->fecha_entrega_cliente->gt($ot->salida_estimada))
                        @php $diasTarde = $ot->salida_estimada->diffInDays($ot->fecha_entrega_cliente); @endphp
                        <div class="mt-1">
                            <span class="badge bg-danger-lt text-danger" style="font-size:.7rem">
                                Tardío · {{ $diasTarde }}d
                            </span>
                        </div>
                        @endif
                    </td>
                    <td><x-semaforo :estado="$ot->estado_semaforo" /></td>
                    <td class="text-muted small">{{ $ot->fecha_ingreso->format('d/m/Y') }}</td>
                    <td>
                        <a href="{{ route('ordenes.show', $ot) }}" class="btn btn-sm btn-outline-primary">
                            Ver
                        </a>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="9" class="text-center text-muted py-4">
                        No hay órdenes de trabajo activas.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($ordenes->hasPages())
    <div class="card-footer d-flex align-items-center">
        {{ $ordenes->links() }}
    </div>
    @endif
</div>

{{-- Cards móvil --}}
<div class="d-md-none">
    @forelse($ordenes as $ot)
    <div class="card mb-2">
        <div class="card-body p-3">
            <div class="d-flex justify-content-between align-items-start mb-2">
                <div>
                    <span class="fw-bold text-primary"># {{ $ot->numero_ot }}</span>
                    <span class="badge bg-blue-lt ms-1">{{ $ot->vehiculo->placa }}</span>
                </div>
                <x-semaforo :estado="$ot->estado_semaforo" />
            </div>
            <div class="fw-medium">
                {{ $ot->vehiculo->marca->nombre }} {{ $ot->vehiculo->modelo?->nombre }}
            </div>
            <div class="text-muted small mb-2">
                {{ $ot->empresaCliente->nombre }} · {{ $ot->area === 'LYP' ? 'Latonería y Pintura' : 'Mecánica' }}
                · {{ $ot->fecha_ingreso->format('d/m/Y') }}
            </div>
            <div class="d-flex justify-content-between align-items-center">
                <div class="d-flex flex-wrap gap-1 align-items-center">
                    <x-estado-badge :estado="$ot->estado_proceso" />
                    @if($ot->estado_proceso === 'ENTREGADO' && $ot->fecha_entrega_cliente && $ot->salida_estimada &&
                        $ot->fecha_entrega_cliente->gt($ot->salida_estimada))
                    @php $diasTarde = $ot->salida_estimada->diffInDays($ot->fecha_entrega_cliente); @endphp
                    <span class="badge bg-danger-lt text-danger" style="font-size:.7rem">
                        Tardío · {{ $diasTarde }}d
                    </span>
                    @endif
                </div>
                <a href="{{ route('ordenes.show', $ot) }}" class="btn btn-sm btn-primary">Ver OT</a>
            </div>
        </div>
    </div>
    @empty
    <div class="text-center text-muted py-4">No hay órdenes activas.</div>
    @endforelse

    @if($ordenes->hasPages())
    <div class="mt-2">{{ $ordenes->links() }}</div>
    @endif
</div>

@endsection

@push('styles')
<style>
    /* Filtro de fecha estilo autofiltro (árbol Año → Mes → Día) */
    .filtro-fechas-menu { min-width: 250px; max-width: 92vw; }
    .filtro-fechas-tree { max-height: 300px; overflow-y: auto; }
    .fecha-row { padding: 2px 4px; border-radius: 4px; cursor: pointer; font-size: .9rem; white-space: nowrap; }
    .fecha-row:hover { background: rgba(0,0,0,.05); }
    .fecha-txt { user-select: none; }
    .btn-caret { border: 0; background: transparent; width: 16px; height: 16px; line-height: 1;
                 padding: 0; color: #9aa0a6; font-size: .8rem; transition: transform .12s ease; flex: 0 0 auto; }
    .btn-caret.abierto { transform: rotate(90deg); }
    .fecha-nodo[data-nivel="dia"] .fecha-txt { color: #495057; }
    @media (min-width: 768px) { .filtro-fechas-menu { min-width: 280px; } }
</style>
@endpush

@push('scripts')
<script>
(function () {
    const dd = document.getElementById('dd-fechas');
    if (!dd) return;
    const tree    = document.getElementById('tree-fechas');
    const lbl     = document.getElementById('lbl-fechas');
    const chkAll  = document.getElementById('chk-todas');
    const buscar  = document.getElementById('buscar-fecha');
    const form    = dd.closest('form');
    const dias    = () => Array.from(tree.querySelectorAll('.chk-dia'));

    // Expandir / colapsar
    tree.querySelectorAll('.btn-caret').forEach(btn => {
        btn.addEventListener('click', () => {
            const hijos = btn.closest('.fecha-nodo').querySelector(':scope > .fecha-hijos');
            if (!hijos) return;
            const abierto = hijos.style.display !== 'none';
            hijos.style.display = abierto ? 'none' : '';
            btn.classList.toggle('abierto', !abierto);
        });
    });

    // Clic en un padre (año/mes): marca/desmarca todos sus descendientes
    tree.querySelectorAll('.chk-anio, .chk-mes').forEach(chk => {
        chk.addEventListener('change', () => {
            chk.closest('.fecha-nodo').querySelectorAll('.chk-dia, .chk-mes, .chk-anio').forEach(c => {
                if (c !== chk) { c.checked = chk.checked; c.indeterminate = false; }
            });
            refrescarPadres(); refrescarEtiqueta();
        });
    });

    // Clic en un día
    dias().forEach(d => d.addEventListener('change', () => { refrescarPadres(); refrescarEtiqueta(); }));

    // (Seleccionar todo)
    chkAll.addEventListener('change', () => {
        tree.querySelectorAll('.chk-dia, .chk-mes, .chk-anio').forEach(c => { c.checked = chkAll.checked; c.indeterminate = false; });
        refrescarEtiqueta();
    });

    function sincronizar(nodo, sel) {
        const padre = nodo.querySelector(':scope > .fecha-row ' + sel);
        if (!padre) return;
        const hijos = Array.from(nodo.querySelectorAll('.chk-dia'));
        const marc  = hijos.filter(h => h.checked).length;
        padre.checked = marc === hijos.length && hijos.length > 0;
        padre.indeterminate = marc > 0 && marc < hijos.length;
    }

    function refrescarPadres() {
        tree.querySelectorAll('[data-nivel="mes"]').forEach(m => sincronizar(m, '.chk-mes'));
        tree.querySelectorAll('[data-nivel="anio"]').forEach(a => sincronizar(a, '.chk-anio'));
        const total = dias().length, marc = dias().filter(d => d.checked).length;
        chkAll.checked = marc === total && total > 0;
        chkAll.indeterminate = marc > 0 && marc < total;
    }

    function refrescarEtiqueta() {
        const total = dias().length, n = dias().filter(d => d.checked).length;
        lbl.textContent = (n === 0 || n === total) ? 'Fecha' : (n + ' fecha' + (n > 1 ? 's' : ''));
    }

    // Búsqueda dentro del árbol
    buscar.addEventListener('input', () => {
        const q = buscar.value.trim().toLowerCase();
        tree.querySelectorAll('[data-nivel="dia"]').forEach(d => {
            d.style.display = (!q || d.dataset.fecha.toLowerCase().includes(q)) ? '' : 'none';
        });
        tree.querySelectorAll('[data-nivel="mes"]').forEach(m => {
            const hayDia = m.querySelector('[data-nivel="dia"]:not([style*="display: none"])');
            const vis = !q || (m.dataset.mes || '').includes(q) || hayDia;
            m.style.display = vis ? '' : 'none';
            if (q && vis) { const h = m.querySelector(':scope > .fecha-hijos'); if (h) h.style.display = ''; }
        });
        tree.querySelectorAll('[data-nivel="anio"]').forEach(a => {
            const hayMes = a.querySelector('[data-nivel="mes"]:not([style*="display: none"])');
            const vis = !q || (a.dataset.anio || '').includes(q) || hayMes;
            a.style.display = vis ? '' : 'none';
            if (q && vis) { const h = a.querySelector(':scope > .fecha-hijos'); if (h) h.style.display = ''; }
        });
    });

    // Limpiar solo el filtro de fecha y recargar
    document.getElementById('btn-limpiar-fechas').addEventListener('click', () => {
        dias().forEach(d => d.checked = false);
        form.submit();
    });

    // Al enviar: si están todos o ninguno marcados, no mandar 'fechas' (URL limpia = todas)
    form.addEventListener('submit', () => {
        const total = dias().length, marc = dias().filter(d => d.checked).length;
        if (marc === 0 || marc === total) dias().forEach(d => d.checked = false);
    });

    refrescarPadres();
    refrescarEtiqueta();
})();
</script>
@endpush
