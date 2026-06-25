@extends('layouts.public')
@section('title', $vehicle->name)

@section('content')
<section class="bg-navy text-white pt-28 pb-8">
    <div class="max-w-7xl mx-auto px-4 sm:px-6">
        <a href="{{ route('catalog') }}" class="text-white/70 text-sm hover:text-white">← Volver al catálogo</a>
        <h1 class="text-3xl font-bold mt-2">{{ $vehicle->name }}</h1>
    </div>
</section>

<section class="py-12 bg-slate-50">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 grid lg:grid-cols-3 gap-8">
        {{-- Galería + info --}}
        <div class="lg:col-span-2 space-y-6">
            <div class="bg-white rounded-2xl border border-slate-100 overflow-hidden">
                <div class="h-72 bg-slate-100 grid place-items-center overflow-hidden">
                    @if ($vehicle->primaryImage)
                        <img src="{{ asset('storage/' . $vehicle->primaryImage->path) }}" class="w-full h-full object-cover" alt="">
                    @else <span class="text-7xl">🚗</span> @endif
                </div>
                @if ($vehicle->images->count() > 1)
                    <div class="flex gap-2 p-3 overflow-x-auto">
                        @foreach ($vehicle->images as $img)
                            <img src="{{ asset('storage/' . $img->path) }}" class="w-20 h-16 object-cover rounded-lg border border-slate-100" alt="">
                        @endforeach
                    </div>
                @endif
            </div>

            <div class="bg-white rounded-2xl border border-slate-100 p-6">
                <h2 class="font-semibold text-slate-800 mb-3">Características</h2>
                <div class="grid grid-cols-2 sm:grid-cols-3 gap-3 text-sm text-slate-600">
                    <div><span class="text-slate-400">Categoría:</span> <span class="capitalize">{{ $vehicle->category->value }}</span></div>
                    <div><span class="text-slate-400">Transmisión:</span> <span class="capitalize">{{ $vehicle->transmission->value }}</span></div>
                    <div><span class="text-slate-400">Pasajeros:</span> {{ $vehicle->seats }}</div>
                    <div><span class="text-slate-400">Puertas:</span> {{ $vehicle->doors }}</div>
                    <div><span class="text-slate-400">Combustible:</span> {{ $vehicle->fuel_type ?? '—' }}</div>
                    <div><span class="text-slate-400">Año:</span> {{ $vehicle->year ?? '—' }}</div>
                </div>
                @if ($vehicle->features->isNotEmpty())
                    <div class="flex flex-wrap gap-2 mt-4">
                        @foreach ($vehicle->features as $f)
                            <span class="text-xs bg-slate-100 text-slate-600 rounded-full px-3 py-1">{{ $f->name }}</span>
                        @endforeach
                    </div>
                @endif
                @if ($vehicle->description)
                    <p class="text-sm text-slate-500 mt-4">{{ $vehicle->description }}</p>
                @endif
            </div>

            {{-- Reseñas --}}
            <div class="bg-white rounded-2xl border border-slate-100 p-6">
                <h2 class="font-semibold text-slate-800 mb-4">Calificaciones</h2>
                @forelse ($reviews as $r)
                    <div class="border-b border-slate-50 py-3 last:border-0">
                        <div class="flex items-center justify-between">
                            <span class="text-sm font-medium text-slate-700">{{ $r->customer?->first_name ?? 'Cliente' }}</span>
                            <span class="text-amber-500 text-sm">{!! str_repeat('★', (int) $r->rating_overall) !!}</span>
                        </div>
                        @if ($r->comment)<p class="text-sm text-slate-500 mt-1">{{ $r->comment }}</p>@endif
                    </div>
                @empty
                    <p class="text-sm text-slate-400">Aún sin calificaciones.</p>
                @endforelse
            </div>
        </div>

        {{-- Sidebar precio --}}
        <div>
            <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-6 sticky top-6">
                <p class="text-3xl font-bold text-primary">{{ number_format((float) $vehicle->daily_price, 0) }} {{ $vehicle->currency }}<span class="text-slate-400 text-base font-normal">/día</span></p>
                @if ($vehicle->rating_count > 0)
                    <p class="text-amber-500 text-sm mt-1">★ {{ number_format((float) $vehicle->rating_avg, 1) }} ({{ $vehicle->rating_count }})</p>
                @endif
                @if ($errors->has('booking'))
                    <div class="mt-4 rounded-xl bg-red-50 border border-red-200 text-red-600 px-3 py-2 text-sm">{{ $errors->first('booking') }}</div>
                @endif
                @auth
                    <form method="POST" action="{{ route('booking.store') }}" class="mt-5 space-y-3">
                        @csrf
                        <input type="hidden" name="vehicle_id" value="{{ $vehicle->id }}">
                        <div>
                            <label class="block text-xs text-slate-400 mb-1">Recogida</label>
                            <input type="datetime-local" name="start_datetime" required class="w-full rounded-xl border-slate-200 text-sm px-4 py-2.5 focus:border-primary focus:ring-primary">
                        </div>
                        <div>
                            <label class="block text-xs text-slate-400 mb-1">Devolución</label>
                            <input type="datetime-local" name="end_datetime" required class="w-full rounded-xl border-slate-200 text-sm px-4 py-2.5 focus:border-primary focus:ring-primary">
                        </div>
                        <input type="hidden" name="pickup_type" value="office">
                        <button class="w-full rounded-full bg-primary hover:bg-primary-dark text-white font-medium py-3 transition">Reservar</button>
                    </form>
                    <p class="text-xs text-slate-400 text-center mt-3">El depósito se autoriza, no se cobra.</p>
                @else
                    <a href="{{ route('login') }}" class="mt-5 block text-center rounded-full bg-primary hover:bg-primary-dark text-white font-medium py-3 transition">Inicia sesión para reservar</a>
                    <p class="text-xs text-slate-400 text-center mt-3">¿No tienes cuenta? <a href="{{ route('register') }}" class="text-primary hover:underline">Regístrate</a></p>
                @endauth
            </div>
        </div>
    </div>
</section>
@endsection
