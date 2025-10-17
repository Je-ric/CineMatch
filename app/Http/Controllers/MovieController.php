<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;

class MovieController extends Controller
{

    public function index()
    {
        // ✅ Fetch all movies
        $movies = DB::connection('movies')
            ->table('movies')
            ->leftJoin('countries', 'movies.country_id', '=', 'countries.id')
            ->leftJoin('languages', 'movies.language_id', '=', 'languages.id')
            ->leftJoin('ratings_reviews', 'movies.id', '=', 'ratings_reviews.movie_id')
            ->select(
                'movies.id',
                'movies.title',
                'movies.release_year',
                'movies.poster_url',
                'movies.description',
                'countries.name as country_name',
                'languages.name as language_name',
                DB::raw('ROUND(AVG(ratings_reviews.rating), 1) as avg_rating')
            )
            ->groupBy(
                'movies.id',
                'movies.title',
                'movies.release_year',
                'movies.poster_url',
                'movies.description',
                'countries.name',
                'languages.name'
            )
            ->get();

        // ✅ Trending = Top 12 highest-rated movies (with non-null ratings)
        $trending = $movies
            ->whereNotNull('avg_rating')
            ->sortByDesc('avg_rating')
            ->take(12)
            ->values();

        return view('home', [
            'moviesJson' => $movies,
            'trendingJson' => $trending,
        ]);
    }

    public function show($id)
    {
        try {
            // Get movie with basic info
            $movie = DB::connection('mysql_movies')
                ->table('movies')
                ->leftJoin('countries', 'movies.country_id', '=', 'countries.id')
                ->leftJoin('languages', 'movies.language_id', '=', 'languages.id')
                ->select(
                    'movies.*',
                    'countries.name as country_name',
                    'languages.name as language_name'
                )
                ->where('movies.id', $id)
                ->first();

            if (!$movie) {
                abort(404, 'Movie not found');
            }

            // Fix image URLs - add base URL if not already present
            if ($movie->poster_url && !str_starts_with($movie->poster_url, 'http')) {
                $movie->poster_url = asset($movie->poster_url);
            }

            if ($movie->background_url && !str_starts_with($movie->background_url, 'http')) {
                $movie->background_url = asset($movie->background_url);
            }

            // Fix YouTube URL - extract video ID
            if ($movie->trailer_url) {
                // Handle different YouTube URL formats
                if (preg_match('/(?:youtube\.com\/watch\?v=|youtu\.be\/)([^&\s]+)/', $movie->trailer_url, $matches)) {
                    $movie->youtube_id = $matches[1];
                } else {
                    $movie->youtube_id = null;
                }
            }

            // Get genres
            $genres = DB::connection('mysql_movies')
                ->table('movie_genres')
                ->join('genres', 'movie_genres.genre_id', '=', 'genres.id')
                ->where('movie_genres.movie_id', $id)
                ->pluck('genres.name')
                ->toArray();

            // Get directors
            $directors = DB::connection('mysql_movies')
                ->table('movie_cast')
                ->join('movie_people', 'movie_cast.person_id', '=', 'movie_people.id')
                ->where('movie_cast.movie_id', $id)
                ->where('movie_cast.role', 'Director')
                ->select('movie_people.name')
                ->get();

            // Get actors
            $actors = DB::connection('mysql_movies')
                ->table('movie_cast')
                ->join('movie_people', 'movie_cast.person_id', '=', 'movie_people.id')
                ->where('movie_cast.movie_id', $id)
                ->where('movie_cast.role', 'Actor')
                ->select('movie_people.name')
                ->limit(5)
                ->get();

            // Get reviews with usernames
            $reviewsData = DB::connection('mysql_movies')
                ->table('ratings_reviews')
                ->where('movie_id', $id)
                ->whereNotNull('review')
                ->select('user_id', 'rating', 'review', 'created_at')
                ->orderBy('created_at', 'desc')
                ->limit(10)
                ->get();

            // Transform reviews to include username
            $reviews = $reviewsData->map(function($review) {
                $user = DB::table('users')->where('id', $review->user_id)->first();

                $reviewObj = new \stdClass();
                $reviewObj->username = $user ? ($user->name ?? $user->username ?? $user->email ?? 'Anonymous') : 'Anonymous';
                $reviewObj->rating = $review->rating;
                $reviewObj->review = $review->review;

                return $reviewObj;
            });

            // Get related movies with average ratings
            $relatedMovies = DB::connection('mysql_movies')
                ->table('movies')
                ->join('movie_genres', 'movies.id', '=', 'movie_genres.movie_id')
                ->leftJoin('ratings_reviews', 'movies.id', '=', 'ratings_reviews.movie_id')
                ->whereIn('movie_genres.genre_id', function($query) use ($id) {
                    $query->select('genre_id')
                        ->from('movie_genres')
                        ->where('movie_id', $id);
                })
                ->where('movies.id', '!=', $id)
                ->select(
                    'movies.id',
                    'movies.title',
                    'movies.poster_url',
                    'movies.release_year',
                    DB::raw('ROUND(AVG(ratings_reviews.rating), 1) as avg_rating'),
                    DB::raw('COUNT(DISTINCT ratings_reviews.id) as review_count')
                )
                ->groupBy('movies.id', 'movies.title', 'movies.poster_url', 'movies.release_year')
                ->limit(6)
                ->get();

            // Fix related movies poster URLs
            foreach ($relatedMovies as $related) {
                if ($related->poster_url && !str_starts_with($related->poster_url, 'http')) {
                    $related->poster_url = asset($related->poster_url);
                }
            }

            return view('viewMovie', compact('movie', 'genres', 'directors', 'actors', 'reviews', 'relatedMovies'));

        } catch (\Exception $e) {
            return "Error: " . $e->getMessage() . "<br>File: " . $e->getFile() . "<br>Line: " . $e->getLine();
        }
    }
}
