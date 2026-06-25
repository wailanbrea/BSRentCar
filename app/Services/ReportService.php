<?php

namespace App\Services;

use App\Models\Reservation;
use App\Models\Vehicle;
use App\Enums\PaymentStatus;
use App\Enums\ReservationStatus;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ReportService
{
    /**
     * Reporte de ingresos.
     */
    public function getRevenueReport(string $startDate, string $endDate): array
    {
        $start = Carbon::parse($startDate)->startOfDay();
        $end = Carbon::parse($endDate)->endOfDay();

        $stats = Reservation::where('payment_status', PaymentStatus::Paid)
            ->whereBetween('created_at', [$start, $end])
            ->selectRaw('
                COALESCE(SUM(base_price), 0) as base_revenue,
                COALESCE(SUM(delivery_fee), 0) as delivery_revenue,
                COALESCE(SUM(insurance_fee), 0) as insurance_revenue,
                COALESCE(SUM(tax_amount), 0) as tax_collected,
                COALESCE(SUM(discount_amount), 0) as discount_given,
                COALESCE(SUM(total_amount), 0) as total_revenue,
                COUNT(*) as reservation_count
            ')
            ->first();

        return [
            'start_date' => $startDate,
            'end_date' => $endDate,
            'base_revenue' => (float) $stats->base_revenue,
            'delivery_revenue' => (float) $stats->delivery_revenue,
            'insurance_revenue' => (float) $stats->insurance_revenue,
            'tax_collected' => (float) $stats->tax_collected,
            'discount_given' => (float) $stats->discount_given,
            'total_revenue' => (float) $stats->total_revenue,
            'reservation_count' => (int) $stats->reservation_count,
        ];
    }

    /**
     * Reporte de ocupación de flota.
     * Retorna la tasa general de ocupación y detalles por vehículo.
     */
    public function getOccupancyReport(string $startDate, string $endDate): array
    {
        $start = Carbon::parse($startDate)->startOfDay();
        $end = Carbon::parse($endDate)->startOfDay();
        
        $totalDaysInRange = (int) $start->diffInDays($end) + 1;
        if ($totalDaysInRange <= 0) {
            $totalDaysInRange = 1;
        }

        // Obtener todos los vehículos activos
        $vehicles = Vehicle::whereNull('deleted_at')
            ->where('status', '!=', 'out_of_service')
            ->get();

        $totalVehicles = $vehicles->count();
        if ($totalVehicles === 0) {
            return [
                'start_date' => $startDate,
                'end_date' => $endDate,
                'occupancy_rate' => 0.0,
                'total_vehicles' => 0,
                'total_days' => $totalDaysInRange,
                'vehicles' => [],
            ];
        }

        // Obtener todas las reservaciones en estado active, returned, completed que se solapan con el rango
        $reservations = Reservation::whereIn('reservation_status', [
                ReservationStatus::Active->value,
                ReservationStatus::Returned->value,
                ReservationStatus::Completed->value,
            ])
            ->where('start_datetime', '<=', $end->copy()->endOfDay())
            ->where('end_datetime', '>=', $start)
            ->get();

        $vehicleOccupiedDays = [];
        foreach ($vehicles as $vehicle) {
            $vehicleOccupiedDays[$vehicle->id] = 0;
        }

        foreach ($reservations as $res) {
            if (!isset($vehicleOccupiedDays[$res->vehicle_id])) {
                continue; // vehículo borrado o out_of_service
            }

            // Calcular solape
            $resStart = Carbon::parse($res->start_datetime)->startOfDay();
            $resEnd = Carbon::parse($res->end_datetime)->startOfDay();

            $overlapStart = $resStart->greaterThan($start) ? $resStart : $start;
            $overlapEnd = $resEnd->lessThan($end) ? $resEnd : $end;

            $occupiedDays = (int) $overlapStart->diffInDays($overlapEnd) + 1;
            if ($occupiedDays > 0) {
                $vehicleOccupiedDays[$res->vehicle_id] += $occupiedDays;
            }
        }

        $totalOccupiedDays = array_sum($vehicleOccupiedDays);
        $maxPossibleDays = $totalVehicles * $totalDaysInRange;
        $occupancyRate = ($totalOccupiedDays / $maxPossibleDays) * 100;

        $vehiclesData = [];
        foreach ($vehicles as $vehicle) {
            $occupied = $vehicleOccupiedDays[$vehicle->id];
            $rate = ($occupied / $totalDaysInRange) * 100;
            $vehiclesData[] = [
                'id' => $vehicle->id,
                'name' => $vehicle->name,
                'plate' => $vehicle->plate,
                'occupied_days' => $occupied,
                'occupancy_rate' => round($rate, 2),
            ];
        }

        return [
            'start_date' => $startDate,
            'end_date' => $endDate,
            'occupancy_rate' => round($occupancyRate, 2),
            'total_vehicles' => $totalVehicles,
            'total_days' => $totalDaysInRange,
            'vehicles' => $vehiclesData,
        ];
    }

    /**
     * Top de vehículos por ingresos.
     */
    public function getTopVehiclesReport(string $startDate, string $endDate, int $limit = 5): array
    {
        $start = Carbon::parse($startDate)->startOfDay();
        $end = Carbon::parse($endDate)->endOfDay();

        $topVehicles = Reservation::where('payment_status', PaymentStatus::Paid)
            ->whereBetween('reservations.created_at', [$start, $end])
            ->join('vehicles', 'reservations.vehicle_id', '=', 'vehicles.id')
            ->selectRaw('
                vehicles.id,
                vehicles.name,
                vehicles.plate,
                COALESCE(SUM(reservations.total_amount), 0) as revenue,
                COUNT(*) as reservation_count
            ')
            ->groupBy('vehicles.id', 'vehicles.name', 'vehicles.plate')
            ->orderBy('revenue', 'desc')
            ->limit($limit)
            ->get();

        return $topVehicles->map(fn($item) => [
            'id' => $item->id,
            'name' => $item->name,
            'plate' => $item->plate,
            'revenue' => (float) $item->revenue,
            'reservation_count' => (int) $item->reservation_count,
        ])->toArray();
    }

    /**
     * Estadísticas de reservas y cancelaciones.
     */
    public function getReservationStatsReport(string $startDate, string $endDate): array
    {
        $start = Carbon::parse($startDate)->startOfDay();
        $end = Carbon::parse($endDate)->endOfDay();

        $stats = Reservation::whereBetween('created_at', [$start, $end])
            ->selectRaw('
                COUNT(*) as total,
                COUNT(CASE WHEN reservation_status = ? THEN 1 END) as completed,
                COUNT(CASE WHEN reservation_status = ? THEN 1 END) as cancelled
            ', [ReservationStatus::Completed->value, ReservationStatus::Cancelled->value])
            ->first();

        $total = (int) $stats->total;
        $completed = (int) $stats->completed;
        $cancelled = (int) $stats->cancelled;

        $cancellationRate = $total > 0 ? ($cancelled / $total) * 100 : 0.0;

        return [
            'start_date' => $startDate,
            'end_date' => $endDate,
            'total_reservations' => $total,
            'completed_reservations' => $completed,
            'cancelled_reservations' => $cancelled,
            'cancellation_rate' => round($cancellationRate, 2),
        ];
    }
}
