<?php

namespace App\Helpers;

use App\Models\Movie;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Models\RatingReview;
use App\Models\Genre;
class MovieHelper
{
    // profile.blade.php
    public static function getUserFavorites($userId = null, $limit = 12)
    {
        $userId = $userId ?? Auth::id();
        if (!$userId) return collect();

        $movieIds = DB::table('user_favorites')
            ->where('user_id', $userId)
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->pluck('movie_id'); // pluck, para id lang makuha at hindi buong row

        return Movie::with(['genres', 'country', 'language', 'ratings'])
            ->whereIn('id', $movieIds) // see? id lang need natin
            ->orderBy('title', 'asc')
            ->get();
    }

    // profile.php
    public static function getUserRatedMovies($userId = null, $limit = 12)
    {
        $userId = $userId ?? Auth::id();
        if (!$userId) return collect();

        $ratedIds = DB::table('ratings_reviews')
            ->where('user_id', $userId)
            ->orderByDesc('created_at')
            ->limit($limit)
            ->pluck('movie_id');

        // ORM for relationships
        return Movie::with(['genres', 'ratings'])
            ->whereIn('id', $ratedIds) // only movies rated by user
            ->orderBy('title', 'asc')
            ->get();
    }

    // home.php
    public static function getTrendingMovies($limit = 5)
    {
        // reviews count
        $topIds = DB::table('ratings_reviews')
            ->select('movie_id', DB::raw('COUNT(*) as total_reviews'))
            ->groupBy('movie_id')
            ->orderByDesc('total_reviews')
            ->limit($limit)
            ->pluck('movie_id');

        // average
        // $topIds = DB::table('ratings_reviews')
        //     ->select('movie_id', DB::raw('AVG(rating) as avg_rating'))
        //     ->groupBy('movie_id')
        //     ->orderByDesc('avg_rating')
        //     ->limit($limit)
        //     ->pluck('movie_id');

        // ORm for relations
        return Movie::with(['genres', 'country', 'language'])
            ->whereIn('id', $topIds)
            ->get();
    }

    // viewMovie.php
    public static function getRelatedMovies($movie)
    {
        $genreIds = $movie->genres->pluck('id')->toArray(); // get all genre ids sa current movie
        if (empty($genreIds)) return collect();

        $relatedIds = DB::table('movie_genres')
            ->select('movie_id', DB::raw('COUNT(*) as match_genres')) // for each movie, count how many genres match
            ->whereIn('genre_id', $genreIds)                          // filter by genre ids
            ->where('movie_id', '!=', $movie->id)            // exclude current movie
            ->groupBy('movie_id')
            ->orderByDesc('match_genres')
            ->limit(50)
            ->pluck('movie_id')
            ->toArray();


        // since we already have the related ids, get full movie details
        // since meron din tayong genre ids, foreach movie, we match the genre id ng current, sa related movie (each), then sort by
        $relatedMovies = Movie::with(['genres', 'country', 'language', 'ratings'])
            ->whereIn('id', $relatedIds)
            ->get()
            ->map(function ($m) use ($genreIds) {
                $m->match_genres = $m->genres->pluck('id')->intersect($genreIds)->count();
                $m->avg_rating = $m->ratings->count() ? round($m->ratings->avg('rating'), 1) : 0;
                $m->country_name = $m->country->name ?? 'Unknown';
                $m->language_name = $m->language->name ?? 'Unknown';
                return $m;
            })
            ->sortByDesc(fn($m) => [$m->match_genres, $m->avg_rating ?? 0, $m->release_year ?? 0])
            ->take(5)
            ->values();

        return $relatedMovies;
    }


    // viewMovie.php
    // home.php
    // profile.php
    // this is useful, kase we format movies para sa blade or views, hindi na natin i-call isa-isa then my error handling pa, which is messy
    // dito napreprevent na kaagad yung errors, nahahandle na agad,
    // it makes sure data's are clean, if my null or missing values, handled na agad before sending sa component natin na movie card which is used in multiple views
    public static function formatMovies($movies)
    {
        return $movies->map(function ($m) {
            $totalReviews = $m->ratings->count();
            $avg = $totalReviews ? round($m->ratings->avg('rating'), 1) : null;

            $m->avg_rating = $avg;
            $m->total_reviews = $totalReviews;
            $m->country_name = optional($m->country)->name ?? 'Unknown';
            $m->language_name = optional($m->language)->name ?? 'Unknown';
            $m->genres_list = $m->genres->map(fn($g) => ['id' => $g->id, 'name' => $g->name])->toArray();
            $m->genre_ids = $m->genres->pluck('id')->implode(',');

            $poster = $m->poster_url ?? null;
            if ($poster && !preg_match('/^https?:\/\//', $poster)) {
                $poster = asset($poster);
            }
            $m->poster_url = $poster ?: asset('images/placeholders/sample.jpg');

            return $m;
        });
    }


    // checker for movie using pivot tables, exclude yung meron na
    public static function getExcludedMovieIdsForUser(int $userId): array
    {
        if (!$userId) return [];

        $favIds = DB::table('user_favorites')
            ->where('user_id', $userId)
            ->pluck('movie_id')
            ->toArray();

        $ratedIds = DB::table('ratings_reviews')
            ->where('user_id', $userId)
            ->pluck('movie_id')
            ->toArray();

        return array_values(array_unique(array_merge($favIds, $ratedIds)));
    }





    // ==================================================================================
    //   get genres the given user ($userId) has rated/favorited movies in — and how many.
    //   for each genre, count how many movies in that genre the user has rated/favorited store in cnt
    // ==================================================================================

