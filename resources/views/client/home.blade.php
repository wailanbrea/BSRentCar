@extends('layouts.public')
@section('title', 'Renta de vehículos en RD')

@section('content')
{{-- Hero --}}
<section class="relative overflow-hidden">
    <div class="absolute inset-0 bg-gradient-to-br from-[#7c6ff0] via-[#9b7fd4] to-[#f4a26b]"></div>
    <div class="absolute inset-0 bg-navy/30"></div>
    <div class="relative max-w-7xl mx-auto px-4 sm:px-6 pt-32 pb-24 lg:pt-40 lg:pb-32">
        <div class="max-w-2xl text-white">
            <h1 class="text-4xl sm:text-5xl lg:text-6xl font-bold leading-tight">
                Libertad para<br>viajar a tu manera.
            </h1>
            <p class="mt-5 text-white/80 max-w-md">
                Procesos estandarizados y seguro incluido. Vehículos únicos a precios competitivos, listos cuando los necesites.
            </p>
            <a href="{{ route('catalog') }}"
               class="mt-8 inline-flex items-center rounded-full bg-white text-slate-800 font-medium px-7 py-3 hover:bg-slate-100 transition">
                Elegir un auto
            </a>
        </div>
    </div>
</section>

{{-- Explore Our Deal --}}
<section class="py-16 lg:py-20 bg-slate-50">
    <div class="max-w-7xl mx-auto px-4 sm:px-6">
        <h2 class="text-3xl font-bold text-center text-slate-800 mb-10">Explora nuestras ofertas</h2>
        @if ($deals->isEmpty())
            <p class="text-center text-slate-400">Próximamente nuevos vehículos disponibles.</p>
        @else
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
                @foreach ($deals as $vehicle)
                    <x-client.vehicle-card :vehicle="$vehicle" />
                @endforeach
            </div>
            <div class="text-center mt-10">
                <a href="{{ route('catalog') }}" class="inline-flex rounded-full bg-primary hover:bg-primary-dark text-white font-medium px-7 py-3 transition">Ver todo el catálogo</a>
            </div>
        @endif
    </div>
</section>

{{-- Premium Service (navy) --}}
<section id="service" class="py-16 lg:py-20 bg-navy text-white">
    <div class="max-w-7xl mx-auto px-4 sm:px-6">
        <h2 class="text-3xl font-bold text-center mb-12">Nuestro servicio premium</h2>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
            @foreach ([
                ['Disponible 24/7', 'Atención y vehículos disponibles para emergencias en todo momento.'],
                ['Eco-Friendly', 'Flota moderna y eficiente para un viaje más responsable.'],
                ['Bien mantenidos', 'Cada vehículo pasa inspección antes de cada renta.'],
                ['Pago seguro', 'Transacciones protegidas con Stripe y PayPal.'],
            ] as [$title, $text])
                <div class="rounded-2xl border border-white/10 bg-white/5 p-6 text-center">
                    <div class="w-12 h-12 rounded-full bg-white/10 grid place-items-center mx-auto mb-4">
                        <span class="w-3 h-3 rounded-full bg-primary"></span>
                    </div>
                    <h3 class="font-semibold mb-2">{{ $title }}</h3>
                    <p class="text-sm text-white/70">{{ $text }}</p>
                </div>
            @endforeach
        </div>
    </div>
</section>

{{-- Process --}}
<section id="process" class="py-16 lg:py-20">
    <div class="max-w-7xl mx-auto px-4 sm:px-6">
        <h2 class="text-3xl font-bold text-center text-slate-800 mb-12">Cómo reservar tu auto</h2>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
            @foreach ([
                ['01', 'Encuentra tu auto', 'Explora el catálogo y filtra por fecha, precio y categoría.'],
                ['02', 'Reserva', 'Elige fechas y punto de entrega en segundos.'],
                ['03', 'Paga', 'Completa el pago de forma segura.'],
                ['04', 'Confirmado', 'Firma el contrato digital y a la carretera.'],
            ] as [$n, $title, $text])
                <div class="rounded-2xl border border-slate-100 shadow-sm p-6">
                    <span class="inline-grid place-items-center w-9 h-9 rounded-full bg-navy text-white text-xs font-semibold mb-4">{{ $n }}</span>
                    <h3 class="font-semibold text-slate-800 mb-2">{{ $title }}</h3>
                    <p class="text-sm text-slate-500">{{ $text }}</p>
                </div>
            @endforeach
        </div>
    </div>
