<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Movie;
use App\Models\User; // added import

class FavoriteController extends Controller
{

    public function toggle(Request $request)
    {
        $data = $request->validate([
            'movie_id' => 'required|integer|exists:movies,id',
        ]);

        /** @var \App\Models\User $user */ // type hint for Intelephense
        $user = Auth::user();

        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Unauthenticated'], 401);
        }

        $movieId = $data['movie_id'];

        // check existing
        $exists = $user->favorites()->wherePivot('movie_id', $movieId)->exists();

        if ($exists) {
            $user->favorites()->detach($movieId);
            $added = false;
        } else {
            $user->favorites()->attach($movieId);
            $added = true;
        }

        $totalFavorites = Movie::find($movieId)->favoritedBy()->count();

        return response()->json([
            'success' => true,
            'added' => $added,
            'totalFavorites' => (int)$totalFavorites,
        ]);
    }
}
