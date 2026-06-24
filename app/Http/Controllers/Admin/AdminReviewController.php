<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\ReviewResource;
use App\Models\Review;
use App\Services\ReviewService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminReviewController extends Controller
{
    public function __construct(protected readonly ReviewService $reviewService)
    {
    }

    /**
     * Modifica el estado de visibilidad de una reseña.
     */
    public function moderate(Request $request, Review $review): JsonResponse
    {
        $request->validate([
            'status' => 'required|string|in:visible,hidden',
        ]);

        try {
            $updatedReview = $this->reviewService->updateReviewVisibility($review, $request->input('status'));
            return (new ReviewResource($updatedReview))
                ->response();
        } catch (\InvalidArgumentException $e) {
            abort(422, $e->getMessage());
        }
    }
}
