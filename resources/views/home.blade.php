<x-layout>
    <div class="px-6 md:px-10 py-10">
        <div class="mb-8 flex flex-col md:flex-row items-start md:items-center justify-between gap-4">
            <div>
                <h2 class="text-3xl font-bold mb-1">Discover Movies</h2>
                <p class="text-gray-400 text-sm">Find your next favorite film from our curated collection</p>
            </div>
            <div class="flex flex-col sm:flex-row gap-3 w-full md:w-auto">
                <label class="flex items-center w-full md:w-96 bg-neutral-900 border border-neutral-700 rounded-lg overflow-hidden shadow-sm">
                    <span class="px-3"><i class="bx bx-search text-gray-400 text-lg"></i></span>
                    <input id="search" type="text" placeholder="Search movies by title..."
                        class="w-full bg-neutral-900 text-gray-200 placeholder-gray-500 focus:outline-none px-2 py-2 text-sm">
                </label>
                <select id="sortSelect" class="select select-bordered select-sm bg-neutral-900 border-neutral-700 text-gray-200">
                    <option value="year_desc">Sort: Year (New → Old)</option>
                    <option value="year_asc">Sort: Year (Old → New)</option>
                    <option value="title_asc">Sort: Title (A→Z)</option>
                    <option value="title_desc">Sort: Title (Z→A)</option>
                </select>
                <select id="genreFilter" class="select select-bordered select-sm bg-neutral-900 border-neutral-700 text-gray-200 min-w-48">
                    <option value="">All Genres</option>
                </select>
                <select id="yearFilter" class="select select-bordered select-sm bg-neutral-900 border-neutral-700 text-gray-200 min-w-32">
                    <option value="">All Years</option>
                </select>
                <select id="countryFilter" class="select select-bordered select-sm bg-neutral-900 border-neutral-700 text-gray-200 min-w-40">
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
            <div id="trendingGrid" class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-6 gap-6"></div>
        </section>

        <!-- All Movies Section -->
        <div class="border-t border-neutral-800 my-10"></div>
        <section>
            <div class="flex items-end justify-between mb-4">
                <h3 class="text-2xl font-semibold">All movies</h3>
            </div>
            <div id="allGrid" class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-6 gap-6"></div>
            <div id="allEmpty" class="hidden py-16 text-center">
                <i class='bx bx-search-alt text-5xl text-gray-600 mb-3 block'></i>
                <p class="text-text-muted">No results found. Try adjusting your search or filters.</p>
            </div>
        </section>
    </div>

    @push('scripts')
        <script>
            (function($) {
                const state = {
                    allMovies: @json($moviesJson),
                    trending: @json($trendingJson),
                };


            const isAdmin = false; // Mock - would come from session

            function buildCard(m, showActions = false) {
                const poster = m.poster_url
                    ? `{{ asset('') }}/${m.poster_url}`
                    : 'https://placehold.co/300x450?text=No+Poster';
                const rating = (m.avg_rating != null) ? Number(m.avg_rating).toFixed(1) : 'N/A';
                const year = m.release_year ? `(${m.release_year})` : '';
                const country = m.country_name ? `<span class="px-2 py-0.5 rounded bg-neutral-800/70 border border-neutral-700">${escapeHtml(m.country_name)}</span>` : '';
                const language = m.language_name ? `<span class="px-2 py-0.5 rounded bg-neutral-800/70 border border-neutral-700">${escapeHtml(m.language_name)}</span>` : '';
                const ratingBadge = `<div class="absolute top-2 right-2"><span class="bg-green-600/90 text-white font-semibold text-xs px-2 py-1 rounded-md flex items-center gap-1"><i class='bx bxs-star text-yellow-300'></i>${rating}</span></div>`;

                const adminControls = showActions ? `
                    <div class="mt-auto pt-2">
                        <div class="flex gap-2">
                            <a href="/movie/edit/${m.id}" class="btn btn-xs btn-outline btn-info flex-1">
                                <i class='bx bx-edit'></i> Edit
                            </a>
                            <button type="button" class="btn btn-xs btn-outline btn-error flex-1 btn-delete-movie" data-id="${m.id}">
                                <i class='bx bx-trash'></i> Delete
                            </button>
                        </div>
                    </div>
                ` : '';

                return `
                    <div class="group rounded-xl overflow-hidden bg-neutral-900 border border-neutral-800 hover:border-green-500/70 transition transform hover:-translate-y-2 hover:shadow-xl hover:shadow-green-500/20 flex flex-col">
                        <div class="relative">
                            <a href="{{ url('viewMovie') }}/${m.id}">
                                <img src="${escapeHtml(poster)}" alt="${escapeHtml(m.title)}"
                                    class="w-full h-80 object-cover transition-transform duration-300 group-hover:scale-105">
                            </a>
                            ${ratingBadge}
                        </div>
                        <div class="p-4 flex flex-col flex-grow">
                            <h5 class="font-semibold text-base mb-1 text-white leading-tight">
                                ${escapeHtml(m.title)} <small class="text-gray-400 font-normal">${year}</small>
                            </h5>
                            <div class="text-gray-400 text-xs flex flex-wrap gap-2 mb-3">
                                ${country}${language}
                            </div>
                            ${adminControls}
                        </div>
                    </div>`;
            }

            function renderGrid($container, list, showActions = false) {
                $container.html(list.map(m => buildCard(m, showActions)).join(''));
            }

            function escapeHtml(str) {
                return String(str || '')
                    .replace(/&/g, '&amp;')
                    .replace(/</g, '&lt;')
                    .replace(/>/g, '&gt;')
                    .replace(/"/g, '&quot;')
                    .replace(/'/g, '&#039;');
            }

            function applySortAndFilter(list) {
                const q = $('#search').val().trim().toLowerCase();
                const year = $('#yearFilter').val();
                const country = $('#countryFilter').val();
                const genreId = $('#genreFilter').val();
                const sort = $('#sortSelect').val();

                let res = list;

                if (q) res = res.filter(m => (m.title || '').toLowerCase().includes(q));
                if (year) res = res.filter(m => String(m.release_year) === year);
                if (country) res = res.filter(m => m.country_name === country);
                if (genreId) {
                    res = res.filter(m => {
                        if (!m.genre_ids) return false;
                        const genreArray = m.genre_ids.split(',');
                        const genreNumbers = genreArray.map(Number);
                        return genreNumbers.includes(Number(genreId));
                    });
                }

                if (sort === 'year_desc') res.sort((a, b) => (b.release_year || 0) - (a.release_year || 0));
                else if (sort === 'year_asc') res.sort((a, b) => (a.release_year || 0) - (b.release_year || 0));
                else if (sort === 'title_asc') res.sort((a, b) => (a.title || '').localeCompare(b.title || ''));
                else if (sort === 'title_desc') res.sort((a, b) => (b.title || '').localeCompare(a.title || ''));

                return res;
            }

            function populateFilters() {
                const years = [...new Set(state.allMovies.map(m => m.release_year).filter(Boolean))].sort((a, b) => b - a);
                const countries = [...new Set(state.allMovies.map(m => m.country_name).filter(Boolean))].sort();

                const $yf = $('#yearFilter').empty().append('<option value="">All Years</option>');
                const $cf = $('#countryFilter').empty().append('<option value="">All Countries</option>');

                years.forEach(y => $yf.append(`<option value="${y}">${y}</option>`));
                countries.forEach(c => $cf.append(`<option value="${escapeHtml(c)}">${escapeHtml(c)}</option>`));
            }

            function hasActiveFilters() {
                return $('#search').val().trim()
                    || $('#yearFilter').val()
                    || $('#countryFilter').val()
                    || $('#genreFilter').val()
                    || $('#sortSelect').val() !== 'year_desc';
            }

            function updateTrendingVisibility() {
                $('#trendingSection').toggleClass('hidden', !!hasActiveFilters());
            }

            function renderAllGrid() {
                const filtered = applySortAndFilter(state.allMovies);
                renderGrid($('#allGrid'), filtered, isAdmin);
                $('#allEmpty').toggleClass('hidden', filtered.length > 0);
            }

            function initialLoad() {
                populateFilters();
                renderAllGrid();
                updateTrendingVisibility();

                renderGrid($('#trendingGrid'), state.trending, false);
            }


            function wireEvents() {
                $('#search').on('input', () => {
                    renderAllGrid();
                    updateTrendingVisibility();
                });

                $('#sortSelect, #genreFilter, #yearFilter, #countryFilter').on('change', () => {
                    renderAllGrid();
                    updateTrendingVisibility();
                });

                $(document).on('click', '.btn-delete-movie', function(e) {
                    e.preventDefault();
                    const id = $(this).data('id');
                    if (!confirm('Delete this movie? (Backend not implemented)')) return;
                    alert('Delete functionality - backend not implemented');
                });
            }

            $(initialLoad);
            $(wireEvents);

        })(jQuery);
        </script>
        @endpush
</x-layout>


{{-- hindi pa ayos yung sa admin side HAHAAHAHAAAA --}}


{{-- hindi pa ayos yung trending --}}
{{-- sa viewMovie hindi ko alam pano ano yung related sa clicked movie --}}
{{-- wala pang functionality yung filter sa index --}}
{{-- sa viewMovie wala parin functionality yung leave review, add to fa --}}
