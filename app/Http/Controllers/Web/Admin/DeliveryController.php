<?php

namespace App\Http\Controllers\Web\Admin;

use App\Http\Controllers\Controller;
use App\Models\DeliveryRequest;
use App\Models\User;
use App\Services\DeliveryService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DeliveryController extends Controller
{
    public function __construct(private readonly DeliveryService $deliveries)
    {
    }

    public function index(): View
    {
        $requests = DeliveryRequest::with(['reservation', 'driver'])
            ->latest()
            ->paginate(20);

        $drivers = User::role('driver')->orderBy('name')->get();

        return view('admin.deliveries.index', compact('requests', 'drivers'));
    }

    public function assign(Request $request, DeliveryRequest $deliveryRequest): RedirectResponse
    {
        $data = $request->validate(['driver_id' => ['required', 'exists:users,id']]);
        $driver = User::findOrFail($data['driver_id']);

        try {
            $this->deliveries->assignDriver($deliveryRequest, $driver);
        } catch (\InvalidArgumentException $e) {
            return back()->withErrors(['driver' => $e->getMessage()]);
        }

        return back()->with('status', 'Conductor asignado.');
    }

    public function updateStatus(Request $request, DeliveryRequest $deliveryRequest): RedirectResponse
    {
        $data = $request->validate([
            'status' => ['required', 'in:requested,assigned,in_transit,delivered,returned,cancelled'],
        ]);

        $this->deliveries->updateStatus($deliveryRequest, $data['status']);

        return back()->with('status', 'Estado de entrega actualizado.');
    }
}
