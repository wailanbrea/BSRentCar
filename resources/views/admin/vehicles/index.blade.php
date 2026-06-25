@extends('admin.layouts.app')
@section('title', 'Vehículos')

@section('content')
<div class="flex items-center justify-between mb-6">
    <p class="text-sm text-slate-500">{{ $vehicles->total() }} vehículos en la flota</p>
    <a href="{{ route('admin.vehicles.create') }}"
       class="inline-flex items-center gap-2 rounded-full bg-primary hover:bg-primary-dark text-white text-sm font-medium px-5 py-2.5 transition">
        + Nuevo vehículo
    </a>
</div>

<div class="bg-white rounded-2xl border border-slate-100 shadow-sm overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="text-left text-slate-400 bg-slate-50/60">
                <tr>
                    <th class="px-6 py-3 font-medium">Vehículo</th>
                    <th class="px-6 py-3 font-medium">Placa</th>
                    <th class="px-6 py-3 font-medium">Categoría</th>
                    <th class="px-6 py-3 font-medium">Precio/día</th>
                    <th class="px-6 py-3 font-medium">Estado</th>
                    <th class="px-6 py-3 font-medium text-right">Acciones</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-50">
                @forelse ($vehicles as $vehicle)
                    <tr class="hover:bg-slate-50/50">
                        <td class="px-6 py-4 font-medium text-slate-700">{{ $vehicle->name }}</td>
                        <td class="px-6 py-4 text-slate-500">{{ $vehicle->plate }}</td>
                        <td class="px-6 py-4 text-slate-500 capitalize">{{ $vehicle->category->value }}</td>
                        <td class="px-6 py-4 text-slate-700">{{ number_format((float) $vehicle->daily_price, 2) }} {{ $vehicle->currency }}</td>
                        <td class="px-6 py-4"><x-admin.status-badge :status="$vehicle->status->value" /></td>
                        <td class="px-6 py-4 text-right">
                            <a href="{{ route('admin.vehicles.edit', $vehicle) }}" class="text-primary hover:underline">Editar</a>
                            <form action="{{ route('admin.vehicles.destroy', $vehicle) }}" method="POST" class="inline"
                                  onsubmit="return confirm('¿Archivar este vehículo?')">
                                @csrf @method('DELETE')
                                <button class="ml-3 text-red-500 hover:underline">Archivar</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="px-6 py-10 text-center text-slate-400">No hay vehículos. Crea el primero.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="mt-6">{{ $vehicles->links() }}</div>
@endsection
