<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Movie;
use App\Models\User;
use App\Models\RatingReview;
use App\Models\Genre;
use App\Models\Country;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Helpers\MovieHelper;
use App\Helpers\RecommendHelper;

class RecommendController extends Controller
{
    public function getFavorites($userId = null, $limit = 12)
    {
        return MovieHelper::getUserFavorites($userId, $limit);
    }

    public function getRated($userId = null, $limit = 12)
    {
        return MovieHelper::getUserRatedMovies($userId, $limit);
    }

    public function basedOnFavoriteGenres($userId, $limit = 12)
    {
        return RecommendHelper::basedOnFavoriteGenres($userId, $limit);
    }

    public function getTopGenresFromRatings($userId, $limit = 5)
    {
        return RecommendHelper::getTopGenresFromRatings($userId, $limit);
    }

    public function getTopGenresFromFavorites($userId, $limit = 5)
    {
        return RecommendHelper::getTopGenresFromFavorites($userId, $limit);
    }

    public function getGenreShelvesForUser($userId, $source = 'favorites', $topLimit = 5, $perGenre = 5)
    {
        return RecommendHelper::getGenreShelvesForUser($userId, $source, $topLimit, $perGenre);
    }

    public function getFavCountsByGenre($userId)
    {
        return RecommendHelper::getFavCountsByGenre($userId);
    }

    public function getRatedCountsByGenre($userId)
    {
        return RecommendHelper::getRatedCountsByGenre($userId);
    }
}
