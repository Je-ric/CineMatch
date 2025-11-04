# Profile Recommendations Flow (Based on Favorites & Ratings)

## Goal
Show **personalized movie recommendations** in the user’s profile page, based on:
- Movies the user has **favorited**
- Movies the user has **rated**

---

## Overall Flow

```text
[Profile Blade]
   ↓
ProfileController@show()
   ↓
MovieHelper
   ├─ getUserFavorites()
   ├─ getUserRatedMovies()
   ├─ getTopGenresFromFavorites()
   ├─ getTopGenresFromRatings()
   ├─ basedOnFavoriteGenres()
   ├─ getGenreShelvesForUser()
   ↓
Database (movies, genres, ratings_reviews, user_favorites)
```

---

# Flow

## Step-by-Step Flow

### 1. Get User Favorites

**Function:** `getUserFavorites($userId)`

* Fetches all movies that the user marked as “Favorite”.
* Each favorite movie is connected to one or more genres.

**Example:**

User favorited *Inception* → Genres: Sci-Fi, Thriller

---

### 2. Count Genres from Favorites

**Function:** `getFavCountsByGenre($userId)`

* Counts how many favorites belong to each genre.

**Example Result:**

| Genre  | Count |
| ------ | ----- |
| Sci-Fi | 5     |
| Drama  | 2     |
| Action | 1     |

---

### 3. Get Top Genres from Ratings

**Function:** `getTopGenresFromRatings($userId)`

* Finds movies that the user rated highly (ex. 4–5 stars).
* Extracts and counts their genres.

**Example Result:**

| Genre     | Count |
| --------- | ----- |
| Sci-Fi    | 3     |
| Adventure | 2     |
| Comedy    | 1     |

---

### 4. Separate Recommendations

* In the current code, **favorites and ratings are handled separately**.
* The system does **not merge the top genres** from favorites and ratings into a single combined list.
* Instead, each source generates its own “shelves” in the UI:

  * Favorites → `basedOnFavoriteGenres()` → shelves
  * Rated movies → `getGenreShelvesForUser(..., 'rated', ...)` → shelves

**Example Layout (UI):**

**Favorites Shelves:**  
🎞 Sci-Fi Recommendations → [Movie1, Movie2, Movie3...]  
🎞 Drama Picks → [Movie1, Movie2...]

**Rated Shelves:**  
🎞 Sci-Fi Rated Picks → [Movie4, Movie5...]  
🎞 Adventure Rated → [Movie6, Movie7...]

---

### 5. Get Movies Related to Top Genres

**Function:** `basedOnFavoriteGenres($userId, $limit)`

* Fetches movies that belong to favorite genres only.
* Filters out movies that the user has already favorited or rated.

**Note:** Rated movies are **not included** in this function. They are handled separately.

---

### 6. Organize Results into “Shelves”

**Function:** `getGenreShelvesForUser($userId, $source, $topLimit, $perGenre)`

* Groups recommended movies by genre for easy display in the UI.
* Two separate flows:

  * Source = `'favorites'` → favorites shelves
  * Source = `'rated'` → rated shelves

**Example Layout:**

**Favorites Shelves:**  
🎞 Sci-Fi Recommendations → [Movie1, Movie2, Movie3...]  
🎞 Drama Picks → [Movie1, Movie2...]

**Rated Shelves:**  
🎞 Sci-Fi Rated Picks → [Movie4, Movie5...]  
🎞 Adventure Rated → [Movie6, Movie7...]

---

### 7. Return to Controller → Blade

After processing, the `MovieHelper` returns a structured collection:

```php
[
  'favorites' => [...],
  'rated' => [...],
  'recommendations' => [
      'favorites' => [
          'Sci-Fi' => [...],
          'Drama' => [...],
      ],
      'rated' => [
          'Sci-Fi' => [...],
          'Adventure' => [...],
      ]
  ]
]
```

Then:

* The `ProfileController` passes this data to `profile.blade.php`.
* The Blade view renders movie cards, favorites, and recommendation shelves dynamically.
* Each section is **rendered separately** (favorites vs rated).

---

## 🔁 Simplified Summary Flow

| Step | Description                                                                   |
| ---- | ----------------------------------------------------------------------------- |
| 1    | Get user’s favorites                                                          |
| 2    | Get user’s rated movies                                                       |
| 3    | Identify top genres from favorites                                            |
| 4    | Identify top genres from ratings                                              |
| 5    | Recommend unseen movies from each source separately                           |
| 6    | Group recommendations by genre (“shelves”) separately for favorites and rated |
| 7    | Display in profile page dynamically                                           |

---

## 🧠 Logic Behind the Recommendation

* **Favorites and ratings** reflect user interest.
* **Genres** are the main way to link interests to similar movies.

### The system ensures:

* No duplicate or already seen movies.
* Movies are grouped neatly by genre.
* Favorites and rated movies are handled in separate flows.

### `formatMovies()` is used to:

