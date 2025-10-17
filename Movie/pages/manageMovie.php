<?php
require_once __DIR__ . '/../classes/Movie.php';
$movie = new Movie();

$editing = false;
$movieData = [
  'id' => '',
  'title' => '',
  'description' => '',
  'release_year' => '',
  'poster_url' => '',
  'background_url' => '',
  'trailer_url' => ''
];

$allGenres = $movie->getAllGenres();
$selectedGenres = [];

// if may id, edit mode
if (!empty($_GET['id'])) {
  $editing = true;
  $movieData = $movie->getMovieById($_GET['id']);
  $movieId = $_GET['id'];
  $selectedGenres = $movie->getGenresByMovie($movieId, 'id');
  if (!$movieData) { // invalid id
    header("Location: ../index.php");
    exit;
  }
}

$actionText = $editing ? "Update Movie" : "Add Movie";
$submitAction = $editing ? "update" : "add";


// References:
// https://stackoverflow.com/questions/19758954/get-data-from-json-file-with-php
// https://stackoverflow.com/questions/412467/how-to-embed-youtube-videos-in-php
// https://stackoverflow.com/questions/19050890/find-youtube-link-in-php-string-and-convert-it-into-embed-code
// https://stackoverflow.com/questions/5830387/how-do-i-find-all-youtube-video-ids-in-a-string-using-a-regex
// https://stackoverflow.com/questions/19050890/find-youtube-link-in-php-string-and-convert-it-into-embed-code
// https://stackoverflow.com/questions/9656523/jquery-autocomplete-with-callback-ajax-json
// https://stackoverflow.com/questions/22061558/ajax-jquery-toggle-button


$countryJsonContent = file_get_contents(__DIR__ . '/../JSON/countries.json'); // load
// $countryJsonContent = file_get_contents(__DIR__ . '/../JSON/country.json'); // load
$languageJsonContent = file_get_contents(__DIR__ . '/../JSON/language.json');

$countries = json_decode($countryJsonContent, true); // true = associative array
$languages = json_decode($languageJsonContent, true);

// foreach ($countries as $country) {
//     echo $country['name'] . ' (' . $country['code'] . ')<br>';
// }
?>

