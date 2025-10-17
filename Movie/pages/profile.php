<?php
session_start();
$userId = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : 0;
if ($userId <= 0) {
    header('Location: ../pages/loginRegister.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en" data-theme="dark">

<head>
    <meta charset="UTF-8" />
    <title>My Profile — Personalized Recommendations</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdn.jsdelivr.net/npm/daisyui@4.4.24/dist/full.min.css" rel="stylesheet" type="text/css" />
    <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Oswald:wght@200..700&display=swap" rel="stylesheet">

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
    </style>
</head>

<body class="min-h-screen text-text-primary bg-primary-bg">
    <?php include __DIR__ . '/../partials/header.php'; ?>

    <main class="px-6 md:px-10 py-10">
        <h1 class="text-3xl font-bold mb-8">My Profile</h1>

        <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
            <div class="mb-6 p-4 rounded-lg bg-yellow-900/60 border border-yellow-700 text-yellow-200 text-lg font-semibold">
                You are logged in as an <span class="font-bold">Admin</span>. You can manage movies, but adding favorites and submitting ratings or reviews is not available for your role.
            </div>
        <?php endif; ?>


        <!-- Tabs -->
        <div role="tablist" class="tabs tabs-bordered">
            <input type="radio" name="profile_tabs" role="tab" class="tab" aria-label="Favorites" checked />
            <div role="tabpanel" class="tab-content py-6">
                <div id="favAgg" class="mb-6 space-y-2">
                    <div id="favAggGenres"></div>
                    <div id="favAggCountries"></div>
                </div>
                <section id="shelfFavorites"></section>
                <div id="emptyFavorites" class="hidden text-center py-10 text-gray-400">
                    <i class="bx bx-heart text-5xl mb-2"></i>
                    <div class="text-lg">You haven't favorited any movies yet.</div>
                </div>
            </div>

            <input type="radio" name="profile_tabs" role="tab" class="tab" aria-label="Rated" />
            <div role="tabpanel" class="tab-content py-6">
                <div id="ratedAgg" class="mb-6 space-y-2">
                    <div id="ratedAggGenres"></div>
                    <div id="ratedAggCountries"></div>
                </div>
                <section id="shelfRated"></section>
                <div id="emptyRated" class="hidden text-center py-10 text-gray-400">
                    <i class="bx bx-star text-5xl mb-2"></i>
                    <div class="text-lg">You haven't rated any movies yet.</div>
                </div>
            </div>

            <input type="radio" name="profile_tabs" role="tab" class="tab" aria-label="Recommendations" />
            <div role="tabpanel" class="tab-content py-6">
                <section id="shelfFavGenres" class="mb-12"></section>
                <section id="shelfTopGenres" class="mb-12"></section>
                <section id="shelfFavCountries" class="mb-12"></section>
            </div>
        </div>
    </main>

    <?php include __DIR__ . '/../partials/footer.php'; ?>
    <script src="../src/js/jquery.mins.js"></script>
    <script>
        function buildCard(m) {
            const poster = m.poster_url && m.poster_url.length ?
                `../${escapeHtml(m.poster_url)}` :
                'https://placehold.co/300x450?text=No+Poster';

            const rating = (m.avg_rating !== null && m.avg_rating !== undefined) ?
                Number(m.avg_rating).toFixed(1) :
                'N/A';

            const year = m.release_year ? `(${m.release_year})` : '';
            const ratingBadge = `<div class="absolute top-2 right-2">
                <span class="bg-green-600/90 text-white font-semibold text-xs px-2 py-1 rounded-md flex items-center gap-1">
                <i class='bx bxs-star text-yellow-300'></i>${rating}
                </span>
            </div>`;

            return `
                <div class="group rounded-xl overflow-hidden bg-neutral-900 border border-neutral-800 hover:border-green-500/70 transition transform hover:-translate-y-2 hover:shadow-xl hover:shadow-green-500/20 flex flex-col">
                <div class="relative">
                    <a href="../pages/viewMovie.php?id=${m.id}">
                    <img src="${poster}" alt="${escapeHtml(m.title)}" 
                        class="w-full h-80 object-cover transition-transform duration-300 group-hover:scale-105">
                    </a>
                    ${ratingBadge}
                </div>
                <div class="p-4 flex flex-col flex-grow">
                    <h5 class="font-semibold text-base mb-1 text-white leading-tight">
                    ${escapeHtml(m.title)} <small class="text-gray-400 font-normal">${year}</small>
                    </h5>
                </div>
                </div>`;
        }

        function renderShelf($root, title, items) {
            if (!items || !items.length) {
                if ($root.attr('id') === 'shelfFavorites') {
                    $('#emptyFavorites').removeClass('hidden');
                    $root.empty();
                } else if ($root.attr('id') === 'shelfRated') {
                    $('#emptyRated').removeClass('hidden');
                    $root.empty();
                }
                return;
            } else {
                if ($root.attr('id') === 'shelfFavorites') $('#emptyFavorites').addClass('hidden');
                if ($root.attr('id') === 'shelfRated') $('#emptyRated').addClass('hidden');
            }
            const html = items.map(buildCard).join('');
            $root.html(`
                        <div class="border-t border-neutral-800 mb-6"></div>
                        <h3 class="text-2xl font-semibold mb-4">${title}</h3>
                        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-6 gap-6">
                        ${html}
                        </div>
                    `);
        }

        function escapeHtml(str) {
            return String(str || '')
                .replace(/&/g, '&amp;').replace(/</g, '&lt;')
                .replace(/>/g, '&gt;').replace(/"/g, '&quot;')
                .replace(/'/g, '&#039;');
        }

        function renderPills($root, label, items) {
            $root.empty(); // Clear container first
            if (!Array.isArray(items) || items.length === 0) { 
                return;
            }

            // create/build pill HTML para sa each genre with cnt > 0
            var pills = [];
            for (var i = 0; i < items.length; i++) {
                var it = items[i];
                var count = Number(it.cnt); // make sure na number
                if (!isFinite(count) || count <= 0) continue; // skip if count is not positive  

                var name = escapeHtml(it.name);
                var pillHtml =
                    '<span class="badge badge-outline border-neutral-700 text-gray-300 mr-2 mb-2">' +
                        name + ': <span class="ml-1 font-semibold text-white">' + count + '</span>' +
                    '</span>';
                pills.push(pillHtml);
            }

            if (pills.length === 0) { // blank
                return;
            }

            //  display
            var html =
                '<div class="mb-1 text-sm text-gray-400">' + escapeHtml(label) + '</div>' +
                '<div class="flex flex-wrap">' + pills.join('') + '</div>';

            $root.html(html);
        }

        // Load shelves
        $(function() {
            // Favorites
            $.getJSON('../db/recommendRequests.php', {
                action: 'favorites',
                limit: 500
            }, res => {
                if (res && res.success) renderShelf($('#shelfFavorites'), 'Your Favorites', res.data);
            });

            // Favorites counts
            $.getJSON('../db/recommendRequests.php', {
                action: 'favGenreCounts'
            }, res => {
                if (res && res.success) renderPills($('#favAggGenres'), 'Genres you favorited', res.data);
            });
            $.getJSON('../db/recommendRequests.php', {
                action: 'favCountryCounts'
            }, res => {
                if (res && res.success) renderPills($('#favAggCountries'), 'Countries you favorited', res.data);
            });

            // Rated
            $.getJSON('../db/recommendRequests.php', {
                action: 'rated',
                limit: 500
            }, res => {
                if (res && res.success) renderShelf($('#shelfRated'), 'Movies You Rated', res.data);
            });

            // Rated counts
            $.getJSON('../db/recommendRequests.php', {
                action: 'ratedGenreCounts'
            }, res => {
                if (res && res.success) renderPills($('#ratedAggGenres'), 'Genres you rated', res.data);
            });
            $.getJSON('../db/recommendRequests.php', {
                action: 'ratedCountryCounts'
            }, res => {
                if (res && res.success) renderPills($('#ratedAggCountries'), 'Countries you rated', res.data);
            });

            // Recommendations
            $.getJSON('../db/recommendRequests.php', {
                action: 'favGenres',
                limit: 6
            }, res => {
                if (res && res.success) renderShelf($('#shelfFavGenres'), 'Because you like these genres', res.data);
            });
            $.getJSON('../db/recommendRequests.php', {
                action: 'favCountries',
                limit: 6
            }, res => {
                if (res && res.success) renderShelf($('#shelfFavCountries'), 'From countries you like', res.data);
            });
            $.getJSON('../db/recommendRequests.php', {
                action: 'topGenres',
                limit: 6
            }, res => { // after getting the topGenres id, proceed sa byGenre to get movies excluding yung favorite na
                if (!res || !res.success || !Array.isArray(res.data)) return;
                const list = res.data.slice(0, 6);
                const $wrap = $('#shelfTopGenres');
                list.forEach(g => {
                    const shelfId = 'genreShelf_' + g.id;
                    $wrap.append(`<section id="${shelfId}" class="mb-12"></section>`);
                    $.getJSON('../db/recommendRequests.php', {
                        action: 'byGenre',
                        genre_id: g.id,
                        limit: 6
                    }, res2 => { // since top 5 genre, 6 movie each
                        if (res2 && res2.success) renderShelf($('#' + shelfId), `Popular in ${g.name}`, res2.data);
                    });
                });
            });
        });
    </script>
</body>

</html>