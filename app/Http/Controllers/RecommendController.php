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
    // remove the controller-in-controller show signature if present elsewhere;
    // keep only recommendation helpers here
/**
 * Get the user's favorite movies
 */
public function getFavorites($userId = null, $limit = 12)
    {
        $userId = $userId ?? Auth::id();
        if (!$userId) return [];

        $limit = $this->clampLimit($limit);

        $movies = Movie::with(['genres', 'country', 'language', 'ratings'])
            ->whereHas('favoritedBy', function ($q) use ($userId) {
                $q->where('user_id', $userId);
            })
            ->orderBy('title', 'ASC')
            ->limit($limit)
            ->get();

        return $this->formatMovies($movies);
    }

    /**
     * Get movies the user has rated/reviewed
     * If $userId is null it will use the currently authenticated user.
     */
    public function getRated($userId = null, $limit = 12)
    {
        $userId = $userId ?? Auth::id();
        if (!$userId) return [];

        $limit = $this->clampLimit($limit);

        $movies = Movie::with(['genres', 'ratings'])
            ->whereHas('ratings', function ($q) use ($userId) {
                $q->where('user_id', $userId);
            })
            ->get()
            ->sortByDesc(function ($m) use ($userId) {
                return optional($m->ratings->where('user_id', $userId)->sortByDesc('created_at')->first())->created_at;
            })
            ->take($limit)
            ->values();

        return $this->formatMovies($movies);
    }
    /**
     * Get trending movies based on average rating
     */
    public function getTrending($limit = 12)
    {
        $limit = $this->clampLimit($limit);

        // eager load ratings collection and compute averages in PHP to avoid SQL that references wrong column/table
        $movies = Movie::with(['genres', 'ratings'])
            ->orderBy('title', 'asc')
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

        $topGenres = Genre::withCount(['movies as fav_count' => function ($q) use ($userId) {
            $q->whereHas('favoritedBy', function ($q2) use ($userId) {
                $q2->where('user_id', $userId);
            });
        }])
        ->having('fav_count', '>', 0)
        ->orderByDesc('fav_count')
        ->limit(5)
        ->pluck('id')
        ->toArray();

        if (empty($topGenres)) return [];

        $movies = Movie::with(['genres', 'country', 'language', 'ratings'])
            ->whereHas('genres', function ($q) use ($topGenres) {
                $q->whereIn('genres.id', $topGenres);
            })
            ->whereDoesntHave('favoritedBy', function ($q) use ($userId) {
                $q->where('user_id', $userId);
            })
            ->get()
            ->map(function ($m) use ($topGenres) {
                $m->match_genres = $m->genres->pluck('id')->intersect($topGenres)->count();
                return $m;
            })
            ->filter(function ($m) {
                return $m->match_genres > 0;
            })
            ->sortByDesc(function ($m) {
                // sort by match_genres, then avg rating (computed from collection), then release_year
                $avg = $m->ratings->count() ? $m->ratings->avg('rating') : 0;
                return [$m->match_genres, $avg, $m->release_year ?? 0];
            })
            ->take($limit)
            ->values();

        return $this->formatMovies($movies);
    }

    /**
     * Get recommendations based on user's favorite countries
     */
    public function basedOnFavoriteCountries($userId, $limit = 12)
    {
        if (!$userId) return [];
        $limit = $this->clampLimit($limit);

        $topCountries = Movie::whereHas('favoritedBy', function ($q) use ($userId) {
                $q->where('user_id', $userId);
            })
            ->whereNotNull('country_id')
            ->select('country_id')
            ->selectRaw('count(*) as cnt')
            ->groupBy('country_id')
            ->orderByDesc('cnt')
            ->limit(3)
            ->pluck('country_id')
            ->toArray();

        if (empty($topCountries)) return [];

        $movies = Movie::with(['genres', 'country', 'language', 'ratings'])
            ->whereIn('country_id', $topCountries)
            ->whereDoesntHave('favoritedBy', function ($q) use ($userId) {
                $q->where('user_id', $userId);
            })
            ->get();

        return $this->formatMovies($movies);
    }

    /**
     * Get movies by genre (excluding user's favorites)
     */
    public function getByGenre($genreId, $userId = 0, $limit = 12)
    {
        $limit = $this->clampLimit($limit);

        $query = Movie::with(['genres', 'country', 'language', 'ratings'])
            ->whereHas('genres', function ($q) use ($genreId) {
                $q->where('genres.id', $genreId);
            });

        if ($userId > 0) {
            $query->whereDoesntHave('favoritedBy', function ($subQuery) use ($userId) {
                $subQuery->where('user_id', $userId);
            });
        }

        $movies = $query->get()->take($limit)->values();

        return $this->formatMovies($movies);
    }

    /**
     * Format movies for consistent output
     */
   private function formatMovies($movies)
{
    return $movies->map(function ($movie) {
        $totalReviews = $movie->ratings->count();
        $avg = $totalReviews ? round($movie->ratings->avg('rating'), 2) : 0;

        // Add extra attributes dynamically
        $movie->country_name = optional($movie->country)->name;
        $movie->language_name = optional($movie->language)->name;
        $movie->avg_rating = $avg;
        $movie->total_reviews = $totalReviews;

        return $movie; // Return as Eloquent model
    });
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

    /**
     * Get top genres from user's rated movies
     * returns Collection of Genre models (with count as 'rated_count')
     */
    public function getTopGenresFromRatings($userId, $limit = 5)
    {
        if (!$userId) return collect();

        return Genre::withCount(['movies as rated_count' => function ($q) use ($userId) {
            $q->whereHas('ratings', function ($q2) use ($userId) {
                $q2->where('user_id', $userId);
            });
        }])
        ->having('rated_count', '>', 0)
        ->orderByDesc('rated_count')
        ->limit($limit)
        ->get();
    }

    /**
     * Get top genres from user's favorites
     * returns Collection of Genre models (with count as 'fav_count')
     */
    public function getTopGenresFromFavorites($userId, $limit = 5)
    {
        if (!$userId) return collect();

        return Genre::withCount(['movies as fav_count' => function ($q) use ($userId) {
            $q->whereHas('favoritedBy', function ($q2) use ($userId) {
                $q2->where('user_id', $userId);
            });
        }])
        ->having('fav_count', '>', 0)
        ->orderByDesc('fav_count')
        ->limit($limit)
        ->get();
    }

    /**
     * Build genre shelves for a user.
     * $source = 'favorites' | 'rated'
     * returns array of shelves: ['genre' => GenreModel, 'movies' => Collection(formatted)]
     */
    public function getGenreShelvesForUser($userId, $source = 'favorites', $topLimit = 5, $perGenre = 6)
    {
        if (!$userId) return [];

        $topGenres = $source === 'rated'
            ? $this->getTopGenresFromRatings($userId, $topLimit)
            : $this->getTopGenresFromFavorites($userId, $topLimit);

        if ($topGenres->isEmpty()) return [];

        $shelves = $topGenres->map(function ($genre) use ($userId, $perGenre) {
            // getByGenre returns formatted movies collection
            $movies = $this->getByGenre($genre->id, $userId, $perGenre);
            return [
                'genre' => $genre,
                'movies' => $movies,
            ];
        })->filter(fn($s) => !empty($s['movies']) && $s['movies']->count() > 0)
          ->values()
          ->toArray();

        return $shelves;
    }
}
