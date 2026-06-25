@extends('layouts.public')
@section('title', 'Catálogo de vehículos')

@section('content')
{{-- Banda superior (navy) con filtros --}}
<section class="bg-navy text-white pt-28 pb-10">
    <div class="max-w-7xl mx-auto px-4 sm:px-6">
        <h1 class="text-3xl font-bold mb-6">Encuentra tu vehículo</h1>
        <form method="GET" class="grid grid-cols-2 lg:grid-cols-6 gap-3 bg-white rounded-2xl p-4 text-slate-700">
            <div>
                <label class="block text-xs text-slate-400 mb-1">Desde</label>
                <input type="date" name="start_date" value="{{ $filters['start_date'] ?? '' }}" class="w-full rounded-lg border-slate-200 text-sm px-3 py-2 focus:border-primary focus:ring-primary">
            </div>
            <div>
                <label class="block text-xs text-slate-400 mb-1">Hasta</label>
                <input type="date" name="end_date" value="{{ $filters['end_date'] ?? '' }}" class="w-full rounded-lg border-slate-200 text-sm px-3 py-2 focus:border-primary focus:ring-primary">
            </div>
            <div>
                <label class="block text-xs text-slate-400 mb-1">Categoría</label>
                <select name="category" class="w-full rounded-lg border-slate-200 text-sm px-3 py-2 focus:border-primary focus:ring-primary capitalize">
                    <option value="">Todas</option>
                    @foreach ($categories as $c)
                        <option value="{{ $c->value }}" @selected(($filters['category'] ?? '') === $c->value)>{{ $c->value }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs text-slate-400 mb-1">Transmisión</label>
                <select name="transmission" class="w-full rounded-lg border-slate-200 text-sm px-3 py-2 focus:border-primary focus:ring-primary capitalize">
                    <option value="">Todas</option>
                    @foreach ($transmissions as $t)
                        <option value="{{ $t->value }}" @selected(($filters['transmission'] ?? '') === $t->value)>{{ $t->value }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs text-slate-400 mb-1">Precio máx.</label>
                <input type="number" name="price_max" value="{{ $filters['price_max'] ?? '' }}" placeholder="∞" class="w-full rounded-lg border-slate-200 text-sm px-3 py-2 focus:border-primary focus:ring-primary">
            </div>
            <div class="flex items-end">
                <button class="w-full rounded-full bg-primary hover:bg-primary-dark text-white text-sm font-medium py-2.5">Buscar</button>
            </div>
        </form>
    </div>
</section>

{{-- Resultados --}}
<section class="py-12 bg-slate-50 min-h-[40vh]">
    <div class="max-w-7xl mx-auto px-4 sm:px-6">
        <div class="flex items-center justify-between mb-6">
            <p class="text-sm text-slate-500">{{ $vehicles->total() }} vehículos</p>
            <form method="GET">
                @foreach ($filters as $k => $v) @if ($k !== 'sort' && $v) <input type="hidden" name="{{ $k }}" value="{{ $v }}"> @endif @endforeach
                <select name="sort" onchange="this.form.submit()" class="rounded-lg border-slate-200 text-sm px-3 py-2 focus:border-primary focus:ring-primary">
                    <option value="">Más recientes</option>
                    <option value="price_asc" @selected(($filters['sort'] ?? '') === 'price_asc')>Precio: menor</option>
                    <option value="price_desc" @selected(($filters['sort'] ?? '') === 'price_desc')>Precio: mayor</option>
                    <option value="rating" @selected(($filters['sort'] ?? '') === 'rating')>Mejor valorados</option>
                </select>
            </form>
        </div>

        @if ($vehicles->isEmpty())
            <div class="bg-white rounded-2xl border border-slate-100 p-12 text-center text-slate-400">
                No hay vehículos para esos filtros. Prueba ampliar las fechas o el precio.
            </div>
        @else
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                @foreach ($vehicles as $vehicle)
                    <x-client.vehicle-card :vehicle="$vehicle" />
                @endforeach
            </div>
            <div class="mt-8">{{ $vehicles->links() }}</div>
        @endif
    </div>
</section>
@endsection
