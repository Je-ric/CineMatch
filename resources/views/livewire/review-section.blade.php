<div class="flex items-center gap-4">
    <div class="text-center space-y-2">
        <div class="flex justify-center gap-1">
            @for($i = 1; $i <= 5; $i++)
                <i class="bx {{ $i <= floor($avgRating) ? 'bxs-star text-yellow-400' : 'bx-star text-gray-400' }} text-lg"></i>
            @endfor
        </div>
        <div class="text-lg font-bold text-accent">
            {{ number_format($avgRating, 1) }}/5
        </div>
        <div class="text-sm text-gray-400">
            {{ $totalReviews }} review{{ $totalReviews !== 1 ? 's' : '' }}
        </div>
    </div>

    @if (Auth::check() && Auth::user()->role !== 'admin')
        @if($userReview)
            <button class="btn border-accent bg-transparent text-accent cursor-not-allowed" disabled>
                <i class="bx bx-check"></i> Already Reviewed
            </button>
        @else
            <button class="btn btn-accent" wire:click="$dispatch('openReviewModal')">
                <i class="bx bx-star"></i> Leave a Review
            </button>
            @push('modals')
                <livewire:review-modal :movie="$movie" />
            @endpush
        @endif
    @endif
    
</div>
