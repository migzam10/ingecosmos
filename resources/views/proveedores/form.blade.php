@extends('layouts.app')

@section('title', isset($proveedor) ? 'Editar Proveedor' : 'Nuevo Proveedor')
@section('page_title', isset($proveedor) ? 'Editar: ' . $proveedor->nombre : 'Nuevo Proveedor')
@section('breadcrumb', 'Compras')

@section('content')
<div class="row justify-content-center">
<div class="col-12 col-lg-7">
<form method="POST" action="{{ isset($proveedor) ? route('proveedores.update', $proveedor) : route('proveedores.store') }}">
    @csrf @isset($proveedor) @method('PUT') @endisset
    <x-errores />
    <div class="card">
        <div class="card-body">
            <div class="mb-3">
                <label class="form-label">Nombre <span class="text-danger">*</span></label>
                <input type="text" name="nombre" class="form-control @error('nombre') is-invalid @enderror"
                       value="{{ old('nombre', $proveedor->nombre ?? '') }}" required maxlength="150">
                @error('nombre')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            <div class="row g-2 mb-0">
                <div class="col-12 col-md-6">
                    <label class="form-label">Cédula / NIT</label>
                    <input type="text" name="nit" class="form-control @error('nit') is-invalid @enderror"
                           value="{{ old('nit', $proveedor->nit ?? '') }}" maxlength="30">
                    @error('nit')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    <div class="form-hint small">Sirve para autollenar el proveedor al crear una orden de compra.</div>
                </div>
                <div class="col-12 col-md-6">
                    <label class="form-label">Teléfono</label>
                    <input type="text" name="telefono" class="form-control @error('telefono') is-invalid @enderror"
                           value="{{ old('telefono', $proveedor->telefono ?? '') }}" maxlength="25">
                    @error('telefono')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
            </div>
        </div>
        <div class="card-footer d-flex gap-2">
            <button type="submit" class="btn btn-primary">
                {{ isset($proveedor) ? 'Guardar Cambios' : 'Crear Proveedor' }}
            </button>
            <a href="{{ route('proveedores.index') }}" class="btn btn-outline-secondary">Cancelar</a>
        </div>
    </div>
</form>
</div></div>
@endsection
