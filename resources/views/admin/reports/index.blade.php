@extends('admin.layouts.app')
@section('title', 'Reportes')

@section('content')
<form method="GET" class="flex flex-wrap items-end gap-3 mb-6">
    <div>
        <label class="block text-xs text-slate-500 mb-1">Desde</label>
        <input type="date" name="start" value="{{ $start }}" class="rounded-xl border-slate-300 focus:border-primary focus:ring-primary px-3 py-2 text-sm">
    </div>
    <div>
        <label class="block text-xs text-slate-500 mb-1">Hasta</label>
        <input type="date" name="end" value="{{ $end }}" class="rounded-xl border-slate-300 focus:border-primary focus:ring-primary px-3 py-2 text-sm">
    </div>
    <button class="rounded-full bg-primary hover:bg-primary-dark text-white text-sm font-medium px-5 py-2">Aplicar</button>
</form>

<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
    @php
        $cur = config('rentcar.currency');
        $cards = [
            ['Ingresos', number_format($revenue['total_revenue'], 2).' '.$cur],
            ['ITBIS recaudado', number_format($revenue['tax_collected'], 2).' '.$cur],
            ['Ocupación', $occupancy['occupancy_rate'].'%'],
            ['Tasa cancelación', $stats['cancellation_rate'].'%'],
        ];
    @endphp
    @foreach ($cards as [$label, $value])
        <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-5">
            <p class="text-sm text-slate-500">{{ $label }}</p>
            <p class="text-2xl font-semibold text-slate-800 mt-1">{{ $value }}</p>
        </div>
    @endforeach
</div>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
    <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-6">
        <h2 class="font-semibold text-slate-800 mb-4">Reservas</h2>
        <div class="space-y-2 text-sm">
            <div class="flex justify-between"><span class="text-slate-500">Total</span><span class="text-slate-700">{{ $stats['total_reservations'] }}</span></div>
            <div class="flex justify-between"><span class="text-slate-500">Completadas</span><span class="text-slate-700">{{ $stats['completed_reservations'] }}</span></div>
            <div class="flex justify-between"><span class="text-slate-500">Canceladas</span><span class="text-slate-700">{{ $stats['cancelled_reservations'] }}</span></div>
        </div>
    </div>
    <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-6">
        <h2 class="font-semibold text-slate-800 mb-4">Top vehículos</h2>
        <ul class="space-y-2 text-sm">
            @forelse ($topVehicles as $v)
                <li class="flex justify-between"><span class="text-slate-600">{{ $v['name'] }}</span><span class="font-medium text-slate-800">{{ number_format($v['revenue'], 0) }} {{ $cur }}</span></li>
            @empty
                <li class="text-slate-400">Sin datos.</li>
            @endforelse
        </ul>
    </div>
</div>
@endsection
