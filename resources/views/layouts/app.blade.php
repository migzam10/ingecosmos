<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover"/>
    <meta http-equiv="X-UA-Compatible" content="ie=edge"/>
    <title>@yield('title', 'INGECOSMOS') — Taller</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <!-- Tabler CSS CDN -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/core@1.0.0-beta21/dist/css/tabler.min.css">
    <!-- App CSS propio -->
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
    @stack('styles')
</head>
<body class="antialiased">
<div class="wrapper">

    {{-- SIDEBAR --}}
    <aside class="navbar navbar-vertical navbar-expand-lg" data-bs-theme="dark">
        <div class="container-fluid">
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#sidebarMenu">
                <span class="navbar-toggler-icon"></span>
            </button>

            <h1 class="navbar-brand navbar-brand-autodark">
                <a href="{{ route('dashboard') }}" class="text-white fw-bold text-decoration-none">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"
                         fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                         stroke-linejoin="round" class="me-2">
                        <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                        <circle cx="7" cy="17" r="2"/>
                        <circle cx="17" cy="17" r="2"/>
                        <path d="M5 17h-2v-6l2-5h9l4 5h1a2 2 0 0 1 2 2v4h-2"/>
                        <path d="M9 17h6"/>
                    </svg>
                    INGECOSMOS
                </a>
            </h1>

            <div class="collapse navbar-collapse" id="sidebarMenu">
                <ul class="navbar-nav pt-lg-3">

                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}"
                           href="{{ route('dashboard') }}">
                            <span class="nav-link-icon d-md-none d-lg-inline-block">
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"
                                     fill="none" stroke="currentColor" stroke-width="2">
                                    <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                    <polyline points="5 12 3 12 12 3 21 12 19 12"/>
                                    <path d="M5 12v7a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2v-7"/>
                                    <path d="M9 21v-6a2 2 0 0 1 2-2h2a2 2 0 0 1 2 2v6"/>
                                </svg>
                            </span>
                            <span class="nav-link-title">Panel de Control</span>
                        </a>
                    </li>

                    @php $roles = Auth::user()->roles ?? []; @endphp

                    @if(in_array('ADMIN', $roles) || in_array('COORDINADOR', $roles) || in_array('RECEPCION', $roles) || in_array('COTIZADOR', $roles))
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('ordenes.*') ? 'active' : '' }}"
                           href="{{ route('ordenes.index') }}">
                            <span class="nav-link-icon d-md-none d-lg-inline-block">
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"
                                     fill="none" stroke="currentColor" stroke-width="2">
                                    <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                    <rect x="5" y="3" width="14" height="18" rx="2"/>
                                    <line x1="9" y1="7" x2="15" y2="7"/>
                                    <line x1="9" y1="11" x2="15" y2="11"/>
                                    <line x1="9" y1="15" x2="13" y2="15"/>
                                </svg>
                            </span>
                            <span class="nav-link-title">Órdenes de Trabajo</span>
                        </a>
                    </li>
                    @endif

                    @if(in_array('ADMIN', $roles) || in_array('COORDINADOR', $roles))
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('torre.*') ? 'active' : '' }}"
                           href="{{ route('torre.index') }}">
                            <span class="nav-link-icon d-md-none d-lg-inline-block">
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"
                                     fill="none" stroke="currentColor" stroke-width="2">
                                    <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                    <rect x="3" y="4" width="18" height="12" rx="1"/>
                                    <path d="M8 20l4-4 4 4"/>
                                    <path d="M12 16l0 4"/>
                                </svg>
                            </span>
                            <span class="nav-link-title">Torre de Control</span>
                        </a>
                    </li>
                    @endif

                    @if(in_array('TECNICO', $roles))
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('mis-tareas.*') ? 'active' : '' }}"
                           href="{{ route('mis-tareas.index') }}">
                            <span class="nav-link-icon d-md-none d-lg-inline-block">
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"
                                     fill="none" stroke="currentColor" stroke-width="2">
                                    <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                    <path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"/>
                                </svg>
                            </span>
                            <span class="nav-link-title">Mis Tareas</span>
                        </a>
                    </li>
                    @endif

                    @if(in_array('ADMIN', $roles) || in_array('COTIZADOR', $roles))
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('cotizaciones.*') ? 'active' : '' }}"
                           href="{{ route('cotizaciones.index') }}">
                            <span class="nav-link-icon d-md-none d-lg-inline-block">
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"
                                     fill="none" stroke="currentColor" stroke-width="2">
                                    <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                    <line x1="12" y1="1" x2="12" y2="23"/>
                                    <path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/>
                                </svg>
                            </span>
                            <span class="nav-link-title">Cotizaciones</span>
                        </a>
                    </li>
                    @endif

                    @if(in_array('ADMIN', $roles))
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('catalogo.*') ? 'active' : '' }}"
                           href="{{ route('catalogo.index') }}">
                            <span class="nav-link-icon d-md-none d-lg-inline-block">
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"
                                     fill="none" stroke="currentColor" stroke-width="2">
                                    <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                    <path d="M3 19a9 9 0 0 1 9 0a9 9 0 0 1 9 0"/>
                                    <path d="M3 6a9 9 0 0 1 9 0a9 9 0 0 1 9 0"/>
                                    <line x1="3" y1="6" x2="3" y2="19"/>
                                    <line x1="12" y1="6" x2="12" y2="19"/>
                                    <line x1="21" y1="6" x2="21" y2="19"/>
                                </svg>
                            </span>
                            <span class="nav-link-title">Catálogo MO</span>
                        </a>
                    </li>

                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('liquidacion.*') ? 'active' : '' }}"
                           href="{{ route('liquidacion.index') }}">
                            <span class="nav-link-icon d-md-none d-lg-inline-block">
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"
                                     fill="none" stroke="currentColor" stroke-width="2">
                                    <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                    <rect x="3" y="5" width="18" height="14" rx="2"/>
                                    <polyline points="3 10 21 10"/>
                                </svg>
                            </span>
                            <span class="nav-link-title">Liquidación</span>
                        </a>
                    </li>

                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('produccion.*') ? 'active' : '' }}"
                           href="{{ route('produccion.index') }}">
                            <span class="nav-link-icon d-md-none d-lg-inline-block">
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"
                                     fill="none" stroke="currentColor" stroke-width="2">
                                    <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                    <polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/>
                                </svg>
                            </span>
                            <span class="nav-link-title">Producción / KPIs</span>
                        </a>
                    </li>
                    @endif

                    @if(in_array('ADMIN', $roles))
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle {{ request()->routeIs('admin.*') ? 'active' : '' }}"
                           href="#admin-menu" data-bs-toggle="dropdown">
                            <span class="nav-link-icon d-md-none d-lg-inline-block">
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"
                                     fill="none" stroke="currentColor" stroke-width="2">
                                    <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                    <circle cx="12" cy="12" r="3"/>
                                    <path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83-2.83l.06-.06A1.65 1.65 0 0 0 4.68 15a1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 2.83-2.83l.06.06A1.65 1.65 0 0 0 9 4.68a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 2.83l-.06.06A1.65 1.65 0 0 0 19.4 9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"/>
                                </svg>
                            </span>
                            <span class="nav-link-title">Administración</span>
                        </a>
                        <div class="dropdown-menu">
                            <a href="{{ route('admin.usuarios.index') }}" class="dropdown-item {{ request()->routeIs('admin.usuarios.*') ? 'active' : '' }}">
                                Usuarios
                            </a>
                            <a href="{{ route('admin.tecnicos.index') }}" class="dropdown-item {{ request()->routeIs('admin.tecnicos.*') ? 'active' : '' }}">
                                Técnicos
                            </a>
                            <a href="{{ route('admin.empresas.index') }}" class="dropdown-item {{ request()->routeIs('admin.empresas.*') ? 'active' : '' }}">
                                Empresas / CIAs
                            </a>
                            <a href="{{ route('admin.marcas.index') }}" class="dropdown-item {{ request()->routeIs('admin.marcas.*') ? 'active' : '' }}">
                                Marcas y Modelos
                            </a>
                            <a href="{{ route('admin.festivos.index') }}" class="dropdown-item {{ request()->routeIs('admin.festivos.*') ? 'active' : '' }}">
                                Días Festivos
                            </a>
                        </div>
                    </li>
                    @endif

                </ul>

                <div class="my-2 my-lg-0">
                    <div class="nav-item dropdown">
                        <a href="#" class="nav-link d-flex lh-1 text-reset p-0 text-white" data-bs-toggle="dropdown">
                            <span class="avatar avatar-sm"
                                  style="background-color: #4c6ef5; color: #fff; font-weight: 700;">
                                {{ strtoupper(substr(Auth::user()->name ?? 'U', 0, 1)) }}
                            </span>
                            <div class="d-none d-xl-block ps-2">
                                <div>{{ Auth::user()->name ?? '' }}</div>
                                <div class="mt-1 small text-muted">
                                    {{ implode(', ', Auth::user()->roles ?? []) }}
                                </div>
                            </div>
                        </a>
                        <div class="dropdown-menu dropdown-menu-end dropdown-menu-arrow">
                            <a href="{{ route('profile.edit') }}" class="dropdown-item">Mi Perfil</a>
                            <div class="dropdown-divider"></div>
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button type="submit" class="dropdown-item text-danger">
                                    Cerrar Sesión
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </aside>

    {{-- CONTENIDO PRINCIPAL --}}
    <div class="page-wrapper">
        {{-- HEADER DE PÁGINA --}}
        @hasSection('page_title')
        <div class="page-header d-print-none">
            <div class="container-xl">
                <div class="row g-2 align-items-center">
                    <div class="col">
                        @hasSection('breadcrumb')
                        <div class="page-pretitle">
                            @yield('breadcrumb')
                        </div>
                        @endif
                        <h2 class="page-title">
                            @yield('page_title')
                        </h2>
                    </div>
                    @hasSection('page_actions')
                    <div class="col-auto ms-auto d-print-none">
                        @yield('page_actions')
                    </div>
                    @endif
                </div>
            </div>
        </div>
        @endif

        {{-- FLASH MESSAGES --}}
        @if(session('success') || session('error') || session('info') || session('warning'))
        <div class="container-xl mt-2">
            @if(session('success'))
            <div class="alert alert-success alert-dismissible alert-auto-dismiss" role="alert">
                {{ session('success') }}
                <a class="btn-close" data-bs-dismiss="alert" aria-label="close"></a>
            </div>
            @endif
            @if(session('error'))
            <div class="alert alert-danger alert-dismissible alert-auto-dismiss" role="alert">
                {{ session('error') }}
                <a class="btn-close" data-bs-dismiss="alert" aria-label="close"></a>
            </div>
            @endif
            @if(session('info'))
            <div class="alert alert-info alert-dismissible alert-auto-dismiss" role="alert">
                {{ session('info') }}
                <a class="btn-close" data-bs-dismiss="alert" aria-label="close"></a>
            </div>
            @endif
            @if(session('warning'))
            <div class="alert alert-warning alert-dismissible alert-auto-dismiss" role="alert">
                {{ session('warning') }}
                <a class="btn-close" data-bs-dismiss="alert" aria-label="close"></a>
            </div>
            @endif
        </div>
        @endif

        {{-- CONTENIDO --}}
        <div class="page-body">
            <div class="container-xl">
                @yield('content')
            </div>
        </div>

        {{-- FOOTER --}}
        <footer class="footer footer-transparent d-print-none">
            <div class="container-xl">
                <div class="row text-center">
                    <div class="col">
                        <span class="text-muted"><a href="https://migzam.uk/" target="_blank">MigZam &copy; {{ date('Y') }}</a></span>
                    </div>
                </div>
            </div>
        </footer>
    </div>
</div>

<!-- Tabler JS CDN -->
<script src="https://cdn.jsdelivr.net/npm/@tabler/core@1.0.0-beta21/dist/js/tabler.min.js"></script>
<!-- Chart.js CDN -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<!-- App JS propio -->
<script src="{{ asset('js/app.js') }}"></script>
@stack('scripts')
</body>
</html>
