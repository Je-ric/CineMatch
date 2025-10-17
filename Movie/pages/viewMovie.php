<?php
session_start();
require_once __DIR__ . '/../classes/Movie.php';
require_once __DIR__ . '/../classes/RateReview.php';
require_once __DIR__ . '/../classes/People.php';
require_once __DIR__ . '/../classes/Favorite.php';

if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: ../index.php");
    exit;
}

$movie = new Movie();
$m = $movie->getMovieById($_GET['id']);
if (!$m) die("Movie not found.");

$rate = new RateReview();
$reviews = $rate->getReviewsByMovie($m['id']); // all reviews for this movie
$genres = $movie->getGenresByMovie($m['id'], 'name'); // all genres for this movie

$peopleObj = new People();
$directors = $peopleObj->getMoviePeople($m['id'], 'Director'); // all directors for this movie
$actors = $peopleObj->getMoviePeople($m['id'], 'Cast'); // all actors for this movie

$ratingInfo   = $rate->getAverageRating($m['id']);
$avgRating    = $ratingInfo['avg'] ?? null;
$totalReviews = $ratingInfo['total'] ?? 0;

$fav = new Favorite();
$isFavorited = !empty($_SESSION['user_id']) ? $fav->isFavorited($_SESSION['user_id'], $m['id']) : false;
$favCount = $fav->countByMovie($m['id']); 
?>
<!DOCTYPE html>
<html lang="en" data-theme="dark">

