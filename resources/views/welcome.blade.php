<!DOCTYPE html>
<html lang="es">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>{{ config('app.name') }}</title>
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="min-h-screen bg-slate-950 font-sans text-slate-100">
        <header class="mx-auto flex max-w-6xl items-center justify-between gap-4 px-5 py-6 sm:px-10">
            <h1 class="shrink-0">
                <img src="{{ asset('sebra-logo.png') }}" alt="{{ config('app.name') }}" width="1774" height="887" class="h-12 w-auto sm:h-20">
            </h1>
            <nav aria-label="Cuenta" class="flex items-center gap-4 text-sm">
                @auth
                    <a class="hover:text-cyan-300" href="{{ route('dashboard') }}">Panel</a>
                @else
                    <a class="hover:text-cyan-300" href="{{ route('login') }}">Iniciar sesión</a>
                    @if (Route::has('register'))
                        <a class="rounded-lg border border-slate-700 px-3 py-2 hover:border-cyan-300" href="{{ route('register') }}">Registrarse</a>
                    @endif
                @endauth
            </nav>
        </header>
    </body>
</html>
