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

class RecommendController extends Controller
{
    /**
     * Get trending movies based on average rating
     */
    public function getTrending($limit = 12)
    {
        $limit = $this->clampLimit($limit);

        $movies = Movie::with(['genres', 'countries', 'languages', 'ratings'])
            ->select('movies.*')
            ->selectRaw('ROUND(COALESCE(AVG(ratings_reviews.rating), 0), 2) AS avg_rating')
            ->selectRaw('COUNT(ratings_reviews.id) AS total_reviews')
            ->leftJoin('ratings_reviews', 'ratings_reviews.movie_id', '=', 'movies.id')
            ->groupBy('movies.id')
            ->orderByRaw('(AVG(ratings_reviews.rating) IS NULL), AVG(ratings_reviews.rating) DESC, COUNT(ratings_reviews.id) DESC, movies.release_year DESC')
            ->limit($limit)
            ->get();

        return $this->formatMovies($movies);
    }

    /**
     * Get user's favorite movies
     */
    public function getFavorites($userId, $limit = 12)
    {
        if (!$userId) return [];
        $limit = $this->clampLimit($limit);

        $movies = Movie::with(['genres', 'countries', 'languages', 'ratings'])
            ->select('movies.*')
            ->selectRaw('ROUND(COALESCE(AVG(ratings_reviews.rating), 0), 2) AS avg_rating')
            ->selectRaw('COUNT(ratings_reviews.id) AS total_reviews')
            ->join('user_favorites', 'user_favorites.movie_id', '=', 'movies.id')
            ->leftJoin('ratings_reviews', 'ratings_reviews.movie_id', '=', 'movies.id')
            ->where('user_favorites.user_id', $userId)
            ->groupBy('movies.id')
            ->orderBy('movies.title', 'ASC')
            ->limit($limit)
            ->get();

        return $this->formatMovies($movies);
    }

    /**
     * Get movies the user has rated/reviewed
     */
    public function getRated($userId, $limit = 12)
    {
        if (!$userId) return [];
        $limit = $this->clampLimit($limit);

        $movies = Movie::with(['genres', 'countries', 'languages', 'ratings'])
            ->select('movies.*')
            ->selectRaw('ROUND(COALESCE(AVG(all_reviews.rating), 0), 2) AS avg_rating')
            ->selectRaw('COUNT(all_reviews.id) AS total_reviews')
            ->join('ratings_reviews as user_reviews', 'user_reviews.movie_id', '=', 'movies.id')
            ->leftJoin('ratings_reviews as all_reviews', 'all_reviews.movie_id', '=', 'movies.id')
            ->where('user_reviews.user_id', $userId)
            ->groupBy('movies.id')
            ->orderByRaw('MAX(user_reviews.id) DESC')
            ->limit($limit)
            ->get();

        return $this->formatMovies($movies);
    }

    /**
     * Get recommendations based on user's favorite genres
     */
    public function basedOnFavoriteGenres($userId, $limit = 12)
    {
        if (!$userId) return [];
        $limit = $this->clampLimit($limit);

        // Get top 5 genres from user's favorites
        $topGenres = DB::table('user_favorites')
            ->join('movie_genres', 'movie_genres.movie_id', '=', 'user_favorites.movie_id')
            ->where('user_favorites.user_id', $userId)
            ->groupBy('movie_genres.genre_id')
            ->orderByRaw('COUNT(*) DESC')
            ->limit(5)
            ->pluck('movie_genres.genre_id')
            ->toArray();

        if (empty($topGenres)) return [];

        // Get movies with these genres, excluding user's favorites
        $movies = Movie::with(['genres', 'countries', 'languages', 'ratings'])
            ->select('movies.*')
            ->selectRaw('ROUND(COALESCE(AVG(ratings_reviews.rating), 0), 2) AS avg_rating')
            ->selectRaw('COUNT(DISTINCT movie_genres.genre_id) AS match_genres')
            ->join('movie_genres', 'movie_genres.movie_id', '=', 'movies.id')
            ->leftJoin('ratings_reviews', 'ratings_reviews.movie_id', '=', 'movies.id')
            ->whereIn('movie_genres.genre_id', $topGenres)
            ->whereNotIn('movies.id', function($query) use ($userId) {
                $query->select('movie_id')
                      ->from('user_favorites')
                      ->where('user_id', $userId);
            })
            ->groupBy('movies.id')
            ->orderByRaw('match_genres DESC, (AVG(ratings_reviews.rating) IS NULL), AVG(ratings_reviews.rating) DESC, movies.release_year DESC')
            ->limit($limit)
            ->get();

        return $this->formatMovies($movies);
    }

