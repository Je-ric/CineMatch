<?php

namespace App\Http\Controllers;

use App\Models\Movie;
use App\Models\RatingReview;
use Illuminate\Support\Collection;

class MovieController extends Controller
{
    public function index()
    {
        // eager load relations so views/controllers don't need raw queries
        $movies = Movie::with(['genres', 'countries', 'languages', 'ratings'])->get();

        // If no movies, provide fallback placeholders so the UI has something to render
        if ($movies->isEmpty()) {
            $movies = collect([
                (object)[
                    'id' => 0,
                    'title' => 'Sample Movie — Add real data',
                    'release_year' => date('Y'),
                    'poster_url' => asset('images/placeholders/sample1.jpg'),
                    'avg_rating' => null,
                    'country_name' => 'Unknown',
                    'language_name' => 'Unknown',
                    'genre_ids' => '',
                ],
                (object)[
                    'id' => 1,
                    'title' => 'Another Sample Movie',
                    'release_year' => date('Y'),
                    'poster_url' => asset('images/placeholders/sample2.jpg'),
                    'avg_rating' => null,
                    'country_name' => 'Unknown',
                    'language_name' => 'Unknown',
                    'genre_ids' => '',
                ],
            ]);
        }

        // Normalize to simple arrays for blade JS consumption
        $moviesJson = $movies->map(function ($m) {
            // if $m is an Eloquent model it will have relationships; placeholders are stdClass above
            $avg = null;
            if (isset($m->ratings) && $m->ratings instanceof Collection) {
                $avg = $m->ratings->avg('rating');
                $avg = $avg !== null ? round($avg, 1) : null;
            } elseif (isset($m->avg_rating)) {
                $avg = $m->avg_rating;
            }

            return [
                'id' => $m->id ?? 0,
                'title' => $m->title ?? 'Untitled',
                'release_year' => $m->release_year ?? null,
                // Accessor on Movie model will turn relative urls into full asset() urls
                'poster_url' => $m->poster_url ?? ($m->poster ?? asset('images/placeholders/sample1.jpg')),
                'avg_rating' => $avg,
                'country_name' => isset($m->countries) && $m->countries->first() ? $m->countries->first()->name : ($m->country_name ?? null),
                'language_name' => isset($m->languages) && $m->languages->first() ? $m->languages->first()->name : ($m->language_name ?? null),
                'genre_ids' => isset($m->genres) ? $m->genres->pluck('id')->implode(',') : ($m->genre_ids ?? ''),
            ];
        })->toArray();

        // Trending - choose top-rated then fallback to recent; if empty provide placeholder
        $trendingCollection = Movie::with(['ratings'])->get()->map(function ($m) {
            $avg = $m->ratings->avg('rating');
            $m->avg_rating = $avg !== null ? round($avg, 1) : null;
            return $m;
        });

        $trending = $trendingCollection->sortByDesc('avg_rating')->take(6);

        if ($trending->isEmpty()) {
            $trending = collect([
                [
                    'id' => 0,
                    'title' => 'No trending movies yet',
                    'poster_url' => asset('images/placeholders/no_trending.jpg'),
                    'release_year' => null,
                    'avg_rating' => null,
                    'country_name' => null,
                    'language_name' => null,
                    'genre_ids' => '',
                ],
            ]);
        } else {
            $trending = $trending->map(function ($m) {
                return [
                    'id' => $m->id,
                    'title' => $m->title,
                    'poster_url' => $m->poster_url ?? asset('images/placeholders/sample1.jpg'),
                    'release_year' => $m->release_year,
                    'avg_rating' => $m->avg_rating,
                    'country_name' => $m->countries->first()?->name ?? null,
                    'language_name' => $m->languages->first()?->name ?? null,
                    'genre_ids' => $m->genres->pluck('id')->implode(','),
                ];
            })->toArray();
        }

        $trendingJson = is_array($trending) ? $trending : $trending->toArray();

        return view('home', compact('moviesJson', 'trendingJson'));
    }

    public function show($id)
    {
        $movie = Movie::with(['genres', 'countries', 'languages', 'cast', 'ratings'])->find($id);

        // If movie not found, provide a friendly placeholder object
        if (!$movie) {
            $movie = (object)[
                'id' => 0,
                'title' => 'Movie not found',
                'description' => 'This movie does not exist yet. Add real data in the admin panel.',
                'release_year' => null,
                'poster_url' => asset('images/placeholders/sample1.jpg'),
                'background_url' => asset('images/placeholders/background.jpg'),
                'trailer_url' => null,
                'genres' => collect([]),
                'countries' => collect([]),
                'languages' => collect([]),
                'cast' => collect([]),
            ];
        }

        // Reviews - if none, add a placeholder review
        $reviews = RatingReview::where('movie_id', $id)->with('user')->get();
        if ($reviews->isEmpty()) {
            $reviews = collect([(object)[
                'username' => 'No reviews yet',
                'rating' => null,
                'review' => 'Be the first to review this movie.',
            ]]);
        } else {
            // Normalize review objects with username
            $reviews = $reviews->map(function ($r) {
                return (object)[
                    'username' => $r->user?->name ?? $r->user?->username ?? 'Anonymous',
                    'rating' => $r->rating,
                    'review' => $r->review,
                ];
            });
        }

        // Related movies by shared genres; fallback placeholder if none
        $related = collect();
        if (isset($movie->genres) && $movie->genres->isNotEmpty()) {
            $genreIds = $movie->genres->pluck('id')->toArray();
            $related = Movie::whereHas('genres', function ($q) use ($genreIds) {
                $q->whereIn('genres.id', $genreIds);
            })->where('id', '!=', $movie->id)->take(6)->get();
        }

        if ($related->isEmpty()) {
            $related = collect([(object)[
                'id' => 0,
                'title' => 'No related movies found',
                'poster_url' => asset('images/placeholders/no_related.jpg'),
                'release_year' => null,
                'avg_rating' => null,
            ]]);
        }

        return view('viewMovie', [
            'movie' => $movie,
            'reviews' => $reviews,
            'relatedMovies' => $related,
        ]);
    }
}
