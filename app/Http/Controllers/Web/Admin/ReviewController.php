<?php

namespace App\Http\Controllers\Web\Admin;

use App\Http\Controllers\Controller;
use App\Models\Review;
use App\Services\ReviewService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ReviewController extends Controller
{
    public function __construct(private readonly ReviewService $reviews)
    {
    }

    public function index(): View
    {
        $reviews = Review::with(['vehicle', 'customer'])
            ->latest()
            ->paginate(20);

        return view('admin.reviews.index', compact('reviews'));
    }

    public function moderate(Request $request, Review $review): RedirectResponse
    {
        $status = $request->string('status')->toString();

        try {
            $this->reviews->updateReviewVisibility($review, $status);
        } catch (\InvalidArgumentException) {
            return back()->withErrors(['estado' => 'Estado de reseña inválido.']);
        }

        return back()->with('status', 'Calificación actualizada.');
    }
}
