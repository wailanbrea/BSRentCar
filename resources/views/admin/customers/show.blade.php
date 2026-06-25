@extends('admin.layouts.app')
@section('title', 'Cliente')

@section('content')
<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-6">
        <h2 class="font-semibold text-slate-800 mb-4">{{ $customer->first_name }} {{ $customer->last_name }}</h2>
        <dl class="space-y-2 text-sm">
            <div class="flex justify-between"><dt class="text-slate-400">Correo</dt><dd class="text-slate-700">{{ $customer->user?->email }}</dd></div>
            <div class="flex justify-between"><dt class="text-slate-400">Teléfono</dt><dd class="text-slate-700">{{ $customer->phone ?? '—' }}</dd></div>
            <div class="flex justify-between"><dt class="text-slate-400">Ciudad</dt><dd class="text-slate-700">{{ $customer->city ?? '—' }}</dd></div>
            <div class="flex justify-between"><dt class="text-slate-400">Verificación</dt><dd class="text-slate-700">{{ ucfirst($customer->verification_status->value) }}</dd></div>
            <div class="flex justify-between"><dt class="text-slate-400">Licencia aprobada</dt><dd class="text-slate-700">{{ $customer->hasApprovedLicense() ? 'Sí' : 'No' }}</dd></div>
        </dl>
    </div>

    <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-6">
        <h2 class="font-semibold text-slate-800 mb-4">Documentos</h2>
        <ul class="space-y-2 text-sm">
            @forelse ($customer->documents as $doc)
                <li class="flex justify-between">
                    <span class="text-slate-600 capitalize">{{ str_replace('_',' ', $doc->type->value) }}</span>
                    <span class="text-slate-500">{{ ucfirst($doc->status->value) }}</span>
                </li>
            @empty
                <li class="text-slate-400">Sin documentos.</li>
            @endforelse
        </ul>
    </div>

    <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-6">
        <h2 class="font-semibold text-slate-800 mb-4">Reservas ({{ $customer->reservations->count() }})</h2>
        <ul class="space-y-2 text-sm">
            @forelse ($customer->reservations->take(6) as $r)
                <li class="flex justify-between">
                    <a href="{{ route('admin.reservations.show', $r) }}" class="text-primary hover:underline">{{ $r->reservation_number }}</a>
                    <x-admin.status-badge :status="$r->reservation_status->value" />
                </li>
            @empty
                <li class="text-slate-400">Sin reservas.</li>
            @endforelse
        </ul>
    </div>
</div>

<a href="{{ route('admin.customers.index') }}" class="inline-block mt-6 text-sm text-slate-500 hover:text-slate-700">← Volver a clientes</a>
@endsection
