@extends('layouts.app')

@section('title', 'OT #' . $orden->numero_ot)
@section('page_title', 'OT #' . $orden->numero_ot)
@section('breadcrumb', 'Órdenes de Trabajo')

@section('page_actions')
@php $rAcc = Auth::user()->roles ?: []; $esGestor = in_array('ADMIN',$rAcc) || in_array('COORDINADOR',$rAcc) || in_array('RECEPCION',$rAcc); @endphp
<div class="d-flex gap-2 flex-wrap">

    @if($orden->estado_proceso === 'PTE_COTIZACION' && $esGestor)
    {{-- Editar OT --}}
    <a href="{{ route('ordenes.edit', $orden) }}" class="btn btn-outline-warning btn-sm">
        Editar OT
    </a>
    {{-- Eliminar OT (solo si no tiene cotizaciones ni trabajos) --}}
    @if(!$orden->cotizaciones()->exists() && !$orden->trabajosTecnico()->exists())
    <form method="POST" action="{{ route('ordenes.destroy', $orden) }}"
          data-confirm="¿Eliminar la OT #{{ $orden->numero_ot }}? Esta acción no se puede deshacer.">
        @csrf @method('DELETE')
        <button class="btn btn-outline-danger btn-sm">Eliminar OT</button>
    </form>
    @endif
    @endif

    @if(in_array($orden->estado_proceso, ['PTE_COTIZACION','PTE_AUTORIZACION']))
    @if(in_array('ADMIN',$rAcc) || in_array('COTIZADOR',$rAcc) || in_array('COORDINADOR',$rAcc))
    <a href="{{ route('cotizaciones.create', $orden) }}" class="btn btn-primary btn-sm">
        + Nueva Cotización
    </a>
    @endif
    @endif

    <a href="{{ route('ordenes.index') }}" class="btn btn-outline-secondary btn-sm">← Volver</a>
</div>
@endsection

