# Movie Recommendation System 

An end‑to‑end mini platform for browsing movies, rating and reviewing them, favoriting titles, and getting recommendations. Built with PHP (mysqli), MySQL, TailwindCSS/DaisyUI for UI, and jQuery for client‑side interactions.


## What this project is

- A simple catalog + recommendation app where:
	- Guests can browse, search, and view movie details.
	- Users can register/login, favorite movies, and leave one review per movie.
	- Admins can add/edit/delete movies, upload posters/backgrounds, assign genres, and manage cast/directors.
- Recommendations are computed from ratings and a user’s favorites (genres/countries/languages) with helpful “shelves” on the home page.


## Tech stack

- PHP 8+ with mysqli
- MySQL 5.7+/8 (schema in `docs/db.txt`)
- TailwindCSS + DaisyUI + Boxicons (CDN)
- jQuery 3.x + jQuery UI (for cast/director autocomplete)


## Project structure (high level)

- `index.php` – home page: search, trending, latest, personalized shelves, full grid
- `pages/`
	- `viewMovie.php` – movie details, trailer, genres, cast/crew, average rating, user reviews, favorite toggle
	- `manageMovie.php` – admin form to add/edit a movie, upload media, pick genres, manage cast/crew
	- `loginRegister.php` – simple register/login forms
- `classes/` – core PHP classes (see API below)
- `db/` – request handlers/endpoints (AJAX form targets)
- `partials/` – header, footer, review modal
- `uploads/` – posters and backgrounds storage
- `JSON/` – countries and languages datasets used by admin form
- `docs/db.txt` – full database schema and seed data (genres)


## Roles and permissions

- Guest
	- Browse home page, trending/latest, genre shelves
	- Search by title
	- View movie details
	- Cannot favorite or review; prompted to log in
- User (role = `user`)
	- All guest capabilities
	- Toggle favorites (one per movie)
	- Submit exactly one rating + review per movie
	- Get personalized shelves (by favorite genres/countries/languages)
- Admin (role = `admin`)
	- All guest capabilities
	- Add new movies (title, year, poster, optional background, trailer URL, country, language, genres)
	- Edit/update existing movies and media
	- Delete movies
	- Manage cast/crew (directors, actors) via autocomplete or free‑text add

Note: The UI hides admin actions for non‑admins.

## How it works (flow)

1) Authentication
- `Authentication` class handles register (hashes password), login (verifies hash), and logout.
- Registration defaults to role `user`. To create an admin, set `role='admin'` in DB.

2) Movies and media
- Admin uses `manageMovie.php` to add/edit movies.
- Images are saved under `uploads/posters/` and `uploads/backgrounds/` with sanitized filenames: `{title_lower}_{releaseYear}.{ext}`.
- Countries/languages are normalized: if not existing, they are inserted and linked by FK.
- Genres are a many‑to‑many link via `movie_genres`.

3) Cast and crew
- `People` class stores individuals in `movie_people` and links them to movies via `movie_cast` with a role enum: `Director` or `Cast`.
- Admin can add new or search existing people (jQuery UI autocomplete) and attach/detach them to/from a movie.

Note and disadvantages: If the movie is being created, casts and directors can be attach because it doesnt have id yet. Because it needs to have a movieId as a key to save in the DB.

4) Ratings and reviews
- `RateReview` supports a single review per user per movie (DB unique key enforces it).
- Average rating is computed from all reviews and displayed on cards and the detail page.
- Review submission happens from a modal (AJAX), updating the average and counts live.

5) Favorites
- Users can favorite/unfavorite a movie. The state is reflected on the detail page and included in personalized recommendations.

6) Recommendations
- Trending: ordered by average rating desc, then number of reviews desc, then release year desc.
- Latest: ordered by release year desc.
- Personalized shelves: based on a user’s top favorited genres, and separately, countries and languages; excludes movies already favorited by the user.
- Genre shelves: if logged in, top genres come from user favorites; otherwise, global top genres.


## Class APIs

### classes/Authentication.php
- `__construct()` – opens DB connection.
- `registerUser(string $username, string $password, string $role='user'): bool` – creates a user with hashed password; returns false if username exists.
- `loginUser(string $username, string $password): bool` – verifies password and sets `$_SESSION[user_id, username, role]`.
- `logoutUser(): void` – destroys the session.

### classes/Movie.php
- `getAllMovies(): array` – all movies with country/language names.
- `getMovieById(int $id): ?array` – one movie with country/language names.
- `addMovie($title, $description, $releaseYear, $posterFile, $backgroundFile, $trailerUrl, $countryName, $languageName, array $genreIds): int` – creates a movie, uploads media, sets genres; returns new movie id.
- `updateMovie($id, $title, $description, $releaseYear, $posterFile, $backgroundFile, $trailerUrl, $countryName, $languageName, array $genreIds): bool` – updates movie and media; cleans old files if replaced.
- `deleteMovie(int $id): bool` – deletes movie and its media files.
- `getAllGenres(): array` – list of genres.
- `getGenresByMovie(int $movieId, string $return='name'): array` – returns genre names or ids.
- `updateMovieGenres(int $movieId, array $genreIds): void` – replaces all movie genre links.
- Private helpers: `getCountryId`, `getLanguageId`, `handleUpload`, `handleUploadTo`.

