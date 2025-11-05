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
        [$favorites, $favGenres] = $this->getFavoritesData($user);
        [$rated, $ratedGenres] = $this->getRatedData($user);
        $recommendations = $this->getRecommendationsData($userId);

        return view('profile', array_merge(
            compact(
                'user',
                'favorites', // movies in FaveTab
                'rated', // movies in RatedTab
                'favGenres', // count
                'ratedGenres', // count
            ),
            $recommendations
        ));
    }

    private function getFavoritesData($user): array
    {
        $favModels = MovieHelper::getUserFavorites($user->id); // Collection
        $favorites = MovieHelper::formatMovies($favModels);
        $favGenres = MovieHelper::getFavCountsByGenre($user->id)->toArray();

        return [$favorites, $favGenres];
    }

    private function getRatedData($user): array
    {
        $ratedModels = MovieHelper::getUserRatedMovies($user->id); // Collection of formatted movies
        $rated = MovieHelper::formatMovies($ratedModels);
        $ratedGenres = MovieHelper::getRatedCountsByGenre($user->id)->toArray();

        return [$rated, $ratedGenres];
    }

    private function getRecommendationsData($userId): array
    {
        return [
            'genreShelvesFav' => MovieHelper::getGenreShelvesForUser($userId, 'favorites', 5, 5), 
            'genreShelvesRated' => MovieHelper::getGenreShelvesForUser($userId, 'rated', 5, 5), 
        ];
    }

}
