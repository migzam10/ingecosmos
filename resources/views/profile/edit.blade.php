@extends('layouts.app')

@section('title', 'Mi Perfil')
@section('page_title', 'Mi Perfil')

@section('content')
<div class="row g-4">

    {{-- Información personal --}}
    <div class="col-12 col-md-6">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Información personal</h3>
            </div>
            <div class="card-body">

                @if (session('status') === 'profile-updated')
                    <div class="alert alert-success alert-dismissible" role="alert">
                        <div>Datos actualizados correctamente.</div>
                        <a href="#" class="btn-close" data-bs-dismiss="alert" aria-label="close"></a>
                    </div>
                @endif

                <form method="POST" action="{{ route('profile.update') }}">
                    @csrf
                    @method('PATCH')

                    <div class="mb-3">
                        <label class="form-label required" for="name">Nombre</label>
                        <input type="text" id="name" name="name"
                               class="form-control @error('name') is-invalid @enderror"
                               value="{{ old('name', $user->name) }}"
                               required autofocus autocomplete="name">
                        @error('name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label class="form-label required" for="email">Correo electrónico</label>
                        <input type="email" id="email" name="email"
                               class="form-control @error('email') is-invalid @enderror"
                               value="{{ old('email', $user->email) }}"
                               required autocomplete="username">
                        @error('email')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-0">
                        <label class="form-label">Roles asignados</label>
                        <div>
                            @foreach ($user->roles ?? [] as $rol)
                                <span class="badge bg-blue-lt me-1">{{ $rol }}</span>
                            @endforeach
                        </div>
                        <small class="text-muted">Los roles solo puede cambiarlos el administrador.</small>
                    </div>

                    <div class="mt-4">
                        <button type="submit" class="btn btn-primary">
                            <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24"
                                 stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                <path d="M6 4h10l4 4v10a2 2 0 0 1 -2 2h-12a2 2 0 0 1 -2 -2v-12a2 2 0 0 1 2 -2"/>
                                <circle cx="12" cy="14" r="2"/>
                                <polyline points="14 4 14 8 6 8 6 4"/>
                            </svg>
                            Guardar datos
                        </button>
                    </div>
                </form>

            </div>
        </div>
    </div>

    {{-- Cambiar contraseña --}}
    <div class="col-12 col-md-6">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Cambiar contraseña</h3>
            </div>
            <div class="card-body">

                @if (session('status') === 'password-updated')
                    <div class="alert alert-success alert-dismissible" role="alert">
                        <div>Contraseña actualizada correctamente.</div>
                        <a href="#" class="btn-close" data-bs-dismiss="alert" aria-label="close"></a>
                    </div>
                @endif

                <form method="POST" action="{{ route('password.update') }}">
                    @csrf
                    @method('PUT')

                    <div class="mb-3">
                        <label class="form-label required" for="current_password">Contraseña actual</label>
                        <input type="password" id="current_password" name="current_password"
                               class="form-control @if($errors->updatePassword->has('current_password')) is-invalid @endif"
                               autocomplete="current-password">
                        @if ($errors->updatePassword->has('current_password'))
                            <div class="invalid-feedback">{{ $errors->updatePassword->first('current_password') }}</div>
                        @endif
                    </div>

                    <div class="mb-3">
                        <label class="form-label required" for="password">Nueva contraseña</label>
                        <input type="password" id="password" name="password"
                               class="form-control @if($errors->updatePassword->has('password')) is-invalid @endif"
                               autocomplete="new-password">
                        @if ($errors->updatePassword->has('password'))
                            <div class="invalid-feedback">{{ $errors->updatePassword->first('password') }}</div>
                        @endif
                    </div>

                    <div class="mb-3">
                        <label class="form-label required" for="password_confirmation">Confirmar nueva contraseña</label>
                        <input type="password" id="password_confirmation" name="password_confirmation"
                               class="form-control @if($errors->updatePassword->has('password_confirmation')) is-invalid @endif"
                               autocomplete="new-password">
                        @if ($errors->updatePassword->has('password_confirmation'))
                            <div class="invalid-feedback">{{ $errors->updatePassword->first('password_confirmation') }}</div>
                        @endif
                    </div>

                    <div class="mt-4">
                        <button type="submit" class="btn btn-warning">
                            <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24"
                                 stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                <rect x="5" y="11" width="14" height="10" rx="2"/>
                                <circle cx="12" cy="16" r="1"/>
                                <path d="M8 11v-4a4 4 0 0 1 8 0v4"/>
                            </svg>
                            Cambiar contraseña
                        </button>
                    </div>
                </form>

            </div>
        </div>
    </div>

</div>
@endsection
