<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <!-- CSRF Token -->
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'Odontosistem') }}</title>
   <!-- <script src="{{ asset('js/app.js') }}" defer></script> Vue.JS-->
    <!-- Styles -->
    <link href="{{ asset('css/main.css') }}" rel="stylesheet">
        @yield('odontograma')
        @yield('finanza')
</head>
<body >
    <div>
        <!--Menu fixed top-->
        <div class="app-container app-theme-white body-tabs-shadow fixed-sidebar fixed-header">
            @include('layouts.menu-header')
            @auth
            @include('layouts.boton-config')
            <div class="app-main">
                @include('layouts.menu')
                @endauth
                <div class="app-main__outer">
                    <div class="app-main__inner">
                        @include('partials.alerts')
                        @yield('content')

                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
<script type="text/javascript" src="{{ asset('./assets/scripts/main.js') }}"></script>


</html>
