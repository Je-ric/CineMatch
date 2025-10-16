@php
    $currentRoute = Route::currentRouteName();
    $showBackToMovies = in_array($currentRoute, ['movie.view', 'movie.add', 'movie.edit']);
    // Mock session data - in real app this would come from auth
    $isLoggedIn = false; // Change to true to see logged in state
    $username = 'JohnDoe';
    $userRole = 'user'; // or 'admin'
@endphp

<header class="bg-primary-bg/90 backdrop-blur-md border-b border-accent/50 px-6 md:px-10 py-4 flex items-center justify-between sticky top-0 z-50">

    <a href="{{ route('home') }}" class="text-xl md:text-2xl font-bold text-accent flex items-center font-oswald">
        <i class="bx bx-movie-play mr-2"></i>CineMatch
    </a>

    <div class="flex items-center gap-3 text-sm">

        @if($showBackToMovies)
            <a href="{{ route('home') }}" class="btn btn-outline btn-accent flex items-center gap-1 text-sm">
                <i class="bx bx-arrow-back"></i>
                <span class="hidden sm:inline">Back to Movies</span>
            </a>
        @endif

        @if($isLoggedIn)
            <span class="hidden sm:inline text-text-secondary">
                Hi, {{ $username }} !
            </span>

            @if($userRole === 'admin')
                <a href="{{ route('movie.add') }}" class="btn btn-accent flex items-center gap-1 text-sm">
                    <i class="bx bx-plus"></i>
                    <span class="hidden sm:inline">Add Movie</span>
                </a>
            @endif

            @if($currentRoute !== 'profile')
                <a href="{{ route('profile') }}"
                   class="btn btn-circle btn-accent text-white tooltip flex items-center justify-center"
                   data-tip="My Profile">
                    <i class="bx bx-user text-xl"></i>
                </a>
            @endif

            <button type="button" onclick="alert('Logout functionality - backend not implemented')"
                    class="btn btn-circle bg-red-600 hover:bg-red-700 text-white tooltip"
                    data-tip="Logout">
                <i class="bx bx-log-out text-lg"></i>
            </button>
        @else
            <a href="{{ route('login') }}" class="btn btn-accent flex items-center gap-2 text-sm font-medium">
                <i class="bx bx-log-in"></i>
                Login&nbsp;/&nbsp;Register
            </a>
        @endif
    </div>
</header>
