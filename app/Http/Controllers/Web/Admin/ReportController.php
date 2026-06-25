<?php

namespace App\Http\Controllers\Web\Admin;

use App\Http\Controllers\Controller;
use App\Services\ReportService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ReportController extends Controller
{
    public function __construct(private readonly ReportService $reports)
    {
    }

    public function index(Request $request): View
    {
        $start = $request->date('start')?->toDateString() ?? now()->startOfMonth()->toDateString();
        $end = $request->date('end')?->toDateString() ?? now()->endOfMonth()->toDateString();

        $revenue = $this->reports->getRevenueReport($start, $end);
        $occupancy = $this->reports->getOccupancyReport($start, $end);
        $topVehicles = $this->reports->getTopVehiclesReport($start, $end);
        $stats = $this->reports->getReservationStatsReport($start, $end);

        return view('admin.reports.index', compact('revenue', 'occupancy', 'topVehicles', 'stats', 'start', 'end'));
    }
}
