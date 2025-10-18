<div>
    {{-- trigger the function in livewire --}}
    <button wire:click="toggleFavorite" class="btn {{ $isFavorited ? 'btn-accent' : 'btn-outline btn-accent' }}">
        <i class="bx {{ $isFavorited ? 'bxs-heart' : 'bx-heart' }}"></i>
        <span>{{ $isFavorited ? 'Remove from Favorites' : 'Add to Favorites' }}</span>
        <span class="ml-1 text-sm opacity-70">({{ $favoriteCount }})</span>
    </button>

</div>
