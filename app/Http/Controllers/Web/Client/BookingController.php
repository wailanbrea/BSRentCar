<?php

namespace App\Http\Controllers\Web\Client;

use App\Exceptions\CustomerNotEligibleException;
use App\Exceptions\VehicleNotAvailableException;
use App\Http\Controllers\Controller;
use App\Services\CustomerService;
use App\Services\ReservationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class BookingController extends Controller
{
    public function __construct(
        private readonly ReservationService $reservations,
        private readonly CustomerService $customers,
    ) {
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'vehicle_id' => ['required', 'integer', 'exists:vehicles,id'],
            'start_datetime' => ['required', 'date', 'after:now'],
            'end_datetime' => ['required', 'date', 'after:start_datetime'],
            'pickup_type' => ['nullable', 'in:pickup_point,home,office,airport,hotel,custom'],
            'pickup_address' => ['required_if:pickup_type,home,airport,hotel', 'nullable', 'string', 'max:255'],
        ]);

        $customer = $this->customers->createForUser($request->user());

        try {
            $reservation = $this->reservations->createForCustomer($customer, $data);
        } catch (CustomerNotEligibleException $e) {
            $reasons = collect($e->render()->getData(true)['reasons'] ?? []);
            $msg = $reasons->contains('license_not_approved')
                ? 'Necesitas una licencia de conducir aprobada para reservar. Súbela en tu perfil.'
                : 'No cumples los requisitos para reservar (edad mínima 18 años).';

            return back()->withErrors(['booking' => $msg]);
        } catch (VehicleNotAvailableException $e) {
            return back()->withErrors(['booking' => 'El vehículo no está disponible para esas fechas.']);
        }

        return redirect()
            ->route('account.reservations.show', $reservation)
            ->with('status', 'Reserva creada. Continúa con el pago para confirmarla.');
    }
}
