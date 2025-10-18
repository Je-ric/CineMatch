@extends('layouts.app')

@section('page-content')
    <div class="max-w-7xl mx-auto px-4 py-8 h-screen">
        {{-- Profile Header --}}
        <div class="bg-secondary-bg/90 backdrop-blur-sm border border-border-color rounded-lg p-6 mb-8">
            <div class="flex items-center gap-6">
                <div class="w-20 h-20 bg-accent/20 rounded-full flex items-center justify-center">
                    <i class="bx bx-user text-3xl text-accent"></i>
                </div>
                <div>
                    <h1 class="text-3xl font-bold text-accent">{{ $user->name ?? $user->username }}</h1>
                    <p class="text-text-secondary">{{ $user->email ?? '' }}</p>
                    <div class="flex items-center gap-4 mt-2 text-sm text-text-muted">
                        <span><i class="bx bx-heart"></i> {{ is_countable($favorites) ? count($favorites) : 0 }}
                            Favorites</span>
                        <span><i class="bx bx-star"></i> {{ is_countable($rated) ? count($rated) : 0 }} Reviews</span>
                    </div>
                </div>
            </div>
        </div>

        {{-- Tabs --}}
        {{-- <div class="tabs tabs-boxed mb-6">
            <a class="tab tab-active" onclick="showTab('favorites', event)">Favorites</a>
            <a class="tab" onclick="showTab('rated', event)">Rated Movies</a>
            <a class="tab" onclick="showTab('recommendations', event)">Recommendations</a>
        </div> --}}

        {{-- Favorites Tab --}}
        <div id="favorites-tab" class="tab-content">
            <div class="mb-6">
                <h2 class="text-2xl font-bold text-accent mb-4">Your Favorite Movies</h2>
                @if (!empty($favGenres) || !empty($favCountries))
                    <div class="mb-4">
                        <h3 class="text-lg font-semibold text-accent mb-2">Your Preferences</h3>
                        <div class="flex flex-wrap gap-2">
                            @foreach ($favGenres as $genre)
                                <span class="badge badge-accent">{{ $genre->name }} ({{ $genre->cnt }})</span>
                            @endforeach
                            @foreach ($favCountries as $country)
                                <span class="badge badge-outline">{{ $country->name }} ({{ $country->cnt }})</span>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>

            @if (!empty($favorites))
                <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-6 gap-6">
                    @foreach ($favorites as $movie)
                        <x-movie-card :movie="$movie" />
                    @endforeach
                </div>
            @else
                <div class="text-center py-12">
                    <i class="bx bx-heart text-6xl text-gray-400 mb-4"></i>
                    <p class="text-text-secondary">No favorite movies yet. Start adding some!</p>
                </div>
            @endif
        </div>

        {{-- Rated Tab --}}
        <div id="rated-tab" class="tab-content hidden">
            <div class="mb-6">
                <h2 class="text-2xl font-bold text-accent mb-4">Movies You've Rated</h2>
                @if (!empty($ratedGenres) || !empty($ratedCountries))
                    <div class="mb-4">
                        <h3 class="text-lg font-semibold text-accent mb-2">Your Rating Patterns</h3>
                        <div class="flex flex-wrap gap-2">
                            @foreach ($ratedGenres as $genre)
                                <span class="badge badge-accent">{{ $genre->name }} ({{ $genre->cnt }})</span>
                            @endforeach
                            @foreach ($ratedCountries as $country)
                                <span class="badge badge-outline">{{ $country->name }} ({{ $country->cnt }})</span>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>

            @if (!empty($rated))
                <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-6 gap-6">
                    @foreach ($rated as $movie)
                        <x-movie-card :movie="(object) $movie" />
                    @endforeach
                </div>
            @else
                <div class="text-center py-12">
                    <i class="bx bx-star text-6xl text-gray-400 mb-4"></i>
                    <p class="text-text-secondary">No rated movies yet. Start reviewing some!</p>
                </div>
            @endif
        </div>

        {{-- Recommendations Tab --}}
        <div id="recommendations-tab" class="tab-content hidden">
            <h2 class="text-2xl font-bold text-accent mb-6">Recommended for You</h2>

            {{-- Based on Favorite Genres --}}
            @if (!empty($recommendationsByGenres))
                <div class="mb-8">
                    <h3 class="text-xl font-semibold text-accent mb-4 flex items-center gap-2">
                        <i class="bx bx-magic-wand"></i> Because you like these genres
                    </h3>
                    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-6 gap-6">
                        @foreach ($recommendationsByGenres as $movie)
                            <x-movie-card :movie="(object) $movie" />
                        @endforeach
                    </div>
                </div>
            @endif

            {{-- Based on Favorite Countries --}}
            @if (!empty($recommendationsByCountries))
                <div class="mb-8">
                    <h3 class="text-xl font-semibold text-accent mb-4 flex items-center gap-2">
                        <i class="bx bx-world"></i> From countries you like
                    </h3>
                    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-6 gap-6">
                        @foreach ($recommendationsByCountries as $movie)
                            <x-movie-card :movie="(object) $movie" />
                        @endforeach
                    </div>
                </div>
            @endif

            {{-- Genre Shelves --}}
            @if (!empty($genreShelves) && is_array($genreShelves))
                @foreach ($genreShelves as $shelf)
                    {{-- $shelf = ['genre' => $genreObject, 'movies' => [ ... ]] --}}
                    <div class="mb-8">
                        <h3 class="text-xl font-semibold text-accent mb-4 flex items-center gap-2">
                            <i class="bx bx-category"></i> Popular in {{ $shelf['genre']->name }}
                        </h3>
                        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-6 gap-6">
                            @foreach ($shelf['movies'] as $movie)
                                <x-movie-card :movie="(object) $movie" />
                            @endforeach
                        </div>
                    </div>
                @endforeach
            @endif

            @if (empty($recommendationsByGenres) && empty($recommendationsByCountries) && empty($topGenres))
                <div class="text-center py-12">
                    <i class="bx bx-search text-6xl text-gray-400 mb-4"></i>
                    <p class="text-text-secondary">Add some favorites and reviews to get personalized recommendations!</p>
                </div>
            @endif
        </div>
    </div>

    {{-- <script>
        function showTab(tabName, ev) {
            // Hide all tabs
            document.querySelectorAll('.tab-content').forEach(tab => {
                tab.classList.add('hidden');
            });

            // Remove active class from all tab buttons
            document.querySelectorAll('.tab').forEach(tab => {
                tab.classList.remove('tab-active');
            });

            // Show selected tab
            const sel = document.getElementById(tabName + '-tab');
            if (sel) sel.classList.remove('hidden');

            // Add active class to clicked tab
            if (ev && ev.currentTarget) ev.currentTarget.classList.add('tab-active');
        }
    </script> --}}
@endsection
