@extends('layouts.public')
@section('title', 'Mis reservas')

@section('content')
@include('client.account.partials.header', ['title' => 'Mis reservas'])

<section class="py-10 bg-slate-50 min-h-[40vh]">
    <div class="max-w-6xl mx-auto px-4 sm:px-6">
        @forelse ($reservations as $r)
            <a href="{{ route('account.reservations.show', $r) }}"
               class="flex items-center justify-between bg-white rounded-2xl border border-slate-100 shadow-sm p-5 mb-3 hover:shadow-md transition">
                <div>
                    <p class="font-semibold text-slate-800">{{ $r->vehicle?->name ?? 'Vehículo' }}</p>
                    <p class="text-sm text-slate-500">{{ $r->reservation_number }} · {{ $r->start_datetime->format('d/m/y') }} → {{ $r->end_datetime->format('d/m/y') }}</p>
                </div>
                <div class="text-right">
                    <x-admin.status-badge :status="$r->reservation_status->value" />
                    <p class="text-slate-700 font-medium mt-1">{{ number_format((float) $r->total_amount, 2) }} {{ $r->currency }}</p>
                </div>
            </a>
        @empty
            <div class="bg-white rounded-2xl border border-slate-100 p-12 text-center text-slate-400">
                Aún no tienes reservas. <a href="{{ route('catalog') }}" class="text-primary hover:underline">Explora el catálogo</a>.
            </div>
        @endforelse
        <div class="mt-6">{{ $reservations->links() }}</div>
    </div>
</section>
@endsection
