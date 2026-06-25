@extends('admin.layouts.app')
@section('title', 'Reservas')

@section('content')
<div class="flex flex-wrap items-center gap-2 mb-6">
    @php $filters = ['' => 'Todas', 'pending_payment' => 'Pendientes', 'paid' => 'Pagadas', 'confirmed' => 'Confirmadas', 'active' => 'Activas', 'completed' => 'Completadas', 'cancelled' => 'Canceladas']; @endphp
    @foreach ($filters as $key => $label)
        <a href="{{ route('admin.reservations.index', array_filter(['status' => $key])) }}"
           class="px-3 py-1.5 rounded-full text-sm {{ $status === $key ? 'bg-primary text-white' : 'bg-white border border-slate-200 text-slate-600 hover:bg-slate-50' }}">
            {{ $label }}
        </a>
    @endforeach
</div>

<div class="bg-white rounded-2xl border border-slate-100 shadow-sm overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="text-left text-slate-400 bg-slate-50/60">
                <tr>
                    <th class="px-6 py-3 font-medium">Número</th>
                    <th class="px-6 py-3 font-medium">Cliente</th>
                    <th class="px-6 py-3 font-medium">Vehículo</th>
                    <th class="px-6 py-3 font-medium">Fechas</th>
                    <th class="px-6 py-3 font-medium">Estado</th>
                    <th class="px-6 py-3 font-medium text-right">Total</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-50">
                @forelse ($reservations as $r)
                    <tr class="hover:bg-slate-50/50 cursor-pointer" onclick="window.location='{{ route('admin.reservations.show', $r) }}'">
                        <td class="px-6 py-4 font-medium text-primary">{{ $r->reservation_number }}</td>
                        <td class="px-6 py-4 text-slate-600">{{ $r->customer?->first_name }} {{ $r->customer?->last_name }}</td>
                        <td class="px-6 py-4 text-slate-500">{{ $r->vehicle?->name ?? '—' }}</td>
                        <td class="px-6 py-4 text-slate-500">{{ $r->start_datetime->format('d/m/y') }} → {{ $r->end_datetime->format('d/m/y') }}</td>
                        <td class="px-6 py-4"><x-admin.status-badge :status="$r->reservation_status->value" /></td>
                        <td class="px-6 py-4 text-right text-slate-700">{{ number_format((float) $r->total_amount, 2) }} {{ $r->currency }}</td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="px-6 py-10 text-center text-slate-400">No hay reservas.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="mt-6">{{ $reservations->links() }}</div>
@endsection