<!DOCTYPE html>
<html lang="en" data-theme="dark">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title><?= $actionText ?></title>
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
  <style>
    body {
      font-family: 'Oswald', sans-serif;
    }

    .accordion-icon {
      transition: transform 0.3s ease;
    }

    .accordion-section[open] .accordion-icon {
      transform: rotate(90deg);
    }

    /* jQuery UI autocomplete styling */
    .ui-autocomplete {
      background: #334155 !important;
      border: 2px solid #475569 !important;
      border-radius: 8px !important;
      color: #f8fafc !important;
      max-height: 200px;
      overflow-y: auto;
    }

    .ui-autocomplete .ui-menu-item {
      padding: 8px 12px !important;
      border-bottom: 1px solid #475569 !important;
    }

    .ui-autocomplete .ui-menu-item:hover,
    .ui-autocomplete .ui-menu-item.ui-state-focus {
      background: #0891b2 !important;
      color: white !important;
    }

    .person-badge {
      background: linear-gradient(135deg, #0891b2 0%, #10b981 100%);
      color: white;
      padding: 8px 14px;
      border-radius: 20px;
      margin: 4px;
      display: inline-flex;
      align-items: center;
      gap: 8px;
      font-size: 13px;
      font-weight: 500;
    }

    .remove-person {
      background: rgba(255,255,255,0.2);
      border: none;
      color: white;
      font-size: 14px;
      cursor: pointer;
      width: 18px;
      height: 18px;
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
    }

    .empty-state {
      color: #64748b;
      font-style: italic;
      text-align: center;
      padding: 20px;
      background: #475569;
      border-radius: 8px;
      border: 2px dashed #64748b;
    }
  </style>
</head>
<body class="min-h-screen bg-gradient-to-br from-slate-900 via-slate-800 to-slate-900 text-slate-100">
  
  <?php include __DIR__ . '/../partials/header.php'; ?>  

  <main class="max-w-6xl mx-auto py-8 px-4 md:px-8">
    <div class="bg-slate-800 border border-slate-600 rounded-2xl shadow-2xl overflow-hidden">
      <form action="../db/movieRequests.php" method="POST" enctype="multipart/form-data">
        <input type="hidden" name="action" value="<?= $submitAction ?>" />
        <?php if ($editing): ?>
          <input type="hidden" name="id" value="<?= $movieData['id'] ?>" />
        <?php endif; ?>

        <!-- Basic Info -->
        <details class="accordion-section border-b border-slate-600" open>
          <summary class="bg-gradient-to-r from-slate-700 to-slate-600 hover:from-slate-600 hover:to-slate-500 cursor-pointer p-6 flex items-center gap-3 text-lg font-semibold text-white transition-all duration-300">
            <i class="bx bx-chevron-right accordion-icon text-xl"></i>
            <i class="bx bx-info-circle text-cyan-400"></i>
            Basic Information
          </summary>
          <div class="p-8 bg-slate-800">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
              <div class="space-y-2">
                <label class="flex items-center gap-2 text-slate-300 font-medium text-sm">
                  <i class="bx bx-movie text-cyan-400"></i>
                  Title <span class="text-red-400">*</span>
                </label>
                <input type="text" name="title" value="<?= htmlspecialchars($movieData['title']) ?>" 
                       class="w-full px-4 py-3 bg-slate-700 border-2 border-slate-600 rounded-lg text-white placeholder-slate-400 focus:border-cyan-500 focus:ring-0 focus:outline-none transition-all duration-300" 
                       placeholder="Enter movie title..." required />
              </div>
              
              <div class="space-y-2">
                <label class="flex items-center gap-2 text-slate-300 font-medium text-sm">
                  <i class="bx bx-calendar text-cyan-400"></i>
                  Release Year
                </label>
                <input type="number" name="release_year" value="<?= $movieData['release_year'] ?>" min="1960" max="<?= date('Y') ?>" 
                       class="w-full px-4 py-3 bg-slate-700 border-2 border-slate-600 rounded-lg text-white placeholder-slate-400 focus:border-cyan-500 focus:ring-0 focus:outline-none transition-all duration-300" 
                       placeholder="<?= date('Y') ?>" />
              </div>
              
              <div class="space-y-2">
                <label class="flex items-center gap-2 text-slate-300 font-medium text-sm">
                  <i class="bx bx-world text-cyan-400"></i>
                  Country <span class="text-red-400">*</span>
                </label>
                <select name="countryName" 
                        class="w-full px-4 py-3 bg-slate-700 border-2 border-slate-600 rounded-lg text-white focus:border-cyan-500 focus:ring-0 focus:outline-none transition-all duration-300" required>
                  <option value="">-- Select Country --</option>
                  <?php foreach ($countries as $country): ?>
                    <!-- <option value="<?= htmlspecialchars($country['name']) ?>" <?= ($editing && $movieData['country_name'] === $country['name']) ? 'selected' : '' ?>>
                      <?= htmlspecialchars($country['name']) ?>
                    </option> -->
                     <option value="<?= htmlspecialchars($country['country']) ?>" <?= ($editing && $movieData['country_name'] === $country['country']) ? 'selected' : '' ?>>
                      <?= htmlspecialchars($country['country']) ?>
                    </option>
                  <?php endforeach; ?>
                </select>
              </div>
              
              <div class="space-y-2">
                <label class="flex items-center gap-2 text-slate-300 font-medium text-sm">
                  <i class="bx bx-message text-cyan-400"></i>
                  Language <span class="text-red-400">*</span>
                </label>
                <select name="languageName" 
                        class="w-full px-4 py-3 bg-slate-700 border-2 border-slate-600 rounded-lg text-white focus:border-cyan-500 focus:ring-0 focus:outline-none transition-all duration-300" required>
                  <option value="">-- Select Language --</option>
                  <?php foreach ($languages as $language): ?>
                    <option value="<?= htmlspecialchars($language['name']) ?>" <?= ($editing && $movieData['language_name'] === $language['name']) ? 'selected' : '' ?>>
                      <?= htmlspecialchars($language['name']) ?>
                    </option>
                  <?php endforeach; ?>
                </select>
              </div>
            </div>
          </div>
        </details>

        <!-- Media -->
        <details class="accordion-section border-b border-slate-600">
          <summary class="bg-gradient-to-r from-slate-700 to-slate-600 hover:from-slate-600 hover:to-slate-500 cursor-pointer p-6 flex items-center gap-3 text-lg font-semibold text-white transition-all duration-300">
            <i class="bx bx-chevron-right accordion-icon text-xl"></i>
            <i class="bx bx-image text-emerald-400"></i>
            Media Files
          </summary>
          <div class="p-8 bg-slate-800">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
              <div class="space-y-2">
                <label class="flex items-center gap-2 text-slate-300 font-medium text-sm">
                  <i class="bx bx-image text-emerald-400"></i>
                  Poster Image <?= $editing ? '' : '<span class="text-red-400">*</span>' ?>
                </label>
                <input type="file" name="poster_file" <?= $editing ? '' : 'required' ?>
                       class="w-full px-4 py-3 bg-slate-700 border-2 border-slate-600 rounded-lg text-white file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-cyan-500 file:text-white hover:file:bg-cyan-600 focus:border-cyan-500 focus:ring-0 focus:outline-none transition-all duration-300" />
                <?php if ($editing && !empty($movieData['poster_url'])): ?>
                  <div class="mt-4">
                    <img src="../<?= htmlspecialchars($movieData['poster_url']) ?>" alt="Current Poster" 
                         class="max-h-48 object-cover rounded-xl border-2 border-slate-600 shadow-lg" />
                  </div>
                <?php endif; ?>
              </div>
              
              <div class="space-y-2">
                <label class="flex items-center gap-2 text-slate-300 font-medium text-sm">
                  <i class="bx bx-landscape text-emerald-400"></i>
                  Background Image
                </label>
                <input type="file" name="background_file" 
                       class="w-full px-4 py-3 bg-slate-700 border-2 border-slate-600 rounded-lg text-white file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-cyan-500 file:text-white hover:file:bg-cyan-600 focus:border-cyan-500 focus:ring-0 focus:outline-none transition-all duration-300" />
                <?php if ($editing && !empty($movieData['background_url'])): ?>
                  <div class="mt-4">
                    <img src="../<?= htmlspecialchars($movieData['background_url']) ?>" alt="Current Background" 
                         class="max-h-48 object-cover rounded-xl border-2 border-slate-600 shadow-lg" />
                  </div>
                <?php endif; ?>
              </div>
              
              <div class="md:col-span-2 space-y-2">
                <label class="flex items-center gap-2 text-slate-300 font-medium text-sm">
                  <i class="bx bx-play-circle text-emerald-400"></i>
                  Trailer URL
                </label>
                <input type="text" name="trailer_url" value="<?= htmlspecialchars($movieData['trailer_url']) ?>" 
                       class="w-full px-4 py-3 bg-slate-700 border-2 border-slate-600 rounded-lg text-white placeholder-slate-400 focus:border-cyan-500 focus:ring-0 focus:outline-none transition-all duration-300" 
                       placeholder="https://www.youtube.com/watch?v=..." />
                <?php if ($editing && !empty($movieData['trailer_url'])): ?>
                  <div class="mt-4 max-w-2xl">
                    <?php
                      $trailerUrl = $movieData['trailer_url'];
                      if (strpos($trailerUrl, "youtube.com") !== false || strpos($trailerUrl, "youtu.be") !== false) {
                        if (preg_match('/(youtu\.be\/|v=)([^&]+)/', $trailerUrl, $matches)) {
                          $videoId = $matches[2];
                          echo '<iframe src="https://www.youtube.com/embed/' . htmlspecialchars($videoId) . '" title="Trailer" class="w-full aspect-video rounded-xl border-2 border-slate-600 shadow-lg" allowfullscreen></iframe>';
                        }
                      } else {
                        echo '<video controls class="w-full rounded-xl border-2 border-slate-600 shadow-lg"><source src="' . htmlspecialchars($trailerUrl) . '" type="video/mp4"></video>';
                      }
                    ?>
                  </div>
                <?php endif; ?>
              </div>
            </div>
          </div>
        </details>

        <!-- Casts  -->
        <details class="accordion-section border-b border-slate-600">
          <summary class="bg-gradient-to-r from-slate-700 to-slate-600 hover:from-slate-600 hover:to-slate-500 cursor-pointer p-6 flex items-center gap-3 text-lg font-semibold text-white transition-all duration-300">
            <i class="bx bx-chevron-right accordion-icon text-xl"></i>
            <i class="bx bx-group text-purple-400"></i>
            Cast & Crew
          </summary>
          <div class="p-8 bg-slate-800">
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
              <div class="bg-slate-700 border-2 border-slate-600 rounded-xl p-6 space-y-4">
                <h5 class="flex items-center gap-3 text-purple-400 font-semibold text-lg">
                  <i class="bx bx-user-voice"></i>
                  Directors
                </h5>
                <div id="directors-list" class="min-h-[80px] p-4 bg-slate-800 rounded-lg border-2 border-slate-600">
                  <div class="text-slate-400 text-center italic py-4">No directors added yet</div>
                </div>
                <input type="text" id="director-input" 
                       class="w-full px-4 py-3 bg-slate-600 border-2 border-slate-500 rounded-lg text-white placeholder-slate-300 focus:border-purple-400 focus:ring-0 focus:outline-none transition-all duration-300" 
                       placeholder="Type director name and press Enter..." />
              </div>
              
              <div class="bg-slate-700 border-2 border-slate-600 rounded-xl p-6 space-y-4">
                <h5 class="flex items-center gap-3 text-purple-400 font-semibold text-lg">
                  <i class="bx bx-group"></i>
                  Casts: (Actors & Actresses)
                </h5>
                <div id="actors-list" class="min-h-[80px] p-4 bg-slate-800 rounded-lg border-2 border-slate-600">
                  <div class="text-slate-400 text-center italic py-4">No actors added yet</div>
                </div>
                <input type="text" id="actor-input" 
                       class="w-full px-4 py-3 bg-slate-600 border-2 border-slate-500 rounded-lg text-white placeholder-slate-300 focus:border-purple-400 focus:ring-0 focus:outline-none transition-all duration-300" 
                       placeholder="Type actor name and press Enter..." />
              </div>
            </div>
          </div>
        </details>

        <!-- Description & Genres -->
        <details class="accordion-section">
          <summary class="bg-gradient-to-r from-slate-700 to-slate-600 hover:from-slate-600 hover:to-slate-500 cursor-pointer p-6 flex items-center gap-3 text-lg font-semibold text-white transition-all duration-300">
            <i class="bx bx-chevron-right accordion-icon text-xl"></i>
            <i class="bx bx-text text-orange-400"></i>
            Description & Genres
          </summary>
          <div class="p-8 bg-slate-800 space-y-8">
            <div class="space-y-2">
              <label class="flex items-center gap-2 text-slate-300 font-medium text-sm">
                <i class="bx bx-text text-orange-400"></i>
                Description
              </label>
              <textarea name="description" rows="6" 
                        class="w-full px-4 py-3 bg-slate-700 border-2 border-slate-600 rounded-lg text-white placeholder-slate-400 focus:border-cyan-500 focus:ring-0 focus:outline-none transition-all duration-300 resize-none" 
                        placeholder="Enter movie description..."><?= htmlspecialchars($movieData['description']) ?></textarea>
            </div>
            
            <div class="space-y-4">
              <label class="flex items-center gap-2 text-slate-300 font-medium text-sm">
                <i class="bx bx-category text-orange-400"></i>
                Genres
              </label>
              <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-3">
                <?php foreach ($allGenres as $genre): ?>
                  <label class="flex items-center gap-3 p-4 bg-slate-700 border-2 border-slate-600 rounded-lg cursor-pointer hover:border-orange-400 hover:bg-slate-600 transition-all duration-300 group">
                    <input type="checkbox" name="genres[]" value="<?= $genre['id'] ?>" <?= in_array($genre['id'], $selectedGenres) ? 'checked' : '' ?> 
                           class="w-4 h-4 text-orange-400 bg-slate-800 border-slate-500 rounded focus:ring-orange-400 focus:ring-2">
                    <span class="text-left text-slate-200 group-hover:text-orange-300 font-medium"><?= htmlspecialchars($genre['name']) ?></span>
                  </label>
                <?php endforeach; ?>
              </div>
            </div>
          </div>
        </details>

        <!-- Buttoness -->
        <div class="flex flex-col sm:flex-row gap-4 p-8 bg-slate-700 border-t border-slate-600">
          <button type="submit" 
                  class="btn btn-accent"
                  >
            <i class="bx <?= $editing ? 'bx-save' : 'bx-plus' ?> text-xl"></i>
            <?= htmlspecialchars($actionText) ?>
          </button>
        </div>
      </form>
    </div>
  </main>

  <?php include __DIR__ . '/../partials/footer.php'; ?>

  <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
  <script src="https://code.jquery.com/ui/1.13.2/jquery-ui.min.js"></script>
  <script>
    const movieId = <?= $editing ? $movieData['id'] : 0 ?>;

    $(document).ready(function() {
        if (movieId > 0) {
            loadPeople('Director');
            loadPeople('Cast');
        }
        setupAutocomplete();
    });

    // Load people from server
    function loadPeople(role) {
        $.ajax({
            url: "../db/peopleRequests.php",
            method: "POST",
            dataType: "json",
            data: {
                action: "fetch",
                movie_id: movieId,
                role: role
            },
            success: function(res) {
                console.log('Nakadisplay na', res); 
                renderPeople(role, res.data || []);
            },
            error: function(xhr) {
                console.error('Hindi ma-load...', xhr.responseText); // respond()
            }
        });
    }

    function renderPeople(role, people) {
        const containerId = role === 'Director' ? '#directors-list' : '#actors-list';
        // if director role, render in #directors-list and same lang sa #actors-list
        let html = '';
        
        if (people.length === 0) {
            html = '<div class="empty-state">No ' + role.toLowerCase() + 's added yet</div>';
        } else {
            people.forEach(function(person) {
                html += `<span class="person-badge">
                    <i class="bx ${role === 'Director' ? 'bx-user-voice' : 'bx-user'}"></i>
                    ${person.name}
                    <button type="button" class="remove-person" data-id="${person.id}" data-role="${role}">
                        <i class="bx bx-x"></i>
                    </button>
                </span>`;
            });
        }
        
        $(containerId).html(html);
    }

    // the downside is that, working lang ang adding ng person if the movie exists, since we need movieID.
    // let's say movie is the parent, person is the child
    // we need a parent to have a child, getch?
    function addPerson(name, role) {
        const trimmed = name.trim(); // remove extra spaces
        if (trimmed === '') {
            console.warn('[People] Add -> empty name, abort.');
            return;
        }

        const payload = {
            action: "add",
            movie_id: movieId, 
            name: trimmed,
            role: role
        };
        console.log('[People] Add -> request', payload);

        $.ajax({
            url: "../db/peopleRequests.php",
            method: "POST",
            dataType: "json",
            data: payload,
            success: function(res, textStatus, jqXHR) {
                console.log('[People] Add -> response', { res, status: textStatus, http: jqXHR.status });
                const inputId = role === 'Director' ? '#director-input' : '#actor-input';
                $(inputId).val('');
                loadPeople(role);
            },
            error: function(xhr, status, err) {
                console.error('[People] Add -> error', { status, err, http: xhr.status, responseText: xhr.responseText });
                alert("Error adding " + role.toLowerCase() + "!");
            }
        });
    }

    $(document).on('click', '.remove-person', function() {
        const id = $(this).data('id');
        const role = $(this).data('role');
        const personName = $(this).parent().text().trim();

        console.log('[People] Remove -> clicked', { id, role, personName });

        if (confirm(`Are you sure you want to remove this ${role.toLowerCase()}?\nName: ${personName}`)) {
            const payload = {
                action: "remove",
                id: id,
                movie_id: movieId,
                role: role
            };
            console.log('[People] Remove -> request', payload);

            $.ajax({
                url: "../db/peopleRequests.php",
                method: "POST",
                dataType: "json",
                data: payload,
                success: function(res, textStatus, jqXHR) {
                    console.log('[People] Remove -> response', { res, status: textStatus, http: jqXHR.status });
                    if (Array.isArray(res) || Array.isArray(res?.data)) {
                        const people = Array.isArray(res) ? res : res.data;
                        renderPeople(role, people);
                    } else {
                        loadPeople(role);
                    }
                },
                error: function(xhr, status, err) {
                    console.error('[People] Remove -> error', { status, err, http: xhr.status, responseText: xhr.responseText });
                    alert("Error removing " + role.toLowerCase() + "!");
                }
            });
        } else {
            console.log('[People] Remove -> cancelled');
        }
    });

    // when enter, determine kung anong input ginamit to get the role then proceed to addPerson
    $('#director-input, #actor-input').on('keydown', function(e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            const role = $(this).attr('id') === 'director-input' ? 'Director' : 'Cast';
            const name = $(this).val();
            addPerson(name, role);
        }
    });

    // more like input search with autocomplete
    function setupAutocomplete() {
      $('#director-input, #actor-input').autocomplete({
        source: function(request, response) {
          console.log('[Autocomplete] query:', request.term);
          $.ajax({
            url: "../db/peopleRequests.php",
            method: "POST",
            dataType: "json",
            data: { action: "search", query: request.term }, // input then search
            success: function(res) {
              console.log('[Autocomplete] results:', res);
              const people = Array.isArray(res) ? res : (res.data || []);
              response(people.map(p => p.name)); // or map to {label:p.name, value:p.name, id:p.id}
            },
            error: function(xhr, status, err) {
              console.error('[Autocomplete] error:', { status, err, http: xhr.status, responseText: xhr.responseText });
              response([]);
            }
          });
        },
        minLength: 2, // ---
        delay: 300
      });
    }
  </script>
</body>

</html>
