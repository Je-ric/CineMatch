<x-layout>
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
        {{-- Movie Background --}}
        @if(!empty($movie->background_url))
            <div class="cover_follow mb-8 absolute top-0 left-0 w-full h-screen -z-10">
                <div class="absolute inset-0 bg-cover"
                    style="background-image: url('{{ $movie->background_url }}');">
                </div>
                <div class="absolute inset-0 bg-gradient-to-t from-primary-bg via-primary-bg/80 to-transparent"></div>
            </div>
        @endif

        {{-- Movie Main Info --}}
        <div class="relative z-10 max-w-6xl mx-auto px-4 py-10">
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                <div class="dp-i-c-poster">
                    <div class="film-poster">
                        <img src="{{ $movie->poster_url }}" alt="{{ $movie->title }} poster" class="film-poster-img rounded-xl shadow-lg">
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

                    {{-- Directors & Cast --}}
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="space-y-4">
                            <div>
                                <h3 class="flex items-center gap-2 text-accent font-semibold text-lg mb-2">
                                    <i class='bx bx-video'></i> Director(s)
                                </h3>
                                <p class="text-text-secondary">
                                    {{ $directors->pluck('name')->implode(' • ') ?: 'N/A' }}
                                </p>
                            </div>

                            <div>
                                <h3 class="flex items-center gap-2 text-accent font-semibold text-lg mb-2">
                                    <i class='bx bxs-user'></i> Cast
                                </h3>
                                <p class="text-text-secondary">
                                    {{ $actors->pluck('name')->implode(' • ') ?: 'N/A' }}
                                </p>
                            </div>
                        </div>

                        {{-- Ratings Summary --}}
                        <div class="text-center space-y-3">
                            @php
                                $avgRating = $reviews->avg('rating') ?? 0;
                                $totalReviews = $reviews->count();
                            @endphp
                            <div id="average-stars" class="star-rating justify-center">
                                @for($i = 0; $i < floor($avgRating); $i++)
                                    <i class="bx bxs-star star text-yellow-400"></i>
                                @endfor
                                @for($i = 0; $i < (5 - floor($avgRating)); $i++)
                                    <i class="bx bx-star star empty"></i>
                                @endfor
                            </div>
                            <div class="text-xl font-bold text-accent">
                                <span id="average-rating">{{ number_format($avgRating, 1) }}</span>/5
                            </div>
                            <div class="text-sm text-text-muted">
                                <span id="total-reviews">{{ $totalReviews }}</span>
                                review{{ $totalReviews !== 1 ? 's' : '' }}
                            </div>
                        </div>
                    </div>

                    {{-- Buttons --}}
                    <div class="flex flex-wrap gap-3">
                        @if(!empty($movie->trailer_url))
                            <a href="#trailer-section" class="btn btn-accent">
                                <i class='bx bx-play'></i> Watch Trailer
                            </a>
                        @endif

                        <button id="favorite-btn" type="button"
                            class="btn btn-outline btn-accent"
                            onclick="alert('Favorite functionality - backend not implemented')">
                            <i class="bx bx-heart"></i>
                            <span class="fav-text">Add to Favorites</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Overview Section --}}
    <section class="relative z-10 max-w-7xl mx-auto px-4 pb-16 space-y-8">
        <div class="bg-secondary-bg/90 backdrop-blur-sm border border-border-color rounded-lg p-6 md:p-8">
            <h2 class="text-2xl md:text-3xl font-bold mb-6 flex items-center gap-3 text-accent">
                <i class='bx bx-detail'></i> Overview
            </h2>
            <p class="text-lg text-text-secondary leading-relaxed">
                {{ $movie->description ?? 'No description available.' }}
            </p>
        </div>

        {{-- Trailer + Reviews --}}
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
            {{-- Trailer --}}
            @if(!empty($movie->trailer_url))
                <div id="trailer-section" class="bg-secondary-bg/90 backdrop-blur-sm border border-border-color rounded-lg p-6">
                    <h2 class="text-2xl font-bold mb-6 text-accent flex items-center gap-3">
                        <i class='bx bx-play-circle'></i> Trailer
                    </h2>
                    <div class="aspect-video rounded-lg overflow-hidden bg-base-300 max-w-lg mx-auto">
                       @if(isset($movie->youtube_id))
                            <iframe class="w-full h-full"
                                    src="https://www.youtube.com/embed/{{ $movie->youtube_id }}"
                                    allowfullscreen></iframe>
                        @else
                            <div class="flex items-center justify-center h-full text-gray-400">
                                <p>Trailer not available</p>
                            </div>
                        @endif
                    </div>
                </div>
            @endif

            {{-- Reviews --}}
            <div class="bg-secondary-bg/90 backdrop-blur-sm border border-border-color rounded-lg p-6">
                <h2 class="text-2xl font-bold mb-6 flex items-center gap-3 text-accent">
                    <i class='bx bx-message-dots'></i> User Reviews
                    <span class="text-base text-text-secondary">({{ $reviews->count() }})</span>
                </h2>

                <button onclick="alert('Review functionality - backend not implemented')"
                        class="btn btn-accent mb-6">
                    <i class='bx bx-star'></i> Leave a Review
                </button>

                <div class="space-y-4 max-h-96 overflow-y-auto">
                    @forelse($reviews as $review)
                        <div class="bg-card-bg/50 border border-border-color rounded-lg p-4 hover:bg-card-bg/70 transition-colors">
                            <div class="flex justify-between items-center mb-3">
                                <h4 class="font-semibold text-accent">{{ $review->username }}</h4>
                                <div class="flex items-center gap-2">
                                    <div class="star-rating">
                                        @for($i = 1; $i <= 5; $i++)
                                            <i class="bx {{ $i <= $review->rating ? 'bxs-star star text-yellow-400' : 'bx-star star empty' }}"
                                            style="font-size: 1rem;"></i>
                                        @endfor
                                    </div>
                                    <span class="text-accent font-semibold text-sm">{{ $review->rating }}</span>
                                </div>
                            </div>
                            <p class="text-text-secondary text-sm">{{ $review->review }}</p>
                        </div>
                    @empty
                        <p class="text-text-secondary text-sm italic">No reviews yet.</p>
                    @endforelse
                </div>
            </div>
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
                <div class="group rounded-xl overflow-hidden bg-neutral-900 border border-neutral-800 hover:border-green-500/70 transition">
                    <div class="relative">
                        <a href="{{ route('movie.show', $related->id) }}">
                            <img src="{{ $related->poster_url }}"
                                class="w-full h-80 object-cover transition-transform duration-300 group-hover:scale-105"
                                alt="{{ $related->title }}">
                        </a>
                        <div class="absolute top-2 right-2">
                            <span class="bg-green-600/90 text-white font-semibold text-xs px-2 py-1 rounded-md flex items-center gap-1">
                                <i class='bx bxs-star text-yellow-300'></i>
                                {{ $related->avg_rating ?? 'N/A' }}
                            </span>
                        </div>
                    </div>
                    <div class="p-4">
                        <h5 class="font-semibold text-base text-white truncate">{{ $related->title }}</h5>
                    </div>
                </div>
            @endforeach
        </div>
    </section>
</x-layout>
