@extends('layouts.app')

@section('page-content')
    <div class="px-6 md:px-10 py-10">

        <div class="mb-8 flex flex-col md:flex-row md:items-center justify-between gap-6">
            <div>
                <h2 class="text-3xl font-bold mb-1">Discover Movies</h2>
                <p class="text-gray-400 text-sm">Find your next favorite film from our curated collection</p>
            </div>

            <div class="flex flex-col sm:flex-row flex-wrap items-center gap-3 md:gap-4 w-full md:w-auto">

                <div
                    class="flex items-center bg-neutral-900 border border-neutral-700 rounded-lg overflow-hidden shadow-sm w-full sm:w-64 md:w-80 h-10">
                    <span class="px-3"><i class="bx bx-search text-gray-400 text-lg"></i></span>
                    <input id="search" type="text" placeholder="Search movies by title..."
                        class="w-full h-full bg-neutral-900 text-gray-200 placeholder-gray-500 focus:outline-none px-2 text-sm">
                </div>

                <select id="sortSelect"
                    class="h-10 rounded-lg bg-neutral-900 border border-neutral-700 text-gray-200 text-sm px-3 min-w-[10rem]">
                    <option value="year_desc">Sort: Year (New → Old)</option>
                    <option value="year_asc">Sort: Year (Old → New)</option>
                    <option value="title_asc">Sort: Title (A→Z)</option>
                    <option value="title_desc">Sort: Title (Z→A)</option>
                </select>

                <select id="genreFilter"
                    class="h-10 rounded-lg bg-neutral-900 border border-neutral-700 text-gray-200 text-sm px-3 min-w-[10rem]">
                    <option value="">All Genres</option>
                    @foreach ($availableGenres ?? [] as $genre)
                        <option value="{{ strtolower($genre) }}">{{ $genre }}</option>
                    @endforeach
                </select>

                <select id="yearFilter"
                    class="h-10 rounded-lg bg-neutral-900 border border-neutral-700 text-gray-200 text-sm px-3 min-w-[7rem]">
                    <option value="">All Years</option>
                    @foreach ($availableYears ?? [] as $year)
                        <option value="{{ $year }}">{{ $year }}</option>
                    @endforeach
                </select>

                <select id="countryFilter"
                    class="h-10 rounded-lg bg-neutral-900 border border-neutral-700 text-gray-200 text-sm px-3 min-w-[9rem]">
                    <option value="">All Countries</option>
                    @foreach ($availableCountries ?? [] as $country)
                        <option value="{{ strtolower($country) }}">{{ $country }}</option>
                    @endforeach
                </select>

                <select id="languageFilter"
                    class="h-10 rounded-lg bg-neutral-900 border border-neutral-700 text-gray-200 text-sm px-3 min-w-[9rem]">
                    <option value="">All Languages</option>
                    @foreach ($availableLanguages ?? [] as $lang)
                        <option value="{{ strtolower($lang) }}">{{ $lang }}</option>
                    @endforeach
                </select>
            </div>
        </div>


        <section id="trendingSection" class="mb-12">
            <div class="border-t border-neutral-800 mb-6"></div>
            <div class="flex items-end justify-between mb-4">
                <h3 class="text-2xl font-semibold">Trending</h3>
            </div>
            <div id="trendingGrid" class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-6 gap-6">
                @foreach ($trendingJson ?? [] as $m)
                    <x-movie-card 
                            :movie="(object) $m" 
                            :is-admin="auth()->check() && (auth()->user()->role ?? null) === 'admin'" />
                @endforeach
            </div>
        </section>

        <div class="border-t border-neutral-800 my-10"></div>
        <section>
            <div class="flex items-end justify-between mb-4">
                <h3 class="text-2xl font-semibold">All movies</h3>
            </div>
            <div id="allGrid" class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-6 gap-6">
                @foreach ($moviesJson ?? [] as $m)
                    <div class="movie-card" data-title="{{ strtolower($m['title']) }}"
                        data-year="{{ $m['release_year'] ?? '' }}"
                        data-country="{{ strtolower($m['country_name'] ?? '') }}"
                        data-language="{{ strtolower($m['language_name'] ?? '') }}"
                        data-genres="{{ implode(',', array_map('strtolower', array_column($m['genres'] ?? [], 'name'))) }}">
                        <x-movie-card :movie="(object) $m" :is-admin="auth()->check() && (auth()->user()->role ?? null) === 'admin'" />
                    </div>
                @endforeach
            </div>
            <div id="allEmpty" class="hidden py-16 text-center">
                <i class='bx bx-search-alt text-5xl text-gray-600 mb-3 block'></i>
                <p class="text-text-muted">No results found. Try adjusting your search or filters.</p>
            </div>
        </section>
    </div>
@endsection

@push('scripts')
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <script>
        $(document).ready(function() {

            function filterMovies() {
                const search = $('#search').val().toLowerCase().trim();
                const sort = $('#sortSelect').val();
                const genreFilter = $('#genreFilter').val()?.toLowerCase();
                const yearFilter = $('#yearFilter').val();
                const countryFilter = $('#countryFilter').val()?.toLowerCase();
                const languageFilter = $('#languageFilter').val()?.toLowerCase();

                let cards = $('#allGrid .movie-card');
                let anyFilterActive = search || genreFilter || yearFilter || countryFilter || languageFilter || (
                    sort && sort !== 'year_desc');

                // Show/hide trending
                if (anyFilterActive) {
                    $('#trendingSection').hide();
                } else {
                    $('#trendingSection').show();
                }

                cards.each(function() {
                    const $card = $(this);
                    const title = $card.data('title') || '';
                    const year = $card.data('year') || '';
                    const country = $card.data('country') || '';
                    const language = $card.data('language') || '';
                    const genres = $card.data('genres') || '';

                    let show = true;

                    if (search && !title.includes(search)) show = false;
                    if (genreFilter && !genres.split(',').includes(genreFilter)) show = false;
                    if (yearFilter && year != yearFilter) show = false;
                    if (countryFilter && country != countryFilter) show = false;
                    if (languageFilter && language != languageFilter) show = false;

                    $card.toggle(show);
                });

                // Show emptry state
                if ($('#allGrid .movie-card:visible').length === 0) {
                    $('#allEmpty').removeClass('hidden');
                } else {
                    $('#allEmpty').addClass('hidden');
                }

                // Sorting
                let sorted = $('#allGrid .movie-card:visible').sort(function(a, b) {
                    const $a = $(a);
                    const $b = $(b);
                    switch (sort) {
                        case 'year_asc':
                            return ($a.data('year') || 0) - ($b.data('year') || 0);
                        case 'year_desc':
                            return ($b.data('year') || 0) - ($a.data('year') || 0);
                        case 'title_asc':
                            return ($a.data('title') || '').localeCompare($b.data('title') || '');
                        case 'title_desc':
                            return ($b.data('title') || '').localeCompare($a.data('title') || '');
                    }
                });

                $('#allGrid').append(sorted); // reorder DOM
            }

            // run whenever change
            $('#search, #sortSelect, #genreFilter, #yearFilter, #countryFilter, #languageFilter').on('input change',
                filterMovies);

        });
    </script>
@endpush
