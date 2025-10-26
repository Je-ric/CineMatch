@extends('layouts.app')

@section('page-content')

    <body class="min-h-screen font-oswald bg-no-repeat bg-cover bg-center"
        style="background-image: url('{{ asset('img/loginRegister-bg.avif') }}');">

        <div class="relative min-h-[calc(100vh-64px)]">
            <div
                class="absolute inset-0 bg-gradient-to-t from-primary-bg via-primary-bg/80 to-transparent pointer-events-none z-0">
            </div>

            <div class="relative z-10 flex items-center justify-center min-h-[calc(100vh-64px)] px-4">
                <div
                    class="w-full max-w-xl p-8 rounded-2xl shadow-xl bg-neutral-900/40 backdrop-blur-md border border-white/20">

                    <div id="login-form-container" class="form-container">
                        <div class="flex flex-col items-center mb-6">
                            <div
                                class="flex flex-row items-center mb-6 text-xl md:text-2xl font-bold text-accent font-oswald">
                                {{-- <i class="bx bx-movie-play text-4xl text-accent"></i>
                                <h1 class="text-2xl ml-3 text-center text-accent">CineMatch</h1> --}}
                                <img src="{{ asset('CineMatch.png') }}" alt="CineMatch Logo" class="w-12 h-12">
                                CineMatch
                            </div>
                            <h2 class="text-xl text-center text-text-primary">Sign In</h2>
                        </div>

                        <form action="{{ route('login') }}" method="POST" class="space-y-4">
                            @csrf
                            <input type="text" name="username" placeholder="Username" required
                                class="w-full px-4 py-2 border border-border-color rounded-lg focus:ring-2 focus:ring-accent focus:outline-none bg-transparent text-text-primary placeholder-text-muted">
                            <input type="password" name="password" placeholder="Password" required
                                class="w-full px-4 py-2 border border-border-color rounded-lg focus:ring-2 focus:ring-accent focus:outline-none bg-transparent text-text-primary placeholder-text-muted">
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

                    {{-- REGISTER FORM --}}
                    <div id="register-form-container" class="form-container hidden">
                        <div class="flex flex-col items-center mb-6">
                            <div class="flex flex-row items-center mb-6">
                                <i class="bx bx-movie-play text-4xl text-accent"></i>
                                <h1 class="text-2xl ml-3 text-center text-accent">CineMatch</h1>
                            </div>
                            <h2 class="text-xl text-center text-text-primary">Sign Up</h2>
                        </div>

                        <form action="{{ route('auth.register') }}" method="POST" class="space-y-4">
                            @csrf
                            <input type="text" name="name" placeholder="Full name" required
                                class="w-full px-4 py-2 border border-border-color rounded-lg focus:ring-2 focus:ring-accent focus:outline-none bg-transparent text-text-primary placeholder-text-muted"
                                value="{{ old('name') }}">
                            <input type="email" name="email" placeholder="Email address" required
                                class="w-full px-4 py-2 border border-border-color rounded-lg focus:ring-2 focus:ring-accent focus:outline-none bg-transparent text-text-primary placeholder-text-muted"
                                value="{{ old('email') }}">
                            <input type="text" name="username" placeholder="Username" required
                                class="w-full px-4 py-2 border border-border-color rounded-lg focus:ring-2 focus:ring-accent focus:outline-none bg-transparent text-text-primary placeholder-text-muted"
                                value="{{ old('username') }}">
                            <input type="password" name="password" placeholder="Password" required
                                class="w-full px-4 py-2 border border-border-color rounded-lg focus:ring-2 focus:ring-accent focus:outline-none bg-transparent text-text-primary placeholder-text-muted">
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


                    <div class="px-9 my-6 flex items-center">
                        <div class="flex-1 border-t border-gray-300"></div>
                        <span class="px-3 text-gray-500 text-sm">OR</span>
                        <div class="flex-1 border-t border-gray-300"></div>
                    </div>

                    <div class="flex flex-col sm:flex-row items-center justify-center gap-4 mt-4">
                        <a href="{{ route('auth.google.redirect') }}"
                            class="flex items-center gap-3 px-5 py-2 border border-gray-300 rounded-full bg-gray-50 transition-all duration-200 shadow-sm hover:shadow-md">
                            <img src="{{ asset('img/auth/google-logo.png') }}" alt="Google" class="w-5 h-5">
                            <span class="text-gray-700 font-medium">Continue with Google</span>
                        </a>

                        <a href="{{ route('auth.facebook.redirect') }}"
                            class="flex items-center gap-3 px-5 py-2 border border-gray-300 rounded-full bg-gray-50 transition-all duration-200 shadow-sm hover:shadow-md">
                            <img src="{{ asset('img/auth/facebook-logo.png') }}" alt="Facebook" class="w-5 h-5">
                            <span class="text-gray-700 font-medium">Continue with Facebook</span>
                        </a>

                        {{--
                            <a href="{{ route('auth.github.redirect') }}"
                                class="flex items-center gap-3 px-5 py-2 border border-gray-300 rounded-full hover:bg-gray-50 transition-all duration-200 shadow-sm hover:shadow-md">
                                <img src="{{ asset('img/auth/github-logo.png') }}" alt="GitHub" class="w-5 h-5">
                                <span class="text-gray-700 font-medium">Continue with GitHub</span>
                            </a>
                            --}}
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