    /**
     * Get recommendations based on user's favorite countries
     */
    public function basedOnFavoriteCountries($userId, $limit = 12)
    {
        if (!$userId) return [];
        $limit = $this->clampLimit($limit);

        // Get top 3 countries from user's favorites
        $topCountries = DB::table('user_favorites')
            ->join('movies', 'movies.id', '=', 'user_favorites.movie_id')
            ->where('user_favorites.user_id', $userId)
            ->whereNotNull('movies.country_id')
            ->groupBy('movies.country_id')
            ->orderByRaw('COUNT(*) DESC')
            ->limit(3)
            ->pluck('movies.country_id')
            ->toArray();

        if (empty($topCountries)) return [];

        // Get movies from these countries, excluding user's favorites
        $movies = Movie::with(['genres', 'countries', 'languages', 'ratings'])
            ->select('movies.*')
            ->selectRaw('ROUND(COALESCE(AVG(ratings_reviews.rating), 0), 2) AS avg_rating')
            ->leftJoin('ratings_reviews', 'ratings_reviews.movie_id', '=', 'movies.id')
            ->whereIn('movies.country_id', $topCountries)
            ->whereNotIn('movies.id', function($query) use ($userId) {
                $query->select('movie_id')
                      ->from('user_favorites')
                      ->where('user_id', $userId);
            })
            ->groupBy('movies.id')
            ->orderByRaw('(AVG(ratings_reviews.rating) IS NULL), AVG(ratings_reviews.rating) DESC, movies.release_year DESC')
            ->limit($limit)
            ->get();

        return $this->formatMovies($movies);
    }

    /**
     * Get movies by genre (excluding user's favorites)
     */
    public function getByGenre($genreId, $userId = 0, $limit = 12)
    {
        $limit = $this->clampLimit($limit);

        $query = Movie::with(['genres', 'countries', 'languages', 'ratings'])
            ->select('movies.*')
            ->selectRaw('ROUND(COALESCE(AVG(ratings_reviews.rating), 0), 2) AS avg_rating')
            ->selectRaw('COUNT(ratings_reviews.id) AS total_reviews')
            ->join('movie_genres', 'movie_genres.movie_id', '=', 'movies.id')
            ->leftJoin('ratings_reviews', 'ratings_reviews.movie_id', '=', 'movies.id')
            ->where('movie_genres.genre_id', $genreId);

        if ($userId > 0) {
            $query->whereNotIn('movies.id', function($subQuery) use ($userId) {
                $subQuery->select('movie_id')
                         ->from('user_favorites')
                         ->where('user_id', $userId);
            });
        }

        $movies = $query->groupBy('movies.id')
            ->orderByRaw('(AVG(ratings_reviews.rating) IS NULL), AVG(ratings_reviews.rating) DESC, movies.release_year DESC')
            ->limit($limit)
            ->get();

        return $this->formatMovies($movies);
    }

    /**
     * Get user's top genres with counts
     */
    public function getUserTopGenres($userId, $limit = 5)
    {
        if (!$userId) return [];
        $limit = $this->clampLimit($limit, 10);

        return DB::table('user_favorites')
            ->join('movie_genres', 'movie_genres.movie_id', '=', 'user_favorites.movie_id')
            ->join('genres', 'genres.id', '=', 'movie_genres.genre_id')
            ->where('user_favorites.user_id', $userId)
            ->groupBy('genres.id', 'genres.name')
            ->orderByRaw('COUNT(*) DESC')
            ->limit($limit)
            ->select('genres.id', 'genres.name', DB::raw('COUNT(*) as cnt'))
            ->get()
            ->toArray();
    }

