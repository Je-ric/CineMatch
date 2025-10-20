<?php

namespace App\Helpers;

use App\Models\Movie;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

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
