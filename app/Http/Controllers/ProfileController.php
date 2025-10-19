<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use App\Models\Genre;
use App\Http\Controllers\RecommendController;

class ProfileController extends Controller
{
    public function show(RecommendController $recommend)
    {
        $user = Auth::user();
        if (! $user) return redirect()->route('login');

        $userId = $user->id;

        // Modularized helpers
        [$favorites, $favGenres, $favCountries] = $this->getFavoritesData($user);
        [$rated, $ratedGenres, $ratedCountries] = $this->getRatedData($user);
        $recommendations = $this->getRecommendationsData($recommend, $userId);

        return view('profile', array_merge(
            compact(
                'user',
                'favorites',
                'rated',
                'favGenres',
                'favCountries',
                'ratedGenres',
                'ratedCountries'
            ),
            $recommendations
        ));
    }

    private function getFavoritesData($user): array
    {
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

        $favGenres = $favModels
            ->flatMap(fn($m) => $m->genres)
            ->groupBy('id')
            ->map(fn($group) => (object)[
                'id' => $group->first()->id,
                'name' => $group->first()->name,
                'cnt' => $group->count(),
            ])
            ->values()
            ->toArray();

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

        return [$favorites, $favGenres, $favCountries];
    }

    private function getRatedData($user): array
    {
        $ratedModels = $user->ratings()
            ->with('movie.genres', 'movie.country', 'movie.language', 'movie.ratings')
            ->get();

        $ratedMovieModels = $ratedModels
            ->map(fn($r) => $r->movie)
            ->filter()
            ->unique('id')
            ->values();

        $rated = $ratedMovieModels->map(function($m) {
            return (object)[
                'id' => $m->id,
                'title' => $m->title,
                'release_year' => $m->release_year,
                'poster_url' => $m->poster_url ? asset($m->poster_url) : null,
                'avg_rating' => $m->ratings->count() ? round($m->ratings->avg('rating'), 1) : null,
                'country_name' => optional($m->country)->name,
                'language_name' => optional($m->language)->name,
                'genre_ids' => $m->genres->pluck('id')->implode(','),
            ];
        });

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

        return [$rated, $ratedGenres, $ratedCountries];
    }

    private function getRecommendationsData(RecommendController $recommend, $userId): array
    {
        return [
            'genreShelvesFav' => $recommend->getGenreShelvesForUser($userId, 'favorites', 5, 6),
            'genreShelvesRated' => $recommend->getGenreShelvesForUser($userId, 'rated', 5, 6),
            'topGenresFav' => $recommend->getTopGenresFromFavorites($userId, 5),
            'topGenresRated' => $recommend->getTopGenresFromRatings($userId, 5),
        ];
    }
}
