<?php

namespace App\Helpers;

use App\Models\Movie;
use App\Models\Genre;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use App\Helpers\MovieHelper;

class RecommendHelper
{
    // - RecommendController::basedOnFavoriteGenres
    // - ProfileController::getRecommendationsData (favorites-based recommendations)
    // - profile.blade.php (recommendation shelves)
    public static function basedOnFavoriteGenres(int $userId, int $limit = 12)
    {
        if (!$userId) return collect();

        // Top genres from favorites
        $topGenreIds = DB::table('movie_genre')
            ->join('user_favorites', 'movie_genre.movie_id', '=', 'user_favorites.movie_id')
            ->where('user_favorites.user_id', $userId)
            ->select('movie_genre.genre_id', DB::raw('COUNT(*) as fav_count'))
            ->groupBy('movie_genre.genre_id')
            ->orderByDesc('fav_count')
            ->limit(5)
            ->pluck('genre_id');

        if ($topGenreIds->isEmpty()) return collect();

        // ORM for related movies
        $movies = Movie::with(['genres', 'country', 'language', 'ratings'])
            ->whereHas('genres', fn($q) => $q->whereIn('genres.id', $topGenreIds))
            ->whereDoesntHave('favoritedBy', fn($q) => $q->where('user_id', $userId))
            ->get()
            ->map(function ($m) use ($topGenreIds) {
                $m->match_genres = $m->genres->pluck('id')->intersect($topGenreIds)->count();
                $m->avg_rating = $m->ratings->avg('rating') ? round($m->ratings->avg('rating'), 1) : 0;
                return $m;
            })
            ->sortByDesc(fn($m) => [$m->match_genres, $m->avg_rating ?? 0, $m->release_year ?? 0])
            ->take($limit)
            ->values();

        return MovieHelper::formatMovies($movies);
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

        if ($source === 'rated') {
            $topGenres = self::getTopGenresFromRatings($userId, $topLimit);
        } else {
            $topGenres = self::getTopGenresFromFavorites($userId, $topLimit);
        }

        if ($topGenres->isEmpty()) return [];

        $shelves = $topGenres->map(function ($genre) use ($userId, $perGenre) {
            $movies = Movie::with(['genres', 'country', 'language', 'ratings'])
                ->whereHas('genres', fn($q) => $q->where('genres.id', $genre->id))
                ->whereDoesntHave('favoritedBy', fn($q) => $q->where('user_id', $userId))
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

        // $query = Genre::withCount([
        //     'movies as cnt' => function ($q) use ($userId) {
        //         $q->whereHas('ratings', function ($r) use ($userId) {
        //             $r->where('user_id', $userId);
        //         });
        //     }
        // ])
        // ->having('cnt', '>', 0)
        // ->orderByDesc('cnt')
        // ->get()
        // ->map(function ($g) {
        //     return (object) [
        //         'id' => $g->id,
        //         'name' => $g->name,
        //         'cnt' => (int) $g->cnt
        //     ];
        // })
        // ->values();

        // return $query;
    }
}
