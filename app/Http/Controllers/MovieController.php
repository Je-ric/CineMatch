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
        $movies = Movie::with(['genres', 'ratings', 'country', 'language'])->get();

        // Fallback if no movies
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
            ]);
        }

        // Transform for frontend
        $moviesJson = $movies->map(function ($m) {
            $avg = isset($m->ratings) && $m->ratings instanceof \Illuminate\Support\Collection
                ? round($m->ratings->avg('rating') ?? 0, 1)
                : ($m->avg_rating ?? null);

            return [
                'id' => $m->id ?? 0,
                'title' => $m->title ?? 'Untitled',
                'release_year' => $m->release_year ?? null,
                'poster_url' => $m->poster_url ?? asset('images/placeholders/sample1.jpg'),
                'avg_rating' => $avg ?: null,
                'country_name' => $m->country->name ?? 'Unknown',
                'language_name' => $m->language->name ?? 'Unknown',
                'genre_ids' => $m->genres->pluck('id')->implode(',') ?? '',
            ];
        })->toArray();

        // Trending movies
        $trendingCollection = Movie::with(['ratings', 'country', 'language', 'genres'])->get()->map(function ($m) {
            $m->avg_rating = round($m->ratings->avg('rating') ?? 0, 1);
            return $m;
        });

        $trending = $trendingCollection->sortByDesc('avg_rating')->take(6);

        $trendingJson = $trending->map(function ($m) {
            return [
                'id' => $m->id,
                'title' => $m->title,
                'poster_url' => $m->poster_url ?? asset('images/placeholders/sample1.jpg'),
                'release_year' => $m->release_year,
                'avg_rating' => $m->avg_rating ?: null,
                'country_name' => $m->country->name ?? 'Unknown',
                'language_name' => $m->language->name ?? 'Unknown',
                'genre_ids' => $m->genres->pluck('id')->implode(','),
            ];
        })->values()->toArray();

        return view('home', compact('moviesJson', 'trendingJson'));
    }

    public function show($id)
    {
        $movie = Movie::with(['genres', 'country', 'language', 'cast', 'ratings'])->find($id);

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
                'country' => (object)['name' => 'Unknown'],
                'language' => (object)['name' => 'Unknown'],
                'cast' => collect([]),
            ];

            $genres = [];
            $directors = collect([]);
            $actors = collect([]);
            $reviews = collect();
            $related = collect([(object)[
                'id' => 0,
                'title' => 'No related movies found',
                'poster_url' => asset('images/placeholders/no_related.jpg'),
                'release_year' => null,
                'avg_rating' => null,
                'country_name' => null,
                'language_name' => null,
            ]]);

            return view('viewMovie', compact('movie', 'reviews', 'related', 'genres', 'directors', 'actors'))
                ->with(['realReviewCount' => 0, 'avgRating' => null]);
        }

        // Derived attributes
        $movie->country_name = $movie->country->name ?? 'Unknown';
        $movie->language_name = $movie->language->name ?? 'Unknown';
        $genres = $movie->genres->pluck('name')->toArray();

        // Split cast
        $directors = $movie->cast->filter(
            fn($p) =>
            strcasecmp($p->pivot->role ?? '', 'Director') === 0
        )->values();

        $actors = $movie->cast->filter(
            fn($p) =>
            strcasecmp($p->pivot->role ?? '', 'Cast') === 0
        )->values();

        // Reviews
        $reviews = $movie->ratings()->with('user')->latest()->get();
        $realReviewCount = $reviews->count();
        $avgRating = $reviews->isNotEmpty() ? round($reviews->avg('rating'), 1) : null;

        // Related movies (same genre)
        $related = Movie::with(['genres', 'country', 'language', 'ratings'])
            ->get()
            ->reject(fn($m) => $m->id === $movie->id)
            ->filter(fn($m) => $m->genres->pluck('id')->intersect($movie->genres->pluck('id'))->isNotEmpty())
            ->take(6)
            ->map(function ($m) {
                $m->country_name = $m->country->name ?? 'Unknown';
                $m->language_name = $m->language->name ?? 'Unknown';
                $m->avg_rating = round($m->ratings->avg('rating') ?? 0, 1);
                return $m;
            })
            ->values();

        return view('viewMovie', [
            'movie' => $movie,
            'reviews' => $reviews,
            'realReviewCount' => $realReviewCount,
            'avgRating' => $avgRating,
            'relatedMovies' => $related,
            'genres' => $genres,
            'directors' => $directors,
            'actors' => $actors,
        ]);
    }

    // ====================================================================================
    // 💡💡💡💡💡💡💡💡💡💡💡💡💡💡💡💡💡💡💡💡💡💡💡💡💡💡💡💡💡💡💡💡💡💡💡💡💡
    // ====================================================================================
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

        $posterRelative = $this->storeImageToPublic(
            $request->file('poster_file'),
            'uploads/posters',
            $validated['title'],
            $validated['release_year'] ?? null
        );

        $bgRelative = null;
        if ($request->hasFile('background_file')) {
            $bgRelative = $this->storeImageToPublic(
                $request->file('background_file'),
                'uploads/backgrounds',
                $validated['title'],
                $validated['release_year'] ?? null
            );
        }

        $countryId = $this->findOrCreateCountryFromJson($validated['countryName']);
        $languageId = $this->findOrCreateLanguageFromJson($validated['languageName']);

        // Defensive transaction + explicit save
        \DB::beginTransaction();
        try {
            $movie = new Movie([
                'title' => $validated['title'],
                'description' => $validated['description'] ?? null,
                'release_year' => $validated['release_year'] ?? null,
                'trailer_url' => $validated['trailer_url'] ?? null,
                'poster_url' => $posterRelative,
                'background_url' => $bgRelative,
                'country_id' => $countryId,
                'language_id' => $languageId,
            ]);

            if (! $movie->save()) {
                \DB::rollBack();
                \Log::error('Movie::save returned false', ['input' => $validated]);
                return redirect()->back()->withInput()->withErrors(['general' => 'Failed saving movie.']);
            }

            // ensure id present before touching pivots
            if (empty($movie->id)) {
                \DB::rollBack();
                \Log::error('Movie created but id missing', ['movie' => $movie->toArray()]);
                return redirect()->back()->withInput()->withErrors(['general' => 'Failed saving movie (no id).']);
            }

            if (!empty($validated['genres']) && is_array($validated['genres'])) {
                $movie->genres()->sync($validated['genres']);
            }

            \DB::commit();

            return redirect()->route('movies.manage.edit', ['id' => $movie->id])
                ->with('success', 'Movie created. You can now add people and media.');
        } catch (\Throwable $e) {
            \DB::rollBack();
            \Log::error('Failed creating movie', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString(), 'input' => $validated]);
            return redirect()->back()->withInput()->withErrors(['general' => 'Failed saving movie. Check logs.']);
        }
    }

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
            'genres' => 'nullable|array',
            'countryName' => 'required|string',
            'languageName' => 'required|string',
        ]);

        $posterRelative = $movie->poster_url;
        if ($request->hasFile('poster_file')) {
            $posterRelative = $this->storeImageToPublic(
                $request->file('poster_file'),
                'uploads/posters',
                $validated['title'],
                $validated['release_year'] ?? null
            );
        }

        $bgRelative = $movie->background_url;
        if ($request->hasFile('background_file')) {
            $bgRelative = $this->storeImageToPublic(
                $request->file('background_file'),
                'uploads/backgrounds',
                $validated['title'],
                $validated['release_year'] ?? null
            );
        }

        $countryId = $this->findOrCreateCountryFromJson($validated['countryName']);
        $languageId = $this->findOrCreateLanguageFromJson($validated['languageName']);

        $movie->update([
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
            'release_year' => $validated['release_year'] ?? null,
            'trailer_url' => $validated['trailer_url'] ?? null,
            'poster_url' => $posterRelative ?? $movie->poster_url,
            'background_url' => $bgRelative ?? $movie->background_url,
            'country_id' => $countryId,
            'language_id' => $languageId,
        ]);

        // ✅ Only now sync genres
        if (!empty($validated['genres'])) {
            $movie->genres()->sync($validated['genres']);
        } else {
            $movie->genres()->detach();
        }

        return redirect()->route('movies.manage.edit', ['id' => $movie->id])
            ->with('success', 'Movie details updated successfully.');
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

    // ====================================================================================
    // 💡💡💡💡💡💡💡💡💡💡💡💡💡💡💡💡💡💡💡💡💡💡💡💡💡💡💡💡💡💡💡💡💡💡💡💡💡
    // ====================================================================================

    private function storeImageToPublic($file, $folder, $movieTitle, $releaseYear)
    {
        $folder = trim($folder, '/ ');

        // Rename: title_year_timestamp_random.ext
        $safeTitle = Str::slug($movieTitle ?: 'movie');
        $extension = strtolower($file->getClientOriginalExtension() ?: 'jpg');
        $year = $releaseYear ? (int)$releaseYear : date('Y');
        $filename = sprintf(
            '%s_%s_%s_%s.%s',
            $safeTitle,
            $year,
            time(),
            Str::random(6),
            $extension
        );

        $destinationPath = public_path($folder);
        if (!is_dir($destinationPath)) {
            @mkdir($destinationPath, 0775, true);
        }

        // Relative
        // Move uploaded file into /public/uploads/posters/filename.jpg
        $file->move($destinationPath, $filename);

        return $folder . '/' . $filename;
    }

    private function deletePublicFile($relativePath)
    {
        if (empty($relativePath)) {
            return;
        }

        // Normalize relative path and delete
        $cleanPath = ltrim($relativePath, '/ ');
        $absolutePath = public_path($cleanPath);

        if (is_file($absolutePath)) {
            @unlink($absolutePath);
        }
    }

    // ====================================================================================
    // 💡💡💡💡💡💡💡💡💡💡💡💡💡💡💡💡💡💡💡💡💡💡💡💡💡💡💡💡💡💡💡💡💡💡💡💡💡
    // ====================================================================================

    private function findOrCreateCountryFromJson(string $countryName): ?int
    {
        $countryName = trim($countryName);
        if ($countryName === '') return null;

        $jsonPathCandidates = [
            public_path('JSON/countries.json'),
            public_path('JSON/country.json'),
            base_path('JSON/countries.json'),
            base_path('JSON/country.json'),
        ];

        // If JSON exists, we could use it for suggestions only 
        foreach ($jsonPathCandidates as $jsonPath) {
            if (is_file($jsonPath)) {
                break;
            }
        }

        $model = Country::firstOrCreate(['name' => $countryName]);
        return $model->id;
    }

    private function findOrCreateLanguageFromJson(string $languageName): ?int
    {
        $languageName = trim($languageName);
        if ($languageName === '') return null;

        $jsonPathCandidates = [
            public_path('JSON/language.json'),
            public_path('JSON/languages.json'),
            base_path('JSON/language.json'),
            base_path('JSON/languages.json'),
        ];

        foreach ($jsonPathCandidates as $jsonPath) {
            if (is_file($jsonPath)) {
                break;
            }
        }

        $model = Language::firstOrCreate(['name' => $languageName]);
        return $model->id;
    }


    // ====================================================================================
    // 💡💡💡💡💡💡💡💡💡💡💡💡💡💡💡💡💡💡💡💡💡💡💡💡💡💡💡💡💡💡💡💡💡💡💡💡💡
    // ====================================================================================

    public function create()
    {
        $allGenres = Genre::orderBy('name')->get();
        return view('manageMovie', [
            'editing' => false,
            'movie' => null,
            'allGenres' => $allGenres,
        ]);
    }

    public function edit($id)
    {
        $movie = Movie::with(['genres'])->findOrFail($id);
        $allGenres = Genre::orderBy('name')->get();
        return view('manageMovie', [
            'editing' => true,
            'movie' => $movie,
            'allGenres' => $allGenres,
        ]);
    }
}
