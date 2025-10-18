@extends('layouts.app')

@section('page-content')
    <div class="px-6 md:px-10 py-10">
        <div class="mb-8 flex flex-col md:flex-row items-start md:items-center justify-between gap-4">
            <div>
                <h2 class="text-3xl font-bold mb-1">Discover Movies</h2>
                <p class="text-gray-400 text-sm">Find your next favorite film from our curated collection</p>
            </div>
            @if(auth()->check() && (auth()->user()->role ?? null) === 'admin')
                <div class="mb-4 md:mb-0">
                    <a href="{{ route('movies.manage.create') }}" class="btn btn-accent">
                        <i class="bx bx-plus"></i> Add New Movie
                    </a>
                </div>
            @endif
            <div class="flex flex-col sm:flex-row gap-3 w-full md:w-auto">
                <label
                    class="flex items-center w-full md:w-96 bg-neutral-900 border border-neutral-700 rounded-lg overflow-hidden shadow-sm">
                    <span class="px-3"><i class="bx bx-search text-gray-400 text-lg"></i></span>
                    <input id="search" type="text" placeholder="Search movies by title..."
                        class="w-full bg-neutral-900 text-gray-200 placeholder-gray-500 focus:outline-none px-2 py-2 text-sm">
                </label>
                <select id="sortSelect"
                    class="select select-bordered select-sm bg-neutral-900 border-neutral-700 text-gray-200">
                    <option value="year_desc">Sort: Year (New → Old)</option>
                    <option value="year_asc">Sort: Year (Old → New)</option>
                    <option value="title_asc">Sort: Title (A→Z)</option>
                    <option value="title_desc">Sort: Title (Z→A)</option>
                </select>
                <select id="genreFilter"
                    class="select select-bordered select-sm bg-neutral-900 border-neutral-700 text-gray-200 min-w-48">
                    <option value="">All Genres</option>
                </select>
                <select id="yearFilter"
                    class="select select-bordered select-sm bg-neutral-900 border-neutral-700 text-gray-200 min-w-32">
                    <option value="">All Years</option>
                </select>
                <select id="countryFilter"
                    class="select select-bordered select-sm bg-neutral-900 border-neutral-700 text-gray-200 min-w-40">
                    <option value="">All Countries</option>
                </select>
            </div>
        </div>

        <!-- Trending Section -->
        <section id="trendingSection" class="mb-12">
            <div class="border-t border-neutral-800 mb-6"></div>
            <div class="flex items-end justify-between mb-4">
                <h3 class="text-2xl font-semibold">Trending now</h3>
            </div>
            <div id="trendingGrid" class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-6 gap-6">
                @if (auth()->check() && (auth()->user()->role ?? null) === 'admin')
                    @foreach($trendingJson ?? [] as $m)
                        <x-movie-card :movie="(object) $m" :is-admin="true" />
                    @endforeach
                @else
                    @foreach($trendingJson ?? [] as $m)
                        <x-movie-card :movie="(object) $m" />
                        {{-- array --}}
                    @endforeach
                @endif
                {{-- @foreach($trendingJson ?? [] as $m)
                    <x-movie-card :movie="(object) $m" :is-admin="auth()->check() && (auth()->user()->role ?? null) === 'admin'" />
                @endforeach --}}
            </div>
        </section>

        <!-- All Movies Section -->
        <div class="border-t border-neutral-800 my-10"></div>
        <section>
            <div class="flex items-end justify-between mb-4">
                <h3 class="text-2xl font-semibold">All movies</h3>
            </div>
            <div id="allGrid" class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-6 gap-6">
                @foreach($moviesJson ?? [] as $m)
                    <x-movie-card :movie="(object) $m" :is-admin="auth()->check() && (auth()->user()->role ?? null) === 'admin'" />
                @endforeach
            </div>
            <div id="allEmpty" class="hidden py-16 text-center">
                <i class='bx bx-search-alt text-5xl text-gray-600 mb-3 block'></i>
                <p class="text-text-muted">No results found. Try adjusting your search or filters.</p>
            </div>
        </section>
    </div>

    @push('scripts')
        <script>
            // Simple search and filter functionality
            document.addEventListener('DOMContentLoaded', function() {
                const searchInput = document.getElementById('search');
                const sortSelect = document.getElementById('sortSelect');
                const genreFilter = document.getElementById('genreFilter');
                const yearFilter = document.getElementById('yearFilter');
                const countryFilter = document.getElementById('countryFilter');

                const allMovies = @json($moviesJson);
                const trendingMovies = @json($trendingJson);

                function filterMovies() {
                    const searchTerm = searchInput.value.toLowerCase();
                    const selectedYear = yearFilter.value;
                    const selectedCountry = countryFilter.value;
                    const selectedGenre = genreFilter.value;
                    const sortBy = sortSelect.value;

                    let filtered = allMovies.filter(movie => {
                        const matchesSearch = !searchTerm || movie.title.toLowerCase().includes(searchTerm);
                        const matchesYear = !selectedYear || movie.release_year == selectedYear;
                        const matchesCountry = !selectedCountry || movie.country_name === selectedCountry;
                        const matchesGenre = !selectedGenre || (movie.genre_ids && movie.genre_ids.split(',').includes(selectedGenre));

                        return matchesSearch && matchesYear && matchesCountry && matchesGenre;
                    });

                    // Sort movies
                    if (sortBy === 'year_desc') {
                        filtered.sort((a, b) => (b.release_year || 0) - (a.release_year || 0));
                    } else if (sortBy === 'year_asc') {
                        filtered.sort((a, b) => (a.release_year || 0) - (b.release_year || 0));
                    } else if (sortBy === 'title_asc') {
                        filtered.sort((a, b) => (a.title || '').localeCompare(b.title || ''));
                    } else if (sortBy === 'title_desc') {
                        filtered.sort((a, b) => (b.title || '').localeCompare(a.title || ''));
                    }

                    // Show/hide trending section
                    const hasFilters = searchTerm || selectedYear || selectedCountry || selectedGenre || sortBy !== 'year_desc';
                    document.getElementById('trendingSection').style.display = hasFilters ? 'none' : 'block';

                    // Show empty state if no results
                    const emptyState = document.getElementById('allEmpty');
                    emptyState.style.display = filtered.length === 0 ? 'block' : 'none';

                    console.log(`Filtered ${filtered.length} movies`);
                }

                // Add event listeners
                [searchInput, sortSelect, genreFilter, yearFilter, countryFilter].forEach(element => {
                    element.addEventListener('input', filterMovies);
                    element.addEventListener('change', filterMovies);
                });

                // Populate filters
                const years = [...new Set(allMovies.map(m => m.release_year).filter(Boolean))].sort((a, b) => b - a);
                const countries = [...new Set(allMovies.map(m => m.country_name).filter(Boolean))].sort();

                years.forEach(year => {
                    const option = document.createElement('option');
                    option.value = year;
                    option.textContent = year;
                    yearFilter.appendChild(option);
                });

                countries.forEach(country => {
                    const option = document.createElement('option');
                    option.value = country;
                    option.textContent = country;
                    countryFilter.appendChild(option);
                });

                console.log('Home page filters initialized');
            });
        </script>
        @endpush
@endsection

{{-- hindi pa ayos yung sa admin side HAHAAHAHAAAA --}}


{{-- hindi pa ayos yung trending --}}
{{-- sa viewMovie hindi ko alam pano ano yung related sa clicked movie --}}
{{-- wala pang functionality yung filter sa index --}}
{{-- sa viewMovie wala parin functionality yung leave review, add to fa --}}
