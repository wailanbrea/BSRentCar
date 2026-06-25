<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\ReportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminReportController extends Controller
{
    public function __construct(protected readonly ReportService $reportService)
    {
    }

    public function revenue(Request $request): JsonResponse
    {
        $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
        ]);

        $report = $this->reportService->getRevenueReport(
            $request->input('start_date'),
            $request->input('end_date')
        );

        return response()->json(['data' => $report]);
    }

    public function occupancy(Request $request): JsonResponse
    {
        $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
        ]);

        $report = $this->reportService->getOccupancyReport(
            $request->input('start_date'),
            $request->input('end_date')
        );

        return response()->json(['data' => $report]);
    }

    public function topVehicles(Request $request): JsonResponse
    {
        $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'limit' => 'nullable|integer|min:1|max:50',
        ]);

        $report = $this->reportService->getTopVehiclesReport(
            $request->input('start_date'),
            $request->input('end_date'),
            $request->input('limit', 5)
        );

        return response()->json(['data' => $report]);
    }

    public function stats(Request $request): JsonResponse
    {
        $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
        ]);

        $report = $this->reportService->getReservationStatsReport(
            $request->input('start_date'),
            $request->input('end_date')
        );

        return response()->json(['data' => $report]);
    }
}
