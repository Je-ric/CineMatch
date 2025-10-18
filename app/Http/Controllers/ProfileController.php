<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\RecommendController;
use App\Models\Genre;

class ProfileController extends Controller
{
    public function show(RecommendController $recommend)
    {
        $user = Auth::user();
        if (! $user) return redirect()->route('login');

        $userId = $user->id;

        // --- Favorites: return ALL favorited movies for the logged in user ---
        $favModels = $user->favorites()->with(['country', 'language', 'genres', 'ratings'])->get();
        $favorites = $favModels->map(function($m) {
            return [
                'id'            => $m->id,
                'title'         => $m->title,
                'release_year'  => $m->release_year,
                'poster_url'    => $m->poster_url,
                'avg_rating'    => $m->ratings->count() ? round($m->ratings->avg('rating'), 1) : null,
                'country_name'  => optional($m->country)->name,
                'language_name' => optional($m->language)->name,
                'genre_ids'     => $m->genres->pluck('id')->implode(','),
            ];
        })->toArray();

        // --- Rated: return all movies the user has rated (use user's ratings relation) ---
        $ratedModels = $user->ratings()->with('movie.genres', 'movie.country', 'movie.language', 'movie.ratings')->get();
        $rated = $ratedModels->map(function($r) {
            $m = $r->movie;
            if (! $m) return null;
            return [
                'id'            => $m->id,
                'title'         => $m->title,
                'release_year'  => $m->release_year,
                'poster_url'    => $m->poster_url,
                'avg_rating'    => $m->ratings->count() ? round($m->ratings->avg('rating'), 1) : null,
                'country_name'  => optional($m->country)->name,
                'language_name' => optional($m->language)->name,
                'genre_ids'     => $m->genres->pluck('id')->implode(','),
            ];
        })->filter()->unique('id')->values()->toArray();

        // keep other view variables defined to avoid undefined variable issues
        $favGenres = $favCountries = $ratedGenres = $ratedCountries = $recommendationsByGenres = $recommendationsByCountries = $genreShelves = $topGenres = [];

        return view('profile', compact(
            'user',
            'favorites',
            'rated',
            'favGenres',
            'favCountries',
            'ratedGenres',
            'ratedCountries',
            'recommendationsByGenres',
            'recommendationsByCountries',
            'genreShelves',
            'topGenres'
        ));
    }
}
