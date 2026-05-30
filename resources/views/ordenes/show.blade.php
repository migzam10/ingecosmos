@extends('layouts.app')

@section('title', 'OT #' . $orden->numero_ot)
@section('page_title', 'OT #' . $orden->numero_ot)
@section('breadcrumb', 'Órdenes de Trabajo')

@section('page_actions')
<div class="d-flex gap-2">
    @if(in_array($orden->estado_proceso, ['PTE_COTIZACION','PTE_AUTORIZACION']))
    @php $r = Auth::user()->roles ?: []; @endphp
    @if(in_array('ADMIN',$r) || in_array('COTIZADOR',$r) || in_array('COORDINADOR',$r))
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
                @php
                $inv = $orden->inventario;
                $itemsInv = [
                    'parabrisas'           => 'Parabrisas',
                    'vidrio_delantero_izq' => 'Vidrio Del. Izq',
                    'vidrio_delantero_der' => 'Vidrio Del. Der',
                    'vidrio_trasero_izq'   => 'Vidrio Tra. Izq',
                    'vidrio_trasero_der'   => 'Vidrio Tra. Der',
                    'vidrio_trasero'       => 'Vidrio Trasero',
                    'espejo_izq'           => 'Espejo Izq',
                    'espejo_der'           => 'Espejo Der',
                    'llanta_del_izq'       => 'Llanta Del. Izq',
                    'llanta_del_der'       => 'Llanta Del. Der',
                    'llanta_tra_izq'       => 'Llanta Tra. Izq',
                    'llanta_tra_der'       => 'Llanta Tra. Der',
                    'llanta_repuesto'      => 'Llanta Repuesto',
                    'antena'               => 'Antena',
                    'radio'                => 'Radio',
                    'encendedor'           => 'Encendedor',
                    'gato'                 => 'Gato',
                    'triangulo'            => 'Triángulo',
                ];
                $colorMap = ['B' => 'success', 'R' => 'warning', 'M' => 'danger'];
                @endphp
                <div class="row g-2">
                    @foreach($itemsInv as $campo => $etiqueta)
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
                @if($inv->observaciones)
                <div class="mt-3 p-2 bg-light rounded small">
                    <strong>Obs. inventario:</strong> {{ $inv->observaciones }}
                </div>
                @endif
            </div>
        </div>
    </div>
    @endif

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
                                <th class="text-end">Valor liquidar</th>
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
                                <td class="text-end small">
                                    @if(in_array('ADMIN', Auth::user()->roles ?: []) || in_array('COORDINADOR', Auth::user()->roles ?: []))
                                    <form method="POST" action="{{ route('trabajos.valor', $trabajo) }}" class="d-flex gap-1 justify-content-end">
                                        @csrf
                                        <div class="input-group input-group-sm" style="max-width:130px">
                                            <span class="input-group-text">$</span>
                                            <input type="number" name="valor_liquidar" class="form-control text-end"
                                                   value="{{ $trabajo->valor_liquidar }}" min="0" step="1000">
                                        </div>
                                        <button class="btn btn-sm btn-outline-secondary">✓</button>
                                    </form>
                                    @else
                                    $ {{ number_format($trabajo->valor_liquidar, 0, ',', '.') }}
                                    @endif
                                </td>
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

    {{-- Historial --}}
    <div class="col-12">
        <div class="card">
            <div class="card-header"><h3 class="card-title">Historial de Estados</h3></div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-sm table-hover mb-0">
                        <thead>
                            <tr>
                                <th>Fecha</th>
                                <th>Estado</th>
                                <th>Usuario</th>
                                <th>Comentario</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($orden->historial as $h)
                            <tr>
                                <td class="text-muted small">{{ $h->created_at->format('d/m/Y H:i') }}</td>
                                <td><x-estado-badge :estado="$h->estado_nuevo" /></td>
                                <td class="small">{{ $h->user->name }}</td>
                                <td class="small text-muted">{{ $h->comentario ?? '—' }}</td>
                            </tr>
                            @empty
                            <tr><td colspan="4" class="text-muted text-center">Sin historial.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

</div>
@endsection
