@props(['movie', 'isFavorited' => false, 'favoriteCount' => 0])

@php
    $isLoggedIn = auth()->check();
@endphp

<div class="flex items-center gap-2">
    <button
        id="favorite-btn-{{ $movie->id }}"
        type="button"
        class="btn btn-outline btn-accent {{ $isFavorited ? 'btn-active' : '' }}"
        {{ !$isLoggedIn ? 'disabled title="Login required"' : '' }}
        data-movie-id="{{ $movie->id }}"
        data-favorited="{{ $isFavorited ? 'true' : 'false' }}"
    >
        <i class="bx {{ $isFavorited ? 'bxs-heart' : 'bx-heart' }}"></i>
        <span class="fav-text">
            {{ $isFavorited ? 'Remove from Favorites' : 'Add to Favorites' }}
        </span>
        <span class="badge badge-sm">{{ $favoriteCount }}</span>
    </button>
</div>

@if($isLoggedIn)
<script>
document.addEventListener('DOMContentLoaded', function() {
    const btn = document.getElementById('favorite-btn-{{ $movie->id }}');

    btn.addEventListener('click', function() {
        const movieId = this.dataset.movieId;
        const isFavorited = this.dataset.favorited === 'true';

        fetch('{{ route("favorites.toggle") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify({ movie_id: movieId })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Update button state
                const icon = this.querySelector('i');
                const text = this.querySelector('.fav-text');
                const badge = this.querySelector('.badge');

                if (data.added) {
                    icon.className = 'bx bxs-heart';
                    text.textContent = 'Remove from Favorites';
                    this.classList.add('btn-active');
                } else {
                    icon.className = 'bx bx-heart';
                    text.textContent = 'Add to Favorites';
                    this.classList.remove('btn-active');
                }

                // Update count badge
                badge.textContent = data.totalFavorites;

                // Update data attribute
                this.dataset.favorited = data.added ? 'true' : 'false';

                console.log('Favorite toggled successfully');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Failed to update favorite');
        });
    });
});
</script>
@endif
