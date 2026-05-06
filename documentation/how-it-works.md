# CineMatch — How It Works

Complete system flow documentation covering authentication, movie browsing, favorites, reviews, recommendations, and admin management.

---

## Table of Contents

1. [System Overview](#1-system-overview)
2. [Authentication Flow](#2-authentication-flow)
3. [Home Page — Movie Browsing](#3-home-page--movie-browsing)
4. [Movie Detail Page](#4-movie-detail-page)
5. [Favorites System](#5-favorites-system)
6. [Ratings & Reviews System](#6-ratings--reviews-system)
7. [Recommendation System](#7-recommendation-system)
8. [Profile Page](#8-profile-page)
9. [Movie People (Cast & Directors)](#9-movie-people-cast--directors)
10. [Admin — Movie Management](#10-admin--movie-management)
11. [Livewire Component Communication](#11-livewire-component-communication)
12. [Database Structure](#12-database-structure)
13. [Route Reference](#13-route-reference)

---

## 1. System Overview

```
[Home Page]
    │
    ├── Browse movies (grid/list, filter by genre)
    └── Click movie → [Movie Detail Page]
                            │
                            ├── View details (genres, cast, directors, ratings)
                            ├── Add/remove favorite (Livewire)
                            ├── Leave rating & review (Livewire modal)
                            └── See related movies

[Profile Page] (authenticated users)
    │
    ├── Favorites tab → movies user has favorited
    ├── Rated Movies tab → movies user has rated
    └── Recommendations tab → genre-based shelves from favorites + ratings

[Admin — Manage Movies]
    │
    ├── Add / edit / delete movies
    └── Manage cast and directors (Livewire)
```

---

## 2. Authentication Flow

### 2.1 Registration

```
POST /register → AuthController@register
    │
    ├── Validate: name, email, username (unique), password
    ├── Hash password with Hash::make()
    ├── Create User (role = 'user')
    └── Redirect to login with success message
```

### 2.2 Login

```
POST /login → AuthController@login
    │
    ├── Validate: username, password
    ├── Auth::attempt(['username' => ..., 'password' => ...])
    ├── If valid:
    │       ├── session()->regenerate() → prevent session fixation
    │       └── Redirect to home
    └── If invalid → back with error message
```

### 2.3 Logout

```
POST /logout → AuthController@logout
    │
    ├── Auth::logout()
    ├── session()->invalidate()
    ├── session()->regenerateToken()
    └── Redirect to login
```

### 2.4 Social Login (Google / Facebook)

```
GET /auth/google → AuthController@redirectGoogle
    └── Socialite::driver('google')->stateless()->with(['prompt' => 'select_account'])->redirect()

GET /auth/google/callback → AuthController@handleGoogleCallback
    │
    ├── Socialite::driver('google')->stateless()->user()
    ├── findOrCreateSocialUser($socialUser, 'google')
    │       ├── If email exists → update provider_id + provider_token
    │       └── If not → create User (role = 'user', random password)
    ├── Auth::login($user)
    └── Redirect to home
```

Facebook follows the same pattern with `redirectFacebook()` and `handleFacebookCallback()`.

**`generateUsername($socialUser)`** — creates a unique username from the social user's name, stripping invalid characters and appending a number if the username already exists.

See `documentation/md/Authentication.md` for the full detailed flow.

---

## 3. Home Page — Movie Browsing

```
GET / → HomeController (or web.php closure)
    │
    ├── Load movies (paginated or all)
    ├── Load genres for filter
    └── Return home.blade.php
```

The home page displays a grid of movie cards. Each card shows the poster, title, genres, and average rating. Users can filter by genre.

---

## 4. Movie Detail Page

```
GET /movie/{id} → MovieController@show
    │
    ├── MovieHelper::getMovieWithDetails($movieId)
    │       └── Movie with genres, country, language, cast, ratings eager loaded
    ├── MovieHelper::splitCastRoles($movie)
    │       └── Separates cast into $directors and $actors
    ├── MovieHelper::getMovieReviews($movieId)
    │       └── Returns: reviews list, total count, average rating
    ├── MovieHelper::getRelatedMovies($movie)
    │       └── Movies sharing genres, sorted by genre overlap count
    └── Return viewMovie.blade.php with all data
```

**Livewire components on this page:**
- `<livewire:favorite-button :movie="$movie" />` — toggle favorite
- `<livewire:review-section :movie="$movie" />` — review trigger + stats
- `<livewire:reviews-list :movieId="$movie->id" />` — reviews display
- `<livewire:movie-people :movie="$movie" role="Director" />` — directors (admin only)
- `<livewire:movie-people :movie="$movie" role="Cast" />` — cast (admin only)

**Blade components on this page:**
- `<x-trailer-section>` — YouTube trailer embed
- `<x-related-movies>` — related movies grid

---

## 5. Favorites System

### Toggle Favorite (Livewire)

```
User clicks Favorite button
    │
    FavoriteButton@toggleFavorite()
    │
    ├── Check if user already favorited this movie:
    │       ├── Yes → detach from user_favorites pivot
    │       └── No  → attach to user_favorites pivot
    ├── Update $isFavorited state
    ├── Update $favoriteCount
    └── Livewire re-renders only the button component (no page reload)
```

**Data model:** `user_favorites` pivot table — simple link between `users.id` and `movies.id`. No extra data needed, so a pivot table is sufficient (no full model required).

---

## 6. Ratings & Reviews System

### Submit / Update Review (Livewire)

```
User clicks "Leave a Review"
    │
    ReviewSection dispatches → $dispatch('openReviewModal')
    │
    ReviewModal@mount() → $isOpen = true
    │
    ├── If user already reviewed this movie → prefill rating + text
    └── If not → empty form

User submits review
    │
    ReviewModal@submitReview()
    │
    ├── Validate: rating (1-5), review text
    ├── RatingReview::updateOrCreate(['user_id', 'movie_id'], [...])
    ├── Close modal → $isOpen = false
    └── dispatch('reviewUpdated')
            │
            ├── ReviewSection listens → #[On('reviewUpdated')] → refresh stats
            └── ReviewsList listens  → #[On('reviewUpdated')] → refresh list
```

**Why `updateOrCreate`?** A user can only have one review per movie. If they submit again, it updates their existing review.

---

## 7. Recommendation System

The recommendation system is content-based, using the user's genre preferences derived from their favorites and rated movies.

### Full Flow

```
ProfileController@getRecommendationsData($userId)
    │
    ├── MovieHelper::getGenreShelvesForUser($userId, 'favorites', topLimit=5, perGenre=5)
    │       ├── getTopGenresFromFavorites($userId) → top N genres from user_favorites
    │       ├── getExcludedMovieIdsForUser($userId) → movies already favorited or rated
    │       └── For each top genre:
    │               └── Fetch movies in that genre, excluding already-seen movies
    │
    └── MovieHelper::getGenreShelvesForUser($userId, 'rated', topLimit=5, perGenre=5)
            ├── getTopGenresFromRatings($userId) → top N genres from ratings_reviews
            ├── getExcludedMovieIdsForUser($userId)
            └── For each top genre:
                    └── Fetch movies in that genre, excluding already-seen movies
```

### Output Structure

```php
$genreShelvesFav = [
    ['genre' => 'Sci-Fi',  'movies' => [Movie1, Movie2, ...]],
    ['genre' => 'Action',  'movies' => [Movie3, Movie4, ...]],
    ...
]

$genreShelvesRated = [
    ['genre' => 'Drama',   'movies' => [Movie5, Movie6, ...]],
    ...
]
```

### Key Rules
- Favorites and ratings are handled **separately** — genres are not merged
- Movies already favorited or rated are **excluded** from recommendations
- `formatMovies()` ensures consistent data structure for Blade components

---

## 8. Profile Page

```
GET /profile → ProfileController@show
    │
    ├── getFavoritesData($user)
    │       ├── MovieHelper::getUserFavorites($userId)
    │       ├── MovieHelper::formatMovies($movies)
    │       └── MovieHelper::getFavCountsByGenre($userId)
    │
    ├── getRatedData($user)
    │       ├── MovieHelper::getUserRatedMovies($userId)
    │       ├── MovieHelper::formatMovies($movies)
    │       └── MovieHelper::getRatedCountsByGenre($userId)
    │
    ├── getRecommendationsData($userId)
    │       ├── getGenreShelvesForUser($userId, 'favorites')
    │       └── getGenreShelvesForUser($userId, 'rated')
    │
    └── Return profile.blade.php with all compacted variables
```

### Profile Tabs

| Tab | Data | Description |
|---|---|---|
| Favorites | `$favorites`, `$favGenres` | Movies user has favorited + genre counts |
| Rated Movies | `$rated`, `$ratedGenres` | Movies user has rated + genre counts |
| Recommendations | `$genreShelvesFav`, `$genreShelvesRated` | Genre-based shelves |

Tabs are switched via JavaScript `openTab()` function in the Blade view.

---

## 9. Movie People (Cast & Directors)

The `MoviePeople` Livewire component handles adding and removing directors and cast members. Two instances are mounted on the movie detail page — one for Directors, one for Cast.

```
@livewire('movie-people', ['movie' => $movie, 'role' => 'Director'], key('director-'.$movie->id))
@livewire('movie-people', ['movie' => $movie, 'role' => 'Cast'],     key('cast-'.$movie->id))
```

### Add Person Flow

```
User types name + presses Enter
    │
    MoviePeople@addPerson()
    │
    ├── Check if MoviePerson with that name exists in DB
    │       ├── Yes → reuse existing record
    │       └── No  → create new MoviePerson
    ├── Attach to movie via movie_cast pivot with role
    ├── loadPeople() → refresh list
    └── Livewire re-renders the component
```

### Remove Person Flow

```
User clicks × beside a name
    │
    MoviePeople@removePerson($personId)
    │
    ├── Detach person from movie for this role (pivot)
    ├── loadPeople() → refresh list
    └── Livewire re-renders the component
```

See `documentation/md/MoviePeople.md` for the full detailed flow.

---

## 10. Admin — Movie Management

```
GET /manage-movies → ManageMovieController@index
    └── Return manageMovie.blade.php

POST /manage-movies → ManageMovieController@store
    ├── Validate movie fields
    ├── Handle poster + background image uploads
    ├── Create Movie record
    └── Attach genres (sync)

PUT /manage-movies/{id} → ManageMovieController@update
    ├── Validate + update movie fields
    ├── Handle new image uploads (delete old if replaced)
    └── Sync genres

DELETE /manage-movies/{id} → ManageMovieController@destroy
    ├── Delete stored images
    └── Delete movie (cascades reviews, favorites, cast via DB)
```

---

## 11. Livewire Component Communication

Livewire components communicate via events. This allows components to update each other without a full page reload.

| Dispatcher | Event | Listener | Action |
|---|---|---|---|
| `ReviewSection` | `openReviewModal` | `ReviewModal` | Opens the review modal |
| `ReviewModal` | `reviewUpdated` | `ReviewSection` | Refreshes review stats |
| `ReviewModal` | `reviewUpdated` | `ReviewsList` | Refreshes reviews list |

**How it works:**
1. Component A calls `$this->dispatch('eventName')` or `$dispatch('eventName')` in Blade
2. Component B has `#[On('eventName')]` attribute on a method
3. When the event fires, Component B's method runs and Livewire re-renders it

See `documentation/md/Livewire.md` for a full explanation of how Livewire works.

---

## 12. Database Structure

### Tables

| Table | Purpose |
|---|---|
| `users` | Auth accounts (name, email, username, password, role, provider info) |
| `movies` | Movie records (title, description, poster, background, year, etc.) |
| `genres` | Genre list |
| `countries` | Country list |
| `languages` | Language list |
| `movie_people` | Actors, directors, contributors |
| `movie_genres` | Pivot — movies ↔ genres (many-to-many) |
| `movie_cast` | Pivot — movies ↔ people, with `role` column (Director/Cast) |
| `ratings_reviews` | User ratings and reviews (user_id, movie_id, rating, review) |
| `user_favorites` | Pivot — users ↔ movies for favorites |

### Key Relationships

```
Movie ──── many-to-many ──── Genre       (via movie_genres)
Movie ──── many-to-many ──── MoviePerson (via movie_cast, with role)
Movie ──── has-many ─────── RatingReview
Movie ──── many-to-many ──── User        (via user_favorites)
Movie ──── belongs-to ───── Country
Movie ──── belongs-to ───── Language

User ──── has-many ──── RatingReview
User ──── many-to-many ──── Movie (via user_favorites)
```

### Pivot vs Full Model

| Table | Approach | Reason |
|---|---|---|
| `user_favorites` | Pivot only | Simple link, no extra data needed |
| `movie_genres` | Pivot only | Simple link |
| `movie_cast` | Pivot with `role` column | Needs to store Director vs Cast |
| `ratings_reviews` | Full Model (`RatingReview`) | Stores rating, review text, timestamps |

---

## 13. Route Reference

| Method | URI | Controller | Action |
|---|---|---|---|
| GET | `/` | HomeController | index — movie browsing |
| GET | `/movie/{id}` | MovieController | show — movie detail |
| GET | `/profile` | ProfileController | show — user profile |
| POST | `/register` | AuthController | register |
| POST | `/login` | AuthController | login |
| POST | `/logout` | AuthController | logout |
| GET | `/auth/google` | AuthController | redirectGoogle |
| GET | `/auth/google/callback` | AuthController | handleGoogleCallback |
| GET | `/auth/facebook` | AuthController | redirectFacebook |
| GET | `/auth/facebook/callback` | AuthController | handleFacebookCallback |
| GET | `/manage-movies` | ManageMovieController | index (admin) |
| POST | `/manage-movies` | ManageMovieController | store (admin) |
| PUT | `/manage-movies/{id}` | ManageMovieController | update (admin) |
| DELETE | `/manage-movies/{id}` | ManageMovieController | destroy (admin) |
