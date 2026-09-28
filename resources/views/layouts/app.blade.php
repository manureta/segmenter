<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- CSRF Token -->
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'Mandarina') }} @yield('title','') </title>

    <!-- Scripts & Styles -->
    @vite(['resources/js/app.js', 'resources/sass/app.scss'])

    <!-- More Scripts -->
    @yield ('header_scripts')
    
    <!-- Switches (custom checkboxes) -->
    <style>
    .toggle.ios, .toggle-on.ios, .toggle-off.ios { border-radius: 20rem; }
    .toggle.ios .toggle-handle { border-radius: 20rem; }
    .toggle-handle{
      margin-top: 4px !important;
    }
    .toggle-group{
      margin-top: -8px !important;
    }
    </style>
</head>
<body>
    @yield('divs4content')
    <div id="app">
            {{-- Barra 1: logo + usuario. Sólo esto - nada de menú acá, para que
                 no crezca de nuevo con cada pantalla nueva que se agregue. --}}
            <nav class="navbar navbar-expand-md navbar-light bg-white shadow-sm py-1">
            <div class="container d-flex justify-content-between align-items-center">
                <a class="navbar-brand text-uppercase d-flex align-items-center mb-0" href="{{ url('/') }}">
                    <img src="/images/mandarina.svg" width="26" height="26" class="d-inline-block align-top mr-2" alt="">
                    {{ config('app.name', 'App sin nombre') }}
                    <small class="text-muted ml-2" style="text-transform: none;">{{ Git::branch() }}</small>
                </a>
                <div class="d-flex align-items-center">
                    @guest
                        <a class="nav-link" href="{{ route('login') }}">{{ __('Login') }}</a>
                        @if (Route::has('register'))
                            <a class="nav-link" href="{{ route('register') }}">{{ __('Register') }}</a>
                        @endif
                    @else
                        <a class="nav-link" alt="Ver/Ocultar mensajes" title="Ver/Ocultar Mensajes flash"
                           onclick="$('div.alert').toggle();" style="cursor: pointer;">Mensajes</a>
                        <div class="dropdown">
                            <a id="navbarDropdownLogin" class="nav-link dropdown-toggle d-flex align-items-center"
                               href="#" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                <img src="{{Auth::user()->getProfilePicURL()}}" style="border-radius: 50%;" width="26" height="26" class="mr-1" alt="Foto de perfil">
                                {{ Auth::user()->name }}
                            </a>
                            <div class="dropdown-menu dropdown-menu-right" aria-labelledby="navbarDropdownLogin">
                                <a class="dropdown-item" href="{{ route('perfil') }}">{{ __('Perfil') }}</a>
                                <a class="dropdown-item" href="{{ route('archivos') }}">{{ __('Archivos') }}</a>
                                @if (Auth::user()->hasRole('Super Admin'))
                                    <a class="dropdown-item" href="{{ route('admin.listarUsuarios') }}">{{ __('Usuarios') }}</a>
                                @endif
                                <div class="dropdown-divider"></div>
                                <a class="dropdown-item" href="{{ route('logout') }}"
                                   onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                                    {{ __('Logout') }}
                                </a>
                                <form id="logout-form" action="{{ route('logout') }}" method="POST" style="display: none;">
                                    @csrf
                                </form>
                            </div>
                        </div>
                    @endguest
                </div>
            </div>
        </nav>

        @auth
            {{-- Barra 2: menús agrupados (antes eran 10 links sueltos en fila,
                 dejaron de entrar/leerse) + contexto (operativo/alcance). --}}
            <nav class="navbar navbar-expand-md navbar-light bg-light border-bottom">
            <div class="container">
                <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="{{ __('Toggle navigation') }}">
                    <span class="navbar-toggler-icon"></span>
                </button>
                <div class="collapse navbar-collapse" id="navbarSupportedContent">
                    <ul class="navbar-nav mr-auto">
                        <li class="nav-item"><a class="nav-link" href="{{ url('/home') }}">Inicio</a></li>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" role="button" data-toggle="dropdown">Territorio</a>
                            <div class="dropdown-menu">
                                <a class="dropdown-item" href="{{ url('/provs') }}">Provincias</a>
                                <a class="dropdown-item" href="{{ url('/prov/deptos') }}">Departamentos</a>
                                <a class="dropdown-item" href="{{ url('/aglos') }}">Aglomerados</a>
                                <a class="dropdown-item" href="{{ url('/localidades') }}">Localidades</a>
                                <a class="dropdown-item" href="{{ route('vias') }}">Vías</a>
                            </div>
                        </li>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" role="button" data-toggle="dropdown">Trabajo</a>
                            <div class="dropdown-menu">
                                <a class="dropdown-item" href="{{ url('/segmentador') }}">Cargar</a>
                                <a class="dropdown-item" href="{{ url('/archivos') }}">Archivos</a>
                                <a class="dropdown-item" href="{{ url('/guia') }}">Guía</a>
                                <a class="dropdown-item" href="{{ url('https://github.com/bichav/salidagrafica-atlas/archive/master.zip') }}">Descargar plugin</a>
                            </div>
                        </li>
                        <li class="nav-item"><a class="nav-link" href="{{ route('controles-calidad') }}">Controles</a></li>
                    </ul>
                    <ul class="navbar-nav ml-auto">
                        @include('includes.operativo_selector')
                        @include('includes.objeto_actual_selector')
                    </ul>
                </div>
            </div>
        </nav>
        @endauth
        <main class="py-4">
		    @include('flash::message')
            @yield('content')
        </main>
        </div>
            @yield('content_main')
        <div id="copyright" class="text-center justify-content-center"
            style="display:block"><hr />© 2023 INDEC - Geoestadística
            <div>{{ Git::branch() }} - {{ Git::version() }} -  {{ Git::lastCommitDate() }}</div>
            <div>{{ Git::submoduleStatus() }}</div>

            </div>
<script>
   document.addEventListener('DOMContentLoaded',  function () {
    $('#flash-overlay-modal').modal();
    $('div.alert').not('.alert-important').delay(6000).fadeOut(2000);
});
</script>
    @yield ('footer_scripts')
</body>
</html>
