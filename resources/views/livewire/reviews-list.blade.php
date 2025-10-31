<div id="reviews-container-{{ $movie->id }}" class="space-y-4 max-h-96 overflow-y-auto pr-2">
    @forelse($reviews as $review)
        <div class="bg-accent/10 border border-accent/30 rounded-lg p-4 hover:bg-accent/20 transition-colors">
            <div class="flex justify-between items-start">
                <div class="flex-1">
                    <div class="flex items-center gap-3 mb-2">
                        <h3 class="font-bold text-accent mb-2">{{ $review->user->name ?? $review->username ?? 'Anonymous' }}</h3>
                        @if(auth()->check() && $review->user_id === auth()->id())
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
