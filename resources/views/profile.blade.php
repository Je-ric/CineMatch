@extends('layouts.app')

@section('page-content')
    <div class="max-w-7xl mx-auto px-4 py-8 min-h-screen">

        {{-- Profile --}}
        <div
            class="bg-secondary-bg/90 backdrop-blur-sm border border-border-color rounded-lg p-6 mb-8 flex flex-col md:flex-row items-center md:items-start gap-6">
            <div class="w-24 h-24 bg-accent/20 rounded-full flex items-center justify-center text-4xl text-accent">
                <i class="bx bx-user"></i>
            </div>
            <div class="flex-1">
                <h1 class="text-3xl font-bold text-accent">{{ $user->name ?? $user->username }}</h1>
                <p class="text-text-secondary">{{ $user->email ?? '' }}</p>
                <div class="flex items-center gap-4 mt-2 text-sm text-text-muted">
                    <span><i class="bx bx-heart"></i> {{ is_countable($favorites) ? count($favorites) : 0 }} Favorites</span>
                    <span><i class="bx bx-star"></i> {{ is_countable($rated) ? count($rated) : 0 }} Reviews</span>
                </div>
            </div>
        </div>

        <div class="tab-container flex gap-6 border-b border-gray-300 mb-6">
            <button
                class="tab-link py-2 px-4 text-gray-500 font-medium hover:text-accent transition-colors"
                id="favorites-tab"
                data-tab="favorites"
                onclick="openTab(event, 'favorites')">
                Favorites
            </button>
            <button
                class="tab-link py-2 px-4 text-gray-500 font-medium hover:text-accent transition-colors"
                id="rated-tab"
                data-tab="rated"
                onclick="openTab(event, 'rated')">
                Rated Movies
            </button>
            <button
                class="tab-link py-2 px-4 text-gray-500 font-medium hover:text-accent transition-colors"
                id="recommendations-tab"
                data-tab="recommendations"
                onclick="openTab(event, 'recommendations')">
                Recommendations
            </button>
        </div>


        {{-- Tab --}}
        <div id="favorites" class="tab-content">
            <h2 class="text-xl font-semibold text-accent mb-4 flex items-center gap-2">
                <i class="bx bx-heart text-accent"></i>
                Favorite Movies
                <span class="ml-2 bg-gray-700 text-white text-sm font-semibold px-2 py-1 rounded">
                    {{ is_countable($favorites) ? count($favorites) : 0 }}
                </span>
            </h2>
            <div class="mb-6 space-y-2">
                @if (!empty($favGenres))
                    <div class="flex flex-wrap gap-2">
                        @foreach ($favGenres as $genre)
                            <span class="badge badge-outline border-neutral-700 text-gray-300 mr-2 mb-2">
                                {{ $genre->name }}:
                                <span class="ml-1 font-semibold text-white">{{ $genre->cnt }}</span>
                            </span>
                        @endforeach
                    </div>
                @endif
            </div>

            <section class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 gap-6">
                @forelse ($favorites as $movie)
                    {{-- <x-movie-card :movie="is_array($movie) ? (object) $movie : $movie" /> --}}
                    <x-movie-card :movie="$movie" />
                @empty
                    <div class="text-center py-10 text-gray-400 col-span-full">
                        <i class="bx bx-heart text-5xl mb-2"></i>
                        <div class="text-lg">You haven't favorited any movies yet.</div>
                    </div>
                @endforelse
            </section>
        </div>


        {{-- Tab --}}
        <div id="rated" class="tab-content hidden mt-8">
            <h2 class="text-xl font-semibold text-accent mb-4 flex items-center gap-2">
                <i class="bx bx-star text-accent"></i>
                Rated Movies
                <span class="ml-2 bg-gray-700 text-white text-sm font-semibold px-2 py-1 rounded">
                    {{ is_countable($rated) ? count($rated) : 0 }}
                </span>
            </h2>
            <div class="mb-6 space-y-2">
                @if (!empty($ratedGenres))
                    <div class="flex flex-wrap gap-2">
                        @foreach ($ratedGenres as $genre)
                            <span class="badge badge-outline border-neutral-700 text-gray-300 mr-2 mb-2">
                                {{ $genre->name }}:
                                <span class="ml-1 font-semibold text-white">{{ $genre->cnt }}</span>
                            </span>
                        @endforeach
                    </div>
                @endif
            </div>

            <section class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 gap-6">
                @forelse ($rated as $movie)
                    <x-movie-card :movie="(object) $movie" />
                @empty
                    <div class="text-center py-10 text-gray-400 col-span-full">
                        <i class="bx bx-star text-5xl mb-2"></i>
                        <div class="text-lg">You haven't rated any movies yet.</div>
                    </div>
                @endforelse
            </section>
        </div>

        
        {{-- Tab  --}}
        <div id="recommendations" class="tab-content hidden mt-8">
            {{-- Because you like these genres (from favorites) --}}
            @if (!empty($genreShelvesFav) && count($genreShelvesFav) > 0)
                <h3 class="text-xl font-semibold text-accent mb-4">Because you like these genres</h3>
                @foreach ($genreShelvesFav as $shelf)
                    <h4 class="text-lg font-medium text-white mb-2">{{ $shelf['genre']->name }}</h4>
                    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 gap-6 mb-8">
                        @foreach ($shelf['movies'] as $movie)
                            <x-movie-card :movie="$movie" />
                        @endforeach
                    </div>
                @endforeach
            @endif

            {{-- From genres you rated --}}
            @if (!empty($genreShelvesRated) && count($genreShelvesRated) > 0)
                <h3 class="text-xl font-semibold text-accent mb-4">Recommended from genres you rated</h3>
                @foreach ($genreShelvesRated as $shelf)
                    <h4 class="text-lg font-medium text-white mb-2">{{ $shelf['genre']->name }}</h4>
                    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 gap-6 mb-8">
                        @foreach ($shelf['movies'] as $movie)
                            <x-movie-card :movie="$movie" />
                        @endforeach
                    </div>
                @endforeach
            @endif

            @if ((empty($genreShelvesFav) || count($genreShelvesFav) === 0) && (empty($genreShelvesRated) || count($genreShelvesRated) === 0))
                <div class="text-center py-10 text-gray-400">
                    <i class="bx bx-movie text-5xl mb-2"></i>
                    <div class="text-lg">No recommendations yet — favorite or rate some movies to get recommendations.</div>
                </div>
            @endif
        </div>

    </div>

    @push('scripts')
    {{-- W3Schools --}}
        <script>
            function openTab(evt, tabName) {
                var i, tabContent, tabLinks;

                tabContent = document.getElementsByClassName("tab-content");
                for (i = 0; i < tabContent.length; i++) {
                    tabContent[i].style.display = "none";
                }

                tabLinks = document.getElementsByClassName("tab-link");
                for (i = 0; i < tabLinks.length; i++) {
                    tabLinks[i].classList.remove("active-tab");
                }

                document.getElementById(tabName).style.display = "block";
                evt.currentTarget.classList.add("active-tab");
            }

            document.addEventListener("DOMContentLoaded", function() {
                const firstTab = document.querySelector(".tab-link");
                firstTab.classList.add("active-tab");
                document.getElementById(firstTab.dataset.tab).style.display = "block";
            });


        </script>
    @endpush

@endsection
