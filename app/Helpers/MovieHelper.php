<?php

namespace App\Helpers;

use App\Models\Movie;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Models\RatingReview;
class MovieHelper
{
    public static function getUserFavorites($userId = null, $limit = 12)
    {
        $userId = $userId ?? Auth::id();
        if (!$userId) return collect();

        $movieIds = DB::table('user_favorites')
            ->where('user_id', $userId)
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->pluck('movie_id');

        return Movie::with(['genres', 'country', 'language', 'ratings'])
            ->whereIn('id', $movieIds)
            ->orderBy('title', 'asc')
            ->get();
    }

    public static function getUserRatedMovies($userId = null, $limit = 12)
    {
        $userId = $userId ?? Auth::id();
        if (!$userId) return collect();

        $ratedIds = DB::table('ratings_reviews')
            ->where('user_id', $userId)
            ->orderByDesc('created_at')
            ->limit($limit)
            ->pluck('movie_id');

        // ORM for relationships and transformations
        return Movie::with(['genres', 'ratings'])
            ->whereIn('id', $ratedIds)
            ->get()
            ->sortByDesc(function ($m) use ($userId) {
                return optional($m->ratings->where('user_id', $userId)->first())->created_at;
            })
            ->values();
    }

    public static function getTrendingMovies($limit = 5)
    {
        $topIds = DB::table('ratings_reviews')
            ->select('movie_id', DB::raw('COUNT(*) as total_reviews'))
            ->groupBy('movie_id')
            ->orderByDesc('total_reviews')
            ->limit($limit)
            ->pluck('movie_id');

        // ORm for relations
        return Movie::with(['genres', 'country', 'language'])
            ->whereIn('id', $topIds)
            ->get();
    }




    public static function getMovieWithDetails($movieId)
    {
        $movie = Movie::with(['genres', 'country', 'language', 'cast', 'ratings'])->find($movieId);

        if (!$movie) return null;

        $movie->country_name = $movie->country->name ?? 'Unknown';
        $movie->language_name = $movie->language->name ?? 'Unknown';

        return $movie;
    }

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
    public static function splitCastRoles($movie)
    {
        $directors = $movie->cast->filter(fn($p) => strcasecmp($p->pivot->role ?? '', 'Director') === 0)->values();
        $actors = $movie->cast->filter(fn($p) => strcasecmp($p->pivot->role ?? '', 'Cast') === 0)->values();

        return compact('directors', 'actors');
    }

    public static function getRelatedMovies($movie)
    {
        $genreIds = $movie->genres->pluck('id')->toArray();
        if (empty($genreIds)) return collect();

        $relatedIds = DB::table('movie_genres')
            ->select('movie_id', DB::raw('COUNT(*) as match_genres'))
            ->whereIn('genre_id', $genreIds)
            ->where('movie_id', '!=', $movie->id)
            ->groupBy('movie_id')
            ->orderByDesc('match_genres')
            ->limit(20)
            ->pluck('movie_id')
            ->toArray();

        
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






    public static function formatMovies($movies)
    {
        return $movies->map(function ($m) {
            $totalReviews = $m->ratings->count();
            $avg = $totalReviews ? round($m->ratings->avg('rating'), 1) : 0;

            return [
                'id' => $m->id,
                'title' => $m->title ?? 'Untitled',
                'release_year' => $m->release_year,
                'poster_url' => $m->poster_url ?? asset('images/placeholders/sample.jpg'),
                'avg_rating' => $avg,
                'total_reviews' => $totalReviews,
                'country_name' => optional($m->country)->name ?? 'Unknown',
                'language_name' => optional($m->language)->name ?? 'Unknown',
                'genres' => $m->genres->map(fn($g) => ['id' => $g->id, 'name' => $g->name])->toArray(),
                'genre_ids' => $m->genres->pluck('id')->implode(','),
            ];
        });
    }

}
