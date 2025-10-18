@props(['movie', 'userReview' => null, 'isLoggedIn' => null])

@php
    $isLoggedIn = $isLoggedIn ?? auth()->check();
@endphp

@if ($userReview)
    <button class="btn border-accent bg-transparent relative z-50 text-accent hover:bg-transparent hover:text-white" title="Already reviewed">
        <i class="bx bx-check"></i> Already Reviewed
    </button>
@else
    <button
        id="open-review-modal-{{ $movie->id }}"
        type="button"
        class="btn btn-accent relative z-50"
        {!! !$isLoggedIn ? 'disabled aria-disabled="true" title="Login required"' : '' !!}
        onclick="document.getElementById('review-modal-{{ $movie->id }}').showModal()"
        aria-haspopup="dialog" aria-controls="review-modal-{{ $movie->id }}"
    >
        <i class="bx bx-star"></i> Leave a Review
    </button>
@endif

{{-- Include the modal --}}
<x-review-modal :movie="$movie" :user-review="$userReview" :is-logged-in="$isLoggedIn" />
