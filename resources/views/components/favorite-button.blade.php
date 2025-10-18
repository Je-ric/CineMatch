@props(['movie', 'isFavorited' => false, 'favoriteCount' => 0])

@php
    $isLoggedIn = auth()->user();
@endphp

{{-- ====================================================================== --}}
{{-- ====================================================================== --}}
{{-- ====================================================================== --}}

@if ($isLoggedIn && $isLoggedIn->role === 'user')
    @php
        $badgeClasses = $isFavorited
            ? 'text-black'
            : 'text-accent';
    @endphp

    <button id="favorite-btn-{{ $movie->id }}" type="button"
        class="btn {{ $isFavorited ? 'btn-accent' : 'btn-outline btn-accent' }}"
        data-movie-id="{{ $movie->id }}"
        data-favorited="{{ $isFavorited ? 'true' : 'false' }}">
        <i class="bx {{ $isFavorited ? 'bxs-heart' : 'bx-heart' }}"></i>
        <span class="fav-text">
            {{ $isFavorited ? 'Remove from Favorites' : 'Add to Favorites' }}
        </span>
        <span class="ml-1 text-sm opacity-70 fav-count {{ $badgeClasses }}">
            ({{ $favoriteCount }})
        </span>
    </button>

{{-- ====================================================================== --}}
{{-- ====================================================================== --}}
{{-- ====================================================================== --}}
@elseif ($isLoggedIn && $isLoggedIn->role === 'admin')
    <button type="button"
        class="btn btn-outline btn-accent tooltip cursor-not-allowed"
        data-tip="Admins cannot add favorites"
        disabled aria-disabled="true">
        <i class="bx bx-heart"></i>
        <span>Favorites disabled for admins</span>
        <span class="ml-1 text-sm opacity-70">({{ $favoriteCount }})</span>
    </button>

{{-- ====================================================================== --}}
{{-- ====================================================================== --}}
{{-- ====================================================================== --}}

@else
    <a href="{{ route('login') }}" class="btn btn-outline btn-accent">
        <i class='bx bx-heart'></i> Login to Favorite
    </a>
@endif

{{-- ====================================================================== --}}
{{-- ====================================================================== --}}
{{-- ====================================================================== --}}
@if ($isLoggedIn)
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const btn = document.getElementById('favorite-btn-{{ $movie->id }}');

            btn.addEventListener('click', function() {
                const movieId = this.dataset.movieId;
                const isFavorited = this.dataset.favorited === 'true';

                fetch('{{ route('favorites.toggle') }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        },
                        body: JSON.stringify({
                            movie_id: movieId
                        })
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            // Update button state
                            const icon = this.querySelector('i');
                            const text = this.querySelector('.fav-text');
                            const badge = this.querySelector('.fav-count');

                            if (data.added) {
                                icon.className = 'bx bxs-heart';
                                text.textContent = 'Remove from Favorites';
                                this.classList.add('btn-active');
                            } else {
                                icon.className = 'bx bx-heart';
                                text.textContent = 'Add to Favorites';
                                this.classList.remove('btn-active');
                            }

                            // Update count
                            badge.textContent = `(${data.totalFavorites})`;

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
