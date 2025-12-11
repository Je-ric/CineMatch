# CineMatch Models & Data Flows

This document explains how the Laravel models work for movies, favorites, and reviews, their connections, and the flow of data.

---

## 1. Movie

Represents a single movie in the database.

**Relationships:**
- `genres()` → belongs to many genres (`movie_genres` pivot table)
- `country()` → belongs to a country
- `language()` → belongs to a language
- `ratings()` → has many `RatingReview`
- `favoritedBy()` → belongs to many users (`user_favorites` pivot table)
- `cast()` → belongs to many `MoviePerson` via `movie_cast` pivot with `role` (Director/Cast)

**Example Flow:**
1. User views a movie.
2. Load movie genres, cast, ratings, country, language.
3. Display average rating and user favorites.

---

## 2. User

Represents an app user.

**Relationships:**
- `favorites()` → movies user has favorited (many-to-many via `user_favorites`)
- `ratings()` → movies user has rated (has many `RatingReview`)

**Flow:**
1. User clicks "Add to Favorites":
   - `user_favorites` table updated via pivot.
   - Favorite count updates dynamically on UI.
2. User submits a rating/review:
   - Record saved in `ratings_reviews`.
   - UI refreshes average rating and review list.

**Why two approaches?**
- **Favorites:** Only a simple link → pivot table is sufficient.
- **Ratings/Reviews:** Stores extra data (`rating`, `review`, timestamps`) → use a full model (`RatingReview`).

---

## 3. RatingReview

Represents a user rating and review of a movie.

**Relationships:**
- Belongs to **User**
- Belongs to **Movie**

**Flow:**
1. User opens review modal.
2. If review exists → prefill rating and text.
3. User submits review → save or update in DB.
4. Close modal → dispatch `reviewUpdated` event.
5. Other components (`ReviewSection`, `ReviewsList`) listening via `#[On('reviewUpdated')]` refresh their UI dynamically.

---

## 4. UserFavorite

Optional model for the `user_favorites` pivot table.

**Relationships:**
- Belongs to **User**
- Belongs to **Movie**

**Flow:**
1. User clicks favorite button.
2. Check if already favorited:
   - Yes → detach
   - No → attach
3. Update `favoriteCount`.
4. Livewire re-renders only the button component without page reload.

---

## 5. MoviePerson

Represents actors, directors, or other movie contributors.

**Relationships:**
- Many-to-many **Movies** via `movie_cast` pivot
- Pivot `role` specifies `Director` or `Cast`

**Flow:**
1. Movie page loads → fetch all cast and directors.
2. Display names and roles.

---

## 6. Genre, Country, Language

| Model   | Relationship with Movie        | Notes                       |
|---------|-------------------------------|-----------------------------|
| Genre   | Many-to-many (`movie_genres`) | Movies can have multiple genres |
| Country | One-to-many (`country_id`)    | Movie belongs to one country   |
| Language| One-to-many (`language_id`)   | Movie belongs to one language  |

---

## 7. Pivot Tables

| Table           | Purpose                                        |
|-----------------|-----------------------------------------------|
| movie_genres    | Connects movies & genres                      |
| movie_cast      | Connects movies & people, stores `role`      |
| user_favorites  | Connects users & movies for favorites        |

**Tip:**  
- Use pivot tables for **simple links** (ex, `user_favorites`).  
- Use full models for **tables with extra data** (ex, `ratings_reviews`).

---

## 8. Livewire Event Flows

**Reviews Flow:**
1. User clicks "Leave a Review" → `$dispatch('openReviewModal')`
2. `ReviewModal` opens → `$isOpen = true`
3. User submits review → save/update DB
4. Close modal → dispatch `reviewUpdated` to:
   - `ReviewSection`
   - `ReviewsList`
5. Components listening refresh their UI dynamically.

**Favorites Flow:**
1. User clicks "Favorite" → `toggleFavorite()`
2. Check if already favorited:
   - Yes → detach
   - No → attach
3. Update favorite count.
4. Livewire re-renders only the favorite button component.

---

## 9. Summary

- **Pivot table** = simple link between models  
- **Full model** = stores extra info like rating, review, timestamps  
- **Livewire** = updates only part of the page dynamically without full reload  
- **Movie** = central hub connecting genres, cast, ratings, favorites, country, language
