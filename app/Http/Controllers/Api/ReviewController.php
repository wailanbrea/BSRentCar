<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ReviewResource;
use App\Models\Reservation;
use App\Models\Vehicle;
use App\Services\ReviewService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReviewController extends Controller
{
    public function __construct(protected readonly ReviewService $reviewService)
    {
    }

    /**
     * Crea una reseña para una reservación propia y completada.
     */
    public function store(Request $request, Reservation $reservation): JsonResponse
    {
        $request->validate([
            'rating_vehicle' => 'required|integer|min:1|max:5',
            'rating_cleanliness' => 'required|integer|min:1|max:5',
            'rating_service' => 'required|integer|min:1|max:5',
            'rating_delivery' => 'required|integer|min:1|max:5',
            'rating_overall' => 'required|integer|min:1|max:5',
            'comment' => 'nullable|string|max:1000',
        ]);

        try {
            $review = $this->reviewService->createReview($reservation, $request->all(), $request->user());
            return (new ReviewResource($review))
                ->response()
                ->setStatusCode(201);
        } catch (\DomainException $e) {
            abort($e->getCode() ?: 409, $e->getMessage());
        }
    }

    /**
     * Obtiene las reseñas visibles de un vehículo.
     */
    public function index(Vehicle $vehicle): JsonResponse
    {
        $reviews = $vehicle->reviews()
            ->where('status', \App\Enums\ReviewStatus::Visible)
            ->with('customer')
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        return ReviewResource::collection($reviews)
            ->additional([
                'rating_avg' => (float) $vehicle->rating_avg,
                'rating_count' => (int) $vehicle->rating_count,
            ])
            ->response();
    }
}
