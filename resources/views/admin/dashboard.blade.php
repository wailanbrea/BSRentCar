@extends('admin.layouts.app')
@section('title', 'Dashboard')

@section('content')
@php
    $cur = config('rentcar.currency');
    $cards = [
        ['Ingresos del mes', number_format($kpis['revenue'], 2).' '.$cur, 'bg-primary/10 text-primary'],
        ['Reservas del mes', $kpis['reservations_month'], 'bg-emerald-50 text-emerald-600'],
        ['Ocupación de flota', $kpis['occupancy_rate'].'%', 'bg-amber-50 text-amber-600'],
        ['Vehículos', $kpis['fleet'], 'bg-slate-100 text-slate-600'],
        ['Clientes', $kpis['customers'], 'bg-indigo-50 text-indigo-600'],
        ['Pagos pendientes', $kpis['pending_payment'], 'bg-red-50 text-red-600'],
    ];
@endphp

<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 mb-8">
    @foreach ($cards as [$label, $value, $tone])
        <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-5">
            <div class="inline-flex items-center justify-center w-10 h-10 rounded-xl {{ $tone }} mb-3">
                <span class="w-2.5 h-2.5 rounded-full bg-current"></span>
            </div>
            <p class="text-sm text-slate-500">{{ $label }}</p>
            <p class="text-2xl font-semibold text-slate-800 mt-1">{{ $value }}</p>
        </div>
    @endforeach
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    {{-- Reservas recientes --}}
    <div class="lg:col-span-2 bg-white rounded-2xl border border-slate-100 shadow-sm p-6">
        <h2 class="font-semibold text-slate-800 mb-4">Reservas recientes</h2>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="text-left text-slate-400 border-b border-slate-100">
                    <tr>
                        <th class="py-2 font-medium">Número</th>
                        <th class="py-2 font-medium">Vehículo</th>
                        <th class="py-2 font-medium">Estado</th>
                        <th class="py-2 font-medium text-right">Total</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-50">
                    @forelse ($recent as $r)
                        <tr>
                            <td class="py-3 font-medium text-slate-700">{{ $r->reservation_number }}</td>
                            <td class="py-3 text-slate-500">{{ $r->vehicle?->name ?? '—' }}</td>
                            <td class="py-3"><x-admin.status-badge :status="$r->reservation_status->value" /></td>
                            <td class="py-3 text-right text-slate-700">{{ number_format((float) $r->total_amount, 2) }} {{ $r->currency }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="py-6 text-center text-slate-400">Aún no hay reservas.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Top vehículos --}}
    <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-6">
        <h2 class="font-semibold text-slate-800 mb-4">Top vehículos (mes)</h2>
        <ul class="space-y-3">
            @forelse ($topVehicles as $v)
                <li class="flex items-center justify-between text-sm">
                    <span class="text-slate-600">{{ $v['name'] }}</span>
                    <span class="font-medium text-slate-800">{{ number_format($v['revenue'], 0) }} {{ $cur }}</span>
                </li>
            @empty
                <li class="text-slate-400 text-sm">Sin datos todavía.</li>
            @endforelse
        </ul>
    </div>
</div>
@endsection