### classes/People.php
- `addPerson(string $name): int` – upsert by name; returns person id.
- `attachToMovie(int $movieId, int $personId, string $role): bool` – links person as Director/Cast (duplicates prevented).
- `getMoviePeople(int $movieId, string $role=''): array` – people by movie and optional role.
- `removeFromMovie(int $id): bool` – deletes a `movie_cast` row by id.
- `search(string $term): array` – top 15 matches by name for autocomplete.

### classes/Favorite.php
- `isFavorited(int $userId, int $movieId): bool`
- `addFavorite(int $userId, int $movieId): bool` – safe insert (idempotent via unique key).
- `removeFavorite(int $userId, int $movieId): bool`
- `toggleFavorite(int $userId, int $movieId): bool` – returns new state (true if now favorited).
- `countByMovie(int $movieId): int`

### classes/RateReview.php
- `getReviewsByMovie(int $movieId): array` – latest first, joined with username.
- `addReview(int $userId, int $movieId, int $rating, string $review): bool`
- `hasReviewed(int $userId, int $movieId): bool`
- `getUserReview(int $userId, int $movieId): ?array`
- `getAverageRating(int $movieId): array{avg: float|null, total: int}`

### classes/Recommend.php
- `getTrending(int $limit=12): array`
- `getLatest(int $limit=12): array`
- `searchByTitle(string $term, int $limit=24): array`
- `basedOnFavoriteGenres(int $userId, int $limit=12): array` – excludes already favorited movies.
- `basedOnFavoriteCountries(int $userId, int $limit=12): array`
- `basedOnFavoriteLanguages(int $userId, int $limit=12): array`
- `getUserTopGenres(int $userId, int $limit=5): array`
- `getTopGenresOverall(int $limit=5): array` – fallback to most common genres if no favorites.
- `getByGenre(int $genreId, int $userId=0, int $limit=12): array` – excludes user favorites when `userId>0`.


## Endpoints (request handlers)

All handlers live in `db/` and expect form or JSON‑like requests.

- `db/authRequests.php` (POST)
	- `action=register` – fields: `username, password`
	- `action=login` – fields: `username, password`
	- `action=logout`

- `db/movieRequests.php` (POST; should be admin‑only)
	- `action=add|update|delete`
	- Common fields: movie fields from `manageMovie.php` (`title, description, release_year, poster_file, background_file, trailer_url, countryName, languageName, genres[]`)

- `db/peopleRequests.php` (POST, JSON response)
	- `action=fetch|load` – fields: `movie_id, role(Director|Cast)` – returns `[{id,name}]`
	- `action=add` – fields: `movie_id, role, name` – returns `{success, message, person_id}`
	- `action=remove` – fields: `id` (movie_cast.id) – returns `{success, message}`
	- `action=search` – fields: `query` – returns `[{id,name}]` (for autocomplete)

- `db/favoriteRequests.php` (POST, JSON response; requires login)
	- `action=status|toggle|add|remove` – fields: `movie_id`; returns `{success, favorited, count}`

- `db/rateRequests.php` (POST, JSON response; role=user)
	- Fields: `movie_id, rating(1..5), review`
	- Returns `{success, message, average:{avg,total}}`

- `db/recommendRequests.php` (GET/POST, JSON response)
	- `action=trending|latest|search|favGenres|favCountries|favLanguages|topGenres|byGenre`
	- Common: `limit` (optional)
	- `search`: `q` (query)
	- `byGenre`: `genre_id`


## Installation and setup

Prerequisites
- PHP 8+ with mysqli extension
- MySQL 5.7+/8
- A local web server stack (ex., XAMPP)


## Security notes and limitations

- Server‑side authorization for movie create/update/delete is not enforced in `db/movieRequests.php`. Add role checks (admin) before performing mutations.
- Add CSRF protection for all POST endpoints.
- Validate and sanitize uploads more strictly (MIME type, size limits, image dimensions).
- Consider rate limiting, password policy, and stronger session management for production.


## Troubleshooting

- If images are not showing, confirm the `uploads/` folders exist and are readable by the server.
- If country/language drop‑downs are empty, verify the JSON files in `JSON/` and that `manageMovie.php` can read them.
- If reviews don’t appear, verify `ratings_reviews` table and that the user role is `user`.
- If recommendations are empty for a user, favorites and rate/review a few movies to seed preferences.


## License

No explicit license specified in this repository. Assume all rights reserved unless stated otherwise.


