@props(['reviews', 'movie', 'userReview' => null])

@php
    $isLoggedIn = auth()->check();
@endphp

<div class="bg-secondary-bg/90 backdrop-blur-sm border border-border-color rounded-lg p-6">
    <h2 class="text-2xl font-bold mb-6 flex items-center gap-3 text-accent">
        <i class='bx bx-message-dots'></i> User Reviews
        <span class="text-base text-text-secondary">({{ $reviews->count() }})</span>
    </h2>

    <div class="space-y-4 max-h-96 overflow-y-auto pr-2">
        @if($userReview)
            <button class="btn btn-outline btn-disabled" disabled title="Already reviewed">
                <i class="bx bx-check"></i>
                Already Reviewed
            </button>
        @else
            <button
                id="open-review-modal-{{ $movie->id }}"
                class="btn btn-accent"
                {{ !$isLoggedIn ? 'disabled title="Login required"' : '' }}
            >
                <i class="bx bx-star"></i>
                Leave a Review
            </button>
        @endif
        @forelse($reviews as $review)
            <div class="bg-card-bg/50 border border-border-color rounded-lg p-4 hover:bg-card-bg/70 transition-colors {{ $review->user_id === auth()->id() ? '' : '' }}">
                <div class="flex justify-between items-center mb-3">
                    <div class="flex items-center gap-2">
                        <h4 class="font-semibold text-accent">{{ $review->username }}</h4>
                        @if($review->user_id === auth()->id())
                            <span class="badge badge-sm badge-accent">Your Review</span>
                        @endif
                    </div>
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
