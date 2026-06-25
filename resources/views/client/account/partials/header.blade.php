@props(['title' => 'Mi cuenta'])
<section class="bg-navy text-white pt-28 pb-8">
    <div class="max-w-6xl mx-auto px-4 sm:px-6">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-4">
            <h1 class="text-2xl font-bold">{{ $title }}</h1>
            <div class="text-sm text-white/80">
                Sesión iniciada como: <span class="font-semibold text-white">{{ auth()->user()?->name }}</span> ({{ auth()->user()?->email }})
            </div>
        </div>
        <nav class="flex flex-wrap gap-2 text-sm">
            @php
                $items = [
                    ['Resumen', 'account.dashboard'],
                    ['Mis reservas', 'account.reservations'],
                    ['Wallet', 'account.wallet'],
                    ['Perfil', 'account.profile'],
                ];
            @endphp
            @foreach ($items as [$label, $route])
                <a href="{{ route($route) }}"
                   class="px-4 py-1.5 rounded-full {{ request()->routeIs($route) || request()->routeIs($route.'.*') ? 'bg-primary text-white' : 'bg-white/10 text-white/80 hover:bg-white/20' }}">
                    {{ $label }}
                </a>
            @endforeach
        </nav>
    </div>
</section>
@if (session('status'))
    <div class="max-w-6xl mx-auto px-4 sm:px-6 mt-4">
        <div class="rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-700 px-4 py-3 text-sm">{{ session('status') }}</div>
    </div>
@endif
@if ($errors->any())
    <div class="max-w-6xl mx-auto px-4 sm:px-6 mt-4">
        <div class="rounded-xl bg-red-50 border border-red-200 text-red-600 px-4 py-3 text-sm">{{ $errors->first() }}</div>
    </div>
@endif
