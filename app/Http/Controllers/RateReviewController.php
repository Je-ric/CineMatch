<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\RatingReview;
use Illuminate\Support\Facades\Auth;

class RateReviewController extends Controller
{
    public function store(Request $request)
    {
        $data = $request->validate([
            'movie_id' => 'required|integer|exists:movies,id',
            'rating'   => 'required|integer|min:1|max:5',
            'review'   => 'nullable|string|max:2000',
        ]);

        $userId = Auth::id();

        $record = RatingReview::updateOrCreate(
            ['user_id' => $userId, 'movie_id' => $data['movie_id']],
            ['rating' => $data['rating'], 'review' => $data['review'] ?? null]
        );

        $avg = RatingReview::where('movie_id', $data['movie_id'])->avg('rating');
        $total = RatingReview::where('movie_id', $data['movie_id'])->count();

        return response()->json([
            'success' => true,
            'average' => [
                'avg' => $avg !== null ? round((float)$avg, 1) : null,
                'total' => (int)$total,
            ],
            'review' => [
                'username' => Auth::user()->name ?? Auth::user()->username ?? 'You',
                'rating' => (int)$record->rating,
                'review' => $record->review,
            ],
        ]);
    }
}
