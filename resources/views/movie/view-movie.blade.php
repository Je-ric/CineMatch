@extends('layouts.app')

@section('title', 'View Movie - CineMatch')

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

@section('content')
@php
    // Mock movie data
    $movie = [
        'id' => 1,
        'title' => 'Inception',
        'release_year' => 2010,
        'description' => 'A thief who steals corporate secrets through the use of dream-sharing technology is given the inverse task of planting an idea into the mind of a C.E.O.',
        'poster_url' => 'https://placehold.co/300x450?text=Inception',
        'background_url' => 'https://images.unsplash.com/photo-1478720568477-152d9b164e26?w=1920',
        'trailer_url' => 'https://www.youtube.com/watch?v=YoHD9XEInc0',
        'country_name' => 'United States',
        'language_name' => 'English',
        'avg_rating' => 4.5,
        'total_reviews' => 150,
    ];
    
    $genres = ['Action', 'Thriller', 'Sci-Fi'];
    $directors = [['name' => 'Christopher Nolan']];
    $actors = [['name' => 'Leonardo DiCaprio'], ['name' => 'Tom Hardy'], ['name' => 'Ellen Page']];
    $reviews = [
        ['username' => 'MovieFan123', 'rating' => 5, 'review' => 'Absolutely mind-blowing! A masterpiece.'],
        ['username' => 'CinemaLover', 'rating' => 4, 'review' => 'Great movie with amazing visuals.']
    ];
    $isFavorited = false;
    $favCount = 42;
@endphp

