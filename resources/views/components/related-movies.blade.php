<section class="relative z-10 max-w-7xl mx-auto px-4 pb-16">
    <div class="flex items-end justify-between mb-4">
        <h2 class="text-2xl md:text-3xl font-bold text-accent">Related Movies</h2>
    </div>

    <div id="relatedGrid" class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 gap-6">
        @if (!empty($relatedMovies) && $relatedMovies->isNotEmpty())
            @foreach ($relatedMovies as $related)
                <x-movie-card :movie="$related" />
            @endforeach
        @else
            <div class="col-span-full text-center text-gray-400 py-10">
                No related movies found.
            </div>
        @endif
    </div>
</section>
