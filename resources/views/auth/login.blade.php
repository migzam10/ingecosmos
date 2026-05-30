<x-guest-layout>
<div class="card card-md">
    <div class="card-body">
        <h2 class="h2 text-center mb-4">Ingresar al sistema</h2>

        @if(session('status'))
        <div class="alert alert-info mb-3">{{ session('status') }}</div>
        @endif

        <form method="POST" action="{{ route('login') }}" autocomplete="off">
            @csrf

            <div class="mb-3">
                <label class="form-label" for="email">Correo electrónico</label>
                <input id="email" type="email" name="email"
                       class="form-control @error('email') is-invalid @enderror"
                       value="{{ old('email') }}" required autofocus autocomplete="username">
                @error('email')
                <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="mb-3">
                <label class="form-label" for="password">Contraseña</label>
                <div class="input-group input-group-flat">
                    <input id="password" type="password" name="password"
                           class="form-control @error('password') is-invalid @enderror"
                           required autocomplete="current-password">
                    @error('password')
                    <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>

            <div class="mb-3">
                <label class="form-check">
                    <input type="checkbox" name="remember" class="form-check-input">
                    <span class="form-check-label">Recordarme</span>
                </label>
            </div>

            <div class="form-footer">
                <button type="submit" class="btn btn-primary w-100">
                    Ingresar
                </button>
            </div>
        </form>
    </div>
</div>

@if(Route::has('password.request'))
<div class="text-center text-muted mt-3">
    <a href="{{ route('password.request') }}">Olvidé mi contraseña</a>
</div>
@endif
</x-guest-layout>
