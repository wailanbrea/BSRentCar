@extends('admin.layouts.app')
@section('title', 'Depósitos')

@section('content')
<div class="bg-white rounded-2xl border border-slate-100 shadow-sm overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="text-left text-slate-400 bg-slate-50/60">
                <tr>
                    <th class="px-6 py-3 font-medium">Reserva</th>
                    <th class="px-6 py-3 font-medium">Tipo</th>
                    <th class="px-6 py-3 font-medium">Estado</th>
                    <th class="px-6 py-3 font-medium">Vence</th>
                    <th class="px-6 py-3 font-medium text-right">Monto</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-50">
                @forelse ($deposits as $d)
                    <tr class="hover:bg-slate-50/50">
                        <td class="px-6 py-4">
                            @if ($d->reservation)
                                <a href="{{ route('admin.reservations.show', $d->reservation) }}" class="text-primary hover:underline">{{ $d->reservation->reservation_number }}</a>
                            @else <span class="text-slate-400">—</span> @endif
                        </td>
                        <td class="px-6 py-4 text-slate-500 capitalize">{{ str_replace('_',' ', $d->type->value) }}</td>
                        <td class="px-6 py-4">
                            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium
                                {{ $d->status->value === 'authorized' ? 'bg-amber-50 text-amber-600' : ($d->status->value === 'captured' ? 'bg-emerald-50 text-emerald-600' : 'bg-slate-100 text-slate-500') }}">
                                {{ ucfirst($d->status->value) }}
                            </span>
                        </td>
                        <td class="px-6 py-4 text-slate-500">{{ $d->expires_at?->format('d/m/y') ?? '—' }}</td>
                        <td class="px-6 py-4 text-right font-medium text-slate-700">{{ number_format((float) $d->amount, 2) }} {{ $d->currency }}</td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="px-6 py-10 text-center text-slate-400">No hay depósitos registrados.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
<div class="mt-6">{{ $deposits->links() }}</div>
@endsection
