<?php

namespace App\Services;

use App\Models\Reservation;
use App\Models\Review;
use App\Models\User;
use App\Models\Vehicle;
use App\Enums\ReviewStatus;
use App\Enums\ReservationStatus;
use Illuminate\Support\Facades\DB;

class ReviewService
{
    /**
     * Crea una reseña para una reservación.
     */
    public function createReview(Reservation $reservation, array $data, User $user): Review
    {
        return DB::transaction(function () use ($reservation, $data, $user) {
            $customer = $user->customer;

            // Validar propiedad de la reserva
            if (!$customer || $reservation->customer_id !== $customer->id) {
                throw new \DomainException("No autorizado. Esta reservación no le pertenece.", 403);
            }

            // Validar que la reservación esté completada
            if ($reservation->reservation_status !== ReservationStatus::Completed) {
                throw new \DomainException("No se puede calificar una reservación que no está en estado 'completed'.", 409);
            }

            // Validar que no se haya calificado antes
            if ($reservation->review()->exists()) {
                throw new \DomainException("Esta reservación ya ha sido calificada anteriormente.", 409);
            }

            // Crear la reseña
            $review = Review::create([
                'reservation_id' => $reservation->id,
                'customer_id' => $customer->id,
                'vehicle_id' => $reservation->vehicle_id,
                'rating_vehicle' => $data['rating_vehicle'],
                'rating_cleanliness' => $data['rating_cleanliness'],
                'rating_service' => $data['rating_service'],
                'rating_delivery' => $data['rating_delivery'],
                'rating_overall' => $data['rating_overall'],
                'comment' => $data['comment'] ?? null,
                'status' => ReviewStatus::Visible->value,
            ]);

            // Recalcular rating del vehículo de forma atómica
            $this->recalculateVehicleRating($reservation->vehicle);

            return $review;
        });
    }

    /**
     * Modifica el estado de visibilidad de una reseña.
     */
    public function updateReviewVisibility(Review $review, string $status): Review
    {
        return DB::transaction(function () use ($review, $status) {
            if (!in_array($status, [ReviewStatus::Visible->value, ReviewStatus::Hidden->value], true)) {
                throw new \InvalidArgumentException("Estado de reseña inválido: " . $status);
            }

            $review->update(['status' => $status]);

            // Recalcular rating
            $this->recalculateVehicleRating($review->vehicle);

            return $review->refresh();
        });
    }

    /**
     * Recalcula el promedio de calificación y cantidad de calificaciones de un vehículo.
     */
    public function recalculateVehicleRating(Vehicle $vehicle): void
    {
        $stats = Review::where('vehicle_id', $vehicle->id)
            ->where('status', ReviewStatus::Visible)
            ->selectRaw('COALESCE(AVG(rating_overall), 0) as avg, COUNT(*) as count')
            ->first();

        $vehicle->rating_avg = round($stats->avg, 2);
        $vehicle->rating_count = (int) $stats->count;
        $vehicle->save();
    }
}
