@props(['status'])

@php
    $map = [
        // reserva
        'pending_payment' => ['Pendiente pago', 'bg-amber-50 text-amber-600'],
        'paid' => ['Pagada', 'bg-emerald-50 text-emerald-600'],
        'confirmed' => ['Confirmada', 'bg-emerald-50 text-emerald-600'],
        'active' => ['Activa', 'bg-primary/10 text-primary'],
        'completed' => ['Completada', 'bg-slate-100 text-slate-600'],
        'cancelled' => ['Cancelada', 'bg-red-50 text-red-600'],
        'refunded' => ['Reembolsada', 'bg-red-50 text-red-600'],
        'expired' => ['Expirada', 'bg-slate-100 text-slate-500'],
        'no_show' => ['No-show', 'bg-red-50 text-red-600'],
        // vehículo
        'available' => ['Disponible', 'bg-emerald-50 text-emerald-600'],
        'maintenance' => ['Mantenimiento', 'bg-amber-50 text-amber-600'],
        'blocked' => ['Bloqueado', 'bg-red-50 text-red-600'],
        'out_of_service' => ['Fuera de servicio', 'bg-slate-100 text-slate-500'],
    ];
    [$label, $tone] = $map[$status] ?? [ucfirst(str_replace('_', ' ', $status)), 'bg-slate-100 text-slate-600'];
@endphp

<span {{ $attributes->merge(['class' => "inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium $tone"]) }}>
    {{ $label }}
</span>
