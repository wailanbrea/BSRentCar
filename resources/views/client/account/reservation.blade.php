@extends('layouts.public')
@section('title', 'Reserva '.$reservation->reservation_number)

@section('content')
@include('client.account.partials.header', ['title' => 'Reserva '.$reservation->reservation_number])

<section class="py-10 bg-slate-50 min-h-[40vh]">
    <div class="max-w-6xl mx-auto px-4 sm:px-6 grid lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2 space-y-6">
            <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-6">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="font-semibold text-slate-800">{{ $reservation->vehicle?->name }}</h2>
                    <x-admin.status-badge :status="$reservation->reservation_status->value" />
                </div>
                <dl class="grid grid-cols-2 gap-3 text-sm">
                    <div><dt class="text-slate-400">Recogida</dt><dd class="text-slate-700">{{ $reservation->start_datetime->format('d/m/Y H:i') }}</dd></div>
                    <div><dt class="text-slate-400">Devolución</dt><dd class="text-slate-700">{{ $reservation->end_datetime->format('d/m/Y H:i') }}</dd></div>
                    <div><dt class="text-slate-400">Entrega</dt><dd class="text-slate-700 capitalize">{{ str_replace('_',' ',$reservation->pickup_type) }}</dd></div>
                    <div><dt class="text-slate-400">Pago</dt><dd><x-admin.status-badge :status="$reservation->payment_status->value" /></dd></div>
                </dl>
            </div>
            <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-6">
                <h2 class="font-semibold text-slate-800 mb-4">Desglose</h2>
                <div class="space-y-2 text-sm">
                    @foreach (['Base' => 'base_price', 'Entrega' => 'delivery_fee', 'Seguro' => 'insurance_fee', 'ITBIS (18%)' => 'tax_amount'] as $label => $field)
                        <div class="flex justify-between"><span class="text-slate-500">{{ $label }}</span><span class="text-slate-700">{{ number_format((float) $reservation->$field, 2) }} {{ $reservation->currency }}</span></div>
                    @endforeach
                    <div class="flex justify-between border-t border-slate-100 pt-2 font-semibold"><span>Total</span><span>{{ number_format((float) $reservation->total_amount, 2) }} {{ $reservation->currency }}</span></div>
                    <div class="flex justify-between text-slate-500"><span>Depósito (hold)</span><span>{{ number_format((float) $reservation->deposit_amount, 2) }} {{ $reservation->currency }}</span></div>
                </div>
            </div>
        </div>
        <div>
            <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-6">
                <h2 class="font-semibold text-slate-800 mb-2">Pago</h2>
                @if ($reservation->payment_status->value === 'paid')
                    <p class="text-sm text-emerald-600">Pago confirmado ✓</p>
                @else
                    <p class="text-sm text-slate-500 mb-4">Tu reserva está pendiente de pago. El pago con Stripe/PayPal se habilitará al configurar las credenciales del proveedor.</p>
                    <button disabled class="w-full rounded-full bg-slate-200 text-slate-400 font-medium py-3 cursor-not-allowed">Pagar (próximamente)</button>
                @endif
            </div>
            <a href="{{ route('account.reservations') }}" class="block text-center text-sm text-slate-500 hover:text-slate-700 mt-4">← Mis reservas</a>
        </div>
    </div>
</section>
@endsection
