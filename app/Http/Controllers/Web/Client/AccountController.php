<?php

namespace App\Http\Controllers\Web\Client;

use App\Http\Controllers\Controller;
use App\Http\Requests\Customer\StoreDocumentRequest;
use App\Http\Requests\Customer\UpdateProfileRequest;
use App\Models\Reservation;
use App\Services\CustomerService;
use App\Services\WalletService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AccountController extends Controller
{
    public function __construct(
        private readonly CustomerService $customers,
        private readonly WalletService $wallets,
    ) {
    }

    public function dashboard(Request $request): View
    {
        $customer = $this->customers->createForUser($request->user());
        $wallet = $this->wallets->getWallet($customer);

        $nextReservation = $customer->reservations()
            ->whereIn('reservation_status', ['paid', 'confirmed', 'in_preparation', 'contract_signed', 'delivery_assigned', 'delivered', 'active'])
            ->orderBy('start_datetime')
            ->with('vehicle')
            ->first();

        return view('client.account.dashboard', compact('customer', 'wallet', 'nextReservation'));
    }

    public function reservations(Request $request): View
    {
        $customer = $this->customers->createForUser($request->user());
        $reservations = $customer->reservations()->with('vehicle')->latest()->paginate(10);

        return view('client.account.reservations', compact('reservations'));
    }

    public function showReservation(Request $request, Reservation $reservation): View
    {
        $customer = $this->customers->createForUser($request->user());
        abort_unless($reservation->customer_id === $customer->id, 403);

        $reservation->load(['vehicle', 'statusLogs' => fn ($q) => $q->latest()]);

        return view('client.account.reservation', compact('reservation'));
    }

    public function wallet(Request $request): View
    {
        $customer = $this->customers->createForUser($request->user());
        $wallet = $this->wallets->getWallet($customer);
        $transactions = $wallet->transactions()->latest()->paginate(15);

        return view('client.account.wallet', compact('wallet', 'transactions'));
    }

    public function profile(Request $request): View
    {
        $customer = $this->customers->createForUser($request->user());
        $customer->load('documents');

        return view('client.account.profile', compact('customer'));
    }

    public function updateProfile(UpdateProfileRequest $request): RedirectResponse
    {
        $customer = $this->customers->createForUser($request->user());
        $this->customers->updateProfile($customer, $request->validated());

        return back()->with('status', 'Perfil actualizado.');
    }

    public function uploadDocument(StoreDocumentRequest $request): RedirectResponse
    {
        $customer = $this->customers->createForUser($request->user());
        $this->customers->storeDocument($customer, $request->string('type'), $request->file('file'));

        return back()->with('status', 'Documento subido. Quedará pendiente de verificación.');
    }
}
