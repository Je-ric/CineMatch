# CineMatch — Future Works & Known Issues

This document tracks planned improvements, feature ideas, and known bugs for CineMatch.

---

## Table of Contents

1. [Critical Priority](#1-critical-priority)
2. [Normal Priority](#2-normal-priority)
3. [Low Priority](#3-low-priority)
4. [Known Bugs](#4-known-bugs)
5. [Technical Debt](#5-technical-debt)

---

## 1. Critical Priority

Issues that affect security, data integrity, or core functionality.

---

### 1.1 No Authorization on Admin Routes

**Problem:** Movie management routes (`/manage-movies`) may not be protected by a role-check middleware. Any authenticated user who knows the URL could potentially access admin features.

**Fix:** Ensure all admin routes are wrapped in middleware that checks `role = admin`.

```php
Route::middleware(['auth', 'role:admin'])->group(function () {
    Route::resource('/manage-movies', ManageMovieController::class);
});
```

**Impact:** Security vulnerability.

---

### 1.2 Social Login Does Not Verify Email Ownership

**Problem:** When a user logs in via Google/Facebook, the system trusts the email provided by the OAuth provider. If a provider returns an unverified email, it could be used to hijack an existing account.

**Fix:** Check `$socialUser->user['email_verified']` (Google) before linking accounts. Only link if the email is verified by the provider.

---

### 1.3 No Rate Limiting on Review Submission

**Problem:** A user can spam the review submission endpoint rapidly. There is no rate limiting on Livewire review actions.

**Fix:** Add rate limiting to the `submitReview()` Livewire method using Laravel's `RateLimiter`.

---

### 1.4 Movie Deletion Does Not Clean Up Uploaded Files

**Problem:** When a movie is deleted, the poster and background images stored in `public/uploads/` may not be deleted, leaving orphaned files on disk.

**Fix:** In `ManageMovieController@destroy`, delete associated image files before deleting the movie record.

```php
if ($movie->poster) Storage::delete('public/uploads/posters/' . $movie->poster);
if ($movie->background) Storage::delete('public/uploads/backgrounds/' . $movie->background);
```

---

### 1.5 `Others.md` Contains Only a Facebook Developer URL

**Problem:** `documentation/md/Others.md` contains only a raw Facebook developer URL with no context. This is not useful documentation.

**Fix:** Either document what that URL is for (Facebook app settings for OAuth) or remove the file.

---

## 2. Normal Priority

Features that would significantly improve the system.

---

### 2.1 Search and Filter on Home Page

**Problem:** The home page may not have a robust search and filter system. Users cannot easily find movies by title, year, or multiple genres simultaneously.

**Improvement:**
- Full-text search by title
- Filter by genre (multi-select)
- Filter by year range
- Sort by rating, newest, most reviewed

Consider using Livewire for real-time filtering without page reloads.

---

### 2.2 Recommendation Algorithm Improvement

**Problem:** The current recommendation system is genre-based only. It does not consider:
- Rating scores (a 5-star rated genre should weigh more than a 1-star)
- Recency (recently favorited genres should weigh more)
- Popularity of movies within a genre

**Improvement:** Weight genres by average rating score, not just count. Boost recently-interacted genres.

```php
// Instead of: count of favorites per genre
// Use: sum of ratings per genre, weighted by recency
```

---

### 2.3 Pagination on Profile Tabs

**Problem:** The Favorites and Rated Movies tabs load all movies at once. For users with many favorites, this could be slow and unwieldy.

**Improvement:** Add pagination or infinite scroll to each profile tab.

---

### 2.4 Movie Trailer Integration

**Problem:** The trailer section (`<x-trailer-section>`) may rely on a manually entered YouTube URL. There is no automatic trailer fetching.

**Improvement:** Integrate with TMDB API or YouTube Data API to automatically fetch trailers by movie title/year.

---

### 2.5 User Watchlist (Want to Watch)

**Problem:** Users can only favorite movies they've seen. There is no "want to watch" or watchlist feature.

**Improvement:** Add a separate `user_watchlist` pivot table and a "Add to Watchlist" button on movie pages.

---

### 2.6 Review Helpfulness Voting

**Problem:** All reviews are displayed equally. There is no way to surface the most helpful reviews.

**Improvement:** Add a "Was this helpful?" thumbs up/down on reviews. Sort reviews by helpfulness score.

---

### 2.7 Admin Movie Import via CSV / TMDB API

**Problem:** Movies must be added one by one through the admin form.

**Improvement:** Allow bulk import via CSV upload, or integrate with TMDB API to import movie data automatically.

---

### 2.8 Email Verification

**Problem:** Users can register with any email without verifying it. This allows fake accounts.

**Fix:** Enable Laravel's email verification (`MustVerifyEmail` interface on the User model). Jetstream/Fortify already supports this — it may just need to be enabled in config.

---

### 2.9 Notification System

**Problem:** No notifications exist for user activity.

**Improvement:**
- Notify user when someone likes their review
- Notify admin when a new movie is requested by users
- Weekly recommendation digest email

---

## 3. Low Priority

Nice-to-have features for future versions.

---

### 3.1 Dark Mode

Add a dark mode toggle. Tailwind's `dark:` classes make this straightforward.

---

### 3.2 Movie Collections / Lists

Allow users to create named lists (e.g., "Best Sci-Fi", "Watch with Family") and add movies to them.

---

### 3.3 Social Features

- Follow other users
- See what friends are watching or rating
- Public profile pages

---

### 3.4 Advanced Recommendation: Collaborative Filtering

The current system is content-based (genre matching). A future improvement would be collaborative filtering — recommending movies that users with similar taste have liked.

---

### 3.5 Movie Request System

Allow users to request movies to be added to the database. Admins can approve or reject requests.

---

### 3.6 Trailer Auto-Play on Hover

On the home page movie grid, auto-play a short trailer clip when hovering over a movie card.

---

### 3.7 Progressive Web App (PWA)

Add a service worker and manifest to make CineMatch installable as a PWA on mobile devices.

---

## 4. Known Bugs

---

### Bug 1: `Others.md` Is Not Useful Documentation

**Symptom:** `documentation/md/Others.md` contains only a raw Facebook developer URL with no explanation.

**Impact:** Confusing for anyone reading the documentation.

**Fix:** Document what the URL is for or remove the file.

**Status:** Not yet fixed.

---

### Bug 2: Profile Page `READ MO DIN ITO` Comment in Documentation

**Symptom:** `documentation/md/Profile.md` contains the informal note `READ MO DIN ITO` as a section header. This is a personal dev note, not documentation.

**Impact:** Unprofessional if shared publicly.

**Fix:** Remove or replace with a proper section title.

**Status:** Not yet fixed.

---

### Bug 3: `stateless()` on Social Login May Cause Issues in Some Environments

**Symptom:** Using `stateless()` on Socialite disables CSRF state verification for OAuth. In some server configurations or with certain providers, this can cause authentication failures or security warnings.

**Impact:** Potential security concern; may cause intermittent login failures.

**Fix:** Evaluate whether `stateless()` is necessary. If sessions are available, remove `stateless()` and let Socialite handle state verification normally.

**Status:** Not yet fixed.

---

### Bug 4: Duplicate Movie Person on Add

**Symptom:** If an admin types a person's name with different casing (e.g., "Tom Hanks" vs "tom hanks"), the system may create duplicate `MoviePerson` records.

**Root Cause:** The `MoviePeople` component checks for existing persons by name but may be case-sensitive.

**Fix:** Use a case-insensitive query when checking for existing persons:
```php
MoviePerson::whereRaw('LOWER(name) = ?', [strtolower($name)])->first()
```

**Status:** Not yet fixed.

---

### Bug 5: Recommendation Shelves Show Empty if User Has No Activity

**Symptom:** If a user has no favorites and no ratings, the recommendations tab shows empty shelves or nothing at all, with no helpful message.

**Fix:** Show a friendly empty state message: "Start favoriting or rating movies to get personalized recommendations."

**Status:** Not yet fixed.

---

### Bug 6: Related Movies May Include the Current Movie

**Symptom:** `MovieHelper::getRelatedMovies()` may include the current movie in the related movies list if the genre matching query does not exclude it.

**Fix:** Add `->where('id', '!=', $movie->id)` to the related movies query.

**Status:** Needs verification.

---

## 5. Technical Debt

---

### 5.1 MovieHelper Is a Large Static Class

`MovieHelper` contains all movie-related logic as static methods. As the project grows, this class will become very large and hard to maintain.

**Improvement:** Split into smaller service classes:
- `RecommendationService`
- `MovieQueryService`
- `MovieFormatterService`

---

### 5.2 No Feature Tests for Core Flows

The project has Pest test files but most are Jetstream boilerplate. Core flows (favorites, reviews, recommendations) have no test coverage.

**Priority tests to write:**
- Toggle favorite adds/removes from `user_favorites`
- Submit review creates/updates `ratings_reviews`
- Recommendation shelves exclude already-seen movies
- Social login creates user if not exists

---

### 5.3 Blade Views in `resources/views/` Root

`Notes.md` and `README.md` are inside `resources/views/`. These are documentation files and should not be in the views directory.

**Fix:** Move them to `documentation/` or the project root.

---

### 5.4 `documentation/md/Others.md` Is Not Documentation

Contains only a Facebook developer URL. Should be properly documented or removed.

---

### 5.5 No API Layer

All data is served via Blade/Livewire. If a mobile app or external client needs to consume CineMatch data in the future, an API layer would need to be built from scratch.

**Improvement:** Add API routes with Laravel Sanctum authentication for key endpoints (movies, favorites, reviews).
