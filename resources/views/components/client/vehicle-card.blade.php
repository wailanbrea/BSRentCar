@props(['vehicle'])

@php
    $img = $vehicle->primaryImage
        ? asset('storage/' . $vehicle->primaryImage->path)
        : null;
@endphp

<div class="bg-white rounded-2xl border border-slate-100 shadow-sm overflow-hidden hover:shadow-md transition">
    <a href="{{ route('vehicles.show', $vehicle) }}" class="block">
        <div class="h-40 bg-slate-50 flex items-center justify-center overflow-hidden p-3">
            @if ($img)
                <img src="{{ $img }}" alt="{{ $vehicle->name }}" class="max-w-full h-full w-auto object-contain mx-auto">
            @else
                <span class="text-slate-300 text-5xl">🚗</span>
            @endif
        </div>
    </a>
    <div class="p-4">
        <div class="flex items-center justify-between">
            <h3 class="font-semibold text-slate-800">{{ $vehicle->name }}</h3>
            @if ($vehicle->rating_count > 0)
                <span class="text-xs text-amber-500">★ {{ number_format((float) $vehicle->rating_avg, 1) }}</span>
            @endif
        </div>
        <p class="text-primary font-semibold mt-1">{{ number_format((float) $vehicle->daily_price, 0) }} {{ $vehicle->currency }}<span class="text-slate-400 text-sm font-normal">/día</span></p>
        <div class="flex items-center gap-3 text-xs text-slate-400 mt-2 capitalize">
            <span>{{ $vehicle->category->value }}</span>
            <span>·</span>
            <span>{{ $vehicle->transmission->value }}</span>
            <span>·</span>
            <span>{{ $vehicle->seats }} pas.</span>
        </div>
        <a href="{{ route('vehicles.show', $vehicle) }}"
           class="mt-4 block text-center rounded-lg bg-brandslate hover:bg-slate-600 text-white text-sm font-medium py-2.5 transition">
            Reservar
        </a>
    </div>
</div>