* Ensure each movie has consistent structure.
* Handle missing data (poster, average rating, etc.).
# Profile Recommendations Flow (Based on Favorites & Ratings)

## Goal
Show **personalized movie recommendations** in the user’s profile page, based on:
- Movies the user has **favorited**
- Movies the user has **rated**

---

## Overall Flow

```text
[Profile Blade]
   ↓
ProfileController@show()
   ↓
MovieHelper
   ├─ getUserFavorites()
   ├─ getUserRatedMovies()
   ├─ getTopGenresFromFavorites()
   ├─ getTopGenresFromRatings()
   ├─ basedOnFavoriteGenres()
   ├─ getGenreShelvesForUser()
   ↓
Database (movies, genres, ratings_reviews, user_favorites)
```

---

# Flow

## Step-by-Step Flow

### 1. Get User Favorites

**Function:** `getUserFavorites($userId)`

* Fetches all movies that the user marked as “Favorite”.
* Each favorite movie is connected to one or more genres.

**Example:**

User favorited *Inception* → Genres: Sci-Fi, Thriller

---

### 2. Count Genres from Favorites

**Function:** `getFavCountsByGenre($userId)`

* Counts how many favorites belong to each genre.

**Example Result:**

| Genre  | Count |
| ------ | ----- |
| Sci-Fi | 5     |
| Drama  | 2     |
| Action | 1     |

---

### 3. Get Top Genres from Ratings

**Function:** `getTopGenresFromRatings($userId)`

* Finds movies that the user rated highly (ex. 4–5 stars).
* Extracts and counts their genres.

**Example Result:**

| Genre     | Count |
| --------- | ----- |
| Sci-Fi    | 3     |
| Adventure | 2     |
| Comedy    | 1     |

---

### 4. Separate Recommendations

* In the current code, **favorites and ratings are handled separately**.
* The system does **not merge the top genres** from favorites and ratings into a single combined list.
* Instead, each source generates its own “shelves” in the UI:

  * Favorites → `basedOnFavoriteGenres()` → shelves
  * Rated movies → `getGenreShelvesForUser(..., 'rated', ...)` → shelves

**Example Layout (UI):**

**Favorites Shelves:**  
🎞 Sci-Fi Recommendations → [Movie1, Movie2, Movie3...]  
🎞 Drama Picks → [Movie1, Movie2...]

**Rated Shelves:**  
🎞 Sci-Fi Rated Picks → [Movie4, Movie5...]  
🎞 Adventure Rated → [Movie6, Movie7...]

---

### 5. Get Movies Related to Top Genres

**Function:** `basedOnFavoriteGenres($userId, $limit)`

* Fetches movies that belong to favorite genres only.
* Filters out movies that the user has already favorited or rated.

**Note:** Rated movies are **not included** in this function. They are handled separately.

---

### 6. Organize Results into “Shelves”

**Function:** `getGenreShelvesForUser($userId, $source, $topLimit, $perGenre)`

* Groups recommended movies by genre for easy display in the UI.
* Two separate flows:

  * Source = `'favorites'` → favorites shelves
  * Source = `'rated'` → rated shelves

**Example Layout:**

**Favorites Shelves:**  
🎞 Sci-Fi Recommendations → [Movie1, Movie2, Movie3...]  
🎞 Drama Picks → [Movie1, Movie2...]

**Rated Shelves:**  
🎞 Sci-Fi Rated Picks → [Movie4, Movie5...]  
🎞 Adventure Rated → [Movie6, Movie7...]

---

### 7. Return to Controller → Blade

After processing, the `MovieHelper` returns a structured collection:

```php
[
  'favorites' => [...],
  'rated' => [...],
  'recommendations' => [
      'favorites' => [
          'Sci-Fi' => [...],
          'Drama' => [...],
      ],
      'rated' => [
          'Sci-Fi' => [...],
          'Adventure' => [...],
      ]
  ]
]
```

Then:

* The `ProfileController` passes this data to `profile.blade.php`.
* The Blade view renders movie cards, favorites, and recommendation shelves dynamically.
* Each section is **rendered separately** (favorites vs rated).

---

## 🔁 Simplified Summary Flow

| Step | Description                                                                   |
| ---- | ----------------------------------------------------------------------------- |
| 1    | Get user’s favorites                                                          |
| 2    | Get user’s rated movies                                                       |
| 3    | Identify top genres from favorites                                            |
| 4    | Identify top genres from ratings                                              |
| 5    | Recommend unseen movies from each source separately                           |
| 6    | Group recommendations by genre (“shelves”) separately for favorites and rated |
| 7    | Display in profile page dynamically                                           |

---

## 🧠 Logic Behind the Recommendation

* **Favorites and ratings** reflect user interest.
* **Genres** are the main way to link interests to similar movies.

### The system ensures:

* No duplicate or already seen movies.
* Movies are grouped neatly by genre.
* Favorites and rated movies are handled in separate flows.

### `formatMovies()` is used to:

* Ensure each movie has consistent structure.
* Handle missing data (poster, average rating, etc.).
