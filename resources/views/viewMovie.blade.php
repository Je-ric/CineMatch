@extends('layouts.app')

@section('page-content')

    @push('styles')
        <style>
            .star-rating {
                display: inline-flex;
                gap: 2px;
            }
            .star {
                font-size: 1.5rem;
            }
            .star.empty {
                color: #64748b;
            }
    </style>
    @endpush

    <div class="mx-auto relative z-10">
        @if(!empty($movie->background_url))
            <div class="cover_follow mb-8 absolute top-0 left-0 w-full h-screen -z-10">
                <div class="absolute inset-0 bg-cover"
                    style="background-image: url('{{ $movie->background_url }}');">
                </div>

                <div class="absolute inset-0 bg-gradient-to-t from-primary-bg via-primary-bg/80 to-transparent"></div>
            </div>
        @endif

        <div class="relative z-10 max-w-6xl mx-auto px-4 py-10">
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                {{-- <div class="dp-i-c-poster">
                    <div class="film-poster">
                        <img src="{{ $movie->poster_url }}" alt="{{ $movie->title }} poster" class="film-poster-img rounded-xl shadow-lg">
                    </div>
                </div> --}}

                <div class="dp-i-c-poster">
                    <div class="film-poster">
                        <img src="{{ $movie->poster_url }}"
                            alt="{{ $movie->title }} poster"
                            class="film-poster-img">
                    </div>
                </div>

                <div class="lg:col-span-2 space-y-6">
                    <h1 class="text-3xl md:text-4xl lg:text-5xl font-bold leading-tight">
                        {{ $movie->title }}
                        <span class="text-gray-300 text-2xl md:text-3xl ml-2">({{ $movie->release_year }})</span>
                    </h1>

                    <div class="flex flex-wrap items-center gap-3 text-text-secondary text-base md:text-lg">
                        <span>{{ $movie->country_name }}</span>
                        <span class="text-accent">•</span>
                        <span>{{ $movie->language_name }}</span>
                    </div>

                    {{-- Genres --}}
                    <div class="flex flex-wrap gap-2">
                        @foreach($genres as $genre)
                            <span class="px-3 py-1 bg-accent/20 text-accent rounded-full text-sm font-medium border border-accent/30">
                                {{ $genre }}
                            </span>
                        @endforeach
                    </div>

                    {{-- Directors & Cast with Rating Summary --}}
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="space-y-4">
                            <div>
                                <h3 class="flex items-center gap-2 text-accent font-semibold text-lg mb-2">
                                    <i class='bx bx-video'></i> Director(s)
                                </h3>
                                <p class="text-text-secondary">
                                    {{ (is_array($directors) ? implode(' • ', array_column($directors, 'name')) : $directors->pluck('name')->implode(' • ')) ?: 'N/A' }}
                                </p>
                            </div>

                            <div>
                                <h3 class="flex items-center gap-2 text-accent font-semibold text-lg mb-2">
                                    <i class='bx bxs-user'></i> Cast
                                </h3>
                                <p class="text-text-secondary">
                                    {{ (is_array($actors) ? implode(' • ', array_column($actors, 'name')) : $actors->pluck('name')->implode(' • ')) ?: 'N/A' }}
                                </p>
                            </div>
                        </div>

                        {{-- Rating Summary --}}
                        <div class="text-center space-y-3">
                            @if(auth()->check())
                                <x-review-section
                                    :movie="$movie"
                                    :user-review="$reviews->where('user_id', auth()->id())->first()"
                                    :avg-rating="$reviews->avg('rating') ?? 0"
                                    :total-reviews="$reviews->count()"
                                />
                            @endif
                        </div>

                    </div>

                    <div class="flex flex-wrap gap-3">
                        @if(!empty($movie->trailer_url))
                            <a href="#trailer-section" class="btn btn-accent">
                                <i class='bx bx-play'></i> Watch Trailer
                            </a>
                        @endif

                        {{-- <x-favorite-button
                            :movie="$movie"
                            :is-favorited="auth()->check() && auth()->user()->favorites()->wherePivot('movie_id', $movie->id)->exists()"
                            :favorite-count="$movie->favoritedBy()->count()"
                        /> --}}
                       @livewire('favorite-button', [
                            'movie' => $movie,
                            'isFavorited' => auth()->check() && auth()->user()->favorites()->wherePivot('movie_id', $movie->id)->exists(),
                            'favoriteCount' => $movie->favoritedBy()->count()
                        ])

                            <x-review-button
                :movie="$movie"
                :user-review="$reviews->where('user_id', auth()->id())->first()"
            />
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Overview --}}
    <section class="relative z-10 max-w-7xl mx-auto px-4 pb-16 space-y-8">
        <div class="bg-secondary-bg/90 backdrop-blur-sm border border-border-color rounded-lg p-6 md:p-8">
            <h2 class="text-2xl md:text-3xl font-bold mb-6 flex items-center gap-3 text-accent">
                <i class='bx bx-detail'></i> Overview
            </h2>
            <p class="text-lg text-text-secondary leading-relaxed">
                {{ $movie->description ?? 'No description available.' }}
            </p>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
            {{-- Trailer --}}
            <x-trailer-section :movie="$movie" />
            
            {{-- Reviews --}}
            <x-reviews-list :reviews="$reviews" :movie="$movie" />
        </div>
    </section>

    {{-- Related Movies --}}
    <section class="relative z-10 max-w-7xl mx-auto px-4 pb-16">
        <div class="flex items-end justify-between mb-4">
            <h2 class="text-2xl md:text-3xl font-bold text-accent">
                Related Movies
            </h2>
        </div>

        <div id="relatedGrid" class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-6 gap-6">
            @foreach($relatedMovies ?? [] as $related)
                <x-movie-card :movie="$related" />
            @endforeach
        </div>
    </section>


@endsection
