<div id="recommendations" class="tab-content hidden mt-8">
    {{-- Because you like these genres (from favorites) --}}
    @if (!empty($genreShelvesFav) && count($genreShelvesFav) > 0)
        <h3 class="text-xl font-semibold text-accent mb-4">Because you like these genres</h3>
        @foreach ($genreShelvesFav as $shelf)
            <h4 class="text-lg font-medium text-white mb-2">{{ $shelf['genre']->name }}</h4>
            <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 gap-6 mb-8">
                @foreach ($shelf['movies'] as $movie)
                    <x-movie-card :movie="$movie" />
                @endforeach
            </div>
        @endforeach
    @endif

    {{-- From genres you rated --}}
    @if (!empty($genreShelvesRated) && count($genreShelvesRated) > 0)
        <h3 class="text-xl font-semibold text-accent mb-4">Recommended from genres you rated</h3>
        @foreach ($genreShelvesRated as $shelf)
            <h4 class="text-lg font-medium text-white mb-2">{{ $shelf['genre']->name }}</h4>
            <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 gap-6 mb-8">
                @foreach ($shelf['movies'] as $movie)
                    <x-movie-card :movie="$movie" />
                @endforeach
            </div>
        @endforeach
    @endif
    
    @if (
        (empty($genreShelvesFav) || count($genreShelvesFav) === 0) &&
            (empty($genreShelvesRated) || count($genreShelvesRated) === 0))
        <div class="text-center py-10 text-gray-400">
            <i class="bx bx-movie text-5xl mb-2"></i>
            <div class="text-lg">No recommendations yet — favorite or rate some movies to get recommendations.</div>
        </div>
    @endif
</div>
