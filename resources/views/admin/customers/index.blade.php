@extends('admin.layouts.app')
@section('title', 'Clientes')

@section('content')
<div class="bg-white rounded-2xl border border-slate-100 shadow-sm overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="text-left text-slate-400 bg-slate-50/60">
                <tr>
                    <th class="px-6 py-3 font-medium">Cliente</th>
                    <th class="px-6 py-3 font-medium">Correo</th>
                    <th class="px-6 py-3 font-medium">Verificación</th>
                    <th class="px-6 py-3 font-medium text-right">Reservas</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-50">
                @forelse ($customers as $c)
                    <tr class="hover:bg-slate-50/50 cursor-pointer" onclick="window.location='{{ route('admin.customers.show', $c) }}'">
                        <td class="px-6 py-4 font-medium text-slate-700">{{ $c->first_name }} {{ $c->last_name }}</td>
                        <td class="px-6 py-4 text-slate-500">{{ $c->user?->email }}</td>
                        <td class="px-6 py-4">
                            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium
                                {{ $c->verification_status->value === 'verified' ? 'bg-emerald-50 text-emerald-600' : ($c->verification_status->value === 'rejected' ? 'bg-red-50 text-red-600' : 'bg-slate-100 text-slate-500') }}">
                                {{ ucfirst($c->verification_status->value) }}
                            </span>
                        </td>
                        <td class="px-6 py-4 text-right text-slate-700">{{ $c->reservations_count }}</td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="px-6 py-10 text-center text-slate-400">No hay clientes.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="mt-6">{{ $customers->links() }}</div>
@endsection
