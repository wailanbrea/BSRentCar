@extends('admin.layouts.app')
@section('title', 'Editar vehículo')

@section('content')
<div class="max-w-3xl space-y-6">
    <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-6 sm:p-8">
        <form method="POST" action="{{ route('admin.vehicles.update', $vehicle) }}">
            @csrf @method('PUT')
            @include('admin.vehicles._form', ['submitLabel' => 'Guardar cambios'])
        </form>
    </div>

    {{-- Fotos --}}
    <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-6 sm:p-8">
        <h2 class="font-semibold text-slate-800 mb-4">Fotos</h2>

        <form method="POST" action="{{ route('admin.vehicles.images.upload', $vehicle) }}" enctype="multipart/form-data"
              class="flex items-center gap-3 mb-5">
            @csrf
            <input type="file" name="image" accept="image/*" required
                   class="text-sm text-slate-600 file:mr-3 file:rounded-full file:border-0 file:bg-primary file:text-white file:px-4 file:py-2 file:text-sm">
            <button class="rounded-full bg-slate-800 hover:bg-slate-900 text-white text-sm px-4 py-2">Subir</button>
        </form>

        @if ($vehicle->images->isEmpty())
            <p class="text-sm text-slate-400">Aún no hay fotos.</p>
        @else
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                @foreach ($vehicle->images as $img)
                    <div class="relative group">
                        <img src="{{ asset('storage/' . $img->path) }}"
                             alt="" class="w-full h-24 object-cover rounded-xl border border-slate-100">
                        @if ($img->is_primary)
                            <span class="absolute top-1 left-1 bg-primary text-white text-[10px] px-1.5 py-0.5 rounded">Principal</span>
                        @endif
                        <div class="absolute inset-x-0 bottom-0 flex justify-between p-1 opacity-0 group-hover:opacity-100 transition">
                            @unless ($img->is_primary)
                                <form method="POST" action="{{ route('admin.vehicles.images.primary', [$vehicle, $img]) }}">
                                    @csrf
                                    <button class="text-[10px] bg-white/90 rounded px-1.5 py-0.5 text-primary">Principal</button>
                                </form>
                            @else <span></span> @endunless
                            <form method="POST" action="{{ route('admin.vehicles.images.delete', [$vehicle, $img]) }}"
                                  onsubmit="return confirm('¿Eliminar foto?')">
                                @csrf @method('DELETE')
                                <button class="text-[10px] bg-white/90 rounded px-1.5 py-0.5 text-red-500">Eliminar</button>
                            </form>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</div>
@endsection
