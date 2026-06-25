<!DOCTYPE html>
<html lang="es" class="h-full bg-slate-50">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Panel') · RentCar Admin</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="h-full font-sans text-slate-800" x-data="{ sidebar: false }">
<div class="min-h-full">
    {{-- Sidebar --}}
    <aside class="fixed inset-y-0 left-0 z-40 w-64 bg-navy text-slate-200 transform transition-transform lg:translate-x-0"
           :class="sidebar ? 'translate-x-0' : '-translate-x-full'">
        <div class="flex items-center gap-2 px-6 h-16 border-b border-white/10">
            <span class="inline-block w-3 h-3 rotate-45 bg-primary rounded-sm"></span>
            <span class="font-semibold text-white text-lg">RentCar</span>
        </div>
        <nav class="px-3 py-4 space-y-1 text-sm">
            @php
                $nav = [
                    ['dashboard', 'Dashboard', 'admin.dashboard'],
                    ['vehicles', 'Vehículos', 'admin.vehicles.index'],
                    ['reservations', 'Reservas', 'admin.reservations.index'],
                    ['customers', 'Clientes', 'admin.customers.index'],
                    ['payments', 'Pagos', 'admin.payments.index'],
                    ['deposits', 'Depósitos', 'admin.deposits.index'],
                    ['deliveries', 'Entregas', 'admin.deliveries.index'],
                    ['inspections', 'Inspecciones', 'admin.inspections.index'],
                    ['contracts', 'Contratos', 'admin.contracts.index'],
                    ['reviews', 'Calificaciones', 'admin.reviews.index'],
                    ['reports', 'Reportes', 'admin.reports.index'],
                ];
            @endphp
            @foreach ($nav as [$key, $label, $routeName])
                @php $active = request()->routeIs($routeName) || request()->routeIs(str_replace('.index','.*',$routeName)); @endphp
                <a href="{{ Route::has($routeName) ? route($routeName) : '#' }}"
                   class="flex items-center gap-3 px-3 py-2.5 rounded-xl transition
                          {{ $active ? 'bg-primary text-white' : 'text-slate-300 hover:bg-white/5 hover:text-white' }}">
                    <span class="w-1.5 h-1.5 rounded-full {{ $active ? 'bg-white' : 'bg-slate-500' }}"></span>
                    {{ $label }}
                </a>
            @endforeach
        </nav>
    </aside>

    {{-- Overlay móvil --}}
    <div x-show="sidebar" @click="sidebar = false" class="fixed inset-0 z-30 bg-black/40 lg:hidden" x-cloak></div>

    <div class="lg:pl-64">
        {{-- Topbar --}}
        <header class="sticky top-0 z-20 flex items-center justify-between h-16 px-4 sm:px-6 bg-white border-b border-slate-200">
            <button @click="sidebar = !sidebar" class="lg:hidden text-slate-500" aria-label="Menú">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
            </button>
            <h1 class="text-lg font-semibold text-slate-800">@yield('title', 'Panel')</h1>
            <div class="flex items-center gap-3" x-data="{ open: false }">
                <span class="hidden sm:block text-sm text-slate-500">{{ auth()->user()?->name }}</span>
                <div class="relative">
                    <button @click="open = !open" class="w-9 h-9 rounded-full bg-primary/10 text-primary font-semibold grid place-items-center">
                        {{ strtoupper(substr(auth()->user()?->name ?? 'A', 0, 1)) }}
                    </button>
                    <div x-show="open" @click.outside="open = false" x-cloak
                         class="absolute right-0 mt-2 w-44 bg-white rounded-xl shadow-lg border border-slate-100 py-1 text-sm">
                        <form method="POST" action="{{ route('admin.logout') }}">
                            @csrf
                            <button type="submit" class="w-full text-left px-4 py-2 text-slate-600 hover:bg-slate-50">Cerrar sesión</button>
                        </form>
                    </div>
                </div>
            </div>
        </header>

        <main class="p-4 sm:p-6 lg:p-8">
            @if (session('status'))
                <div class="mb-4 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-700 px-4 py-3 text-sm">
                    {{ session('status') }}
                </div>
            @endif
            @yield('content')
        </main>
    </div>
</div>
<style>[x-cloak]{display:none!important}</style>
</body>
</html>
