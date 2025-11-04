# CineMatch Laravel Recommendation Flow

## Overview

This document explains the flow of the **Profile** and **Movie viewing** system in a Laravel application, focusing on how Blade templates, controllers, and helper functions communicate.

---

## 1. Blade Templates

### 1.1 Profile Blade (`profile.blade.php`)

**Purpose:** Display authenticated user information, favorite movies, rated movies, and personalized recommendations.

**Flow:**
1. User visits `/profile` → `ProfileController@show` is triggered.
2. Blade receives:
   - `$user`
   - `$favorites`, `$favGenres`, `$favCountries`
   - `$rated`, `$ratedGenres`, `$ratedCountries`
   - `$genreShelvesFav`, `$genreShelvesRated`
3. Tabs in Blade:
   - **Favorites:** Loops through `$favorites` and `$favGenres`.
   - **Rated Movies:** Loops through `$rated` and `$ratedGenres`.
   - **Recommendations:** Loops through `$genreShelvesFav` and `$genreShelvesRated`.
4. Components used:
   - `<x-movie-card>` → displays individual movie.
   - `<livewire:review-section>` → handles user reviews dynamically.
   - `<livewire:favorite-button>` → handles favoriting.

---

### 1.2 View Movie Blade (`viewMovie.blade.php`)

**Purpose:** Show detailed information about a movie.

**Flow:**
1. User visits `/movie/{id}` → `MovieController@show`.
2. Blade receives:
   - `$movie` → details of the movie
   - `$directors`, `$actors` → via `splitCastRoles`
   - `$reviews` → via `getMovieReviews`
   - `$relatedMovies` → via `getRelatedMovies`
3. Components used:
   - `<x-trailer-section>`
   - `<x-related-movies>`
   - `<livewire:review-section>`
   - `<livewire:favorite-button>`

---

## 2. Controller Flow

### 2.1 ProfileController

```text
ProfileController@show
    ├─> Auth::user() → $user
    ├─> getFavoritesData($user)
    │     ├─> MovieHelper::getUserFavorites($userId)
    │     ├─> MovieHelper::formatMovies()
    │     ├─> MovieHelper::getFavCountsByGenre($userId)
    ├─> getRatedData($user)
    │     ├─> MovieHelper::getUserRatedMovies($userId)
    │     ├─> MovieHelper::formatMovies()
    │     ├─> MovieHelper::getRatedCountsByGenre($userId)
    └─> getRecommendationsData($userId)
          ├─> MovieHelper::getGenreShelvesForUser($userId, 'favorites')
          ├─> MovieHelper::getGenreShelvesForUser($userId, 'rated')
          ├─> MovieHelper::getTopGenresFromFavorites($userId)
          └─> MovieHelper::getTopGenresFromRatings($userId)
```
Return: Blade profile.blade.php with all compacted variables.

## 2.2 MovieController (simplified)

```text
MovieController@show
    ├─> MovieHelper::getMovieWithDetails($movieId) → $movie
    ├─> MovieHelper::splitCastRoles($movie) → $directors, $actors
    ├─> MovieHelper::getMovieReviews($movieId) → $reviews
    └─> MovieHelper::getRelatedMovies($movie) → $relatedMovies
```
Return: Blade viewMovie.blade.php with all data.
# 3. MovieHelper Function Flow

## 3.1 User Data

- `getUserFavorites($userId, $limit)` → Fetches favorites from `user_favorites` pivot table.  
- `getUserRatedMovies($userId, $limit)` → Fetches rated movies from `ratings_reviews`.  
- `getExcludedMovieIdsForUser($userId)` → Excludes movies already rated/favorited.  
- `getFavCountsByGenre($userId)` → Counts favorites per genre.  
- `getRatedCountsByGenre($userId)` → Counts ratings per genre.  

## 3.2 Recommendations

- `getTopGenresFromFavorites($userId, $limit)` → Fetches top N genres from favorites.  
- `getTopGenresFromRatings($userId, $limit)` → Fetches top N genres from ratings.  
- `getGenreShelvesForUser($userId, $source, $topLimit, $perGenre)` → Builds "shelves" for UI by genre.  
- `basedOnFavoriteGenres($userId, $limit)` → Personalized recommendations based on favorite genres.  

## 3.3 Movie Details

- `getMovieWithDetails($movieId)` → Fetches full movie details and relations.  
- `getMovieReviews($movieId)` → Returns list, count, average rating.  
- `splitCastRoles($movie)` → Separates directors and cast.  
- `getRelatedMovies($movie)` → Finds movies with matching genres, sorted by relevance.  

## 3.4 Utilities

- `formatMovies($movies)` → Ensures consistent data structure for Blade.  
- Handles missing values (`poster_url`, `country_name`, `language_name`, `avg_rating`, etc.)  

## 4. Full Flow Diagram (Text-based)

```text
[Profile Blade] / [View Movie Blade]
       │
       ▼
[Controller: ProfileController / MovieController]
       │
       ▼
[MovieHelper] ──> Database tables (movies, ratings_reviews, user_favorites, movie_genres, genres)
       │
       ├─ Format data
       ├─ Compute aggregates (avg ratings, top genres)
       ├─ Build recommendations / shelves
       └─ Return structured collections to Controller
       │
       ▼
[Blade Templates / Livewire Components]
       │
       ├─ Render movie cards
       ├─ Render review sections
       ├─ Render favorite buttons
       └─ Render recommendations & genre analytics
```

## 5. Notes

- **Helpers centralize logic** → avoids repetition in controllers and views.
- **Livewire components** → provide dynamic interactivity (reviews, favorites) without page reload.

### Recommendation Flow

- Based on top genres from favorites and ratings.
- Excludes movies already seen or favorited.
- Builds multiple shelves per genre for UI display.

### Data Formatting

- `formatMovies()` ensures every movie object has consistent properties.
- Handles null values and prepares relationships for Blade.

### Tab-based UI

- Favorites / Rated / Recommendations handled via JavaScript tab switching.

