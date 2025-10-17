<header class="bg-primary-bg/90 backdrop-blur-md border-b border-accent/50 px-6 md:px-10 py-4 flex items-center justify-between sticky top-0 z-50">

    {{-- Logo / Home --}}
    <a href="{{ route('home') }}" class="text-xl md:text-2xl font-bold text-accent flex items-center font-oswald">
        <i class="bx bx-movie-play mr-2"></i>CineMatch
    </a>

    <div class="flex items-center gap-3 text-sm">

        {{-- BACK TO MOVIES (Hidden on Home page) --}}
        @if (!request()->routeIs('home'))
            <a href="{{ route('home')}}" class="btn btn-outline btn-accent flex items-center gap-1 text-sm">
                <i class="bx bx-arrow-back"></i>
                <span class="hidden sm:inline">Back to Movies</span>
            </a>
        @endif

        {{-- AUTHENTICATED USER --}}
        @auth
            <span class="hidden sm:inline text-text-secondary">
                Hi, {{ Auth::user()->name ?? Auth::user()->username}}!
            </span>

            {{-- ADMIN ONLY: Add Movie --}}
            @if (Auth::user()->role === 'admin')
                <a href="" class="btn btn-accent flex items-center gap-1 text-sm">
                    <i class="bx bx-plus"></i>
                    <span class="hidden sm:inline">Add Movie</span>
                </a>
            @endif

            {{-- REGULAR USER: Profile (hide on profile page) --}}
            @if (Auth::user()->role === 'user' && !request()->routeIs('profile'))
                <a href=""
                   class="btn btn-circle btn-accent text-white tooltip flex items-center justify-center"
                   data-tip="My Profile">
                    <i class="bx bx-user text-xl"></i>
                </a>
            @endif

            {{-- LOGOUT --}}
            <form action="{{ route('logout') }}" method="POST" class="inline-flex">
                @csrf
                <button type="submit"
                        class="btn btn-circle bg-red-600 hover:bg-red-700 text-white tooltip"
                        data-tip="Logout">
                    <i class="bx bx-log-out text-lg"></i>
                </button>
            </form>

        {{-- GUEST USER --}}
        @else
            {{-- Hide Login/Register button on auth page --}}
            @unless (request()->routeIs('auth'))
                <a href="{{ route('auth') }}" class="btn btn-accent flex items-center gap-2 text-sm font-medium">
                    <i class="bx bx-log-in"></i>
                    Login&nbsp;/&nbsp;Register
                </a>
            @endunless
        @endauth
    </div>
</header>
