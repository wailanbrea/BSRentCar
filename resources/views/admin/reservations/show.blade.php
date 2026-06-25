@extends('admin.layouts.app')
@section('title', 'Reserva '.$reservation->reservation_number)

@section('content')
@if ($errors->any())
    <div class="mb-4 rounded-xl bg-red-50 border border-red-200 text-red-600 px-4 py-3 text-sm">{{ $errors->first() }}</div>
@endif

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    {{-- Resumen --}}
    <div class="lg:col-span-2 space-y-6">
        <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-6">
            <div class="flex items-center justify-between mb-4">
                <h2 class="font-semibold text-slate-800">{{ $reservation->reservation_number }}</h2>
                <x-admin.status-badge :status="$reservation->reservation_status->value" />
            </div>
            <dl class="grid grid-cols-2 gap-4 text-sm">
                <div><dt class="text-slate-400">Cliente</dt><dd class="text-slate-700">{{ $reservation->customer?->first_name }} {{ $reservation->customer?->last_name }}</dd></div>
                <div><dt class="text-slate-400">Correo</dt><dd class="text-slate-700">{{ $reservation->customer?->user?->email }}</dd></div>
                <div><dt class="text-slate-400">Vehículo</dt><dd class="text-slate-700">{{ $reservation->vehicle?->name }}</dd></div>
                <div><dt class="text-slate-400">Pago</dt><dd><x-admin.status-badge :status="$reservation->payment_status->value" /></dd></div>
                <div><dt class="text-slate-400">Inicio</dt><dd class="text-slate-700">{{ $reservation->start_datetime->format('d/m/Y H:i') }}</dd></div>
                <div><dt class="text-slate-400">Devolución</dt><dd class="text-slate-700">{{ $reservation->end_datetime->format('d/m/Y H:i') }}</dd></div>
                <div><dt class="text-slate-400">Entrega</dt><dd class="text-slate-700 capitalize">{{ str_replace('_',' ',$reservation->pickup_type) }}</dd></div>
                <div><dt class="text-slate-400">Contrato</dt><dd class="text-slate-700 capitalize">{{ $reservation->contract_status->value }}</dd></div>
            </dl>
        </div>

        {{-- Desglose --}}
        <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-6">
            <h2 class="font-semibold text-slate-800 mb-4">Desglose</h2>
            <div class="space-y-2 text-sm">
                @foreach (['Base' => 'base_price', 'Entrega' => 'delivery_fee', 'Seguro' => 'insurance_fee', 'Descuento' => 'discount_amount', 'ITBIS (18%)' => 'tax_amount'] as $label => $field)
                    <div class="flex justify-between"><span class="text-slate-500">{{ $label }}</span><span class="text-slate-700">{{ number_format((float) $reservation->$field, 2) }} {{ $reservation->currency }}</span></div>
                @endforeach
                <div class="flex justify-between border-t border-slate-100 pt-2 font-semibold"><span>Total</span><span>{{ number_format((float) $reservation->total_amount, 2) }} {{ $reservation->currency }}</span></div>
                <div class="flex justify-between text-slate-500"><span>Depósito (hold)</span><span>{{ number_format((float) $reservation->deposit_amount, 2) }} {{ $reservation->currency }}</span></div>
            </div>
        </div>

        {{-- Historial --}}
        <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-6">
            <h2 class="font-semibold text-slate-800 mb-4">Historial de estados</h2>
            <ol class="space-y-3">
                @forelse ($reservation->statusLogs as $log)
                    <li class="flex items-center gap-3 text-sm">
                        <span class="w-2 h-2 rounded-full bg-primary"></span>
                        <span class="text-slate-700">{{ str_replace('_',' ', $log->from_status ?? 'inicio') }} → <strong>{{ str_replace('_',' ', $log->to_status) }}</strong></span>
                        <span class="text-slate-400 ml-auto">{{ $log->created_at->format('d/m/y H:i') }}</span>
                    </li>
                @empty
                    <li class="text-slate-400 text-sm">Sin registros.</li>
                @endforelse
            </ol>
        </div>
    </div>

    {{-- Acciones --}}
    <div class="space-y-4">
        <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-6">
            <h2 class="font-semibold text-slate-800 mb-4">Acciones</h2>
            <div class="space-y-2">
                @if ($reservation->reservation_status->value === 'pending_payment')
                    <form method="POST" action="{{ route('admin.reservations.mark-paid', $reservation) }}">
                        @csrf
                        <button class="w-full rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-medium py-2.5">Confirmar pago manual</button>
                    </form>
                @endif
                @if ($reservation->reservation_status->value === 'paid')
                    <form method="POST" action="{{ route('admin.reservations.confirm', $reservation) }}">
                        @csrf
                        <button class="w-full rounded-xl bg-primary hover:bg-primary-dark text-white text-sm font-medium py-2.5">Confirmar reserva</button>
                    </form>
                @endif
                @if (in_array($reservation->reservation_status->value, ['paid','confirmed','in_preparation','contract_pending']))
                    <form method="POST" action="{{ route('admin.contracts.generate', $reservation) }}">
                        @csrf
                        <button class="w-full rounded-xl bg-white border border-slate-200 text-slate-700 hover:bg-slate-50 text-sm font-medium py-2.5">Generar contrato</button>
                    </form>
                @endif
                @if (! in_array($reservation->reservation_status->value, ['cancelled','completed','refunded','expired','no_show']))
                    <form method="POST" action="{{ route('admin.reservations.cancel', $reservation) }}" onsubmit="return confirm('¿Cancelar esta reserva?')">
                        @csrf
                        <button class="w-full rounded-xl bg-white border border-red-200 text-red-600 hover:bg-red-50 text-sm font-medium py-2.5">Cancelar reserva</button>
                    </form>
                @endif
            </div>
        </div>
        <a href="{{ route('admin.reservations.index') }}" class="block text-center text-sm text-slate-500 hover:text-slate-700">← Volver a reservas</a>
    </div>
</div>
@endsection
