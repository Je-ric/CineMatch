@props(['reviews', 'movie', 'userReview' => null, 'realReviewCount' => 0])

@php
    $user = auth()->user();
@endphp

<div id="user-review-section" class="bg-secondary-bg/90 backdrop-blur-sm border border-border-color rounded-lg p-6">
    <h2 class="text-2xl font-bold mb-6 flex items-center gap-3 text-accent">
        <i class='bx bx-message-dots'></i> User Reviews
        <span class="text-base text-text-secondary">({{ $realReviewCount }})</span>
    </h2>

    @if ($user && $user->role === 'user')
        @if (!$userReview)
            {{-- <button
                id="open-review-modal-{{ $movie->id }}"
                class="btn btn-accent mb-6"
                onclick="document.getElementById('review-modal-{{ $movie->id }}').showModal()"
            >
                <i class="bx bx-star"></i> Leave a Review
            </button> --}}

        @else
            <div class="bg-accent/10 border border-accent/30 rounded-lg p-4 mb-6">
                <div class="flex justify-between items-start">
                    <div class="flex-1">
                        <h3 class="font-bold text-accent mb-2">Your Review</h3>
                        <p class="text-text-secondary">{{ $userReview->review }}</p>
                    </div>
                    <div class="flex items-center gap-2 ml-4">
                        <div class="star-rating">
                            @for ($i = 1; $i <= 5; $i++)
                                <i class="bx {{ $i <= $userReview->rating ? 'bxs-star text-yellow-400' : 'bx-star text-gray-600' }}"></i>
                            @endfor
                        </div>
                        <span class="text-accent font-semibold">{{ $userReview->rating }}</span>
                    </div>
                </div>
            </div>
        @endif

    @elseif ($user && $user->role === 'admin')
        <div class="bg-blue-500/10 border border-blue-500/30 rounded-lg p-4 mb-6">
            <span class="text-blue-400">Admins cannot leave reviews.</span>
        </div>

    @else
        <div class="bg-orange-500/10 border border-orange-500/30 rounded-lg p-4 mb-6">
            <span class="text-orange-400">
                Please
                <a href="{{ route('login') }}" class="text-accent hover:underline">login</a>
                to leave a review.
            </span>
        </div>
    @endif

    {{-- ALL REVIEWS --}}
    <div id="reviews-container-{{ $movie->id }}" class="space-y-4 max-h-96 overflow-y-auto pr-2">
        @forelse($reviews as $review)
            <div class="bg-accent/10 border border-accent/30 rounded-lg p-4 hover:bg-accent/20 transition-colors">
                <div class="flex justify-between items-start">
                    <div class="flex-1">
                        <div class="flex items-center gap-3 mb-2">
                            <h3 class="font-bold text-accent mb-2">
                                {{ $review->user->name ?? $review->username ?? 'Anonymous' }}
                            </h3>
                            @if ($user && $review->user_id === $user->id)
                                <span class="badge badge-sm badge-accent">Your Review</span>
                            @endif
                        </div>
                        <p class="text-text-secondary">{{ $review->review }}</p>
                    </div>

                    <div class="flex items-center gap-2 ml-4">
                        <div class="star-rating">
                            @for ($i = 1; $i <= 5; $i++)
                                <i class="bx {{ $i <= $review->rating ? 'bxs-star text-yellow-400' : 'bx-star text-gray-600' }}"></i>
                            @endfor
                        </div>
                        <span class="text-accent font-semibold">{{ $review->rating }}</span>
                    </div>
                </div>
            </div>
        @empty
            <p class="text-text-secondary text-sm italic">No reviews yet.</p>
        @endforelse
    </div>
</div>
