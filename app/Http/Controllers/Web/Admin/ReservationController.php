<?php

namespace App\Http\Controllers\Web\Admin;

use App\Exceptions\VehicleNotAvailableException;
use App\Http\Controllers\Controller;
use App\Models\Reservation;
use App\Services\ReservationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ReservationController extends Controller
{
    public function __construct(private readonly ReservationService $reservations)
    {
    }

    public function index(Request $request): View
    {
        $status = $request->string('status')->toString();

        $query = Reservation::with(['vehicle', 'customer'])->latest();
        if ($status) {
            $query->where('reservation_status', $status);
        }

        $reservations = $query->paginate(15)->withQueryString();

        return view('admin.reservations.index', compact('reservations', 'status'));
    }

    public function show(Reservation $reservation): View
    {
        $reservation->load(['vehicle', 'customer.user', 'statusLogs' => fn ($q) => $q->latest()]);

        return view('admin.reservations.show', compact('reservation'));
    }

    public function markPaid(Reservation $reservation): RedirectResponse
    {
        try {
            $this->reservations->markAsPaid($reservation, request()->user());
        } catch (VehicleNotAvailableException) {
            return back()->withErrors(['estado' => 'No se pudo: el vehículo ya no está disponible para esas fechas.']);
        } catch (\InvalidArgumentException $e) {
            return back()->withErrors(['estado' => 'La reserva no está en un estado que permita confirmar el pago.']);
        }

        return back()->with('status', 'Pago confirmado. Reserva marcada como pagada.');
    }

    public function confirm(Reservation $reservation): RedirectResponse
    {
        try {
            $this->reservations->confirm($reservation, request()->user());
        } catch (\InvalidArgumentException $e) {
            return back()->withErrors(['estado' => 'La reserva no puede confirmarse desde su estado actual.']);
        }

        return back()->with('status', 'Reserva confirmada.');
    }

    public function cancel(Request $request, Reservation $reservation): RedirectResponse
    {
        try {
            $this->reservations->cancel($reservation, $request->user(), $request->string('reason')->toString() ?: null);
        } catch (\InvalidArgumentException $e) {
            return back()->withErrors(['estado' => 'La reserva no puede cancelarse desde su estado actual.']);
        }

        return back()->with('status', 'Reserva cancelada.');
    }
}
