<?php
session_start();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>JMR CineRecoSys — Login & Register</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdn.jsdelivr.net/npm/daisyui@4.12.10/dist/full.min.css" rel="stylesheet" type="text/css" />
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Oswald:wght@200..700&display=swap" rel="stylesheet">
    <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://code.jquery.com/ui/1.13.2/themes/ui-darkness/jquery-ui.css">

    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        'primary-bg': '#0f172a',
                        'secondary-bg': '#1e293b',
                        'card-bg': '#334155',
                        'accent': '#10b981',
                        'accent-hover': '#059669',
                        'text-primary': '#f8fafc',
                        'text-secondary': '#cbd5e1',
                        'text-muted': '#64748b',
                        'border-color': '#475569',
                    },
                    fontFamily: {
                        'oswald': ['Oswald', 'sans-serif']
                    }
                }
            }
        }
    </script>
</head>

<body class="min-h-screen font-oswald bg-no-repeat bg-cover bg-center"
    style="background-image: url('../src/img/loginRegister-bg.avif');">

<?php include __DIR__ . '/../partials/header.php'; ?>

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

                <form action="../db/authRequests.php" method="POST" class="space-y-4">
                    <input type="hidden" name="action" value="login">

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
                <form action="../db/authRequests.php" method="POST" class="space-y-4">
                    <input type="hidden" name="action" value="register">

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
    </div>
</div>

<script>
    function toggleForms() {
        document.getElementById('login-form-container').classList.toggle('hidden');
        document.getElementById('register-form-container').classList.toggle('hidden');
    }
</script>
</body>

</html>