<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($m['title']) ?> - Movie Recommender</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdn.jsdelivr.net/npm/daisyui@4.4.24/dist/full.min.css" rel="stylesheet" type="text/css" />
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Anton&family=Oswald:wght@200..700&display=swap" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
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
    <style>
        body {
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
            font-family: "Oswald", sans-serif;
            color: #fff;
        }

        .cover_follow {
            position: relative;
            /* width: 100%; */
            height: 420px;
            background-size: cover;
            /* background-position: top; */
            /* background-position: center; */
            background-position: 50% 25%;
            overflow: hidden;
            opacity: .6;
        }

        .detail_page-infor {
            position: relative;
            max-width: 1160px;
            margin: 0 auto;
            padding: 2rem 1rem;
            z-index: 5;
            font-size: 14px;
            line-height: 1.6em;
        }

        .dp-i-c-poster {
            flex-shrink: 0;
            width: 240px;
            margin-right: 2rem;
        }

        .dp-i-c-poster .film-poster {
            width: 100%;
            padding-bottom: 148%;
            position: relative;
            overflow: hidden;
            border-radius: 0.5rem;
            box-shadow: 0 30px 30px rgba(0, 0, 0, .5);
        }

        .film-poster .film-poster-img {
            position: absolute;
            inset: 0;
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .dp-i-c-right {
            flex: 1;
            min-width: 0;
        }
    </style>
</head>

<body class="min-h-screen text-text-primary">

    <?php include __DIR__ . '/../partials/header.php'; ?>

    <div class="mx-auto relative z-10">
        <?php if (!empty($m['background_url'])): ?>
            <div class="cover_follow mb-8 absolute top-0 left-0 w-full h-screen -z-10">
                <div class="absolute inset-0 bg-cover"
                    style="background-image: url('../<?= htmlspecialchars($m['background_url']) ?>');">
                </div>

                <div class="absolute inset-0 bg-gradient-to-t from-primary-bg via-primary-bg/80 to-transparent"></div>
            </div>
        <?php endif; ?>

        <div class="relative z-10 max-w-6xl mx-auto px-4 py-10">
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">

                <div class="dp-i-c-poster">
                    <div class="film-poster">
                        <img src="../<?= htmlspecialchars($m['poster_url']); ?>"
                            alt="<?= htmlspecialchars($m['title']); ?> poster"
                            class="film-poster-img">
                    </div>
                </div>

                <div class="lg:col-span-2 space-y-6">
                    <h1 class="text-3xl md:text-4xl lg:text-5xl font-bold leading-tight">
                        <?= htmlspecialchars($m['title']) ?>
                        <span class="text-gray-300 text-2xl md:text-3xl ml-2">
                            (<?= htmlspecialchars($m['release_year']) ?>)
                        </span>
                    </h1>

                    <div class="flex flex-wrap items-center gap-3 text-text-secondary text-base md:text-lg">
                        <span><?= htmlspecialchars($m['country_name']) ?></span>
                        <span class="text-accent">•</span>
                        <span><?= htmlspecialchars($m['language_name']) ?></span>
                    </div>

                    <div class="flex flex-wrap gap-2">
                        <?php if (!empty($genres)): ?>
                            <?php foreach ($genres as $genre): ?>
                                <span class="px-3 py-1 bg-accent/20 text-accent rounded-full text-sm font-medium border border-accent/30">
                                    <?= htmlspecialchars($genre) ?>
                                </span>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <span class="text-text-muted">No genres available</span>
                        <?php endif; ?>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="space-y-4">
                            <div>
                                <h3 class="flex items-center gap-2 text-accent font-semibold text-lg mb-2">
                                    <i class='bx bx-video'></i> Director(s)
                                </h3>
                                <p class="text-text-secondary">
                                    <?= !empty($directors) ? implode(" • ", array_column($directors, 'name')) : "N/A" ?>
                                </p>
                            </div>

                            <div>
                                <h3 class="flex items-center gap-2 text-accent font-semibold text-lg mb-2">
                                    <i class='bx bxs-user'></i> Cast
                                </h3>
                                <p class="text-text-secondary">
                                    <?= !empty($actors) ? implode(" • ", array_column($actors, 'name')) : "N/A" ?>
                                </p>
                            </div>
                        </div>

                        <!-- <div class="bg-secondary-bg/80 backdrop-blur-sm rounded-lg p-6 border border-border-color"> -->
                            <?php if ($avgRating): ?>
                                <div class="text-center space-y-3">
                                    <div id="average-stars" class="star-rating justify-center">
                                        <?php
                                        $filledStars = floor($avgRating);
                                        $emptyStars = 5 - $filledStars;

                                        for ($i = 0; $i < $filledStars; $i++): ?>
                                            <i class="bx bxs-star star text-yellow-400"></i>
                                        <?php endfor; ?>

                                        <?php for ($i = 0; $i < $emptyStars; $i++): ?>
                                            <i class="bx bx-star star empty"></i>
                                        <?php endfor; ?>
                                    </div>
                                    <div class="text-xl font-bold text-accent">
                                        <span id="average-rating"><?= number_format($avgRating, 1) ?></span>/5
                                    </div>
                                    <div class="text-sm text-text-muted">
                                        <span id="total-reviews"><?= (int)$totalReviews ?></span>
                                        review<?= $totalReviews !== 1 ? 's' : '' ?>
                                    </div>
                                </div>
                            <?php else: ?>
                                <!-- <div id="no-ratings-placeholder" class="text-center text-text-muted">No ratings yet</div> -->
                                <div class="sr-only">
                                    <div id="average-stars"></div>
                                    <span id="average-rating">0.0</span>
                                    <span id="total-reviews">0</span>
                                </div>
                            <?php endif; ?>
                        </div>
                    <!-- </div> -->


                    <div class="flex flex-wrap gap-3">
                        <?php if (!empty($m['trailer_url'])): ?>
                            <a href="#trailer-section" class="btn btn-accent">
                                <i class='bx bx-play'></i> Watch Trailer
                            </a>
                        <?php endif; ?>

                        <?php if (!empty($_SESSION['user_id']) && (($_SESSION['role'] ?? '') === 'user')): ?>
                            <button id="favorite-btn" type="button"
                                class="btn <?= $isFavorited ? 'btn-accent' : 'btn-outline btn-accent' ?>"
                                data-movie-id="<?= (int)$m['id'] ?>"
                                data-favorited="<?= $isFavorited ? '1' : '0' ?>">
                                <i class="bx <?= $isFavorited ? 'bxs-heart' : 'bx-heart' ?>"></i>
                                <span class="fav-text"><?= $isFavorited ? 'Favorited' : 'Add to Favorites' ?></span>
                                <span class="ml-1 text-sm opacity-70 fav-count">(<?= (int)$favCount ?>)</span>
                            </button>
                        <?php elseif (!empty($_SESSION['user_id']) && (($_SESSION['role'] ?? '') === 'admin')): ?>
                            <button type="button" class="btn btn-outline btn-accent tooltip cursor-not-allowed" 
                                data-tip="Admins cannot add favorites" disabled aria-disabled="true">
                                <i class="bx bx-heart"></i>
                                <span>Favorites disabled for admins</span>
                                <span class="ml-1 text-sm opacity-70">(<?= (int)$favCount ?>)</span>
                            </button>

                        <?php else: ?>
                            <a href="loginRegister.php" class="btn btn-outline btn-accent">
                                <i class='bx bx-heart'></i> Login to Favorite
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <section class="relative z-10 max-w-7xl mx-auto px-4 pb-16 space-y-8">

        <div class="bg-secondary-bg/90 backdrop-blur-sm border border-border-color rounded-lg p-6 md:p-8">
            <h2 class="text-2xl md:text-3xl font-bold mb-6 flex items-center gap-3 text-accent">
                <i class='bx bx-detail'></i> Overview
            </h2>
            <p class="text-lg text-text-secondary leading-relaxed">
                <?= nl2br(htmlspecialchars($m['description'])) ?>
            </p>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">

            <?php if (!empty($m['trailer_url'])): ?>
                <div id="trailer-section" class="bg-secondary-bg/90 backdrop-blur-sm border border-border-color rounded-lg p-6">
                    <h2 class="text-2xl font-bold mb-6 text-accent flex items-center gap-3">
                        <i class='bx bx-play-circle'></i> Trailer
                    </h2>

                    <div class="aspect-video rounded-lg overflow-hidden bg-base-300 max-w-lg mx-auto">
                        <?php
                        // strpos hinahanap yung "youtube.com" or yung "youtu.be" sa link (parang check kung yt talaga)
                        // then preg_match gets the video (yt) ID
                        // the [0] contains the full match (youtube.com/watch?v=...)
                        // the [1] contains the type (youtu.be/ or v=)
                        // the [2] contains the video ID

                        // Example
                        //  URL: https://www.youtube.com/watch?v=dQw4w9WgXcQ
                        // After preg_match, $matches[2] = dQw4w9WgXcQ
                        // $videoId = dQw4w9WgXcQ
                        
                        $trailerUrl = $m['trailer_url'];
                        if (strpos($trailerUrl, "youtube.com") !== false || strpos($trailerUrl, "youtu.be") !== false) {
                            preg_match('/(youtu\.be\/|v=)([^&]+)/', $trailerUrl, $matches);
                            $videoId = $matches[2] ?? '';
                            if ($videoId) {
                                echo '<iframe class="w-full h-full" src="https://www.youtube.com/embed/' . htmlspecialchars($videoId) . '" allowfullscreen></iframe>';
                            }
                        } else {
                            echo '<video controls class="w-full h-full"><source src="../' . htmlspecialchars($trailerUrl) . '" type="video/mp4"></video>';
                        }
                        ?>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Rating and Review -->
            <!-- -========================================================================== -->
            <div class="bg-secondary-bg/90 backdrop-blur-sm border border-border-color rounded-lg p-6">
                <h2 class="text-2xl font-bold mb-6 flex items-center gap-3 text-accent">
                    <i class='bx bx-message-dots'></i> User Reviews
                    <span class="text-base text-text-secondary">(<span id="reviews-count-number"><?= (int)$totalReviews ?></span>)</span>
                </h2>

                <div id="user-review-section">
                    <?php if (isset($_SESSION['user_id']) && ($_SESSION['role'] ?? '') === 'user'): ?>
                        <?php if (!$rate->hasReviewed($_SESSION['user_id'], $m['id'])): ?>
                            <button id="open-review-modal" class="btn btn-accent mb-6" onclick="review_modal.showModal()">
                                <i class='bx bx-star'></i> Leave a Review
                            </button>
                        <?php else: ?>
                            <?php $userReview = $rate->getUserReview($_SESSION['user_id'], $m['id']); ?>
                            <div class="bg-accent/10 border border-accent/30 rounded-lg p-4 mb-6">
                                <div class="flex justify-between items-start">
                                    <div class="flex-1">
                                        <h3 class="font-bold text-accent mb-2">Your Review</h3>
                                        <p class="text-text-secondary"><?= htmlspecialchars($userReview['review']) ?></p>
                                    </div>
                                    <div class="flex items-center gap-2 ml-4">
                                        <div class="star-rating">
                                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                                <i class="bx <?= $i <= $userReview['rating'] ? 'bxs-star star text-yellow-400' : 'bx-star star empty' ?>"></i>
                                            <?php endfor; ?>
                                        </div>
                                        <span class="text-accent font-semibold"><?= $userReview['rating'] ?></span>
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>
                    <?php elseif (isset($_SESSION['user_id']) && ($_SESSION['role'] ?? '') === 'admin'): ?>
                        <div class="bg-blue-500/10 border border-blue-500/30 rounded-lg p-4 mb-6">
                            <span class="text-blue-400">Admins cannot leave reviews.</span>
                        </div>
                    <?php else: ?>
                        <div class="bg-orange-500/10 border border-orange-500/30 rounded-lg p-4 mb-6">
                            <span class="text-orange-400">Please <a href="loginRegister.php" class="text-accent hover:underline">login</a> to leave a review.</span>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="space-y-4 max-h-96 overflow-y-auto">
                    <?php
                        $skipUserId = null;
                        if (isset($_SESSION['user_id']) && ($_SESSION['role'] ?? '') === 'user' && $rate->hasReviewed($_SESSION['user_id'], $m['id'])) {
                            $skipUserId = (int)$_SESSION['user_id'];
                        }
                    ?>
                    <?php if (!empty($reviews)): ?>
                        <!-- loop each movie review -->
                        <?php foreach ($reviews as $r): ?>
                            <?php if ($skipUserId !== null && isset($r['user_id']) && (int)$r['user_id'] === $skipUserId) { continue; } ?>
                            <div class="bg-card-bg/50 border border-border-color rounded-lg p-4 hover:bg-card-bg/70 transition-colors">
                                <div class="flex justify-between items-center mb-3">
                                    <h4 class="font-semibold text-accent"><?= htmlspecialchars($r['username']) ?></h4>
                                    <div class="flex items-center gap-2">
                                        <div class="star-rating">
                                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                                <i class="bx <?= $i <= $r['rating'] ? 'bxs-star star text-yellow-400' : 'bx-star star empty' ?>" style="font-size: 1rem;"></i>
                                            <?php endfor; ?>
                                        </div>
                                        <span class="text-accent font-semibold text-sm"><?= $r['rating'] ?></span>
                                    </div>
                                </div>
                                <p class="text-text-secondary text-sm"><?= htmlspecialchars($r['review']) ?></p>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="text-center py-8">
                            <p class="text-text-muted" id="no-reviews-placeholder">No reviews yet. Be the first to review this movie!</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </section>

    <!-- Related movies -->
    <section class="relative z-10 max-w-7xl mx-auto px-4 pb-16">
        <div class="flex items-end justify-between mb-4">
            <h2 class="text-2xl md:text-3xl font-bold text-accent">
                Related to "<?= htmlspecialchars($m['title']) ?>"
            </h2>
        </div>
        <div id="relatedGrid" class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-6 gap-6"></div>
        <div id="relatedEmpty" class="text-text-muted text-sm mt-4 hidden">No related movies found.</div>
    </section>

    <?php if (isset($_SESSION['user_id']) && ($_SESSION['role'] ?? '') === 'user'): ?>
        <?php include __DIR__ . '/../partials/rateReviewModal.php'; ?>
    <?php endif; ?>

    <?php include __DIR__ . '/../partials/footer.php'; ?>

    <script>
        $(function() {
            const $btn = $('#favorite-btn'); 
            const $icon = $btn.find('i'); 
            const $text = $btn.find('.fav-text');
            const $count = $btn.find('.fav-count');

            if (!$btn.length) return;

            $btn.on('click', function() {
                $.post('../db/favoriteRequests.php', {
                action: 'toggle',
                movie_id: $btn.data('movie-id')
                }, function(res) {
                    if (!res?.success) {
                        console.log('Hindi gumagana:', res);
                        return alert(res?.message || 'Failed');
                    }

                    console.log('Working na!!!!!:', res);
                    const fav = !!res.favorited;

                    $btn
                        .toggleClass('btn-accent', fav)
                        .toggleClass('btn-outline', !fav);

                    $icon.attr('class', 'bx ' + (fav ? 'bxs-heart' : 'bx-heart'));
                    $text.text(fav ? 'Favorited' : 'Add to Favorites');
                    $count.text(`(${res.count || 0})`);

                    $icon.fadeIn(300);
                    $text.fadeIn(300);
                    $count.fadeIn(300);

                    }, 'json').fail((xhr) => {
                    alert(xhr.responseText || 'Request failed');
                    });
                });
            });


        // Related movies
        (function($) {
            const movieId = <?= (int)$m['id'] ?>;

            function escapeHtml(str) {
                return String(str || '')
                    .replace(/&/g, '&amp;')
                    .replace(/</g, '&lt;')
                    .replace(/>/g, '&gt;')
                    .replace(/"/g, '&quot;')
                    .replace(/'/g, '&#039;');
            }

            function buildCard(mv) {
                const posterPath = mv.poster_url ? ('../' + mv.poster_url) : 'https://placehold.co/300x450?text=No+Poster';
                const rating = (mv.avg_rating !== null && mv.avg_rating !== undefined) ? Number(mv.avg_rating).toFixed(1) : 'N/A';
                const year = mv.release_year ? `(${mv.release_year})` : '';
                const country = mv.country_name ? `<span class="px-2 py-0.5 rounded bg-neutral-800/70 border border-neutral-700">${escapeHtml(mv.country_name)}</span>` : '';
                const language = mv.language_name ? `<span class="px-2 py-0.5 rounded bg-neutral-800/70 border border-neutral-700">${escapeHtml(mv.language_name)}</span>` : '';

                return `
                <div class="group rounded-xl overflow-hidden bg-neutral-900 border border-neutral-800 hover:border-green-500/70 transition transform hover:-translate-y-2 hover:shadow-xl hover:shadow-green-500/20 flex flex-col">
                    <div class="relative">
                        <a href="viewMovie.php?id=${mv.id}">
                            <img src="${escapeHtml(posterPath)}" alt="${escapeHtml(mv.title)}" class="w-full h-80 object-cover transition-transform duration-300 group-hover:scale-105">
                        </a>
                        <div class="absolute top-2 right-2">
                            <span class="bg-green-600/90 text-white font-semibold text-xs px-2 py-1 rounded-md flex items-center gap-1">
                                <i class='bx bxs-star text-yellow-300'></i>${rating}
                            </span>
                        </div>
                    </div>
                    <div class="p-4 flex flex-col flex-grow">
                        <div class="flex-grow">
                            <h5 class="font-semibold text-base mb-1 text-white leading-tight">
                                ${escapeHtml(mv.title)} <small class="text-gray-400 font-normal">${year}</small>
                            </h5>
                            <div class="text-gray-400 text-xs flex flex-wrap gap-2 mb-3">
                                ${country}${language}
                            </div>
                        </div>
                    </div>
                </div>`;
            }

            function renderRelated(list) {
                const $grid = $('#relatedGrid');
                const $empty = $('#relatedEmpty');
                if (!Array.isArray(list) || list.length === 0) {
                    $grid.empty();
                    $empty.removeClass('hidden');
                    return;
                }
                $empty.addClass('hidden');
                $grid.html(list.map(buildCard).join(''));
            }

            function fetchRelated() {
                $.getJSON('../db/recommendRequests.php', 
                { 
                    action: 'related', 
                    movie_id: movieId, 
                    limit: 12 
                })
                .done(res => renderRelated(res && res.success ? res.data : []))
                .fail(() => renderRelated([]));
            }

            $(function() {
                fetchRelated();
            });
        })(jQuery);
    </script>
</body>

</html>