<div class="mx-auto relative z-10">
    @if(!empty($movie['background_url']))
        <div class="cover_follow mb-8 absolute top-0 left-0 w-full h-screen -z-10">
            <div class="absolute inset-0 bg-cover"
                style="background-image: url('{{ $movie['background_url'] }}');">
            </div>
            <div class="absolute inset-0 bg-gradient-to-t from-primary-bg via-primary-bg/80 to-transparent"></div>
        </div>
    @endif

    <div class="relative z-10 max-w-6xl mx-auto px-4 py-10">
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <div class="dp-i-c-poster">
                <div class="film-poster">
                    <img src="{{ $movie['poster_url'] }}" alt="{{ $movie['title'] }} poster" class="film-poster-img">
                </div>
            </div>

            <div class="lg:col-span-2 space-y-6">
                <h1 class="text-3xl md:text-4xl lg:text-5xl font-bold leading-tight">
                    {{ $movie['title'] }}
                    <span class="text-gray-300 text-2xl md:text-3xl ml-2">({{ $movie['release_year'] }})</span>
                </h1>

                <div class="flex flex-wrap items-center gap-3 text-text-secondary text-base md:text-lg">
                    <span>{{ $movie['country_name'] }}</span>
                    <span class="text-accent">•</span>
                    <span>{{ $movie['language_name'] }}</span>
                </div>

                <div class="flex flex-wrap gap-2">
                    @foreach($genres as $genre)
                        <span class="px-3 py-1 bg-accent/20 text-accent rounded-full text-sm font-medium border border-accent/30">
                            {{ $genre }}
                        </span>
                    @endforeach
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="space-y-4">
                        <div>
                            <h3 class="flex items-center gap-2 text-accent font-semibold text-lg mb-2">
                                <i class='bx bx-video'></i> Director(s)
                            </h3>
                            <p class="text-text-secondary">
                                {{ implode(' • ', array_column($directors, 'name')) }}
                            </p>
                        </div>

                        <div>
                            <h3 class="flex items-center gap-2 text-accent font-semibold text-lg mb-2">
                                <i class='bx bxs-user'></i> Cast
                            </h3>
                            <p class="text-text-secondary">
                                {{ implode(' • ', array_column($actors, 'name')) }}
                            </p>
                        </div>
                    </div>

                    <div class="text-center space-y-3">
                        <div id="average-stars" class="star-rating justify-center">
                            @for($i = 0; $i < floor($movie['avg_rating']); $i++)
                                <i class="bx bxs-star star text-yellow-400"></i>
                            @endfor
                            @for($i = 0; $i < (5 - floor($movie['avg_rating'])); $i++)
                                <i class="bx bx-star star empty"></i>
                            @endfor
                        </div>
                        <div class="text-xl font-bold text-accent">
                            <span id="average-rating">{{ number_format($movie['avg_rating'], 1) }}</span>/5
                        </div>
                        <div class="text-sm text-text-muted">
                            <span id="total-reviews">{{ $movie['total_reviews'] }}</span>
                            review{{ $movie['total_reviews'] !== 1 ? 's' : '' }}
                        </div>
                    </div>
                </div>

                <div class="flex flex-wrap gap-3">
                    @if(!empty($movie['trailer_url']))
                        <a href="#trailer-section" class="btn btn-accent">
                            <i class='bx bx-play'></i> Watch Trailer
                        </a>
                    @endif

                    <button id="favorite-btn" type="button"
                        class="btn {{ $isFavorited ? 'btn-accent' : 'btn-outline btn-accent' }}"
                        onclick="alert('Favorite functionality - backend not implemented')">
                        <i class="bx {{ $isFavorited ? 'bxs-heart' : 'bx-heart' }}"></i>
                        <span class="fav-text">{{ $isFavorited ? 'Favorited' : 'Add to Favorites' }}</span>
                        <span class="ml-1 text-sm opacity-70 fav-count">({{ $favCount }})</span>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<section class="relative z-10 max-w-7xl mx-auto px-4 pb-16 space-y-8">
    <div class="bg-secondary-bg/90 backdrop-blur-sm border border-border-color rounded-lg p-6 md:p-8">
        <h2 class="text-2xl md:text-3xl font-bold mb-6 flex items-center gap-3 text-accent">
            <i class='bx bx-detail'></i> Overview
        </h2>
        <p class="text-lg text-text-secondary leading-relaxed">
            {{ $movie['description'] }}
        </p>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
        @if(!empty($movie['trailer_url']))
            <div id="trailer-section" class="bg-secondary-bg/90 backdrop-blur-sm border border-border-color rounded-lg p-6">
                <h2 class="text-2xl font-bold mb-6 text-accent flex items-center gap-3">
                    <i class='bx bx-play-circle'></i> Trailer
                </h2>
                <div class="aspect-video rounded-lg overflow-hidden bg-base-300 max-w-lg mx-auto">
                    <iframe class="w-full h-full" 
                            src="https://www.youtube.com/embed/YoHD9XEInc0" 
                            allowfullscreen></iframe>
                </div>
            </div>
        @endif

        <div class="bg-secondary-bg/90 backdrop-blur-sm border border-border-color rounded-lg p-6">
            <h2 class="text-2xl font-bold mb-6 flex items-center gap-3 text-accent">
                <i class='bx bx-message-dots'></i> User Reviews
                <span class="text-base text-text-secondary">({{ count($reviews) }})</span>
            </h2>

            <button onclick="alert('Review functionality - backend not implemented')" 
                    class="btn btn-accent mb-6">
                <i class='bx bx-star'></i> Leave a Review
            </button>

            <div class="space-y-4 max-h-96 overflow-y-auto">
                @foreach($reviews as $review)
                    <div class="bg-card-bg/50 border border-border-color rounded-lg p-4 hover:bg-card-bg/70 transition-colors">
                        <div class="flex justify-between items-center mb-3">
                            <h4 class="font-semibold text-accent">{{ $review['username'] }}</h4>
                            <div class="flex items-center gap-2">
                                <div class="star-rating">
                                    @for($i = 1; $i <= 5; $i++)
                                        <i class="bx {{ $i <= $review['rating'] ? 'bxs-star star text-yellow-400' : 'bx-star star empty' }}" 
                                           style="font-size: 1rem;"></i>
                                    @endfor
                                </div>
                                <span class="text-accent font-semibold text-sm">{{ $review['rating'] }}</span>
                            </div>
                        </div>
                        <p class="text-text-secondary text-sm">{{ $review['review'] }}</p>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</section>

<section class="relative z-10 max-w-7xl mx-auto px-4 pb-16">
    <div class="flex items-end justify-between mb-4">
        <h2 class="text-2xl md:text-3xl font-bold text-accent">
            Related Movies
        </h2>
    </div>
    <div id="relatedGrid" class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-6 gap-6">
        <!-- Mock related movies -->
        @for($i = 1; $i <= 6; $i++)
            <div class="group rounded-xl overflow-hidden bg-neutral-900 border border-neutral-800 hover:border-green-500/70 transition">
                <div class="relative">
                    <a href="/movie/{{ $i }}">
                        <img src="https://placehold.co/300x450?text=Movie+{{ $i }}" 
                             class="w-full h-80 object-cover transition-transform duration-300 group-hover:scale-105">
                    </a>
                    <div class="absolute top-2 right-2">
                        <span class="bg-green-600/90 text-white font-semibold text-xs px-2 py-1 rounded-md flex items-center gap-1">
                            <i class='bx bxs-star text-yellow-300'></i>4.{{ $i }}
                        </span>
                    </div>
                </div>
                <div class="p-4">
                    <h5 class="font-semibold text-base text-white">Movie Title {{ $i }}</h5>
                </div>
            </div>
        @endfor
    </div>
</section>
@endsection