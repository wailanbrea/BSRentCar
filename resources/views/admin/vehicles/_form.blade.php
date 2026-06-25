@php $v = $vehicle ?? null; $cur = config('rentcar.currency'); @endphp

@if ($errors->any())
    <div class="mb-5 rounded-xl bg-red-50 border border-red-200 text-red-600 px-4 py-3 text-sm">
        Revisa los campos: {{ $errors->first() }}
    </div>
@endif

<div class="grid grid-cols-1 md:grid-cols-2 gap-5">
    <div class="md:col-span-2">
        <label class="block text-sm font-medium text-slate-700 mb-1">Nombre *</label>
        <input name="name" value="{{ old('name', $v?->name ?? '') }}" required
               class="w-full rounded-xl border-slate-300 focus:border-primary focus:ring-primary px-4 py-2.5">
    </div>

    <div>
        <label class="block text-sm font-medium text-slate-700 mb-1">Marca</label>
        <input name="brand" value="{{ old('brand', $v?->brand ?? '') }}"
               class="w-full rounded-xl border-slate-300 focus:border-primary focus:ring-primary px-4 py-2.5">
    </div>
    <div>
        <label class="block text-sm font-medium text-slate-700 mb-1">Modelo</label>
        <input name="model" value="{{ old('model', $v?->model ?? '') }}"
               class="w-full rounded-xl border-slate-300 focus:border-primary focus:ring-primary px-4 py-2.5">
    </div>

    <div>
        <label class="block text-sm font-medium text-slate-700 mb-1">Año</label>
        <input type="number" name="year" value="{{ old('year', $v?->year ?? '') }}"
               class="w-full rounded-xl border-slate-300 focus:border-primary focus:ring-primary px-4 py-2.5">
    </div>
    <div>
        <label class="block text-sm font-medium text-slate-700 mb-1">Placa *</label>
        <input name="plate" value="{{ old('plate', $v?->plate ?? '') }}" required
               class="w-full rounded-xl border-slate-300 focus:border-primary focus:ring-primary px-4 py-2.5">
    </div>

    <div>
        <label class="block text-sm font-medium text-slate-700 mb-1">Categoría *</label>
        <select name="category" class="w-full rounded-xl border-slate-300 focus:border-primary focus:ring-primary px-4 py-2.5 capitalize">
            @foreach ($categories as $c)
                <option value="{{ $c->value }}" @selected(old('category', $v?->category->value ?? '') === $c->value)>{{ $c->value }}</option>
            @endforeach
        </select>
    </div>
    <div>
        <label class="block text-sm font-medium text-slate-700 mb-1">Transmisión *</label>
        <select name="transmission" class="w-full rounded-xl border-slate-300 focus:border-primary focus:ring-primary px-4 py-2.5 capitalize">
            @foreach ($transmissions as $t)
                <option value="{{ $t->value }}" @selected(old('transmission', $v?->transmission->value ?? '') === $t->value)>{{ $t->value }}</option>
            @endforeach
        </select>
    </div>

    <div>
        <label class="block text-sm font-medium text-slate-700 mb-1">Pasajeros *</label>
        <input type="number" name="seats" value="{{ old('seats', $v?->seats ?? 5) }}" required min="1" max="20"
               class="w-full rounded-xl border-slate-300 focus:border-primary focus:ring-primary px-4 py-2.5">
    </div>
    <div>
        <label class="block text-sm font-medium text-slate-700 mb-1">Puertas</label>
        <input type="number" name="doors" value="{{ old('doors', $v?->doors ?? 4) }}" min="1" max="8"
               class="w-full rounded-xl border-slate-300 focus:border-primary focus:ring-primary px-4 py-2.5">
    </div>

    <div>
        <label class="block text-sm font-medium text-slate-700 mb-1">Precio diario ({{ $cur }}) *</label>
        <input type="number" step="0.01" name="daily_price" value="{{ old('daily_price', $v?->daily_price ?? '') }}" required
               class="w-full rounded-xl border-slate-300 focus:border-primary focus:ring-primary px-4 py-2.5">
    </div>
    <div>
        <label class="block text-sm font-medium text-slate-700 mb-1">Depósito ({{ $cur }})</label>
        <input type="number" step="0.01" name="deposit_amount" value="{{ old('deposit_amount', $v?->deposit_amount ?? '') }}"
               class="w-full rounded-xl border-slate-300 focus:border-primary focus:ring-primary px-4 py-2.5">
    </div>

    <div>
        <label class="block text-sm font-medium text-slate-700 mb-1">Sucursal</label>
        <select name="location_id" class="w-full rounded-xl border-slate-300 focus:border-primary focus:ring-primary px-4 py-2.5">
            <option value="">— Sin asignar —</option>
            @foreach ($locations as $loc)
                <option value="{{ $loc->id }}" @selected((int) old('location_id', $v?->location_id ?? 0) === $loc->id)>{{ $loc->name }}</option>
            @endforeach
        </select>
    </div>
    <div>
        <label class="block text-sm font-medium text-slate-700 mb-1">Estado</label>
        <select name="status" class="w-full rounded-xl border-slate-300 focus:border-primary focus:ring-primary px-4 py-2.5 capitalize">
            @foreach ($statuses as $s)
                <option value="{{ $s->value }}" @selected(old('status', $v?->status->value ?? 'available') === $s->value)>{{ str_replace('_',' ',$s->value) }}</option>
            @endforeach
        </select>
    </div>

    <div class="md:col-span-2">
        <label class="block text-sm font-medium text-slate-700 mb-1">Descripción</label>
        <textarea name="description" rows="3"
                  class="w-full rounded-xl border-slate-300 focus:border-primary focus:ring-primary px-4 py-2.5">{{ old('description', $v?->description ?? '') }}</textarea>
    </div>
</div>

<div class="flex items-center gap-3 mt-6">
    <button type="submit" class="rounded-full bg-primary hover:bg-primary-dark text-white font-medium px-6 py-2.5 transition">
        {{ $submitLabel }}
    </button>
    <a href="{{ route('admin.vehicles.index') }}" class="text-slate-500 hover:text-slate-700 text-sm">Cancelar</a>
</div>
