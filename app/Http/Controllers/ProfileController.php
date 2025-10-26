<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use App\Models\Genre;
use App\Http\Controllers\RecommendController;
use App\Helpers\MovieHelper;

class ProfileController extends Controller
{
    public function show(RecommendController $recommend)
    {
        $user = Auth::user();
        if (! $user) return redirect()->route('login');

        $userId = $user->id;

        // Fetch to RecommendController
        // for example, we have the
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
        // may retrieval sa RecommendController
        $favModels = $recommend->getFavorites($user->id); // Collection

        // shape for view (keep minimal object form)
        $favorites = MovieHelper::formatMovies($favModels);

        // same here
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
        // may retrieval sa RecommendController
        $ratedModels = $recommend->getRated($user->id); // Collection of formatted movies

        $rated = MovieHelper::formatMovies($ratedModels);

        // same here
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
            'genreShelvesFav' => $recommend->getGenreShelvesForUser($userId, 'favorites', 5, 5),
            'genreShelvesRated' => $recommend->getGenreShelvesForUser($userId, 'rated', 5, 5),
            'topGenresFav' => $recommend->getTopGenresFromFavorites($userId, 5),
            'topGenresRated' => $recommend->getTopGenresFromRatings($userId, 5),
        ];
    }
}
