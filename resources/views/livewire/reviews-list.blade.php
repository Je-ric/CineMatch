<div class="bg-slate-800/60 border border-slate-700 rounded-xl p-4 max-h-[28rem] overflow-y-auto space-y-4 scrollbar-thin scrollbar-thumb-accent/60 scrollbar-track-slate-700/40 hover:scrollbar-thumb-accent/80 transition">
    <h2 class="text-2xl font-bold mb-6 text-accent flex items-center gap-3">
        <i class='bx bx-message-dots'></i> Reviews
    </h2>
    @forelse($reviews as $review)
        <div class="bg-accent/10 border border-accent/30 rounded-lg p-4 hover:bg-accent/20 transition-colors">
            <div class="flex justify-between items-start">
                <div class="flex-1">
                    <div class="flex items-center gap-3 mb-2">
                        <h3 class="font-bold text-accent">{{ $review->user->name ?? $review->username ?? 'Anonymous' }}</h3>
                        @if(auth()->check() && $review->user_id === auth()->id())
                            <span class="badge badge-sm badge-accent">Your Review</span>
                        @endif
                    </div>
                    <p class="text-gray-300 leading-snug">{{ $review->review }}</p>
                </div>
                <div class="flex items-center gap-2 ml-4 shrink-0">
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
        <p class="text-gray-400 text-sm italic text-center">No reviews yet.</p>
    @endforelse
</div>