    /**
     * Get favorite counts by genre for user
     */
    public function getFavCountsByGenre($userId)
    {
        if (!$userId) return [];

        return DB::table('user_favorites')
            ->join('movie_genres', 'movie_genres.movie_id', '=', 'user_favorites.movie_id')
            ->join('genres', 'genres.id', '=', 'movie_genres.genre_id')
            ->where('user_favorites.user_id', $userId)
            ->groupBy('genres.id', 'genres.name')
            ->havingRaw('COUNT(*) > 0')
            ->orderByRaw('COUNT(*) DESC, genres.name ASC')
            ->select('genres.id', 'genres.name', DB::raw('COUNT(*) as cnt'))
            ->get()
            ->toArray();
    }

    /**
     * Get favorite counts by country for user
     */
    public function getFavCountsByCountry($userId)
    {
        if (!$userId) return [];

        return DB::table('user_favorites')
            ->join('movies', 'movies.id', '=', 'user_favorites.movie_id')
            ->join('countries', 'countries.id', '=', 'movies.country_id')
            ->where('user_favorites.user_id', $userId)
            ->whereNotNull('movies.country_id')
            ->groupBy('countries.id', 'countries.name')
            ->havingRaw('COUNT(*) > 0')
            ->orderByRaw('COUNT(*) DESC, countries.name ASC')
            ->select('countries.id', 'countries.name', DB::raw('COUNT(*) as cnt'))
            ->get()
            ->toArray();
    }

    /**
     * Get rated counts by genre for user
     */
    public function getRatedCountsByGenre($userId)
    {
        if (!$userId) return [];

        return DB::table('ratings_reviews')
            ->join('movie_genres', 'movie_genres.movie_id', '=', 'ratings_reviews.movie_id')
            ->join('genres', 'genres.id', '=', 'movie_genres.genre_id')
            ->where('ratings_reviews.user_id', $userId)
            ->groupBy('genres.id', 'genres.name')
            ->havingRaw('COUNT(DISTINCT ratings_reviews.movie_id) > 0')
            ->orderByRaw('COUNT(DISTINCT ratings_reviews.movie_id) DESC, genres.name ASC')
            ->select('genres.id', 'genres.name', DB::raw('COUNT(DISTINCT ratings_reviews.movie_id) as cnt'))
            ->get()
            ->toArray();
    }

    /**
     * Get rated counts by country for user
     */
    public function getRatedCountsByCountry($userId)
    {
        if (!$userId) return [];

        return DB::table('ratings_reviews')
            ->join('movies', 'movies.id', '=', 'ratings_reviews.movie_id')
            ->join('countries', 'countries.id', '=', 'movies.country_id')
            ->where('ratings_reviews.user_id', $userId)
            ->whereNotNull('movies.country_id')
            ->groupBy('countries.id', 'countries.name')
            ->havingRaw('COUNT(DISTINCT ratings_reviews.movie_id) > 0')
            ->orderByRaw('COUNT(DISTINCT ratings_reviews.movie_id) DESC, countries.name ASC')
            ->select('countries.id', 'countries.name', DB::raw('COUNT(DISTINCT ratings_reviews.movie_id) as cnt'))
            ->get()
            ->toArray();
    }

    /**
     * Format movies for consistent output
     */
    private function formatMovies($movies)
    {
        return $movies->map(function ($movie) {
            return [
                'id' => $movie->id,
                'title' => $movie->title,
                'release_year' => $movie->release_year,
                'poster_url' => $movie->poster_url,
                'country_name' => $movie->countries->first()?->name ?? null,
                'language_name' => $movie->languages->first()?->name ?? null,
                'avg_rating' => $movie->avg_rating ?? 0,
                'total_reviews' => $movie->total_reviews ?? 0,
            ];
        })->toArray();
    }

    /**
     * Clamp limit to safe range
     */
    private function clampLimit($limit, $max = 48)
    {
        if ($limit <= 0) $limit = 12;
        if ($limit > $max) $limit = $max;
        return $limit;
    }
}
