<?php

namespace App\Http\Controllers\Web\Admin;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Reservation;
use App\Models\Vehicle;
use App\Services\ReportService;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __construct(private readonly ReportService $reports)
    {
    }

    public function index(): View
    {
        $start = now()->startOfMonth()->toDateString();
        $end = now()->endOfMonth()->toDateString();

        $revenue = $this->reports->getRevenueReport($start, $end);
        $occupancy = $this->reports->getOccupancyReport($start, $end);
        $topVehicles = $this->reports->getTopVehiclesReport($start, $end);

        $kpis = [
            'revenue' => $revenue['total_revenue'],
            'reservations_month' => $revenue['reservation_count'],
            'occupancy_rate' => $occupancy['occupancy_rate'],
            'fleet' => Vehicle::count(),
            'customers' => Customer::count(),
            'pending_payment' => Reservation::where('reservation_status', 'pending_payment')->count(),
        ];

        $recent = Reservation::with('vehicle')->latest()->limit(8)->get();

        return view('admin.dashboard', compact('kpis', 'topVehicles', 'recent'));
    }
}
