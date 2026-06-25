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
            <div class="bg-white rounded-2xl border border-slate-100 overflow-hidden"
                 x-data="{ activeImage: '{{ asset('storage/' . ($vehicle->primaryImage?->path ?? '')) }}' }">
                <div class="h-72 bg-slate-50 flex items-center justify-center overflow-hidden p-4">
                    @if ($vehicle->primaryImage)
                        <img :src="activeImage" class="max-w-full h-full w-auto object-contain mx-auto" alt="{{ $vehicle->name }}">
                    @else 
                        <span class="text-7xl">🚗</span> 
                    @endif
                </div>
                @if ($vehicle->images->count() > 1)
                    <div class="flex gap-2 p-3 overflow-x-auto bg-slate-50/50 border-t border-slate-100">
                        @foreach ($vehicle->images as $img)
                            @php $url = asset('storage/' . $img->path); @endphp
                            <button type="button" @click="activeImage = '{{ $url }}'"
                                    class="focus:outline-none transition transform hover:scale-105">
                                <img src="{{ $url }}"
                                     :class="activeImage === '{{ $url }}' ? 'border-primary ring-2 ring-primary/20' : 'border-slate-200'"
                                     class="w-24 h-16 object-contain bg-white rounded-lg border-2 p-1"
                                     alt="{{ $img->alt }}">
                            </button>
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
            <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-6 sticky top-6"
                 x-data="{
                     start: '{{ old('start_datetime') }}',
                     end: '{{ old('end_datetime') }}',
                     pickupType: '{{ old('pickup_type', 'office') }}',
                     pickupAddress: '{{ old('pickup_address') }}',
                     dailyPrice: {{ (float) $vehicle->daily_price }},
                     depositAmount: {{ (float) ($vehicle->deposit_amount ?? 0) }},
                     currency: '{{ $vehicle->currency }}',
                     get days() {
                         if (!this.start || !this.end) return 0;
                         const s = new Date(this.start);
                         const e = new Date(this.end);
                         if (isNaN(s.getTime()) || isNaN(e.getTime()) || e <= s) return 0;
                         const diffMs = e.getTime() - s.getTime();
                         const diffHours = diffMs / (1000 * 60 * 60);
                         return Math.max(1, Math.ceil(diffHours / 24));
                     },
                     get subtotal() {
                         return this.days * this.dailyPrice;
                     },
                     get tax() {
                         return this.subtotal * 0.18;
                     },
                     get total() {
                         return this.subtotal > 0 ? (this.subtotal + this.tax + this.depositAmount) : 0;
                     }
                 }">
                <p class="text-3xl font-bold text-primary">{{ number_format((float) $vehicle->daily_price, 0) }} {{ $vehicle->currency }}<span class="text-slate-400 text-base font-normal">/día</span></p>
                @if ($vehicle->rating_count > 0)
                    <p class="text-amber-500 text-sm mt-1">★ {{ number_format((float) $vehicle->rating_avg, 1) }} ({{ $vehicle->rating_count }})</p>
                @endif
                @if ($errors->any())
                    <div class="mt-4 rounded-xl bg-red-50 border border-red-200 text-red-600 p-3 text-sm">
                        <ul class="list-disc pl-4 space-y-1">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif
                @auth
                    <form method="POST" action="{{ route('booking.store') }}" class="mt-5 space-y-3">
                        @csrf
                        <input type="hidden" name="vehicle_id" value="{{ $vehicle->id }}">
                        <div>
                            <label class="block text-xs text-slate-400 mb-1">Recogida</label>
                            <input type="datetime-local" x-model="start" name="start_datetime" required class="w-full rounded-xl border-slate-200 text-sm px-4 py-2.5 focus:border-primary focus:ring-primary">
                        </div>
                        <div>
                            <label class="block text-xs text-slate-400 mb-1">Devolución</label>
                            <input type="datetime-local" x-model="end" name="end_datetime" required class="w-full rounded-xl border-slate-200 text-sm px-4 py-2.5 focus:border-primary focus:ring-primary">
                        </div>
                        <div>
                            <label class="block text-xs text-slate-400 mb-1">Método de entrega</label>
                            <select x-model="pickupType" name="pickup_type" class="w-full rounded-xl border-slate-200 text-sm px-4 py-2.5 focus:border-primary focus:ring-primary">
                                <option value="office">Recogida en oficina (Gratis)</option>
                                <option value="home">Entrega a domicilio</option>
                                <option value="airport">Entrega en aeropuerto</option>
                                <option value="hotel">Entrega en hotel</option>
                            </select>
                        </div>
                        <div x-show="pickupType !== 'office'" x-cloak class="mt-2 transition-all">
                            <label class="block text-xs text-slate-400 mb-1">Dirección de entrega *</label>
                            <input type="text" x-model="pickupAddress" name="pickup_address" :required="pickupType !== 'office'" placeholder="Calle, número, apto, etc." class="w-full rounded-xl border-slate-200 text-sm px-4 py-2.5 focus:border-primary focus:ring-primary">
                        </div>
                        
                        {{-- Estimación de precios --}}
                        <div x-show="days > 0" x-cloak class="mt-5 border-t border-slate-100 pt-4 space-y-2 text-sm text-slate-600">
                            <div class="flex justify-between">
                                <span>Días de renta:</span>
                                <span class="font-semibold text-slate-800" x-text="days + ' días'"></span>
                            </div>
                            <div class="flex justify-between">
                                <span>Subtotal renta:</span>
                                <span class="font-semibold text-slate-800" x-text="subtotal.toFixed(2) + ' ' + currency"></span>
                            </div>
                            <div class="flex justify-between">
                                <span>ITBIS (18%):</span>
                                <span class="font-semibold text-slate-800" x-text="tax.toFixed(2) + ' ' + currency"></span>
                            </div>
                            <div class="flex justify-between">
                                <span>Depósito de garantía:</span>
                                <span class="font-semibold text-slate-800" x-text="depositAmount.toFixed(2) + ' ' + currency"></span>
                            </div>
                            <div class="flex justify-between border-t border-slate-100 pt-2 text-base font-bold text-slate-800">
                                <span>Total estimado (con depósito):</span>
                                <span class="text-primary" x-text="total.toFixed(2) + ' ' + currency"></span>
                            </div>
                        </div>

                        <button class="w-full rounded-full bg-primary hover:bg-primary-dark text-white font-medium py-3 transition mt-4">Reservar</button>
                    </form>
                    <p class="text-xs text-slate-400 text-center mt-3">El depósito se autoriza, no se cobra.</p>
                @else
                    <div class="mt-5 space-y-3">
                        <div>
                            <label class="block text-xs text-slate-400 mb-1">Recogida</label>
                            <input type="datetime-local" x-model="start" class="w-full rounded-xl border-slate-200 text-sm px-4 py-2.5 focus:border-primary focus:ring-primary">
                        </div>
                        <div>
                            <label class="block text-xs text-slate-400 mb-1">Devolución</label>
                            <input type="datetime-local" x-model="end" class="w-full rounded-xl border-slate-200 text-sm px-4 py-2.5 focus:border-primary focus:ring-primary">
                        </div>
                        <div>
                            <label class="block text-xs text-slate-400 mb-1">Método de entrega</label>
                            <select x-model="pickupType" class="w-full rounded-xl border-slate-200 text-sm px-4 py-2.5 focus:border-primary focus:ring-primary">
                                <option value="office">Recogida en oficina (Gratis)</option>
                                <option value="home">Entrega a domicilio</option>
                                <option value="airport">Entrega en aeropuerto</option>
                                <option value="hotel">Entrega en hotel</option>
                            </select>
                        </div>
                        <div x-show="pickupType !== 'office'" x-cloak class="mt-2 transition-all">
                            <label class="block text-xs text-slate-400 mb-1">Dirección de entrega *</label>
                            <input type="text" x-model="pickupAddress" placeholder="Calle, número, apto, etc." class="w-full rounded-xl border-slate-200 text-sm px-4 py-2.5 focus:border-primary focus:ring-primary">
                        </div>
                        
                        {{-- Estimación de precios --}}
                        <div x-show="days > 0" x-cloak class="mt-5 border-t border-slate-100 pt-4 space-y-2 text-sm text-slate-600">
                            <div class="flex justify-between">
                                <span>Días de renta:</span>
                                <span class="font-semibold text-slate-800" x-text="days + ' días'"></span>
                            </div>
                            <div class="flex justify-between">
                                <span>Subtotal renta:</span>
                                <span class="font-semibold text-slate-800" x-text="subtotal.toFixed(2) + ' ' + currency"></span>
                            </div>
                            <div class="flex justify-between">
                                <span>ITBIS (18%):</span>
                                <span class="font-semibold text-slate-800" x-text="tax.toFixed(2) + ' ' + currency"></span>
                            </div>
                            <div class="flex justify-between">
                                <span>Depósito de garantía:</span>
                                <span class="font-semibold text-slate-800" x-text="depositAmount.toFixed(2) + ' ' + currency"></span>
                            </div>
                            <div class="flex justify-between border-t border-slate-100 pt-2 text-base font-bold text-slate-800">
                                <span>Total estimado (con depósito):</span>
                                <span class="text-primary" x-text="total.toFixed(2) + ' ' + currency"></span>
                            </div>
                        </div>

                        <a href="{{ route('login') }}" class="block text-center rounded-full bg-primary hover:bg-primary-dark text-white font-medium py-3 transition mt-4">Inicia sesión para reservar</a>
                        <p class="text-xs text-slate-400 text-center mt-3">¿No tienes cuenta? <a href="{{ route('register') }}" class="text-primary hover:underline">Regístrate</a></p>
                    </div>
                @endauth
            </div>
        </div>
    </div>
</section>
@endsection
