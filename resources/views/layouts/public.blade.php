<!DOCTYPE html>
<html lang="es" class="h-full scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Renta de vehículos') · RentCar</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="h-full font-sans text-slate-800 bg-white" x-data="{ menu: false }">

{{-- Header --}}
<header class="absolute inset-x-0 top-0 z-30">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 h-20 flex items-center justify-between">
        <a href="{{ route('home') }}" class="flex items-center gap-2 text-white">
            <span class="inline-block w-4 h-4 rotate-45 bg-primary rounded-sm"></span>
            <span class="font-semibold text-xl">RentCar</span>
        </a>
        <nav class="hidden md:flex items-center gap-8 text-sm text-white/90">
            <a href="{{ route('home') }}" class="hover:text-white">Inicio</a>
            <a href="{{ route('catalog') }}" class="hover:text-white">Vehículos</a>
            <a href="{{ route('home') }}#service" class="hover:text-white">Servicios</a>
            <a href="{{ route('home') }}#why" class="hover:text-white">Por qué nosotros</a>
        </nav>
        <div class="hidden md:flex items-center gap-3">
            @auth
                <a href="{{ route('account.dashboard') }}" class="text-white/90 text-sm hover:text-white">Mi cuenta</a>
                <form method="POST" action="{{ route('logout') }}">@csrf
                    <button class="rounded-full border border-white/40 text-white text-sm px-5 py-2 hover:bg-white/10">Salir</button>
                </form>
            @else
                <a href="{{ route('login') }}" class="text-white/90 text-sm hover:text-white">Entrar</a>
                <a href="{{ route('register') }}" class="inline-flex items-center rounded-full border border-white/40 text-white text-sm px-5 py-2 hover:bg-white/10">Crear cuenta</a>
            @endauth
        </div>
        <button @click="menu = !menu" class="md:hidden text-white" aria-label="Menú">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
        </button>
    </div>
    <div x-show="menu" x-cloak class="md:hidden bg-navy/95 backdrop-blur px-6 py-4 space-y-2 text-white/90 text-sm">
        <a href="{{ route('home') }}" class="block py-1">Inicio</a>
        <a href="{{ route('catalog') }}" class="block py-1">Vehículos</a>
        <a href="{{ route('catalog') }}" class="block py-1">Reservar ahora</a>
    </div>
</header>

<main>
    @yield('content')
</main>

{{-- Footer --}}
<footer class="bg-white border-t border-slate-100 pt-14 pb-8">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 grid grid-cols-1 md:grid-cols-4 gap-10">
        <div>
            <div class="flex items-center gap-2 mb-3">
                <span class="inline-block w-4 h-4 rotate-45 bg-primary rounded-sm"></span>
                <span class="font-semibold text-xl text-slate-800">RentCar</span>
            </div>
            <p class="text-sm text-slate-500 mb-4 max-w-xs">La solución de renta de vehículos para tus viajes en República Dominicana.</p>
            <form class="flex max-w-xs">
                <input type="email" placeholder="Tu correo" class="flex-1 rounded-l-full border-slate-200 text-sm px-4 py-2 focus:border-primary focus:ring-primary">
                <button type="button" class="rounded-r-full bg-primary text-white text-sm px-4">Suscribir</button>
            </form>
        </div>
        <div>
            <h4 class="font-semibold text-slate-800 mb-3 text-sm">Catálogo</h4>
            <ul class="space-y-2 text-sm text-slate-500">
                <li><a href="{{ route('catalog') }}" class="hover:text-primary">Rentar un auto</a></li>
                <li><a href="{{ route('catalog') }}?category=suv" class="hover:text-primary">SUVs</a></li>
                <li><a href="{{ route('catalog') }}?category=luxury" class="hover:text-primary">Lujo</a></li>
            </ul>
        </div>
        <div>
            <h4 class="font-semibold text-slate-800 mb-3 text-sm">RentCar</h4>
            <ul class="space-y-2 text-sm text-slate-500">
                <li><a href="{{ route('home') }}#why" class="hover:text-primary">Sobre nosotros</a></li>
                <li><a href="{{ route('home') }}#process" class="hover:text-primary">Cómo funciona</a></li>
                <li><a href="{{ route('home') }}#service" class="hover:text-primary">Servicios</a></li>
            </ul>
        </div>
        <div>
            <h4 class="font-semibold text-slate-800 mb-3 text-sm">Soporte</h4>
            <ul class="space-y-2 text-sm text-slate-500">
                <li><a href="#" class="hover:text-primary">FAQ</a></li>
                <li><a href="#" class="hover:text-primary">Contacto</a></li>
                <li><a href="{{ route('admin.login') }}" class="hover:text-primary">Panel admin</a></li>
            </ul>
        </div>
    </div>
    <div class="max-w-7xl mx-auto px-4 sm:px-6 mt-10 pt-6 border-t border-slate-100 text-xs text-slate-400">
        © {{ date('Y') }} RentCar. Todos los derechos reservados.
    </div>
</footer>

<style>[x-cloak]{display:none!important}</style>
</body>
</html>
