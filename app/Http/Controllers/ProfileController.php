<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Genre;
use App\Http\Controllers\RecommendController;

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
            return (object)[
                'id' => $m->id,
                'title' => $m->title,
                'release_year' => $m->release_year,
                'poster_url' => $m->poster_url ? asset($m->poster_url) : null,
                'avg_rating' => $m->ratings->count() ? round($m->ratings->avg('rating'), 1) : 0,
                'country_name' => optional($m->country)->name ?? 'Unknown',
                'language_name' => optional($m->language)->name ?? 'Unknown',
                'genre_ids' => $m->genres->pluck('id')->implode(','),
            ];
        });

        // --- Fav genres: count each genre occurrence across favorited movies ---
        $favGenres = $favModels
            ->flatMap(fn($m) => $m->genres)    // collection of Genre models
            ->groupBy('id')
            ->map(fn($group) => (object)[
                'id' => $group->first()->id,
                'name' => $group->first()->name,
                'cnt' => $group->count(),
            ])
            ->values()
            ->toArray();

        // --- Fav countries: count per country for favorited movies ---
        $favCountries = $favModels
            ->filter(fn($m) => !empty($m->country))
            ->groupBy(fn($m) => $m->country->id)
            ->map(fn($group) => (object)[
                'id' => $group->first()->country->id,
                'name' => $group->first()->country->name,
                'cnt' => $group->count(),
            ])
            ->values()
            ->toArray();

        // --- Rated: return all movies the user has rated (use user's ratings relation) ---
        $ratedModels = $user->ratings()->with('movie.genres', 'movie.country', 'movie.language', 'movie.ratings')->get();

        // get unique movie models the user rated (avoid duplicate counts if multiple ratings)
        $ratedMovieModels = $ratedModels
            ->map(fn($r) => $r->movie)
            ->filter()
            ->unique('id')
            ->values();

        $rated = $ratedMovieModels->map(function($m) {
            return (object)[
                'id'            => $m->id,
                'title'         => $m->title,
                'release_year'  => $m->release_year,
                'poster_url'    => $m->poster_url ? asset($m->poster_url) : null,
                'avg_rating'    => $m->ratings->count() ? round($m->ratings->avg('rating'), 1) : null,
                'country_name'  => optional($m->country)->name,
                'language_name' => optional($m->language)->name,
                'genre_ids'     => $m->genres->pluck('id')->implode(','),
            ];
        });

        // --- Rated genres: count each genre occurrence across rated movies ---
        $ratedGenres = $ratedMovieModels
            ->flatMap(fn($m) => $m->genres)
            ->groupBy('id')
            ->map(fn($group) => (object)[
                'id' => $group->first()->id,
                'name' => $group->first()->name,
                'cnt' => $group->count(),
            ])
            ->values()
            ->toArray();

        // --- Rated countries: count per country for rated movies ---
        $ratedCountries = $ratedMovieModels
            ->filter(fn($m) => !empty($m->country))
            ->groupBy(fn($m) => $m->country->id)
            ->map(fn($group) => (object)[
                'id' => $group->first()->country->id,
                'name' => $group->first()->country->name,
                'cnt' => $group->count(),
            ])
            ->values()
            ->toArray();

        // === Recommendations: use RecommendController helpers to build shelves ===
        $genreShelvesFav = $recommend->getGenreShelvesForUser($userId, 'favorites', 5, 6);
        $genreShelvesRated = $recommend->getGenreShelvesForUser($userId, 'rated', 5, 6);

        // Also expose top genres lists if needed in the view
        $topGenresFav = $recommend->getTopGenresFromFavorites($userId, 5);
        $topGenresRated = $recommend->getTopGenresFromRatings($userId, 5);

        return view('profile', compact(
            'user',
            'favorites',
            'rated',
            'favGenres',
            'favCountries',
            'ratedGenres',
            'ratedCountries',
            // recommendations data
            'genreShelvesFav',
            'genreShelvesRated',
            'topGenresFav',
            'topGenresRated'
        ));
    }
}
