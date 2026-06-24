<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\ReservationResource;
use App\Models\Reservation;
use App\Services\ReservationService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * Gestión administrativa de reservas. Ver docs/08_ADMIN_PANEL.md (Reservas).
 */
class ReservationController extends Controller
{
    public function __construct(private readonly ReservationService $reservations)
    {
    }

    public function index(): AnonymousResourceCollection
    {
        return ReservationResource::collection(
            Reservation::with('vehicle')->latest()->paginate(20)
        );
    }

    public function show(Reservation $reservation): ReservationResource
    {
        return new ReservationResource($reservation->load(['vehicle', 'statusLogs']));
    }

    /**
     * Confirma un pago manual (efectivo/transferencia, BR-P10) de forma ATÓMICA.
     * Aquí ocurre la revalidación anti-doble-reserva (lockForUpdate). Pagos por
     * pasarela (Stripe/PayPal) usarán el mismo markAsPaid desde sus webhooks (Fase 6/7).
     */
    public function markPaid(Request $request, Reservation $reservation): ReservationResource
    {
        $reservation = $this->reservations->markAsPaid($reservation, $request->user());

        return new ReservationResource($reservation->load('vehicle'));
    }

    public function confirm(Request $request, Reservation $reservation): ReservationResource
    {
        $reservation = $this->reservations->confirm($reservation, $request->user());

        return new ReservationResource($reservation->load('vehicle'));
    }
}
