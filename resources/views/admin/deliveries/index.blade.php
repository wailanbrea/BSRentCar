@extends('admin.layouts.app')
@section('title', 'Entregas')

@section('content')
@if ($errors->any())
    <div class="mb-4 rounded-xl bg-red-50 border border-red-200 text-red-600 px-4 py-3 text-sm">{{ $errors->first() }}</div>
@endif

<div class="bg-white rounded-2xl border border-slate-100 shadow-sm overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="text-left text-slate-400 bg-slate-50/60">
                <tr>
                    <th class="px-6 py-3 font-medium">Reserva</th>
                    <th class="px-6 py-3 font-medium">Dirección</th>
                    <th class="px-6 py-3 font-medium">Tipo</th>
                    <th class="px-6 py-3 font-medium">Conductor</th>
                    <th class="px-6 py-3 font-medium">Estado</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-50">
                @forelse ($requests as $r)
                    <tr class="hover:bg-slate-50/50 align-top">
                        <td class="px-6 py-4">
                            @if ($r->reservation)
                                <a href="{{ route('admin.reservations.show', $r->reservation) }}" class="text-primary hover:underline">{{ $r->reservation->reservation_number }}</a>
                            @else <span class="text-slate-400">—</span> @endif
                        </td>
                        <td class="px-6 py-4 text-slate-500 capitalize">{{ $r->direction }}</td>
                        <td class="px-6 py-4 text-slate-500 capitalize">{{ str_replace('_',' ', $r->type) }}</td>
                        <td class="px-6 py-4">
                            @if ($r->driver)
                                <span class="text-slate-700">{{ $r->driver->name }}</span>
                            @else
                                <form method="POST" action="{{ route('admin.deliveries.assign', $r) }}" class="flex items-center gap-2">
                                    @csrf
                                    <select name="driver_id" class="rounded-lg border-slate-300 text-xs px-2 py-1.5 focus:border-primary focus:ring-primary">
                                        <option value="">Asignar…</option>
                                        @foreach ($drivers as $driver)
                                            <option value="{{ $driver->id }}">{{ $driver->name }}</option>
                                        @endforeach
                                    </select>
                                    <button class="text-primary text-xs hover:underline">OK</button>
                                </form>
                            @endif
                        </td>
                        <td class="px-6 py-4">
                            <form method="POST" action="{{ route('admin.deliveries.status', $r) }}" class="flex items-center gap-2">
                                @csrf
                                <select name="status" onchange="this.form.submit()"
                                        class="rounded-lg border-slate-300 text-xs px-2 py-1.5 focus:border-primary focus:ring-primary capitalize">
                                    @foreach (['requested','assigned','in_transit','delivered','returned','cancelled'] as $st)
                                        <option value="{{ $st }}" @selected($r->status->value === $st)>{{ str_replace('_',' ',$st) }}</option>
                                    @endforeach
                                </select>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="px-6 py-10 text-center text-slate-400">No hay solicitudes de entrega.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
<div class="mt-6">{{ $requests->links() }}</div>
@endsection