    // - RecommendController::getFavCountsByGenre
    // - ProfileController::getFavoritesData (to show counts per genre)
    public static function getFavCountsByGenre(int $userId)
    {
        return Genre::withCount([
            'movies as cnt' => fn($q) => $q->whereHas('favoritedBy', fn($f) => $f->where('user_id', $userId))
        ])
            ->having('cnt', '>', 0)
            ->orderByDesc('cnt')
            ->get()
            ->map(fn($g) => (object) ['id' => $g->id, 'name' => $g->name, 'cnt' => (int) $g->cnt])
            ->values();
    }

    // - RecommendController::getRatedCountsByGenre
    // - ProfileController::getRatedData (to show counts per genre)
    public static function getRatedCountsByGenre(int $userId)
    {
        return Genre::withCount([
            'movies as cnt' => fn($q) => $q->whereHas('ratings', fn($r) => $r->where('user_id', $userId))
        ])
            ->having('cnt', '>', 0)
            ->orderByDesc('cnt')
            ->get()
            ->map(fn($g) => (object) ['id' => $g->id, 'name' => $g->name, 'cnt' => (int) $g->cnt])
            ->values();
    }

    // ==================================================================================
    //   - we are counting the number of movies (within each genre) that the user has rated or favorited.
    //      - rated - `getTopGenresFromRatings()`
    //      - favorited - `getTopGenresFromFavorites()`

    // mas madali kase na hinahanap nalang natin sa genre yung movie
    // think of it, kase if we used the user_favorites table, get each movie, get the genres, count many times each genre then group (madami)
    // but if we use the genre model, so nagloloop tayo for each genre, and find the movie that user has rated/favorited

    //   - RecommendController::getGenreShelvesForUser - builds “shelves” grouped by top genres.
    //   - ProfileController - displays user’s top genres in UI analytics or stats.
    // ==================================================================================

    public static function getTopGenresFromRatings(int $userId, int $limit = 5)
    {
        return Genre::withCount([
            'movies as rated_count' => fn($q) => $q->whereHas('ratings', fn($r) => $r->where('user_id', $userId))
        ])
            ->having('rated_count', '>', 0)
            ->orderByDesc('rated_count')
            ->limit($limit)
            ->get();
    }

    public static function getTopGenresFromFavorites(int $userId, int $limit = 5)
    {
        return Genre::withCount([
            'movies as fav_count' => fn($q) => $q->whereHas('favoritedBy', fn($f) => $f->where('user_id', $userId))
        ])
            ->having('fav_count', '>', 0)
            ->orderByDesc('fav_count')
            ->limit($limit)
            ->get();
    }

    //  - RecommendController::getGenreShelvesForUser
    //  - ProfileController::getRecommendationsData (builds genre shelves for UI)
    //  - profile.blade.php (renders multiple genre shelves)
    public static function getGenreShelvesForUser(int $userId, string $source = 'favorites', int $topLimit = 5, int $perGenre = 5)
    {
        if (!$userId) return [];

        // pick source for top genres (simple if/else)
        if ($source === 'rated') {
            $topGenres = self::getTopGenresFromRatings($userId, $topLimit);
        } else {
            $topGenres = self::getTopGenresFromFavorites($userId, $topLimit);
        }

        if ($topGenres->isEmpty()) return [];


        $shelves = $topGenres->map(function ($genre) use ($userId, $perGenre) {
            $excludedIds = self::getExcludedMovieIdsForUser($userId);
            $movies = Movie::with(['genres', 'country', 'language', 'ratings'])
                ->whereHas('genres', function ($q) use ($genre) {
                    $q->where('genres.id', $genre->id);
                })
                ->when(!empty($excludedIds), function ($q) use ($excludedIds) {
                    $q->whereNotIn('id', $excludedIds);
                })
                ->limit($perGenre)
                ->get();

            return [
                'genre' => $genre,
                'movies' => MovieHelper::formatMovies($movies),
            ];
        })->filter(fn($shelf) => $shelf['movies']->isNotEmpty())
        ->values();

        return $shelves;
    }










    // ==================================================================================
    // From here, all functions sa baba are used only when viewing a single movie which is sa viewMovie.blade.php
    // ==================================================================================

    // viewMovie.php
    // used in MovieController@show
    public static function getMovieWithDetails($movieId)
    {
        $movie = Movie::with(['genres', 'country', 'language', 'cast', 'ratings'])->find($movieId);

        if (!$movie) return null;

        $movie->country_name = $movie->country->name ?? 'Unknown';
        $movie->language_name = $movie->language->name ?? 'Unknown';

        return $movie;
    }

    // viewMovie.php
    // used in MovieController@show
    public static function getMovieReviews($movieId)
    {
        // all reviews
        $reviews = DB::table('ratings_reviews')
            ->where('movie_id', $movieId)
            ->orderByDesc('created_at')
            ->get();

        $count = $reviews->count();
        $average = $count ? round($reviews->avg('rating'), 1) : null;

        $reviewModels = RatingReview::with('user')
            ->where('movie_id', $movieId)
            ->latest()
            ->get();

        return [
            'list' => $reviewModels,
            'count' => $count,
            'average' => $average,
        ];
    }

    // viewMovie.php
    // used in MovieController@show
    public static function splitCastRoles($movie)
    {
        // filter out directors and actors
        $directors = $movie->cast->filter(fn($person) =>
                    strcasecmp($person->pivot->role ?? '', 'Director') === 0)
                    ->values();
        $actors = $movie->cast->filter(fn($person) =>
                    strcasecmp($person->pivot->role ?? '', 'Cast') === 0)
                    ->values();

        return compact('directors', 'actors');
    }


}
