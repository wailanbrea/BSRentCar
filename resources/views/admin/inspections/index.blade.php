@extends('admin.layouts.app')
@section('title', 'Inspecciones')

@section('content')
<div class="bg-white rounded-2xl border border-slate-100 shadow-sm overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="text-left text-slate-400 bg-slate-50/60">
                <tr>
                    <th class="px-6 py-3 font-medium">Reserva</th>
                    <th class="px-6 py-3 font-medium">Vehículo</th>
                    <th class="px-6 py-3 font-medium">Tipo</th>
                    <th class="px-6 py-3 font-medium">Combustible</th>
                    <th class="px-6 py-3 font-medium">Km</th>
                    <th class="px-6 py-3 font-medium text-right">Fotos</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-50">
                @forelse ($inspections as $i)
                    <tr class="hover:bg-slate-50/50">
                        <td class="px-6 py-4">
                            @if ($i->reservation)
                                <a href="{{ route('admin.reservations.show', $i->reservation) }}" class="text-primary hover:underline">{{ $i->reservation->reservation_number }}</a>
                            @else <span class="text-slate-400">—</span> @endif
                        </td>
                        <td class="px-6 py-4 text-slate-500">{{ $i->vehicle?->name ?? '—' }}</td>
                        <td class="px-6 py-4">
                            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium {{ $i->type->value === 'initial' ? 'bg-primary/10 text-primary' : 'bg-slate-100 text-slate-600' }}">
                                {{ $i->type->value === 'initial' ? 'Salida' : 'Retorno' }}
                            </span>
                        </td>
                        <td class="px-6 py-4 text-slate-500">{{ $i->fuel_level ?? '—' }}</td>
                        <td class="px-6 py-4 text-slate-500">{{ $i->mileage ? number_format($i->mileage) : '—' }}</td>
                        <td class="px-6 py-4 text-right text-slate-500">{{ $i->photos_count }}</td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="px-6 py-10 text-center text-slate-400">No hay inspecciones registradas.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
<div class="mt-6">{{ $inspections->links() }}</div>
@endsection
