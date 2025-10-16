<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>JMR CineRecoSys — Login & Register</title>
    
    @vite(['resources/css/app.css'])
    
    <link href="https://cdn.jsdelivr.net/npm/daisyui@4.12.10/dist/full.min.css" rel="stylesheet" type="text/css" />
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Oswald:wght@200..700&display=swap" rel="stylesheet">
    <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">
</head>

<body class="relative min-h-screen flex items-center justify-center font-oswald bg-no-repeat bg-cover bg-center"
    style="background-image: url('https://images.unsplash.com/photo-1478720568477-152d9b164e26?w=1920');">

    <div class="absolute inset-0 bg-gradient-to-t from-primary-bg via-primary-bg/80 to-transparent"></div>

    <div class="relative z-10 w-full max-w-md p-8 rounded-2xl shadow-xl
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

            <form onsubmit="event.preventDefault(); alert('Login functionality - backend not implemented');" class="space-y-4">
                <input type="text" name="username" placeholder="Username" required
                    class="w-full px-4 py-2 bg-secondary-bg/60 border border-border-color rounded-lg 
                           focus:ring-2 focus:ring-accent focus:outline-none 
                           text-text-primary placeholder-text-muted">

                <input type="password" name="password" placeholder="Password" required
                    class="w-full px-4 py-2 bg-secondary-bg/60 border border-border-color rounded-lg 
                           focus:ring-2 focus:ring-accent focus:outline-none 
                           text-text-primary placeholder-text-muted">

                <button type="submit"
                    class="w-full bg-accent text-white py-2 rounded-lg hover:bg-accent-hover transition font-bold">
                    Login
                </button>
            </form>
            <p class="mt-4 text-sm text-center text-text-secondary">
                Don't have an account?
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
            <form onsubmit="event.preventDefault(); alert('Register functionality - backend not implemented');" class="space-y-4">
                <input type="text" name="username" placeholder="Username" required
                    class="w-full px-4 py-2 bg-secondary-bg/60 border border-border-color rounded-lg 
                           focus:ring-2 focus:ring-accent focus:outline-none 
                           text-text-primary placeholder-text-muted">

                <input type="password" name="password" placeholder="Password" required
                    class="w-full px-4 py-2 bg-secondary-bg/60 border border-border-color rounded-lg 
                           focus:ring-2 focus:ring-accent focus:outline-none 
                           text-text-primary placeholder-text-muted">

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

    <script>
        function toggleForms() {
            document.getElementById('login-form-container').classList.toggle('hidden');
            document.getElementById('register-form-container').classList.toggle('hidden');
        }
    </script>

</body>
</html