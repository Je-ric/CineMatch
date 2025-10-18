@if ($userReview)
    <button class="btn btn-outline btn-disabled" disabled title="Already reviewed">
        <i class="bx bx-check"></i>
        Already Reviewed
    </button>
@else
    <button id="open-review-modal-{{ $movie->id }}" class="btn btn-accent"
        {{ !$isLoggedIn ? 'disabled title="Login required"' : '' }}>
        <i class="bx bx-star"></i>
        Leave a Review
    </button>
@endif
