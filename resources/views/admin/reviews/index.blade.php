@extends('admin.layouts.app')
@section('title', 'Calificaciones')

@section('content')
<div class="space-y-3">
    @forelse ($reviews as $review)
        <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-5">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <div class="flex items-center gap-2 mb-1">
                        <span class="text-amber-500">{!! str_repeat('★', (int) $review->rating_overall) !!}{!! str_repeat('☆', 5 - (int) $review->rating_overall) !!}</span>
                        <span class="text-sm text-slate-400">por {{ $review->customer?->first_name ?? 'Cliente' }} · {{ $review->vehicle?->name ?? 'Vehículo' }}</span>
                    </div>
                    <p class="text-sm text-slate-600">{{ $review->comment ?: 'Sin comentario.' }}</p>
                    <p class="text-xs text-slate-400 mt-1">{{ $review->created_at->format('d/m/Y') }}</p>
                </div>
                <div class="flex flex-col items-end gap-2 shrink-0">
                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium
                        {{ $review->status->value === 'visible' ? 'bg-emerald-50 text-emerald-600' : 'bg-slate-200 text-slate-600' }}">
                        {{ ucfirst($review->status->value) }}
                    </span>
                    <form method="POST" action="{{ route('admin.reviews.moderate', $review) }}">
                        @csrf
                        @if ($review->status->value === 'visible')
                            <input type="hidden" name="status" value="hidden">
                            <button class="text-xs text-red-500 hover:underline">Ocultar</button>
                        @else
                            <input type="hidden" name="status" value="visible">
                            <button class="text-xs text-emerald-600 hover:underline">Mostrar</button>
                        @endif
                    </form>
                </div>
            </div>
        </div>
    @empty
        <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-10 text-center text-slate-400">No hay calificaciones.</div>
    @endforelse
</div>
<div class="mt-6">{{ $reviews->links() }}</div>
@endsection