@section('content')
<div class="row g-3">

    {{-- Encabezado de estado --}}
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <div class="row align-items-center">
                    <div class="col-auto">
                        <x-semaforo :estado="$orden->estado_semaforo" />
                    </div>
                    <div class="col">
                        <div class="d-flex flex-wrap gap-2 align-items-center">
                            <x-estado-badge :estado="$orden->estado_proceso" />
                            <x-area-badge :area="$orden->area" />
                            @if($orden->tg)
                            <x-tg-badge :tg="$orden->tg" />
                            @endif
                        </div>
                        <div class="text-muted small mt-1">
                            Ingreso: {{ $orden->fecha_ingreso->format('d/m/Y') }}
                            @if($orden->salida_estimada)
                            · Entrega estimada: <strong>{{ $orden->salida_estimada->format('d/m/Y') }}</strong>
                            @php
                            $dfal = now()->diffInDays($orden->salida_estimada, false);
                            @endphp
                            @if($dfal > 0)
                            <span class="text-success">({{ $dfal }} días)</span>
                            @elseif($dfal == 0)
                            <span class="text-warning">(hoy)</span>
                            @else
                            <span class="text-danger">({{ abs($dfal) }} días vencido)</span>
                            @endif
                            @endif
                        </div>
                    </div>
                    <div class="col-auto text-end">
                        <div class="text-muted small">Empresa</div>
                        <div class="fw-bold">{{ $orden->empresaCliente->nombre }}</div>
                        @if($orden->referencia_forc)
                        <div class="text-muted small">Caso aseguradora: {{ $orden->referencia_forc }}</div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Panel de avance de estado (solo Coordinador/Admin) --}}
    @php $r = Auth::user()->roles ?: []; $esCoor = in_array('ADMIN',$r) || in_array('COORDINADOR',$r); @endphp
    @if($esCoor)
    <div class="col-12">
        <div class="card">
            <div class="card-header"><h3 class="card-title">Avanzar Estado</h3></div>
            <div class="card-body">
                @include('ordenes._panel-estado', ['orden' => $orden])
            </div>
        </div>
    </div>
    @endif

    {{-- Vehículo y propietario --}}
    <div class="col-12 col-md-6">
        <div class="card h-100">
            <div class="card-header"><h3 class="card-title">Vehículo</h3></div>
            <div class="card-body">
                <table class="table table-sm table-borderless mb-0">
                    <tr>
                        <td class="text-muted w-40">Placa</td>
                        <td><span class="badge bg-blue-lt fw-bold fs-5">{{ $orden->vehiculo->placa }}</span></td>
                    </tr>
                    <tr>
                        <td class="text-muted">Marca</td>
                        <td>{{ $orden->vehiculo->marca->nombre }}</td>
                    </tr>
                    <tr>
                        <td class="text-muted">Modelo</td>
                        <td>{{ $orden->vehiculo->modelo?->nombre ?? '—' }}</td>
                    </tr>
                    <tr>
                        <td class="text-muted">Color</td>
                        <td>{{ $orden->vehiculo->color ?? '—' }}</td>
                    </tr>
                    <tr>
                        <td class="text-muted">Año</td>
                        <td>{{ $orden->vehiculo->anio ?? '—' }}</td>
                    </tr>
                    <tr>
                        <td class="text-muted">KM ingreso</td>
                        <td>{{ number_format($orden->km_ingreso) }}</td>
                    </tr>
                </table>
            </div>
        </div>
    </div>

    <div class="col-12 col-md-6">
        <div class="card h-100">
            <div class="card-header"><h3 class="card-title">Propietario</h3></div>
            <div class="card-body">
                @if($orden->clientePersona)
                <table class="table table-sm table-borderless mb-0">
                    <tr>
                        <td class="text-muted w-40">Nombre</td>
                        <td>{{ $orden->clientePersona->nombre }}</td>
                    </tr>
                    <tr>
                        <td class="text-muted">Cédula</td>
                        <td>{{ $orden->clientePersona->cedula ?? '—' }}</td>
                    </tr>
                    <tr>
                        <td class="text-muted">Teléfono</td>
                        <td>{{ $orden->clientePersona->telefono ?? '—' }}</td>
                    </tr>
                    <tr>
                        <td class="text-muted">Email</td>
                        <td>{{ $orden->clientePersona->email ?? '—' }}</td>
                    </tr>
                    @if($orden->clientePersona->direccion)
                    <tr>
                        <td class="text-muted">Dirección</td>
                        <td>{{ $orden->clientePersona->direccion }}</td>
                    </tr>
                    @endif
                    @if($orden->clientePersona->fecha_cumpleanos)
                    <tr>
                        <td class="text-muted">Cumpleaños</td>
                        <td>{{ $orden->clientePersona->fecha_cumpleanos->format('d/m') }}</td>
                    </tr>
                    @endif
                </table>
                @else
                <p class="text-muted">Sin propietario registrado.</p>
                @endif

                <hr>
                <div class="d-flex gap-3">
                    <div>
                        <span class="badge {{ $orden->llaves_entregadas ? 'bg-success-lt' : 'bg-secondary-lt' }}">
                            {{ $orden->llaves_entregadas ? '✓' : '✗' }} Llaves
                        </span>
                    </div>
                    <div>
                        <span class="badge {{ $orden->documentos_entregados ? 'bg-success-lt' : 'bg-secondary-lt' }}">
                            {{ $orden->documentos_entregados ? '✓' : '✗' }} Documentos
                        </span>
                    </div>
                    <div>
                        <span class="badge {{ $orden->ingreso_grua ? 'bg-warning-lt' : 'bg-secondary-lt' }}">
                            {{ $orden->ingreso_grua ? '🚛 Grúa' : 'Propio' }}
                        </span>
                    </div>
                </div>
                <div class="mt-2 text-muted small">
                    Combustible: {{ $orden->nivel_combustible }}/10
                    <div class="progress mt-1" style="height:6px;">
                        <div class="progress-bar bg-warning" style="width: {{ $orden->nivel_combustible * 10 }}%"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Inventario B/R/G --}}
    @if($orden->inventario)
    @php
    $inv = $orden->inventario;
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
    $colorMap = ['B'=>'success','R'=>'warning','M'=>'danger'];
    $puedeEditarInv = in_array('ADMIN', Auth::user()->roles ?: [])
                   || in_array('COORDINADOR', Auth::user()->roles ?: [])
                   || in_array('RECEPCION', Auth::user()->roles ?: []);
    @endphp
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Inventario del Vehículo</h3>
                <div class="card-options small text-muted">
                    <span class="badge bg-success-lt me-1">B</span>Bueno
                    <span class="badge bg-warning-lt me-1 ms-1">R</span>Regular
                    <span class="badge bg-danger-lt ms-1">M</span>Malo
                </div>
            </div>
            <div class="card-body">
                @if($puedeEditarInv)
                <form method="POST" action="{{ route('inventario.update', $orden) }}" id="form-inventario">
                @csrf @method('PUT')

                {{-- Ítems sin cantidad --}}
                <div class="row g-2 mb-3">
                    @foreach($invSimples as $campo => $etiqueta)
                    @php $val = $inv->$campo; @endphp
                    <div class="col-6 col-md-3 col-lg-2">
                        <label class="form-label small mb-1">{{ $etiqueta }}</label>
                        <div class="btn-group w-100" role="group">
                            @foreach($colorMap as $v => $color)
                            <input type="radio" class="btn-check" name="inv_{{ $campo }}"
                                   id="inv_{{ $campo }}_{{ $v }}_e" value="{{ $v }}"
                                   {{ $val === $v ? 'checked' : '' }}>
                            <label class="btn btn-sm btn-outline-{{ $color }}"
                                   for="inv_{{ $campo }}_{{ $v }}_e">{{ $v }}</label>
                            @endforeach
                        </div>
                    </div>
                    @endforeach
                </div>

                {{-- Ítems con cantidad --}}
                <div class="border-top pt-3 mb-3">
                    <div class="text-muted small mb-2">Ítems con cantidad</div>
                    <div class="row g-2">
                        @foreach($invCantidad as $campo => $etiqueta)
                        @php $val = $inv->$campo; $qty = $inv->{$campo.'_qty'}; @endphp
                        <div class="col-6 col-md-4 col-lg-3">
                            <label class="form-label small mb-1">{{ $etiqueta }}</label>
                            <div class="d-flex gap-1 align-items-center">
                                <input type="number" name="inv_{{ $campo }}_qty"
                                       class="form-control form-control-sm text-center"
                                       style="max-width:60px"
                                       value="{{ $qty }}" min="0" max="99" placeholder="#">
                                <div class="btn-group flex-grow-1" role="group">
                                    @foreach($colorMap as $v => $color)
                                    <input type="radio" class="btn-check" name="inv_{{ $campo }}"
                                           id="inv_{{ $campo }}_{{ $v }}_e" value="{{ $v }}"
                                           {{ $val === $v ? 'checked' : '' }}>
                                    <label class="btn btn-sm btn-outline-{{ $color }}"
                                           for="inv_{{ $campo }}_{{ $v }}_e">{{ $v }}</label>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>

                <div class="row g-2 align-items-end">
                    <div class="col-12 col-md-8">
                        <label class="form-label small">Observaciones del inventario</label>
                        <input type="text" name="inv_observaciones" class="form-control form-control-sm"
                               value="{{ $inv->observaciones }}" placeholder="Rayones, accesorios...">
                    </div>
                    <div class="col-auto">
                        <button type="submit" class="btn btn-sm btn-outline-primary">Guardar inventario</button>
                    </div>
                </div>
                </form>
                @else
                <div class="row g-2 mb-3">
                    @foreach($invSimples as $campo => $etiqueta)
                    @php $val = $inv->$campo; @endphp
                    <div class="col-6 col-md-3 col-lg-2">
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="small text-muted">{{ $etiqueta }}</span>
                            @if($val)
                            <span class="badge bg-{{ $colorMap[$val] }}-lt fw-bold">{{ $val }}</span>
                            @else
                            <span class="text-muted small">—</span>
                            @endif
                        </div>
                    </div>
                    @endforeach
                </div>
                <div class="border-top pt-2">
                    <div class="row g-2">
                        @foreach($invCantidad as $campo => $etiqueta)
                        @php $val = $inv->$campo; $qty = $inv->{$campo.'_qty'}; @endphp
                        <div class="col-6 col-md-3 col-lg-2">
                            <div class="d-flex justify-content-between align-items-center">
                                <span class="small text-muted">
                                    {{ $etiqueta }}
                                    @if($qty !== null)
                                    <span class="badge bg-secondary-lt ms-1">{{ $qty }}</span>
                                    @endif
                                </span>
                                @if($val)
                                <span class="badge bg-{{ $colorMap[$val] }}-lt fw-bold">{{ $val }}</span>
                                @else
                                <span class="text-muted small">—</span>
                                @endif
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
                @if($inv->observaciones)
                <div class="mt-3 p-2 bg-light rounded small">
                    <strong>Obs.:</strong> {{ $inv->observaciones }}
                </div>
                @endif
                @endif
            </div>
        </div>
    </div>
    @endif

    {{-- Fotos del vehículo --}}
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Fotos del Vehículo</h3>
                <span class="card-subtitle ms-2 text-muted">{{ $orden->fotos->count() }} foto(s)</span>
            </div>
            <div class="card-body">

                {{-- Galería --}}
                @if($orden->fotos->count())
                <div class="row g-2 mb-3">
                    @foreach($orden->fotos as $foto)
                    <div class="col-6 col-md-3 col-lg-2">
                        <div class="card card-sm">
                            <a href="{{ asset('storage/' . $foto->ruta) }}" target="_blank"
                               title="{{ $foto->descripcion ?? 'Ver foto' }}">
                                <img src="{{ asset('storage/' . $foto->ruta) }}"
                                     class="card-img-top"
                                     style="height:120px; object-fit:cover;"
                                     loading="lazy"
                                     alt="{{ $foto->descripcion ?? 'Foto OT' }}">
                            </a>
                            <div class="card-footer p-1">
                                @if($foto->descripcion)
                                <div class="small text-muted text-truncate" title="{{ $foto->descripcion }}">
                                    {{ $foto->descripcion }}
                                </div>
                                @endif
                                <div class="d-flex justify-content-between align-items-center mt-1">
                                    <span class="text-muted" style="font-size:0.7rem;">
                                        {{ $foto->subidaPor?->name ?? '—' }}<br>
                                        {{ $foto->created_at->format('d/m H:i') }}
                                    </span>
                                    @php $rFoto = Auth::user()->roles ?? []; @endphp
                                    @if(in_array('ADMIN',$rFoto) || in_array('COORDINADOR',$rFoto) || $foto->subida_por === Auth::id())
                                    <form method="POST" action="{{ route('fotos.destroy', $foto) }}"
                                          data-confirm="¿Eliminar esta foto? No se puede recuperar.">
                                        @csrf @method('DELETE')
                                        <button class="btn btn-sm btn-ghost-danger py-0 px-1">✕</button>
                                    </form>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>
                @else
                <p class="text-muted small mb-3">Sin fotos registradas.</p>
                @endif

                {{-- Formulario subir fotos --}}
                @php $rFoto = Auth::user()->roles ?? []; @endphp
                @if(in_array('ADMIN',$rFoto) || in_array('COORDINADOR',$rFoto) || in_array('RECEPCION',$rFoto))
                <form method="POST" action="{{ route('fotos.store', $orden) }}"
                      enctype="multipart/form-data"
                      class="row g-2 align-items-end border-top pt-3">
                    @csrf
                    <div class="col-12 col-md-5">
                        <label class="form-label small fw-bold">
                            Seleccionar fotos
                            <span class="text-muted fw-normal">(JPG/PNG, máx. 10 fotos de 5MB c/u)</span>
                        </label>
                        <input type="file" name="fotos[]" class="form-control form-control-sm"
                               accept="image/*" multiple required>
                    </div>
                    <div class="col-12 col-md-4">
                        <label class="form-label small fw-bold">Descripción <span class="text-muted fw-normal">(opcional)</span></label>
                        <input type="text" name="descripcion" class="form-control form-control-sm"
                               placeholder="Ej: Daño lateral derecho" maxlength="150">
                    </div>
                    <div class="col-auto">
                        <button type="submit" class="btn btn-primary btn-sm">Subir fotos</button>
                    </div>
                </form>
                @endif

            </div>
        </div>
    </div>

    {{-- Observaciones --}}
    @if($orden->observaciones)
    <div class="col-12">
        <div class="card">
            <div class="card-header"><h3 class="card-title">Observaciones</h3></div>
            <div class="card-body">{{ $orden->observaciones }}</div>
        </div>
    </div>
    @endif

    {{-- Panel de técnicos asignados --}}
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Técnicos Asignados</h3>
            </div>
            <div class="card-body">

                {{-- Listado de trabajos --}}
                @if($orden->trabajosTecnico->count() > 0)
                <div class="table-responsive mb-3">
                    <table class="table table-sm table-vcenter mb-0">
                        <thead>
                            <tr>
                                <th>Técnico</th>
                                <th>Función</th>
                                <th>Estado</th>
                                <th>Inicio</th>
                                <th>Fin</th>
                                @if(in_array('ADMIN', Auth::user()->roles ?: []) || in_array('COORDINADOR', Auth::user()->roles ?: []))
                                <th class="text-end">Valor liquidar</th>
                                @endif
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($orden->trabajosTecnico as $trabajo)
                            <tr>
                                @php
                                $nombresEspShow = ['LAT'=>'Latonero','PREP'=>'Preparador','PINT'=>'Pintor','MEC'=>'Mecánico','ELEC'=>'Electricista','AA'=>'Aire Acondicionado','SCANNER'=>'Diagnóstico'];
                                @endphp
                                <td class="fw-medium">{{ $trabajo->tecnico->nombre }}</td>
                                <td class="small">{{ $nombresEspShow[$trabajo->especialidad] ?? $trabajo->especialidad }}</td>
                                <td>
                                    @if($trabajo->estado === 'FINALIZADO')
                                    <span class="badge bg-success-lt">Finalizado</span>
                                    @elseif($trabajo->estado === 'EN_PROCESO')
                                    <span class="badge bg-warning-lt">En proceso</span>
                                    @else
                                    <span class="badge bg-secondary-lt">Pendiente</span>
                                    @endif
                                </td>
                                <td class="small text-muted">{{ $trabajo->inicio_en?->format('d/m H:i') ?? '—' }}</td>
                                <td class="small text-muted">{{ $trabajo->fin_en?->format('d/m H:i') ?? '—' }}</td>
                                @if(in_array('ADMIN', Auth::user()->roles ?: []) || in_array('COORDINADOR', Auth::user()->roles ?: []))
                                <td class="text-end small">
                                    <form method="POST" action="{{ route('trabajos.valor', $trabajo) }}" class="d-flex gap-1 justify-content-end">
                                        @csrf
                                        <div class="input-group input-group-sm" style="max-width:130px">
                                            <span class="input-group-text">$</span>
                                            <input type="number" name="valor_liquidar" class="form-control text-end"
                                                   value="{{ $trabajo->valor_liquidar }}" min="0" step="1">
                                        </div>
                                        <button class="btn btn-sm btn-outline-secondary">✓</button>
                                    </form>
                                </td>
                                @endif
                                <td>
                                    @if($trabajo->estado === 'PENDIENTE')
                                    @php $r = Auth::user()->roles ?: []; @endphp
                                    @if(in_array('ADMIN',$r) || in_array('COORDINADOR',$r))
                                    <form method="POST"
                                          action="{{ route('ordenes.tecnicos.destroy', [$orden, $trabajo]) }}"
                                          data-confirm="¿Quitar este técnico?">
                                        @csrf @method('DELETE')
                                        <button class="btn btn-sm btn-outline-danger">✕</button>
                                    </form>
                                    @endif
                                    @endif
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @else
                <p class="text-muted small mb-3">Sin técnicos asignados aún.</p>
                @endif

                {{-- Actividad de técnicos: comentarios y fotos --}}
                @php
                $conActividad = $orden->trabajosTecnico->filter(
                    fn($t) => $t->historialComentarios->count() > 0 || $t->fotos->count() > 0
                );
                @endphp
                @if($conActividad->count() > 0)
                <div class="border-top pt-3 mt-2">
                    <div class="fw-semibold small text-muted mb-2">Actividad de técnicos</div>
                    @php $nombresEspAct = ['LAT'=>'Latonero','PREP'=>'Preparador','PINT'=>'Pintor','MEC'=>'Mecánico','ELEC'=>'Electricista','AA'=>'Aire Acondicionado','SCANNER'=>'Diagnóstico']; @endphp
                    @foreach($conActividad as $trb)
                    <div class="mb-3 p-2 border rounded">
                        <div class="fw-semibold small mb-2">
                            {{ $trb->tecnico->nombre }}
                            <span class="badge bg-secondary-lt ms-1">{{ $nombresEspAct[$trb->especialidad] ?? $trb->especialidad }}</span>
                        </div>

                        {{-- Comentarios del técnico --}}
                        @if($trb->historialComentarios->count() > 0)
                        <div class="mb-2">
                            <div class="text-muted small mb-1">Comentarios ({{ $trb->historialComentarios->count() }})</div>
                            @foreach($trb->historialComentarios as $com)
                            <div class="d-flex gap-2 small mb-1">
                                <span class="text-muted text-nowrap" style="font-size:.7rem;min-width:90px">
                                    {{ $com->created_at->format('d/m H:i') }}
                                </span>
                                <span>{{ $com->texto }}</span>
                            </div>
                            @endforeach
                        </div>
                        @endif

                        {{-- Fotos del técnico --}}
                        @if($trb->fotos->count() > 0)
                        <div>
                            <div class="text-muted small mb-1">Fotos ({{ $trb->fotos->count() }})</div>
                            <div class="d-flex flex-wrap gap-2">
                                @foreach($trb->fotos as $foto)
                                <a href="{{ asset('storage/' . $foto->ruta) }}" target="_blank">
                                    <img src="{{ asset('storage/' . $foto->ruta) }}"
                                         alt="{{ $foto->descripcion ?? 'Foto técnico' }}"
                                         class="rounded border"
                                         style="height:60px;width:60px;object-fit:cover"
                                         title="{{ $foto->descripcion ?? '' }} — {{ $foto->created_at->format('d/m H:i') }}">
                                </a>
                                @endforeach
                            </div>
                        </div>
                        @endif
                    </div>
                    @endforeach
                </div>
                @endif

                {{-- Formulario asignar técnico --}}
                @php $roles = Auth::user()->roles ?: []; @endphp
                @if(in_array('ADMIN', $roles) || in_array('COORDINADOR', $roles))
                <form method="POST" action="{{ route('ordenes.tecnicos.store', $orden) }}"
                      class="row g-2 align-items-end border-top pt-3">
                    @csrf
                    <div class="col-12 col-md-5">
                        <label class="form-label mb-1 small">Técnico</label>
                        <select name="id_tecnico" class="form-select form-select-sm" required>
                            <option value="">Seleccionar técnico...</option>
                            @foreach($tecnicos as $tec)
                            <option value="{{ $tec->id }}">
                                {{ $tec->nombre }}
                                ({{ implode(', ', $tec->especialidades ?: []) }})
                            </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-6 col-md-3">
                        <label class="form-label mb-1 small">Especialidad</label>
                        <select name="especialidad" class="form-select form-select-sm" required>
                            <option value="">Seleccionar...</option>
                            <option value="LAT">Latonero</option>
                            <option value="PREP">Preparador</option>
                            <option value="PINT">Pintor</option>
                            <option value="MEC">Mecánico</option>
                            <option value="ELEC">Electricista</option>
                            <option value="AA">Aire Acondicionado</option>
                            <option value="SCANNER">Diagnóstico electrónico</option>
                        </select>
                    </div>
                    <div class="col-6 col-md-2">
                        <button type="submit" class="btn btn-primary btn-sm w-100">
                            Asignar
                        </button>
                    </div>
                </form>
                @endif

            </div>
        </div>
    </div>

    {{-- Entregas Parciales --}}
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Entregas Parciales</h3>
            </div>
            <div class="card-body">

                @if($orden->entregasParciales->count())
                <div class="table-responsive mb-3">
                    <table class="table table-sm mb-0">
                        <thead>
                            <tr>
                                <th>Fecha salida</th>
                                <th>Descripción</th>
                                <th>Fecha retorno</th>
                                <th>Motivo</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($orden->entregasParciales as $ep)
                            <tr>
                                <td class="small">{{ $ep->fecha_entrega->format('d/m/Y') }}</td>
                                <td class="small">{{ $ep->descripcion }}</td>
                                <td class="small">
                                    {{ $ep->fecha_retorno?->format('d/m/Y') ?? '' }}
                                    @if(!$ep->fecha_retorno)
                                    <span class="badge bg-warning-lt">Pendiente retorno</span>
                                    @endif
                                </td>
                                <td class="small text-muted">{{ $ep->motivo_retorno ?? '—' }}</td>
                                <td>
                                    @if(!$ep->fecha_retorno)
                                    @php $r2 = Auth::user()->roles ?: []; @endphp
                                    @if(in_array('ADMIN',$r2) || in_array('COORDINADOR',$r2))
                                    <button class="btn btn-sm btn-outline-success" type="button"
                                            data-bs-toggle="collapse"
                                            data-bs-target="#retorno-{{ $ep->id }}">
                                        Registrar retorno
                                    </button>
                                    @endif
                                    @else
                                    <span class="badge bg-success-lt">Retornó</span>
                                    @endif
                                </td>
                            </tr>
                            @if(!$ep->fecha_retorno)
                            <tr>
                                <td colspan="5" class="p-0">
                                    <div class="collapse" id="retorno-{{ $ep->id }}">
                                        <div class="p-3 bg-light">
                                            <form method="POST"
                                                  action="{{ route('entregas-parciales.retorno', $ep) }}"
                                                  class="row g-2 align-items-end">
                                                @csrf
                                                <div class="col-12 col-md-3">
                                                    <label class="form-label small fw-bold">Fecha de retorno</label>
                                                    <input type="date" name="fecha_retorno"
                                                           class="form-control form-control-sm"
                                                           value="{{ now()->toDateString() }}"
                                                           min="{{ $ep->fecha_entrega->format('Y-m-d') }}"
                                                           max="{{ now()->toDateString() }}" required>
                                                </div>
                                                <div class="col-12 col-md-6">
                                                    <label class="form-label small fw-bold">Motivo del retorno <span class="text-danger">*</span></label>
                                                    <input type="text" name="motivo_retorno"
                                                           class="form-control form-control-sm"
                                                           placeholder="¿Por qué retornó el vehículo?"
                                                           required maxlength="500">
                                                </div>
                                                <div class="col-auto">
                                                    <button class="btn btn-success btn-sm">Confirmar retorno</button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                            @endif
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @else
                <p class="text-muted small mb-3">Sin entregas parciales registradas.</p>
                @endif

                {{-- Formulario nueva entrega parcial --}}
                @php $r2 = Auth::user()->roles ?: []; @endphp
                @if((in_array('ADMIN',$r2) || in_array('COORDINADOR',$r2)) && $orden->estado_proceso === 'EN_PROCESO')
                <form method="POST" action="{{ route('entregas-parciales.store', $orden) }}"
                      class="row g-2 align-items-end border-top pt-3">
                    @csrf
                    <div class="col-12 col-md-3">
                        <label class="form-label small fw-bold">Fecha de salida</label>
                        <input type="date" name="fecha_entrega" class="form-control form-control-sm"
                               value="{{ now()->toDateString() }}"
                               max="{{ now()->toDateString() }}" required>
                    </div>
                    <div class="col-12 col-md-6">
                        <label class="form-label small fw-bold">Descripción <span class="text-danger">*</span></label>
                        <input type="text" name="descripcion" class="form-control form-control-sm"
                               placeholder="Ej: Cliente recoge vehículo mientras llegan repuestos..."
                               required maxlength="500">
                    </div>
                    <div class="col-auto">
                        <button class="btn btn-warning btn-sm"
                                data-confirm="¿Registrar esta entrega parcial? El estado cambiará a Entrega Parcial.">
                            Registrar entrega parcial
                        </button>
                    </div>
                </form>
                @endif

            </div>
        </div>
    </div>

    {{-- Cotizaciones --}}
    @if($orden->cotizaciones->count() > 0)
    @php $rCot = Auth::user()->roles ?: []; $puedeVerCot = in_array('ADMIN',$rCot)||in_array('COORDINADOR',$rCot)||in_array('COTIZADOR',$rCot); @endphp
    @if($puedeVerCot)
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Cotizaciones</h3>
                <span class="card-subtitle ms-2 text-muted">{{ $orden->cotizaciones->count() }} registrada(s)</span>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-sm table-hover mb-0">
                        <thead>
                            <tr>
                                <th># COT</th>
                                <th>Estado</th>
                                <th>Fecha</th>
                                <th>Total</th>
                                <th>Creada por</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($orden->cotizaciones->sortByDesc('created_at') as $cot)
                            <tr>
                                <td class="fw-bold text-primary">{{ $cot->numero_cot }}</td>
                                <td>
                                    @if($cot->estado === 'AUTORIZADA')
                                    <span class="badge bg-success-lt">Autorizada</span>
                                    @elseif($cot->estado === 'RECHAZADA')
                                    <span class="badge bg-danger-lt">Rechazada</span>
                                    @elseif($cot->estado === 'BORRADOR')
                                    <span class="badge bg-secondary-lt">Borrador</span>
                                    @else
                                    <span class="badge bg-warning-lt">{{ $cot->estado }}</span>
                                    @endif
                                </td>
                                <td class="small text-muted">{{ $cot->created_at->format('d/m/Y') }}</td>
                                <td class="small fw-medium">$ {{ number_format($cot->total, 0, ',', '.') }}</td>
                                <td class="small text-muted">{{ $cot->creadaPor?->name ?? '—' }}</td>
                                <td>
                                    <div class="d-flex gap-1">
                                        <a href="{{ route('cotizaciones.show', $cot) }}"
                                           class="btn btn-xs btn-outline-primary">Ver</a>
                                        <a href="{{ route('cotizaciones.pdf', $cot) }}"
                                           class="btn btn-xs btn-outline-secondary" target="_blank">PDF</a>
                                    </div>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    @endif
    @endif

    {{-- Historial --}}
    @php $puedeVerCot = in_array('ADMIN', Auth::user()->roles ?: []) || in_array('COORDINADOR', Auth::user()->roles ?: []) || in_array('COTIZADOR', Auth::user()->roles ?: []); @endphp
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Historial de Estados</h3>
                <span class="card-subtitle ms-2 text-muted small">
                    Fecha evento = fecha real del proceso · Registrado = cuándo se capturó en el sistema
                </span>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-sm table-hover mb-0">
                        <thead>
                            <tr>
                                <th style="width:110px">Fecha evento</th>
                                <th style="width:130px">Registrado</th>
                                <th>Estado</th>
                                <th>Usuario</th>
                                <th>Comentario</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($orden->historial as $h)
                            <tr>
                                <td>
                                    @if($h->fecha_evento)
                                        <span class="fw-medium">{{ $h->fecha_evento->format('d/m/Y') }}</span>
                                    @else
                                        <span class="text-muted small">—</span>
                                    @endif
                                </td>
                                <td class="small text-muted">
                                    {{ $h->created_at->format('d/m/Y') }}
                                    @if($h->fecha_evento && $h->fecha_evento->format('Y-m-d') !== $h->created_at->format('Y-m-d'))
                                    <span class="badge bg-warning-lt ms-1" title="La fecha real del evento difiere de cuando se registró">!</span>
                                    @endif
                                    <div class="text-muted" style="font-size:0.7rem">{{ $h->created_at->format('H:i') }}</div>
                                </td>
                                <td><x-estado-badge :estado="$h->estado_nuevo" /></td>
                                <td class="small">{{ $h->user->name }}</td>
                                <td class="small text-muted">
                                    @if($h->comentario && preg_match('/Cotización #(\d+)/', $h->comentario, $m))
                                        @php $cotLink = $orden->cotizaciones->firstWhere('numero_cot', (int)$m[1]); @endphp
                                        @if($cotLink && $puedeVerCot ?? false)
                                            {!! preg_replace(
                                                '/Cotización #(\d+)/',
                                                '<a href="' . route('cotizaciones.show', $cotLink) . '" class="fw-medium">Cotización #$1</a>',
                                                e($h->comentario)
                                            ) !!}
                                        @else
                                            {{ $h->comentario }}
                                        @endif
                                    @else
                                        {{ $h->comentario ?? '—' }}
                                    @endif
                                </td>
                            </tr>
                            @empty
                            <tr><td colspan="5" class="text-muted text-center">Sin historial.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

</div>
@endsection
