@extends('admin.layouts.app')
@section('title', 'Contratos')

@section('content')
@if ($errors->any())
    <div class="mb-4 rounded-xl bg-red-50 border border-red-200 text-red-600 px-4 py-3 text-sm">{{ $errors->first() }}</div>
@endif

<div class="bg-white rounded-2xl border border-slate-100 shadow-sm overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="text-left text-slate-400 bg-slate-50/60">
                <tr>
                    <th class="px-6 py-3 font-medium">Número</th>
                    <th class="px-6 py-3 font-medium">Reserva</th>
                    <th class="px-6 py-3 font-medium">Estado</th>
                    <th class="px-6 py-3 font-medium">Fecha</th>
                    <th class="px-6 py-3 font-medium text-right">PDF</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-50">
                @forelse ($contracts as $c)
                    <tr class="hover:bg-slate-50/50">
                        <td class="px-6 py-4 font-medium text-slate-700">{{ $c->number }}</td>
                        <td class="px-6 py-4">
                            @if ($c->reservation)
                                <a href="{{ route('admin.reservations.show', $c->reservation) }}" class="text-primary hover:underline">{{ $c->reservation->reservation_number }}</a>
                            @else <span class="text-slate-400">—</span> @endif
                        </td>
                        <td class="px-6 py-4 text-slate-500 capitalize">{{ $c->status->value }}</td>
                        <td class="px-6 py-4 text-slate-500">{{ $c->created_at->format('d/m/y') }}</td>
                        <td class="px-6 py-4 text-right">
                            <a href="{{ route('admin.contracts.download', $c) }}" class="text-primary hover:underline">Descargar</a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="px-6 py-10 text-center text-slate-400">No hay contratos generados.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
<div class="mt-6">{{ $contracts->links() }}</div>
@endsection
