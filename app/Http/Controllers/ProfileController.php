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

        // Modularized helpers - delegate actual fetching to RecommendController
        [$favorites, $favGenres, $favCountries] = $this->getFavoritesData($user, $recommend);
        [$rated, $ratedGenres, $ratedCountries] = $this->getRatedData($user, $recommend);
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

    private function getFavoritesData($user, RecommendController $recommend): array
    {
        // delegate retrieval to RecommendController (returns formatted Eloquent models with relations loaded)
        $favModels = $recommend->getFavorites($user->id); // Collection

        // shape for view (keep minimal object form)
        $favorites = $favModels->map(function($m) {
            return (object)[
                'id' => $m->id,
                'title' => $m->title,
                'release_year' => $m->release_year,
                'poster_url' => $m->poster_url ? asset($m->poster_url) : null,
                'avg_rating' => $m->avg_rating ?? ($m->ratings->count() ? round($m->ratings->avg('rating'), 1) : 0),
                'country_name' => optional($m->country)->name ?? 'Unknown',
                'language_name' => optional($m->language)->name ?? 'Unknown',
                'genre_ids' => $m->genres->pluck('id')->implode(','),
            ];
        });

        // use centralized helpers for genre counts (no local flatMap/groupBy redundancy)
        $favGenres = $recommend->getFavCountsByGenre($user->id)->toArray();

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

    private function getRatedData($user, RecommendController $recommend): array
    {
        $ratedModels = $recommend->getRated($user->id); // Collection of formatted movies

        $rated = $ratedModels->map(function($m) {
            return (object)[
                'id' => $m->id,
                'title' => $m->title,
                'release_year' => $m->release_year,
                'poster_url' => $m->poster_url ? asset($m->poster_url) : null,
                'avg_rating' => $m->avg_rating ?? ($m->ratings->count() ? round($m->ratings->avg('rating'), 1) : null),
                'country_name' => optional($m->country)->name,
                'language_name' => optional($m->language)->name,
                'genre_ids' => $m->genres->pluck('id')->implode(','),
            ];
        });

        // use centralized helper for rated-genre counts
        $ratedGenres = $recommend->getRatedCountsByGenre($user->id)->toArray();

        $ratedCountries = $ratedModels
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