</section>

{{-- Why choose us --}}
<section id="why" class="py-16 lg:py-20 bg-slate-50">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 grid lg:grid-cols-2 gap-12 items-center">
        <div class="rounded-2xl bg-gradient-to-br from-slate-200 to-slate-100 h-72 grid place-items-center text-7xl">🚘</div>
        <div>
            <h2 class="text-3xl font-bold text-slate-800 mb-3">¿Por qué elegirnos?</h2>
            <p class="text-slate-500 mb-8 max-w-md">Marca la diferencia con un servicio de renta confiable, atención excepcional y precios competitivos.</p>
            <div class="grid sm:grid-cols-2 gap-6">
                @foreach ([
                    ['Soporte al cliente', 'Equipo dedicado disponible 24/7.'],
                    ['Conductor con experiencia', 'Choferes profesionales bajo solicitud.'],
                    ['Muchas ubicaciones', 'Recogida y entrega convenientes.'],
                    ['Marcas verificadas', 'Flota confiable y bien mantenida.'],
                    ['Mejor precio', 'Tarifas competitivas en cada renta.'],
                    ['Cancelación flexible', 'Reservas con cancelación según política.'],
                ] as [$title, $text])
                    <div class="flex gap-3">
                        <span class="mt-1 w-6 h-6 rounded-full bg-primary/10 text-primary grid place-items-center text-xs shrink-0">✓</span>
                        <div>
                            <h3 class="font-semibold text-slate-800 text-sm">{{ $title }}</h3>
                            <p class="text-xs text-slate-500">{{ $text }}</p>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</section>

{{-- CTA --}}
<section class="py-16">
    <div class="max-w-7xl mx-auto px-4 sm:px-6">
        <div class="relative overflow-hidden rounded-3xl bg-navy text-white p-10 lg:p-14">
            <div class="relative max-w-lg">
                <h2 class="text-3xl font-bold leading-tight">¿Listo para la carretera?<br>Reserva tu auto hoy.</h2>
                <p class="mt-3 text-white/70">Nuestro equipo está aquí para ayudarte. Contáctanos cuando lo necesites.</p>
                <a href="{{ route('catalog') }}" class="mt-6 inline-flex rounded-full bg-primary hover:bg-primary-dark text-white font-medium px-7 py-3 transition">Reservar ahora</a>
            </div>
            <div class="absolute right-6 bottom-0 text-[10rem] leading-none opacity-30 hidden lg:block">🚙</div>
        </div>
    </div>
</section>

{{-- Testimonials --}}
<section class="pb-20">
    <div class="max-w-7xl mx-auto px-4 sm:px-6">
        <h2 class="text-3xl font-bold text-center text-slate-800 mb-12">Lo que dicen nuestros clientes</h2>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
            @foreach (['Alex Méndez', 'Alis White', 'Leslie Alexander', 'Floyd Miles'] as $name)
                <div class="rounded-2xl border border-slate-100 shadow-sm p-6">
                    <div class="flex items-center gap-3 mb-3">
                        <div class="w-10 h-10 rounded-full bg-primary/10 text-primary grid place-items-center font-semibold">{{ substr($name, 0, 1) }}</div>
                        <div>
                            <p class="font-semibold text-slate-800 text-sm">{{ $name }}</p>
                            <p class="text-xs text-slate-400">Cliente</p>
                        </div>
                    </div>
                    <p class="text-sm text-slate-500">Excelente servicio. El auto estaba impecable y el proceso fue rápido y sencillo. ¡Repetiré!</p>
                    <p class="text-amber-500 text-sm mt-3">★★★★★</p>
                </div>
            @endforeach
        </div>
    </div>
</section>
@endsection
