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
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use App\Helpers\MovieHelper;

class MovieController extends Controller
{

    public function index()
    {
        $movies = Movie::with(['genres', 'ratings', 'country', 'language'])->get();

        $moviesJson = MovieHelper::formatMovies($movies)->toArray();

        $trending = MovieHelper::getTrendingMovies(6);
        $trendingJson = MovieHelper::formatMovies($trending)->toArray();

        $availableYears = $movies->pluck('release_year')->filter()->unique()->sortDesc()->values()->toArray();
        $availableCountries = $movies->pluck('country.name')->filter()->unique()->map(fn($c) => trim($c))->sort()->values()->toArray();
        $availableLanguages = $movies->pluck('language.name')->filter()->unique()->map(fn($l) => trim($l))->sort()->values()->toArray();
        $availableGenres = Genre::orderBy('name')->pluck('name')->map(fn($g) => trim($g))->values()->toArray();

        return view('home', compact(
            'moviesJson',
            'trendingJson',
            'availableYears',
            'availableCountries',
            'availableLanguages',
            'availableGenres'
        ));
    }

    public function show($id)
    {
        $movie = MovieHelper::getMovieWithDetails($id);

        if (!$movie) {
            abort(404, 'Movie not found.');
        }

        $reviews = MovieHelper::getMovieReviews($id);
        $related = MovieHelper::getRelatedMovies($movie);
        $castData = MovieHelper::splitCastRoles($movie);

        return view('viewMovie', [
            'movie' => $movie,
            'reviews' => $reviews['list'],
            'realReviewCount' => $reviews['count'],
            'avgRating' => $reviews['average'],
            'relatedMovies' => $related,
            'genres' => $movie->genres->pluck('name')->toArray(),
            'directors' => $castData['directors'],
            'actors' => $castData['actors'],
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

        DB::beginTransaction();
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
                DB::rollBack();
                Log::error('Movie::save returned false', ['input' => $validated]);
                return redirect()->back()->withInput()->withErrors(['general' => 'Failed saving movie.']);
            }

            // ensure id present before touching pivots
            if (empty($movie->id)) {
                DB::rollBack();
                Log::error('Movie created but id missing', ['movie' => $movie->toArray()]);
                return redirect()->back()->withInput()->withErrors(['general' => 'Failed saving movie (no id).']);
            }

            if (!empty($validated['genres']) && is_array($validated['genres'])) {
                $movie->genres()->sync($validated['genres']);
            }

            DB::commit();

            return redirect()->route('movies.manage.edit', ['id' => $movie->id])
                ->with('success', 'Movie created. You can now add people and media.');
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Failed saving movie', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString(), 'input' => $validated]);
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
        // $movie->countries()->detach();
        // $movie->languages()->detach();
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
        // $safeTitle = Str::slug($movieTitle ?: 'movie');
        $safeTitle = Str::slug($movieTitle ?: 'movie', '_');
        $extension = strtolower($file->getClientOriginalExtension() ?: 'jpg');
        $year = $releaseYear ? (int)$releaseYear : date('Y');
        $filename = sprintf(
            '%s_%s_%s_%s.%s',
            $safeTitle,
            $year,
            time(),
            // Str::random(6),
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
