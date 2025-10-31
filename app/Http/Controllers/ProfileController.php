<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use App\Models\Genre;
use App\Helpers\MovieHelper;

class ProfileController extends Controller
{
    public function show()
    {
        $user = Auth::user();
        if (! $user) return redirect()->route('login');

        $userId = $user->id;

        // Fetch to RecommendController
        // for example, we have the
        [$favorites, $favGenres, $favCountries] = $this->getFavoritesData($user);
        [$rated, $ratedGenres, $ratedCountries] = $this->getRatedData($user);
        $recommendations = $this->getRecommendationsData($userId);

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
        // may retrieval sa RecommendController
        // replaced with MovieHelper
        $favModels = MovieHelper::getUserFavorites($user->id); // Collection
        $favorites = MovieHelper::formatMovies($favModels);

        // same here
        $favGenres = MovieHelper::getFavCountsByGenre($user->id)->toArray();

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
        // may retrieval sa RecommendController
        // replaced with MovieHelper
        $ratedModels = MovieHelper::getUserRatedMovies($user->id); // Collection of formatted movies

        $rated = MovieHelper::formatMovies($ratedModels);

        // same here
        $ratedGenres = MovieHelper::getRatedCountsByGenre($user->id)->toArray();

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

    private function getRecommendationsData($userId): array
    {
        return [
            'genreShelvesFav' => MovieHelper::getGenreShelvesForUser($userId, 'favorites', 5, 5),
            'genreShelvesRated' => MovieHelper::getGenreShelvesForUser($userId, 'rated', 5, 5),
            'topGenresFav' => MovieHelper::getTopGenresFromFavorites($userId, 5),
            'topGenresRated' => MovieHelper::getTopGenresFromRatings($userId, 5),
        ];
    }

    
}
