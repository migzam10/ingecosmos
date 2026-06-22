@extends('layouts.app')

@section('title', 'Configuración de Empresa')
@section('page_title', 'Configuración de Empresa')
@section('breadcrumb', 'Administración')

@section('content')

@if(session('success'))
<div class="alert alert-success alert-dismissible" role="alert">
    {{ session('success') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif

<div class="row">

    {{-- Formulario principal --}}
    <div class="col-12 col-lg-7">
        <form method="POST" action="{{ route('admin.configuracion.update') }}"
              enctype="multipart/form-data">
            @csrf
            @method('PUT')

            <div class="card mb-3">
                <div class="card-header">
                    <h3 class="card-title">Datos de la empresa</h3>
                    <div class="card-subtitle">Se usan en cotizaciones, liquidaciones y PDFs</div>
                </div>
                <div class="card-body">

                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label">Razón social <span class="text-danger">*</span></label>
                            <input type="text" name="razon_social" class="form-control @error('razon_social') is-invalid @enderror"
                                   value="{{ old('razon_social', $config->razon_social) }}"
                                   placeholder="Ej: INGECOSMOS S.A.S." required maxlength="200">
                            @error('razon_social')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-12 col-md-6">
                            <label class="form-label">NIT <span class="text-danger">*</span></label>
                            <input type="text" name="nit" class="form-control @error('nit') is-invalid @enderror"
                                   value="{{ old('nit', $config->nit) }}"
                                   placeholder="Ej: 900.123.456-7" required maxlength="30">
                            @error('nit')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-12 col-md-6">
                            <label class="form-label">Ciudad</label>
                            <input type="text" name="ciudad" class="form-control"
                                   value="{{ old('ciudad', $config->ciudad) }}"
                                   placeholder="Ej: Barranquilla" maxlength="100">
                        </div>

                        <div class="col-12">
                            <label class="form-label">Dirección</label>
                            <input type="text" name="direccion" class="form-control"
                                   value="{{ old('direccion', $config->direccion) }}"
                                   placeholder="Ej: Calle 72 # 45-12, Barranquilla" maxlength="300">
                        </div>

                        <div class="col-12 col-md-6">
                            <label class="form-label">Teléfono</label>
                            <input type="text" name="telefono" class="form-control"
                                   value="{{ old('telefono', $config->telefono) }}"
                                   placeholder="Ej: 605 123 4567" maxlength="30">
                        </div>

                        <div class="col-12 col-md-6">
                            <label class="form-label">Email</label>
                            <input type="email" name="email" class="form-control @error('email') is-invalid @enderror"
                                   value="{{ old('email', $config->email) }}"
                                   placeholder="contacto@ingecosmos.com" maxlength="150">
                            @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-12">
                            <label class="form-label">Pie de página en PDFs</label>
                            <input type="text" name="pie_pagina_pdf" class="form-control"
                                   value="{{ old('pie_pagina_pdf', $config->pie_pagina_pdf) }}"
                                   placeholder="Ej: Calle 72 # 45-12 · Tel: 605 123 4567 · Barranquilla, Colombia"
                                   maxlength="300">
                            <div class="form-text">Aparece al pie de cotizaciones y liquidaciones generadas en PDF.</div>
                        </div>
                    </div>

                </div>
            </div>

            {{-- Logo --}}
            <div class="card mb-3">
                <div class="card-header">
                    <h3 class="card-title">Logo de la empresa</h3>
                    <div class="card-subtitle">Se muestra en el sidebar, como favicon y en PDFs</div>
                </div>
                <div class="card-body">

                    @if($config->logo_url)
                    <div class="mb-3 d-flex align-items-center gap-3">
                        <img src="{{ $config->logo_url }}" alt="Logo actual"
                             style="max-height:80px; max-width:200px; object-fit:contain;"
                             class="border rounded p-1">
                        <div>
                            <div class="text-muted small mb-1">Logo actual</div>
                            <form method="POST" action="{{ route('admin.configuracion.eliminar-logo') }}"
                                  onsubmit="return confirm('¿Eliminar el logo actual?')">
                                @csrf @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-outline-danger">
                                    Eliminar logo
                                </button>
                            </form>
                        </div>
                    </div>
                    @endif

                    <div>
                        <label class="form-label">
                            {{ $config->logo_url ? 'Reemplazar logo' : 'Subir logo' }}
                        </label>
                        <input type="file" name="logo" class="form-control @error('logo') is-invalid @enderror"
                               accept="image/png,image/jpeg,image/svg+xml"
                               onchange="previsualizarLogo(this)">
                        <div class="form-text">PNG, JPG o SVG · Máximo 2 MB · Fondo transparente recomendado (PNG).</div>
                        @error('logo')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        <img id="preview-logo" src="" alt="" class="mt-2 d-none border rounded p-1"
                             style="max-height:80px; max-width:200px; object-fit:contain;">
                    </div>

                </div>
            </div>

            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary">Guardar configuración</button>
                <a href="{{ route('admin.usuarios.index') }}" class="btn btn-outline-secondary">Cancelar</a>
            </div>

        </form>
    </div>

    {{-- Panel informativo lateral --}}
    <div class="col-12 col-lg-5">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">¿Dónde se usa esta información?</h3>
            </div>
            <div class="card-body p-0">
                <ul class="list-group list-group-flush">
                    <li class="list-group-item">
                        <strong>Logo en el sidebar</strong>
                        <div class="text-muted small">Reemplaza el icono SVG del menú lateral</div>
                    </li>
                    <li class="list-group-item">
                        <strong>Favicon del navegador</strong>
                        <div class="text-muted small">El ícono que aparece en la pestaña del navegador</div>
                    </li>
                    <li class="list-group-item">
                        <strong>Encabezado de cotizaciones PDF</strong>
                        <div class="text-muted small">Logo + razón social + NIT en la esquina superior</div>
                    </li>
                    <li class="list-group-item">
                        <strong>Encabezado de liquidaciones PDF</strong>
                        <div class="text-muted small">Misma información en recibos de técnicos</div>
                    </li>
                    <li class="list-group-item">
                        <strong>Pie de página PDF</strong>
                        <div class="text-muted small">Dirección y contacto al pie de cada documento</div>
                    </li>
                </ul>
            </div>
        </div>

        @if($config->exists)
        <div class="card mt-3">
            <div class="card-header">
                <h3 class="card-title">Vista previa</h3>
            </div>
            <div class="card-body text-center">
                @if($config->logo_url)
                <img src="{{ $config->logo_url }}" alt="Logo"
                     style="max-height:60px; max-width:160px; object-fit:contain;" class="mb-2">
                <br>
                @endif
                <strong>{{ $config->razon_social }}</strong>
                @if($config->nit)
                <div class="text-muted small">NIT: {{ $config->nit }}</div>
                @endif
                @if($config->ciudad || $config->telefono)
                <div class="text-muted small mt-1">
                    {{ implode(' · ', array_filter([$config->ciudad, $config->telefono])) }}
                </div>
                @endif
            </div>
        </div>
        @endif
    </div>

</div>

@endsection

@push('scripts')
<script>
function previsualizarLogo(input) {
    const preview = document.getElementById('preview-logo');
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = e => {
            preview.src = e.target.result;
            preview.classList.remove('d-none');
        };
        reader.readAsDataURL(input.files[0]);
    } else {
        preview.classList.add('d-none');
    }
}
</script>
@endpush
