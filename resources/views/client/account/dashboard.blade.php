@extends('layouts.public')
@section('title', 'Mi cuenta')

@section('content')
@include('client.account.partials.header', ['title' => 'Hola, '.($customer->first_name ?? auth()->user()->name)])

<section class="py-10 bg-slate-50 min-h-[40vh]">
    <div class="max-w-6xl mx-auto px-4 sm:px-6 grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-6">
            <p class="text-sm text-slate-400">Saldo wallet</p>
            <p class="text-2xl font-bold text-slate-800 mt-1">{{ number_format((float) $wallet->balance, 2) }} {{ $wallet->currency }}</p>
            <a href="{{ route('account.wallet') }}" class="text-primary text-sm hover:underline mt-2 inline-block">Ver movimientos</a>
        </div>
        <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-6">
            <p class="text-sm text-slate-400">Verificación</p>
            <p class="text-lg font-semibold text-slate-800 mt-1 capitalize">{{ $customer->verification_status->value }}</p>
            @unless ($customer->hasApprovedLicense())
                <a href="{{ route('account.profile') }}" class="text-primary text-sm hover:underline mt-2 inline-block">Sube tu licencia para reservar</a>
            @endunless
        </div>
        <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-6">
            <p class="text-sm text-slate-400">Próxima reserva</p>
            @if ($nextReservation)
                <p class="text-lg font-semibold text-slate-800 mt-1">{{ $nextReservation->vehicle?->name }}</p>
                <p class="text-sm text-slate-500">{{ $nextReservation->start_datetime->format('d/m/Y H:i') }}</p>
                <a href="{{ route('account.reservations.show', $nextReservation) }}" class="text-primary text-sm hover:underline mt-2 inline-block">Ver detalle</a>
            @else
                <p class="text-slate-400 mt-1">Sin reservas activas.</p>
                <a href="{{ route('catalog') }}" class="text-primary text-sm hover:underline mt-2 inline-block">Explorar vehículos</a>
            @endif
        </div>
    </div>
</section>
@endsection
