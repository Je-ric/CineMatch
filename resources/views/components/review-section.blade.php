@props(['movie', 'userReview' => null, 'avgRating' => 0, 'totalReviews' => 0])

@php
    $isLoggedIn = auth()->check();
@endphp

<div class="flex items-center gap-4">
    <!-- Rating Summary -->
    <div class="text-center space-y-2">
        <div id="average-stars-{{ $movie->id }}" class="flex justify-center gap-1">
            @for($i = 1; $i <= 5; $i++)
                <i class="bx {{ $i <= floor($avgRating) ? 'bxs-star text-yellow-400' : 'bx-star text-gray-400' }} text-lg"></i>
            @endfor
        </div>
        <div class="text-lg font-bold text-accent">
            <span id="average-rating-{{ $movie->id }}">{{ number_format($avgRating, 1) }}</span>/5
        </div>
        <div class="text-sm text-gray-400">
            <span id="total-reviews-{{ $movie->id }}">{{ $totalReviews }}</span>
            review{{ $totalReviews !== 1 ? 's' : '' }}
        </div>
    </div>

</div>
