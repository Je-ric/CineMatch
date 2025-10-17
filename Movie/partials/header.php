<?php
$script = $_SERVER['SCRIPT_NAME'] ?? '';
$fromPages = strpos($script, '/pages/') !== false;

$homeHref   = $fromPages ? '../index.php' : 'index.php';
$loginHref  = $fromPages ? '../pages/loginRegister.php' : 'pages/loginRegister.php';
$manageHref = $fromPages ? '../pages/manageMovie.php' : 'pages/manageMovie.php';
$authHref   = $fromPages ? '../db/authRequests.php' : 'db/authRequests.php';
$profileHref = $fromPages ? '../pages/profile.php' : 'pages/profile.php';

$currentFile = basename($script);
$isAuthPage = ($currentFile === 'loginRegister.php'); // auth page
$showBackToMovies = in_array($currentFile, ['viewMovie.php', 'manageMovie.php', 'loginRegister.php']); // include login
?>

<header class="bg-primary-bg/90 backdrop-blur-md border-b border-accent/50 px-6 md:px-10 py-4 flex items-center justify-between sticky top-0 z-50">

    <a href="<?= htmlspecialchars($homeHref) ?>" class="text-xl md:text-2xl font-bold text-accent flex items-center font-oswald">
        <i class="bx bx-movie-play mr-2"></i>CineMatch
    </a>

    <div class="flex items-center gap-3 text-sm">

        <?php if ($showBackToMovies): ?>
            <a href="<?= htmlspecialchars($homeHref) ?>"
                class="btn btn-outline btn-accent flex items-center gap-1 text-sm">
                <i class="bx bx-arrow-back"></i>
                <span class="hidden sm:inline">Back to Movies</span>
            </a>
        <?php endif; ?>

    
        <?php if (isset($_SESSION['username'])): ?>
            <span class="hidden sm:inline text-text-secondary">
                Hi, <?= htmlspecialchars($_SESSION['username']) ?> !
            </span>

            <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
                <a href="<?= htmlspecialchars($manageHref) ?>"
                    class="btn btn-accent flex items-center gap-1 text-sm">
                    <i class="bx bx-plus"></i>
                    <span class="hidden sm:inline">Add Movie</span>
                </a>
            <?php endif; ?>

            <?php if (
                isset($_SESSION['user_id']) && $_SESSION['user_id'] > 0 &&
                ($currentFile !== 'profile.php') &&
                (($_SESSION['role'] ?? '') === 'user') // hide for admin
            ): ?>
                <a href="<?= htmlspecialchars($profileHref) ?>"
                    class="btn btn-circle btn-accent text-white tooltip flex items-center justify-center"
                    data-tip="My Profile">
                    <i class="bx bx-user text-xl"></i>
                </a>
            <?php endif; ?>

            <form action="<?= htmlspecialchars($authHref) ?>" method="POST" class="inline-flex">
                <input type="hidden" name="action" value="logout" />
                <button type="submit"
                    class="btn btn-circle bg-red-600 hover:bg-red-700 text-white tooltip"
                    data-tip="Logout">
                    <i class="bx bx-log-out text-lg"></i>
                </button>
            </form>

        <?php else: ?>
            <?php if (!$isAuthPage): // don’t show “Login/Register” on the login page itself ?>
                <a href="<?= htmlspecialchars($loginHref) ?>"
                    class="btn btn-accent flex items-center gap-2 text-sm font-medium">
                    <i class="bx bx-log-in"></i>
                    Login&nbsp;/&nbsp;Register
                </a>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</header>