@extends('admin.layouts.app')
@section('title', 'Pagos')

@section('content')
<div class="bg-white rounded-2xl border border-slate-100 shadow-sm overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="text-left text-slate-400 bg-slate-50/60">
                <tr>
                    <th class="px-6 py-3 font-medium">Fecha</th>
                    <th class="px-6 py-3 font-medium">Reserva</th>
                    <th class="px-6 py-3 font-medium">Proveedor</th>
                    <th class="px-6 py-3 font-medium">Tipo</th>
                    <th class="px-6 py-3 font-medium">Estado</th>
                    <th class="px-6 py-3 font-medium text-right">Monto</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-50">
                @forelse ($payments as $p)
                    <tr class="hover:bg-slate-50/50">
                        <td class="px-6 py-4 text-slate-500">{{ $p->created_at->format('d/m/y H:i') }}</td>
                        <td class="px-6 py-4">
                            @if ($p->reservation)
                                <a href="{{ route('admin.reservations.show', $p->reservation) }}" class="text-primary hover:underline">{{ $p->reservation->reservation_number }}</a>
                            @else <span class="text-slate-400">—</span> @endif
                        </td>
                        <td class="px-6 py-4 text-slate-600 capitalize">{{ $p->provider }}{{ $p->provider_subtype ? ' ('.$p->provider_subtype.')' : '' }}</td>
                        <td class="px-6 py-4 text-slate-500 capitalize">{{ str_replace('_',' ', $p->payment_type->value) }}</td>
                        <td class="px-6 py-4"><x-admin.status-badge :status="$p->status->value" /></td>
                        <td class="px-6 py-4 text-right font-medium text-slate-700">{{ number_format((float) $p->amount, 2) }} {{ $p->currency }}</td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="px-6 py-10 text-center text-slate-400">No hay pagos registrados.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
<div class="mt-6">{{ $payments->links() }}</div>
@endsection
