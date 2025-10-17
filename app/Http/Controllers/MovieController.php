<?php

namespace App\Http\Controllers;

use App\Models\Movie;
use App\Models\RatingReview;
use App\Models\Genre;
use App\Models\Country;
use App\Models\Language;
use Illuminate\Support\Collection;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

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

    // Create a movie with public uploads and JSON-validated country/language
    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'release_year' => 'nullable|integer|min:1900|max:' . date('Y'),
            'trailer_url' => 'nullable|string|max:2048',
            'poster_file' => 'required|image|max:5120',
            'background_file' => 'nullable|image|max:8192',
            'genres' => 'array',
            'genres.*' => 'integer|exists:genres,id',
            'countryName' => 'required|string',
            'languageName' => 'required|string',
        ]);

        $posterRelative = $this->storeImageToPublic($request->file('poster_file'), 'uploads/posters', $validated['title'], $validated['release_year'] ?? null);
        $bgRelative = null;
        if ($request->hasFile('background_file')) {
            $bgRelative = $this->storeImageToPublic($request->file('background_file'), 'uploads/backgrounds', $validated['title'], $validated['release_year'] ?? null);
        }

        $movie = Movie::create([
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
            'release_year' => $validated['release_year'] ?? null,
            'poster_url' => $posterRelative,
            'background_url' => $bgRelative,
            'trailer_url' => $validated['trailer_url'] ?? null,
        ]);

        if (!empty($validated['genres'])) {
            $movie->genres()->sync($validated['genres']);
        }

        $countryId = $this->findOrCreateCountryFromJson($validated['countryName']);
        $languageId = $this->findOrCreateLanguageFromJson($validated['languageName']);
        if ($countryId) {
            $movie->countries()->sync([$countryId]);
        }
        if ($languageId) {
            $movie->languages()->sync([$languageId]);
        }

        return redirect()->route('home')->with('success', 'Movie added successfully');
    }

    // Update a movie, optionally replacing images and syncing relations
    public function update(Request $request, $id)
    {
        $movie = Movie::findOrFail($id);

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'release_year' => 'nullable|integer|min:1900|max:' . date('Y'),
            'trailer_url' => 'nullable|string|max:2048',
            'poster_file' => 'nullable|image|max:5120',
            'background_file' => 'nullable|image|max:8192',
            'genres' => 'array',
            'genres.*' => 'integer|exists:genres,id',
            'countryName' => 'required|string',
            'languageName' => 'required|string',
        ]);

        if ($request->hasFile('poster_file')) {
            $newPoster = $this->storeImageToPublic($request->file('poster_file'), 'uploads/posters', $validated['title'], $validated['release_year'] ?? null);
            $this->deletePublicFile($movie->getRawOriginal('poster_url'));
            $movie->poster_url = $newPoster;
        }

        if ($request->hasFile('background_file')) {
            $newBg = $this->storeImageToPublic($request->file('background_file'), 'uploads/backgrounds', $validated['title'], $validated['release_year'] ?? null);
            $this->deletePublicFile($movie->getRawOriginal('background_url'));
            $movie->background_url = $newBg;
        }

        $movie->title = $validated['title'];
        $movie->description = $validated['description'] ?? null;
        $movie->release_year = $validated['release_year'] ?? null;
        $movie->trailer_url = $validated['trailer_url'] ?? null;
        $movie->save();

        if (isset($validated['genres'])) {
            $movie->genres()->sync($validated['genres']);
        }

        $countryId = $this->findOrCreateCountryFromJson($validated['countryName']);
        $languageId = $this->findOrCreateLanguageFromJson($validated['languageName']);
        if ($countryId) {
            $movie->countries()->sync([$countryId]);
        }
        if ($languageId) {
            $movie->languages()->sync([$languageId]);
        }

        return redirect()->route('home')->with('success', 'Movie updated successfully');
    }

    // Delete a movie and clean up files and pivots
    public function destroy($id)
    {
        $movie = Movie::findOrFail($id);
        $this->deletePublicFile($movie->getRawOriginal('poster_url'));
        $this->deletePublicFile($movie->getRawOriginal('background_url'));
        $movie->genres()->detach();
        $movie->countries()->detach();
        $movie->languages()->detach();
        $movie->cast()->detach();
        $movie->delete();
        return response()->json(['success' => true]);
    }

    // --- Helpers ---
    private function storeImageToPublic($file, $subdir, $title, $year)
    {
        // normalize subdir
        $subdir = trim($subdir, '/ ');
        $safeTitle = Str::slug($title ?: 'movie');
        $ext = strtolower($file->getClientOriginalExtension() ?: 'jpg');
        $yearPart = $year ? (int)$year : date('Y');
        // slug_year_timestamp_random.ext
        $name = sprintf('%s_%s_%s_%s.%s', $safeTitle, $yearPart, time(), Str::random(6), $ext);

        $targetDir = public_path($subdir);
        if (!is_dir($targetDir)) {
            @mkdir($targetDir, 0775, true);
        }

        $file->move($targetDir, $name);

        // return relative path WITHOUT leading slash so asset(...) works consistently
        return trim($subdir . '/' . $name, '/');
    }

    private function deletePublicFile($relativePath)
    {
        if (empty($relativePath)) return;
        // accept paths with or without leading slash
        $relativePath = ltrim($relativePath, '/ ');
        $full = public_path($relativePath);
        if (is_file($full)) {
            @unlink($full);
        }
    }

    private function findOrCreateCountryFromJson(string $countryName): ?int
    {
        $countryName = trim($countryName);
        if ($countryName === '') return null;

        // Look in public/JSON (matches manageMovie fetch('JSON/countries.json'))
        $jsonPathCandidates = [
            public_path('JSON/countries.json'),
            public_path('JSON/country.json'),
            base_path('JSON/countries.json'), // keep legacy fallback if present
            base_path('JSON/country.json'),
        ];

        $allowed = [];
        foreach ($jsonPathCandidates as $jsonPath) {
            if (is_file($jsonPath)) {
                $arr = json_decode(file_get_contents($jsonPath), true) ?: [];
                foreach ($arr as $row) {
                    $allowed[] = $row['country'] ?? $row['name'] ?? null;
                }
                break;
            }
        }

        $allowed = array_filter(array_map('strval', $allowed));
        if (!empty($allowed) && !in_array($countryName, $allowed, true)) {
            return null;
        }

        $model = Country::firstOrCreate(['name' => $countryName]);
        return $model->id;
    }

    private function findOrCreateLanguageFromJson(string $languageName): ?int
    {
        $languageName = trim($languageName);
        if ($languageName === '') return null;

        // Look in public/JSON (matches manageMovie fetch('JSON/language.json'))
        $jsonPathCandidates = [
            public_path('JSON/language.json'),
            public_path('JSON/languages.json'),
            base_path('JSON/language.json'),
            base_path('JSON/languages.json'),
        ];

        $allowed = [];
        foreach ($jsonPathCandidates as $jsonPath) {
            if (is_file($jsonPath)) {
                $arr = json_decode(file_get_contents($jsonPath), true) ?: [];
                foreach ($arr as $row) {
                    $allowed[] = $row['name'] ?? $row['language'] ?? null;
                }
                break;
            }
        }

        $allowed = array_filter(array_map('strval', $allowed));
        if (!empty($allowed) && !in_array($languageName, $allowed, true)) {
            return null;
        }

        $model = Language::firstOrCreate(['name' => $languageName]);
        return $model->id;
    }

    /**
     * Show create form for a movie (was previously in routes closure).
     */
    public function create()
    {
        $allGenres = Genre::orderBy('name')->get();
        return view('manageMovie', [
            'editing' => false,
            'movie' => null,
            'allGenres' => $allGenres,
        ]);
    }

    /**
     * Show edit form for a movie (was previously in routes closure).
     */
    public function edit($id)
    {
        $movie = Movie::with(['genres', 'countries', 'languages'])->findOrFail($id);
        $allGenres = Genre::orderBy('name')->get();
        return view('manageMovie', [
            'editing' => true,
            'movie' => $movie,
            'allGenres' => $allGenres,
        ]);
    }
}
