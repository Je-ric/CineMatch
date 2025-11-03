@extends('layouts.app')

@section('page-content')
    <div class="max-w-7xl mx-auto px-4 py-8 min-h-screen">

        {{-- Profile --}}
        <div
            class="bg-secondary-bg/90 backdrop-blur-sm border border-border-color rounded-lg p-6 mb-8 flex flex-col md:flex-row items-center md:items-start gap-6">
            <div class="w-24 h-24 bg-accent/20 rounded-full flex items-center justify-center text-4xl text-accent">
                <i class="bx bx-user"></i>
            </div>
            <div class="flex-1">
                <h1 class="text-3xl font-bold text-accent">{{ $user->name ?? $user->username }}</h1>
                <p class="text-text-secondary">{{ $user->email ?? '' }}</p>
                <div class="flex items-center gap-4 mt-2 text-sm text-text-muted">
                    <span><i class="bx bx-heart"></i> {{ is_countable($favorites) ? count($favorites) : 0 }} Favorites</span>
                    <span><i class="bx bx-star"></i> {{ is_countable($rated) ? count($rated) : 0 }} Reviews</span>
                </div>
            </div>
        </div>

        <div class="tab-container flex gap-6 border-b border-gray-300 mb-6">
            <button
                class="tab-link py-2 px-4 text-gray-500 font-medium hover:text-accent transition-colors"
                id="favorites-tab"
                data-tab="favorites"
                onclick="openTab(event, 'favorites')">
                Favorites
            </button>
            <button
                class="tab-link py-2 px-4 text-gray-500 font-medium hover:text-accent transition-colors"
                id="rated-tab"
                data-tab="rated"
                onclick="openTab(event, 'rated')">
                Rated Movies
            </button>
            <button
                class="tab-link py-2 px-4 text-gray-500 font-medium hover:text-accent transition-colors"
                id="recommendations-tab"
                data-tab="recommendations"
                onclick="openTab(event, 'recommendations')">
                Recommendations
            </button>
        </div>


        {{-- Tab --}}
        @include('profile.favoriteTab')


        {{-- Tab --}}
        @include('profile.ratedTab')
        
        {{-- Tab  --}}
        @include('profile.recommendationsTab')

    </div>

    @push('scripts')
    {{-- W3Schools --}}
        <script>
            function openTab(evt, tabName) {
                var i, tabContent, tabLinks;

                tabContent = document.getElementsByClassName("tab-content");
                for (i = 0; i < tabContent.length; i++) {
                    tabContent[i].style.display = "none";
                }

                tabLinks = document.getElementsByClassName("tab-link");
                for (i = 0; i < tabLinks.length; i++) {
                    tabLinks[i].classList.remove("active-tab");
                }

                document.getElementById(tabName).style.display = "block";
                evt.currentTarget.classList.add("active-tab");
            }

            document.addEventListener("DOMContentLoaded", function() {
                const firstTab = document.querySelector(".tab-link");
                firstTab.classList.add("active-tab");
                document.getElementById(firstTab.dataset.tab).style.display = "block";
            });


        </script>
    @endpush

@endsection
