<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Reservation\StoreReservationRequest;
use App\Http\Resources\ReservationResource;
use App\Models\Reservation;
use App\Services\CustomerService;
use App\Services\ReservationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Reservas del cliente. Contratos: docs/06_API_CONTRACTS.md (Reservations).
 */
class ReservationController extends Controller
{
    public function __construct(
        private readonly ReservationService $reservations,
        private readonly CustomerService $customers,
    ) {
    }

    public function index(Request $request): \Illuminate\Http\Resources\Json\AnonymousResourceCollection
    {
        $customer = $this->customers->createForUser($request->user());

        return ReservationResource::collection(
            $customer->reservations()->with('vehicle')->latest()->paginate(10)
        );
    }

    public function store(StoreReservationRequest $request): JsonResponse
    {
        $customer = $this->customers->createForUser($request->user());

        $reservation = $this->reservations->createForCustomer($customer, $request->validated());

        return (new ReservationResource($reservation->load('vehicle')))
            ->response()
            ->setStatusCode(201);
    }

    public function show(Reservation $reservation): ReservationResource
    {
        $this->authorize('view', $reservation);

        return new ReservationResource($reservation->load('vehicle'));
    }

    public function cancel(Request $request, Reservation $reservation): ReservationResource
    {
        $this->authorize('cancel', $reservation);

        $reservation = $this->reservations->cancel(
            $reservation,
            $request->user(),
            $request->string('reason')->toString() ?: null,
        );

        return new ReservationResource($reservation);
    }
}
