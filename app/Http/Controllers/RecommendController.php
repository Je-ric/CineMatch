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
    public function getFavorites($userId = null, $limit = 12)
    {
        $userId = $userId ?? Auth::id();
        if (!$userId) {
            return [];
        }

        $limit = $this->clampLimit($limit);

        $movies = Movie::with(["genres", "country", "language", "ratings"])
            ->whereHas("favoritedBy", function ($q) use ($userId) {
                $q->where("user_id", $userId);
            })
            ->orderBy("title", "ASC")
            ->limit($limit)
            ->get();

        return $this->formatMovies($movies);
    }

    public function getRated($userId = null, $limit = 12)
    {
        $userId = $userId ?? Auth::id();
        if (!$userId) {
            return [];
        }

        $limit = $this->clampLimit($limit);

        $movies = Movie::with(["genres", "ratings"])
            ->whereHas("ratings", function ($q) use ($userId) {
                $q->where("user_id", $userId);
            })
            ->get()
            ->sortByDesc(function ($m) use ($userId) {
                return optional(
                    $m->ratings
                        ->where("user_id", $userId)
                        ->sortByDesc("created_at")
                        ->first()
                )->created_at;
            })
            ->take($limit)
            ->values();

        return $this->formatMovies($movies);
    }

    public function getTrending($limit = 12)
    {
        $limit = $this->clampLimit($limit);

        $movies = Movie::with(["genres", "ratings"])
            ->orderBy("title", "asc")
            ->limit($limit)
            ->get();

        return $this->formatMovies($movies);
    }

    public function basedOnFavoriteGenres($userId, $limit = 12)
    {
        if (!$userId) {
            return [];
        }
        $limit = $this->clampLimit($limit);

        $topGenres = Genre::withCount([
            "movies as fav_count" => function ($q) use ($userId) {
                $q->whereHas("favoritedBy", function ($q2) use ($userId) {
                    $q2->where("user_id", $userId);
                });
            },
        ])
            ->having("fav_count", ">", 0)
            ->orderByDesc("fav_count")
            ->limit(5)
            ->pluck("id")
            ->toArray();

        if (empty($topGenres)) {
            return [];
        }

        $movies = Movie::with(["genres", "country", "language", "ratings"])
            ->whereHas("genres", function ($q) use ($topGenres) {
                $q->whereIn("genres.id", $topGenres);
            })
            ->whereDoesntHave("favoritedBy", function ($q) use ($userId) {
                $q->where("user_id", $userId);
            })
            ->get()
            ->map(function ($m) use ($topGenres) {
                $m->match_genres = $m->genres
                    ->pluck("id")
                    ->intersect($topGenres)
                    ->count();
                return $m;
            })
            ->filter(function ($m) {
                return $m->match_genres > 0;
            })
            ->sortByDesc(function ($m) {
                // sort by match_genres, then avg rating (computed from collection), then release_year
                $avg = $m->ratings->count() ? $m->ratings->avg("rating") : 0;
                return [$m->match_genres, $avg, $m->release_year ?? 0];
            })
            ->take($limit)
            ->values();

        return $this->formatMovies($movies);
    }

    // Get movies by genre (excluding user's favorites)
    public function getByGenre($genreId, $userId = 0, $limit = 12)
    {
        $limit = $this->clampLimit($limit);

        $query = Movie::with([
            "genres",
            "country",
            "language",
            "ratings",
        ])->whereHas("genres", function ($q) use ($genreId) {
            $q->where("genres.id", $genreId);
        });

        if ($userId > 0) {
            $query->whereDoesntHave("favoritedBy", function ($subQuery) use (
                $userId
            ) {
                $subQuery->where("user_id", $userId);
            });
        }

        $movies = $query
            ->get()
            ->take($limit)
            ->values();

        return $this->formatMovies($movies);
    }


    // Top 5 genre sa favorites
    public function getTopGenresFromRatings($userId, $limit = 5)
    {
        if (!$userId) {
            return collect();
        }

        return Genre::withCount([
            "movies as rated_count" => function ($q) use ($userId) {
                $q->whereHas("ratings", function ($q2) use ($userId) {
                    $q2->where("user_id", $userId);
                });
            },
        ])
            ->having("rated_count", ">", 0)
            ->orderByDesc("rated_count")
            ->limit($limit)
            ->get();
    }

    // Top 5 genre sa favorites
    public function getTopGenresFromFavorites($userId, $limit = 5)
    {
        if (!$userId) {
            return collect();
        }

        // Get genres that the user has favorited movies in
        $genres = Genre::whereHas('movies.favoritedBy', function ($query) use ($userId) {
            $query->where('user_id', $userId);
        })
        ->withCount(['movies as fav_count' => function ($query) use ($userId) {
            $query->whereHas('favoritedBy', fn($q) => $q->where('user_id', $userId));
        }])
        ->having('fav_count', '>', 0)
        ->orderByDesc('fav_count')
        ->limit($limit)
        ->get();

        return $genres;
    }


    // Build genre shelves for a user.
    // $source = 'favorites' | 'rated'
    // returns array of shelves: ['genre' => GenreModel, 'movies' => Collection(formatted)]
    public function getGenreShelvesForUser(
        $userId,
        $source = "favorites",
        $topLimit = 5,
        $perGenre = 5
    ) {
        if (!$userId) {
            return [];
        }

        $topGenres =
            $source === "rated"
                ? $this->getTopGenresFromRatings($userId, $topLimit)
                : $this->getTopGenresFromFavorites($userId, $topLimit);

        if ($topGenres->isEmpty()) {
            return [];
        }

        $shelves = $topGenres
            ->map(function ($genre) use ($userId, $perGenre) {
                $movies = $this->getByGenre($genre->id, $userId, $perGenre);
                return [
                    "genre" => $genre,
                    "movies" => $movies,
                ];
            })
            ->filter(
                fn($s) => !empty($s["movies"]) && $s["movies"]->count() > 0
            )
            ->values()
            ->toArray();

        return $shelves;
    }



    // Counts per Fav Genre
    public function getFavCountsByGenre(int $userId)
    {
        if (!$userId) {
            return collect();
        }

        $genres = Genre::withCount([
            "movies as cnt" => function ($q) use ($userId) {
                $q->whereHas("favoritedBy", function ($q2) use ($userId) {
                    $q2->where("user_id", $userId);
                });
            },
        ])
            ->having("cnt", ">", 0)
            ->orderByDesc("cnt")
            ->get()
            ->map(
                fn($g) => (object) [
                    "id" => $g->id,
                    "name" => $g->name,
                    "cnt" => (int) $g->cnt,
                ]
            );

        return $genres->values();
    }

    // Counts per Rated Genre
    public function getRatedCountsByGenre(int $userId)
    {
        if (!$userId) {
            return collect();
        }

        $genres = Genre::withCount([
            "movies as cnt" => function ($q) use ($userId) {
                $q->whereHas("ratings", function ($q2) use ($userId) {
                    $q2->where("user_id", $userId);
                });
            },
        ])
            ->having("cnt", ">", 0)
            ->orderByDesc("cnt")
            ->get()
            ->map(
                fn($g) => (object) [
                    "id" => $g->id,
                    "name" => $g->name,
                    "cnt" => (int) $g->cnt,
                ]
            );

        return $genres->values();
    }




        private function formatMovies($movies)
    {
        return $movies->map(function ($movie) {
            $totalReviews = $movie->ratings->count();
            $avg = $totalReviews ? round($movie->ratings->avg("rating"), 2) : 0;

            $movie->country_name = optional($movie->country)->name;
            $movie->language_name = optional($movie->language)->name;
            $movie->avg_rating = $avg;
            $movie->total_reviews = $totalReviews;

            return $movie; // Return as Eloquent model
        });
    }

    private function clampLimit($limit, $max = 48)
    {
        if ($limit <= 0) {
            $limit = 12;
        }
        if ($limit > $max) {
            $limit = $max;
        }
        return $limit;
    }
}
