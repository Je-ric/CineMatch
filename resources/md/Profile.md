# Profile Recommendations Flow (Based on Favorites & Ratings)

## Goal
Show **personalized movie recommendations** in the user’s profile page, based on:
- Movies the user has **favorited**
- Movies the user has **rated**

---

## Overall Flow

```text
[Profile Blade (profile.blade.php)]
   ↓
ProfileController@show()
   ├─ getFavoritesData($user)
   ├─ getRatedData($user)
   ├─ getRecommendationsData($userId)
   ↓
MovieHelper
   ├─ getUserFavorites($userId)
   ├─ getUserRatedMovies($userId)
   ├─ formatMovies($movies)
   ├─ getFavCountsByGenre($userId)
   ├─ getRatedCountsByGenre($userId)
   ├─ getTopGenresFromFavorites($userId)
   ├─ getTopGenresFromRatings($userId)
   ├─ getGenreShelvesForUser($userId, $source, $topLimit, $perGenre)
   ├─ basedOnFavoriteGenres($userId, $limit)
   ↓
Database (movies, genres, ratings_reviews, user_favorites)
```

---

# Step-by-Step Flow

## 1. Fetch User Favorites

**Controller Function:** `ProfileController@getFavoritesData($user)`  
**Helper Functions:** `MovieHelper::getUserFavorites($userId)`, `MovieHelper::formatMovies($movies)`, `MovieHelper::getFavCountsByGenre($userId)`

- Fetches movies that the user has favorited.
- Formats the movie data for Blade using `formatMovies()`.
- Counts favorites per genre.

**Example Data:**

- Favorites: *Inception*, *The Dark Knight*
- Favorite Genres: Sci-Fi, Action

---

## 2. Fetch User Rated Movies

**Controller Function:** `ProfileController@getRatedData($user)`  
**Helper Functions:** `MovieHelper::getUserRatedMovies($userId)`, `MovieHelper::formatMovies($movies)`, `MovieHelper::getRatedCountsByGenre($userId)`

- Fetches movies that the user has rated.
- Formats movies for Blade.
- Counts rated movies per genre.

**Example Data:**

- Rated Movies: *Interstellar*, *Avengers*
- Rated Genres: Sci-Fi, Adventure

---

## 3. Fetch Recommendations

**Controller Function:** `ProfileController@getRecommendationsData($userId)`  
**Helper Functions:** 
- `MovieHelper::getGenreShelvesForUser($userId, 'favorites', 5, 5)`  
- `MovieHelper::getGenreShelvesForUser($userId, 'rated', 5, 5)`  
- `MovieHelper::getTopGenresFromFavorites($userId, 5)`  
- `MovieHelper::getTopGenresFromRatings($userId, 5)`

- Builds “shelves” for favorites and rated separately.
- Limits top genres and top movies per genre.
- Returns structured array to controller.

**Example Output:**

```php
[
  'genreShelvesFav' => [...],
  'genreShelvesRated' => [...],
  'topGenresFav' => [...],
  'topGenresRated' => [...],
]
```

---

## 4. Generate Recommendations Based on Favorite Genres

**Helper Function:** `MovieHelper::basedOnFavoriteGenres($userId, $limit)`

- Fetches movies from **top favorite genres only**.
- Excludes movies already favorited or rated.
- Maps movies with `match_genres`, average rating, etc.
- Formats movies for Blade.

**Example:**
- Top favorite genres: Sci-Fi, Action
- Recommended movies: *Dune*, *Matrix Resurrections*

---

## 5. Format Movies for Blade

**Helper Function:** `MovieHelper::formatMovies($movies)`

- Adds computed fields:
  - `avg_rating`
  - `total_reviews`
  - `country_name`
  - `language_name`
  - `genres_list`
  - `genre_ids`
- Handles missing values (poster, country, language) and sets placeholders.
- Ensures consistent structure for Blade components (cards, shelves).

---

## 6. Generate Genre Shelves

**Helper Function:** `MovieHelper::getGenreShelvesForUser($userId, $source, $topLimit, $perGenre)`

- Source can be `'favorites'` or `'rated'`.
- Fetches top genres for the given source.
- For each genre, fetches movies not already favorited or rated (`getExcludedMovieIdsForUser()`).
- Returns array with genre metadata and movie list.

**Example Layout:**

**Favorites Shelves:**
🎞 Sci-Fi Recommendations → [Movie1, Movie2, Movie3...]  
🎞 Drama Picks → [Movie1, Movie2...]

**Rated Shelves:**
🎞 Sci-Fi Rated Picks → [Movie4, Movie5...]  
🎞 Adventure Rated → [Movie6, Movie7...]

---

## 7. Return Data to Blade

**Controller:** `ProfileController@show()`

- Combines:
  - User info (`$user`)
  - Favorites (`$favorites`, `$favGenres`, `$favCountries`)
  - Rated (`$rated`, `$ratedGenres`, `$ratedCountries`)
  - Recommendations (`genreShelvesFav`, `genreShelvesRated`, `topGenresFav`, `topGenresRated`)
- Passes data to `profile.blade.php` using `array_merge(compact(...), $recommendations)`.
- Blade uses tabbed UI to display:
  - Favorites
  - Rated Movies
  - Recommendations

---

## 8. Blade Tabs

**Blade View:** `profile.blade.php`

- Tabs controlled by JavaScript (`openTab` function).
- Includes:
  - `profile.favoriteTab`
  - `profile.ratedTab`
  - `profile.recommendationsTab`
- Default tab opened on page load.

---

## 🔁 Summary Flow

| Step | Function / Helper | Description |
| ---- | ---------------- | ----------- |
| 1    | `ProfileController@getFavoritesData` / `MovieHelper::getUserFavorites` | Fetch user’s favorites |
| 2    | `ProfileController@getRatedData` / `MovieHelper::getUserRatedMovies` | Fetch user’s rated movies |
| 3    | `ProfileController@getRecommendationsData` / `MovieHelper::getGenreShelvesForUser` | Build genre shelves for favorites and rated |
| 4    | `MovieHelper::getTopGenresFromFavorites` / `MovieHelper::getTopGenresFromRatings` | Identify top genres separately |
| 5    | `MovieHelper::basedOnFavoriteGenres` | Fetch favorite-based recommended movies |
| 6    | `MovieHelper::formatMovies` | Format movie objects for Blade |
| 7    | `ProfileController@show` → `profile.blade.php` | Merge all data and render tabs |

---

## 🧠 Logic Behind Recommendations

- Favorites and rated movies are handled **separately**; genres are **not merged**.
- Recommendations are based on **top favorite genres only**.
- Rated movies are displayed in separate shelves.
- `formatMovies()` ensures data consistency and prevents errors in Blade components.
- `getExcludedMovieIdsForUser()` prevents recommending movies already seen or rated.
