@extends('layouts.public')
@section('title', 'Wallet')

@section('content')
@include('client.account.partials.header', ['title' => 'Mi wallet'])

<section class="py-10 bg-slate-50 min-h-[40vh]">
    <div class="max-w-6xl mx-auto px-4 sm:px-6">
        <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-6 mb-6">
            <p class="text-sm text-slate-400">Saldo disponible</p>
            <p class="text-3xl font-bold text-slate-800 mt-1">{{ number_format((float) $wallet->balance, 2) }} {{ $wallet->currency }}</p>
        </div>

        <div class="bg-white rounded-2xl border border-slate-100 shadow-sm overflow-hidden">
            <table class="w-full text-sm">
                <thead class="text-left text-slate-400 bg-slate-50/60">
                    <tr>
                        <th class="px-6 py-3 font-medium">Fecha</th>
                        <th class="px-6 py-3 font-medium">Tipo</th>
                        <th class="px-6 py-3 font-medium">Descripción</th>
                        <th class="px-6 py-3 font-medium text-right">Monto</th>
                        <th class="px-6 py-3 font-medium text-right">Saldo</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-50">
                    @forelse ($transactions as $t)
                        <tr>
                            <td class="px-6 py-4 text-slate-500">{{ $t->created_at->format('d/m/y H:i') }}</td>
                            <td class="px-6 py-4 text-slate-600 capitalize">{{ str_replace('_',' ', $t->type) }}</td>
                            <td class="px-6 py-4 text-slate-500">{{ $t->description }}</td>
                            <td class="px-6 py-4 text-right text-slate-700">{{ number_format((float) $t->amount, 2) }}</td>
                            <td class="px-6 py-4 text-right text-slate-400">{{ number_format((float) $t->balance_after, 2) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="px-6 py-10 text-center text-slate-400">Sin movimientos.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-6">{{ $transactions->links() }}</div>
    </div>
</section>
@endsection
