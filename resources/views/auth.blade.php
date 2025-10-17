@extends('layouts.app')

@section('page-content')

<body class="min-h-screen font-oswald bg-no-repeat bg-cover bg-center"
    style="background-image: url('{{ asset('img/loginRegister-bg.avif') }}');"  >

    <div class="relative min-h-[calc(100vh-64px)]">
    <div class="absolute inset-0 bg-gradient-to-t from-primary-bg via-primary-bg/80 to-transparent pointer-events-none z-0"></div>

    <div class="relative z-10 flex items-center justify-center min-h-[calc(100vh-64px)] px-4">
        <div class="w-full max-w-md p-8 rounded-2xl shadow-xl
                    bg-neutral-900/40 backdrop-blur-md border border-white/20">

            <!-- Login Form -->
            <div id="login-form-container" class="form-container">
                <div class="flex flex-col items-center mb-6">
                    <div class="flex flex-row items-center mb-6">
                        <i class="bx bx-movie-play text-4xl text-accent"></i>
                        <h1 class="text-2xl ml-3 text-center text-accent">CineMatch</h1>
                    </div>
                    <h2 class="text-xl text-center text-text-primary">Sign In</h2>
                </div>

                <form action="{{ route('login') }}" method="POST" class="space-y-4">
                    @csrf
                    <input type="hidden" name="action" value="login">

                    <input type="text" name="username" placeholder="Username" required
                        class="w-full px-4 py-2 border border-border-color rounded-lg
                                focus:ring-2 focus:ring-accent focus:outline-none bg-transparent
                                text-text-primary placeholder-text-muted">

                    <input type="password" name="password" placeholder="Password" required
                        class="w-full px-4 py-2 border border-border-color rounded-lg
                                focus:ring-2 focus:ring-accent focus:outline-none bg-transparent
                                text-text-primary placeholder-text-muted">

                    <div class="px-9 my-6 flex items-center">
                        <div class="flex-1 border-t border-gray-300"></div>
                        <span class="px-3 text-gray-500 text-sm">OR</span>
                        <div class="flex-1 border-t border-gray-300"></div>
                    </div>

                    <div class="flex flex-row items-center justify-center">

                        {{-- google login option --}}
                        <div class="flex justify-center items-center px-5">
                            <a href="{{ route('auth.redirect', ['provider' => 'google'])  }}"
                                class="w-full font-semibold py-3 px-4 rounded-lg border border-gray-300 transition-colors duration-200 flex items-center justify-center">
                                <img src="{{ asset('img/auth/google-logo.png') }}" alt="google-logo" class="w-6 h-6">
                                {{-- <span>Continue with Google</span> --}}
                            </a>
                        </div>

                        {{-- facebook login option --}}
                        <div class="flex justify-center items-center px-5">
                            <a href="{{ route('auth.redirect', ['provider' => 'facebook']) }}"
                                class="w-full font-semibold py-3 px-4 rounded-lg border border-gray-300 transition-colors duration-200 flex items-center justify-center">
                                <img src="{{ asset('img/auth/facebook-logo.png') }}" alt="facebook-logo" class="w-6 h-6">
                                {{-- <span>Continue with Facebook</span> --}}
                            </a>
                        </div>

                        <div class="flex justify-center items-center px-5">
                            <a href="{{ route('auth.redirect', ['provider' => 'github']) }}"
                                class="w-full font-semibold py-3 px-4 rounded-lg border border-gray-300 transition-colors duration-200 flex items-center justify-center">
                                <img src="{{ asset('img/auth/github-logo.png') }}" alt="github-logo" class="w-6 h-6">
                                {{-- <span>Continue with Facebook</span> --}}
                            </a>
                        </div>

                    </div>

                    <button type="submit"
                        class="w-full bg-accent text-white py-2 rounded-lg hover:bg-accent-hover transition font-bold">
                        Login
                    </button>

                </form>

                <p class="mt-4 text-sm text-center text-text-secondary">
                    Don’t have an account?
                    <a href="#" onclick="toggleForms()" class="text-accent hover:underline">Register here</a>
                </p>

            </div>

            <!-- Register Form (hidden by default) -->
            <div id="register-form-container" class="form-container hidden">
                <div class="flex flex-col items-center mb-6">
                    <div class="flex flex-row items-center mb-6">
                        <i class="bx bx-movie-play text-4xl text-accent"></i>
                        <h1 class="text-2xl ml-3 text-center text-accent">CineMatch</h1>
                    </div>
                    <h2 class="text-xl text-center text-text-primary">Sign Up</h2>
                </div>
                <form action="{{ route('register') }}" method="POST" class="space-y-4">
                    @csrf
                    <input type="hidden" name="action" value="register">

                    <!-- Added name and email (required by users table / controller) -->
                    <input type="text" name="name" placeholder="Full name" required
                        class="w-full px-4 py-2 border border-border-color rounded-lg
                                focus:ring-2 focus:ring-accent focus:outline-none
                                text-text-primary placeholder-text-muted"
                        value="{{ old('name') }}">

                    <input type="email" name="email" placeholder="Email address" required
                        class="w-full px-4 py-2 border border-border-color rounded-lg
                                focus:ring-2 focus:ring-accent focus:outline-none
                                text-text-primary placeholder-text-muted"
                        value="{{ old('email') }}">

                    <input type="text" name="username" placeholder="Username" required
                        class="w-full px-4 py-2 border border-border-color rounded-lg
                                focus:ring-2 focus:ring-accent focus:outline-none
                                text-text-primary placeholder-text-muted"
                        value="{{ old('username') }}">

                    <input type="password" name="password" placeholder="Password" required
                        class="w-full px-4 py-2 border border-border-color rounded-lg
                                focus:ring-2 focus:ring-accent focus:outline-none
                                text-text-primary placeholder-text-muted">

                    <div class="px-9 my-6 flex items-center">
                        <div class="flex-1 border-t border-gray-300"></div>
                        <span class="px-3 text-gray-500 text-sm">OR</span>
                        <div class="flex-1 border-t border-gray-300"></div>
                    </div>

                    <div class="flex flex-row items-center justify-center">

                        {{-- google login option --}}
                        <div class="flex justify-center items-center px-5">
                            <a href="{{ route('auth.redirect', ['provider' => 'google'])  }}"
                                class="w-full font-semibold py-3 px-4 rounded-lg border border-gray-300 transition-colors duration-200 flex items-center justify-center">
                                <img src="{{ asset('img/auth/google-logo.png') }}" alt="google-logo" class="w-6 h-6">
                                {{-- <span>Continue with Google</span> --}}
                            </a>
                        </div>

                        {{-- facebook login option --}}
                        <div class="flex justify-center items-center px-5">
                            <a href="{{ route('auth.redirect', ['provider' => 'facebook']) }}"
                                class="w-full font-semibold py-3 px-4 rounded-lg border border-gray-300 transition-colors duration-200 flex items-center justify-center">
                                <img src="{{ asset('img/auth/facebook-logo.png') }}" alt="facebook-logo" class="w-6 h-6">
                                {{-- <span>Continue with Facebook</span> --}}
                            </a>
                        </div>

                        <div class="flex justify-center items-center px-5">
                            <a href="{{ route('auth.redirect', ['provider' => 'github']) }}"
                                class="w-full font-semibold py-3 px-4 rounded-lg border border-gray-300 transition-colors duration-200 flex items-center justify-center">
                                <img src="{{ asset('img/auth/github-logo.png') }}" alt="github-logo" class="w-6 h-6">
                                {{-- <span>Continue with Facebook</span> --}}
                            </a>
                        </div>

                    </div>

                    <button type="submit"
                        class="w-full bg-accent text-white py-2 rounded-lg hover:bg-accent-hover transition font-bold">
                        Register
                    </button>

                </form>

                <p class="mt-4 text-sm text-center text-text-secondary">
                    Already have an account?
                    <a href="#" onclick="toggleForms()" class="text-accent hover:underline">Login here</a>
                </p>
            </div>
        </div>
    </div>
</div>

<script>
    function toggleForms() {
        document.getElementById('login-form-container').classList.toggle('hidden');
        document.getElementById('register-form-container').classList.toggle('hidden');
    }
</script>
</body>


@endsection