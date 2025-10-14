@extends('layouts.app')

@section('title', 'My Profile')

@section('content')
<div class="px-6 md:px-10 py-10">
    <h1 class="text-3xl font-bold mb-8">My Profile</h1>

    <div class="mb-6 p-4 rounded-lg bg-yellow-900/60 border border-yellow-700 text-yellow-200 text-lg font-semibold">
        You are logged in as a <span class="font-bold">User</span>. (Mock session - backend not implemented)
    </div>

    <!-- Tabs -->
    <div role="tablist" class="tabs tabs-bordered">
        <!-- Favorites Tab -->
        <input type="radio" name="profile_tabs" role="tab" class="tab" aria-label="Favorites" checked />
        <div role="tabpanel" class="tab-content py-6">
            <div id="favAgg" class="mb-6 space-y-2">
                <div id="favAggGenres"></div>
                <div id="favAggCountries"></div>
                <div id="favAggLanguages"></div>
            </div>
            <section id="shelfFavorites">
                <div class="border-t border-neutral-800 mb-6"></div>
                <h3 class="text-2xl font-semibold mb-4">Your Favorites</h3>
                <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-6 gap-6">
                    <!-- Mock favorite movies -->
                    @for($i = 1; $i <= 6; $i++)
                        <div class="group rounded-xl overflow-hidden bg-neutral-900 border border-neutral-800 hover:border-green-500/70 transition transform hover:-translate-y-2 hover:shadow-xl hover:shadow-green-500/20">
                            <div class="relative">
                                <a href="/movie/{{ $i }}">
                                    <img src="https://placehold.co/300x450?text=Favorite+{{ $i }}" 
                                         class="w-full h-80 object-cover transition-transform duration-300 group-hover:scale-105">
                                </a>
                                <div class="absolute top-2 right-2">
                                    <span class="bg-green-600/90 text-white font-semibold text-xs px-2 py-1 rounded-md flex items-center gap-1">
                                        <i class='bx bxs-star text-yellow-300'></i>4.{{ $i }}
                                    </span>
                                </div>
                            </div>
                            <div class="p-4">
                                <h5 class="font-semibold text-base text-white">Favorite Movie {{ $i }}</h5>
                                <small class="text-gray-400">(2024)</small>
                            </div>
                        </div>
                    @endfor
                </div>
            </section>
            <div id="emptyFavorites" class="hidden text-center py-10 text-gray-400">
                <i class="bx bx-heart text-5xl mb-2"></i>
                <div class="text-lg">You haven't favorited any movies yet.</div>
            </div>
        </div>

        <!-- Rated Tab -->
        <input type="radio" name="profile_tabs" role="tab" class="tab" aria-label="Rated" />
        <div role="tabpanel" class="tab-content py-6">
            <div id="ratedAgg" class="mb-6 space-y-2">
                <div id="ratedAggGenres"></div>
                <div id="ratedAggCountries"></div>
                <div id="ratedAggLanguages"></div>
            </div>
            <section id="shelfRated">
                <div class="border-t border-neutral-800 mb-6"></div>
                <h3 class="text-2xl font-semibold mb-4">Movies You Rated</h3>
                <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-6 gap-6">
                    <!-- Mock rated movies -->
                    @for($i = 1; $i <= 4; $i++)
                        <div class="group rounded-xl overflow-hidden bg-neutral-900 border border-neutral-800 hover:border-green-500/70 transition transform hover:-translate-y-2 hover:shadow-xl hover:shadow-green-500/20">
                            <div class="relative">
                                <a href="/movie/{{ $i+10 }}">
                                    <img src="https://placehold.co/300x450?text=Rated+{{ $i }}" 
                                         class="w-full h-80 object-cover transition-transform duration-300 group-hover:scale-105">
                                </a>
                                <div class="absolute top-2 right-2">
                                    <span class="bg-green-600/90 text-white font-semibold text-xs px-2 py-1 rounded-md flex items-center gap-1">
                                        <i class='bx bxs-star text-yellow-300'></i>{{ $i }}.0
                                    </span>
                                </div>
                            </div>
                            <div class="p-4">
                                <h5 class="font-semibold text-base text-white">Rated Movie {{ $i }}</h5>
                                <small class="text-gray-400">(2023)</small>
                            </div>
                        </div>
                    @endfor
                </div>
            </section>
            <div id="emptyRated" class="hidden text-center py-10 text-gray-400">
                <i class="bx bx-star text-5xl mb-2"></i>
                <div class="text-lg">You haven't rated any movies yet.</div>
            </div>
        </div>

        <!-- Recommendations Tab -->
        <input type="radio" name="profile_tabs" role="tab" class="tab" aria-label="Recommendations" />
        <div role="tabpanel" class="tab-content py-6">
            <section id="shelfFavGenres" class="mb-12">
                <div class="border-t border-neutral-800 mb-6"></div>
                <h3 class="text-2xl font-semibold mb-4">Because you like these genres</h3>
                <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-6 gap-6">
                    @for($i = 1; $i <= 6; $i++)
                        <div class="group rounded-xl overflow-hidden bg-neutral-900 border border-neutral-800 hover:border-green-500/70 transition transform hover:-translate-y-2 hover:shadow-xl hover:shadow-green-500/20">
                            <div class="relative">
                                <a href="/movie/{{ $i+20 }}">
                                    <img src="https://placehold.co/300x450?text=Recommended+{{ $i }}" 
                                         class="w-full h-80 object-cover transition-transform duration-300 group-hover:scale-105">
                                </a>
                                <div class="absolute top-2 right-2">
                                    <span class="bg-green-600/90 text-white font-semibold text-xs px-2 py-1 rounded-md flex items-center gap-1">
                                        <i class='bx bxs-star text-yellow-300'></i>4.{{ $i }}
                                    </span>
                                </div>
                            </div>
                            <div class="p-4">
                                <h5 class="font-semibold text-base text-white">Recommended {{ $i }}</h5>
                                <small class="text-gray-400">(2024)</small>
                            </div>
                        </div>
                    @endfor
                </div>
            </section>

            <section id="shelfFavCountries" class="mb-12">
                <div class="border-t border-neutral-800 mb-6"></div>
                <h3 class="text-2xl font-semibold mb-4">From countries you like</h3>
                <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-6 gap-6">
                    @for($i = 1; $i <= 6; $i++)
                        <div class="group rounded-xl overflow-hidden bg-neutral-900 border border-neutral-800 hover:border-green-500/70 transition transform hover:-translate-y-2 hover:shadow-xl hover:shadow-green-500/20">
                            <div class="relative">
                                <a href="/movie/{{ $i+30 }}">
                                    <img src="https://placehold.co/300x450?text=Country+{{ $i }}" 
                                         class="w-full h-80 object-cover transition-transform duration-300 group-hover:scale-105">
                                </a>
                                <div class="absolute top-2 right-2">
                                    <span class="bg-green-600/90 text-white font-semibold text-xs px-2 py-1 rounded-md flex items-center gap-1">
                                        <i class='bx bxs-star text-yellow-300'></i>4.{{ $i }}
                                    </span>
                                </div>
                            </div>
                            <div class="p-4">
                                <h5 class="font-semibold text-base text-white">Country Film {{ $i }}</h5>
                                <small class="text-gray-400">(2023)</small>
                            </div>
                        </div>
                    @endfor
                </div>
            </section>

            <section id="shelfFavLanguages" class="mb-12">
                <div class="border-t border-neutral-800 mb-6"></div>
                <h3 class="text-2xl font-semibold mb-4">In languages you like</h3>
                <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-6 gap-6">
                    @for($i = 1; $i <= 6; $i++)
                        <div class="group rounded-xl overflow-hidden bg-neutral-900 border border-neutral-800 hover:border-green-500/70 transition transform hover:-translate-y-2 hover:shadow-xl hover:shadow-green-500/20">
                            <div class="relative">
                                <a href="/movie/{{ $i+40 }}">
                                    <img src="https://placehold.co/300x450?text=Language+{{ $i }}" 
                                         class="w-full h-80 object-cover transition-transform duration-300 group-hover:scale-105">
                                </a>
                                <div class="absolute top-2 right-2">
                                    <span class="bg-green-600/90 text-white font-semibold text-xs px-2 py-1 rounded-md flex items-center gap-1">
                                        <i class='bx bxs-star text-yellow-300'></i>4.{{ $i }}
                                    </span>
                                </div>
                            </div>
                            <div class="p-4">
                                <h5 class="font-semibold text-base text-white">Language Film {{ $i }}</h5>
                                <small class="text-gray-400">(2024)</small>
                            </div>
                        </div>
                    @endfor
                </div>
            </section>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(function() {
    // Mock aggregation badges
    function renderAggPills($root, label, items) {
        if (!Array.isArray(items) || items.length === 0) {
            $root.empty();
            return;
        }
        const pills = items.map(it => `
            <span class="badge badge-outline border-neutral-700 text-gray-300 mr-2 mb-2">
                ${it.name}: <span class="ml-1 font-semibold text-white">${it.cnt}</span>
            </span>
        `).join('');
        
        $root.html(`
            <div class="mb-1 text-sm text-gray-400">${label}</div>
            <div class="flex flex-wrap">${pills}</div>
        `);
    }

    // Mock data
    const favGenres = [
        {name: 'Action', cnt: 15},
        {name: 'Thriller', cnt: 10},
        {name: 'Sci-Fi', cnt: 8}
    ];
    
    const favCountries = [
        {name: 'United States', cnt: 20},
        {name: 'United Kingdom', cnt: 8}
    ];
    
    const favLanguages = [
        {name: 'English', cnt: 25},
        {name: 'Spanish', cnt: 5}
    ];

    renderAggPills($('#favAggGenres'), 'Genres you favorited', favGenres);
    renderAggPills($('#favAggCountries'), 'Countries you favorited', favCountries);
    renderAggPills($('#favAggLanguages'), 'Languages you favorited', favLanguages);
    
    renderAggPills($('#ratedAggGenres'), 'Genres you rated', favGenres);
    renderAggPills($('#ratedAggCountries'), 'Countries you rated', favCountries);
    renderAggPills($('#ratedAggLanguages'), 'Languages you rated', favLanguages);
});
</script>
@endpush