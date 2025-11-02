# ViewMovie Flow Documentation

## Overview
This document explains how viewing a single movie works, including displaying movie details, favorites functionality, ratings/reviews, related movies, and Livewire component interactions.

---

## Table of Contents
1. [Routes & Entry Points](#routes--entry-points)
2. [Display Movie Flow](#display-movie-flow)
3. [Favorite Button Flow (Livewire)](#favorite-button-flow-livewire)
4. [Rating & Review Flow (Livewire)](#rating--review-flow-livewire)
5. [Related Movies Flow](#related-movies-flow)
6. [MVC Communication Flow](#mvc-communication-flow)
7. [Livewire Component Communication](#livewire-component-communication)
8. [Database Operations](#database-operations)

---

## Routes & Entry Points

### Routes (routes/web.php)

| Route | Method | Controller/Action | Purpose |
|-------|--------|-------------------|---------|
| `/viewMovie/{id}` | GET | `MovieController@show` | Display movie details page |

---

## Display Movie Flow

### Step-by-Step Sequence:

```
User → Click Movie → Route → Controller → Helper → Models → View → Render Components
```

### Detailed Flow:

#### 1. Navigation

**Entry Points:**
- Movie card on home page (`resources/views/components/movie-card.blade.php:14`)
- Related movies section
- Any link to `route('movie.show', $id)`

**Link Format:**
```blade
<a href="{{ url('viewMovie', $movie->id) }}">
    <!-- Movie poster -->
</a>
```

#### 2. Route (`routes/web.php:40`)
```php
Route::get('/viewMovie/{id}', [MovieController::class, 'show'])
    ->name('movie.show');
```

#### 3. Controller: Show Method (`app/Http/Controllers/MovieController.php:42-64`)

**Function:** `show($id)`

**Process:**

```php
public function show($id)
{
    // 1. Get movie with all relationships
    $movie = MovieHelper::getMovieWithDetails($id);
    
    if (!$movie) {
        abort(404, 'Movie not found.');
    }
    
    // 2. Get reviews/ratings data
    $reviews = MovieHelper::getMovieReviews($id);
    
    // 3. Get related movies
    $related = MovieHelper::getRelatedMovies($movie);
    
    // 4. Split cast into directors and actors
    $castData = MovieHelper::splitCastRoles($movie);
    
    // 5. Return view with all data
    return view('viewMovie', [
        'movie' => $movie,
        'reviews' => $reviews['list'],
        'realReviewCount' => $reviews['count'],
        'avgRating' => $reviews['average'],
        'relatedMovies' => $related,
        'genres' => $movie->genres->pluck('name')->toArray(),
        'directors' => $castData['directors'],
        'actors' => $castData['actors'],
    ]);
}
```

#### 4. Helper Functions

**A. Get Movie with Details (`app/Helpers/MovieHelper.php:341-351`)**

**Function:** `getMovieWithDetails($movieId)`

```php
public static function getMovieWithDetails($movieId)
{
    $movie = Movie::with(['genres', 'country', 'language', 'cast', 'ratings'])
        ->find($movieId);
    
    if (!$movie) return null;
    
    $movie->country_name = $movie->country->name ?? 'Unknown';
    $movie->language_name = $movie->language->name ?? 'Unknown';
    
    return $movie;
}
```

**What It Does:**
1. Eager loads relationships: `genres`, `country`, `language`, `cast`, `ratings`
2. Adds formatted properties: `country_name`, `language_name`
3. Returns movie object with all data

**B. Get Movie Reviews (`app/Helpers/MovieHelper.php:355-376`)**

**Function:** `getMovieReviews($movieId)`

```php
public static function getMovieReviews($movieId)
{
    // Get all reviews from database
    $reviews = DB::table('ratings_reviews')
        ->where('movie_id', $movieId)
        ->orderByDesc('created_at')
        ->get();
    
    $count = $reviews->count();
    $average = $count ? round($reviews->avg('rating'), 1) : null;
    
    // Get full models with user relationship
    $reviewModels = RatingReview::with('user')
        ->where('movie_id', $movieId)
        ->latest()
        ->get();
    
    return [
        'list' => $reviewModels,  // Collection of RatingReview models
        'count' => $count,         // Total review count
        'average' => $average,     // Average rating (1-5)
    ];
}
```

**What It Does:**
1. Gets all reviews for the movie
2. Calculates average rating
3. Returns formatted data structure

**C. Get Related Movies (`app/Helpers/MovieHelper.php:86-118`)**

**Function:** `getRelatedMovies($movie)`

```php
public static function getRelatedMovies($movie)
{
    // 1. Get genre IDs from current movie
    $genreIds = $movie->genres->pluck('id')->toArray();
    if (empty($genreIds)) return collect();
    
    // 2. Find movies with matching genres
    $relatedIds = DB::table('movie_genres')
        ->select('movie_id', DB::raw('COUNT(*) as match_genres'))
        ->whereIn('genre_id', $genreIds)
        ->where('movie_id', '!=', $movie->id)
        ->groupBy('movie_id')
        ->orderByDesc('match_genres')
        ->limit(50)
        ->pluck('movie_id')
        ->toArray();
    
    // 3. Get full movie details
    $relatedMovies = Movie::with(['genres', 'country', 'language', 'ratings'])
        ->whereIn('id', $relatedIds)
        ->get()
        ->map(function ($m) use ($genreIds) {
            // Count matching genres
            $m->match_genres = $m->genres->pluck('id')->intersect($genreIds)->count();
            // Calculate average rating
            $m->avg_rating = $m->ratings->count() ? round($m->ratings->avg('rating'), 1) : 0;
            // Format country/language
            $m->country_name = $m->country->name ?? 'Unknown';
            $m->language_name = $m->language->name ?? 'Unknown';
            return $m;
        })
        ->sortByDesc(fn($m) => [
            $m->match_genres,      // More matching genres first
            $m->avg_rating ?? 0,   // Then higher rating
            $m->release_year ?? 0  // Then newer year
        ])
        ->take(5)
        ->values();
    
    return $relatedMovies;
}
```

**Algorithm:**
1. Find movies with same genres (exclude current movie)
2. Count how many genres match for each movie
3. Sort by: matching genres count → average rating → release year
4. Return top 5 movies

**D. Split Cast Roles (`app/Helpers/MovieHelper.php:380-391`)**

**Function:** `splitCastRoles($movie)`

```php
public static function splitCastRoles($movie)
{
    $directors = $movie->cast->filter(fn($person) =>
        strcasecmp($person->pivot->role ?? '', 'Director') === 0
    )->values();
    
    $actors = $movie->cast->filter(fn($person) =>
        strcasecmp($person->pivot->role ?? '', 'Cast') === 0
    )->values();
    
    return compact('directors', 'actors');
}
```

**What It Does:**
1. Filters cast by pivot `role` field
2. Separates into `directors` and `actors` collections
3. Returns both as array

#### 5. View: Display (`resources/views/viewMovie.blade.php`)

**Key Sections:**

**A. Movie Header (lines 21-112)**
- Background image (if exists)
- Poster image
- Title and year
- Country and language
- Genres (badges)
- Directors and cast
- Rating section (Livewire component)
- Favorite button (Livewire component)
- Trailer button

**B. Overview Section (lines 120-129)**
- Movie description

**C. Trailer & Reviews (lines 131-138)**
- Trailer section (component)
- Reviews list (Livewire component)

**D. Related Movies (lines 150-167)**
- Grid of related movie cards

---

## Favorite Button Flow (Livewire)

### Component Overview

**Component:** `FavoriteButton` (`app/Http/Livewire/FavoriteButton.php`)

**Purpose:** Allow users to add/remove movies from favorites without page reload

### Flow Sequence:

```
User Click → Livewire Wire:Click → Component Method → Database → Update State → Re-render
```

### Detailed Flow:

#### 1. Component Mount (`app/Http/Livewire/FavoriteButton.php:17-25`)

**Function:** `mount(Movie $movie)`

```php
public function mount(Movie $movie)
{
    $this->movie = $movie;
    $user = Auth::user();
    if ($user) {
        // Check if user has favorited this movie
        $this->isFavorited = $user->favorites()
            ->wherePivot('movie_id', $movie->id)
            ->exists();
    }
    // Get total favorite count for this movie
    $this->favoriteCount = $movie->favoritedBy()->count();
}
```

**What Happens:**
1. Stores movie object
2. Checks if authenticated user has favorited the movie
3. Gets total favorite count

**Properties:**
- `$movie`: Movie model instance
- `$isFavorited`: Boolean (whether current user favorited)
- `$favoriteCount`: Integer (total favorites count)

#### 2. View: Button Display (`resources/views/livewire/favorite-button.blade.php`)

```blade
<button wire:click="toggleFavorite" 
    class="btn {{ $isFavorited ? 'btn-accent' : 'btn-outline btn-accent' }}">
    <i class="bx {{ $isFavorited ? 'bxs-heart' : 'bx-heart' }}"></i>
    <span>{{ $isFavorited ? 'Remove from Favorites' : 'Add to Favorites' }}</span>
    <span class="ml-1 text-sm opacity-70">({{ $favoriteCount }})</span>
</button>
```

**Key Points:**
- `wire:click="toggleFavorite"` triggers Livewire method
- Button text/icon changes based on `$isFavorited` state
- Shows favorite count

#### 3. Toggle Method (`app/Http/Livewire/FavoriteButton.php:29-44`)

**Function:** `toggleFavorite()`

```php
public function toggleFavorite()
{
    $user = Auth::user();
    if (!$user) return redirect()->route('login');
    if ($user->role === 'admin') return;  // Admins can't favorite
    
    if ($this->isFavorited) {
        // Remove from favorites (detach from pivot table)
        $user->favorites()->detach($this->movie->id);
        $this->isFavorited = false;
    } else {
        // Add to favorites (attach to pivot table)
        $user->favorites()->attach($this->movie->id);
        $this->isFavorited = true;
    }
    
    // Update favorite count
    $this->favoriteCount = $this->movie->favoritedBy()->count();
}
```

**Process:**
1. Check authentication (redirect if not logged in)
2. Check if admin (admins can't favorite)
3. Toggle favorite status:
   - If favorited: `detach()` from `user_favorites` pivot table
   - If not favorited: `attach()` to `user_favorites` pivot table
4. Update local state
5. Recalculate favorite count

**Database Operations:**
- **Attach:** `INSERT INTO user_favorites (user_id, movie_id, created_at, updated_at) VALUES (?, ?, ?, ?)`
- **Detach:** `DELETE FROM user_favorites WHERE user_id = ? AND movie_id = ?`

#### 4. Livewire Re-render

**After method execution:**
1. Livewire sends updated HTML for the component
2. Browser swaps old component HTML with new HTML
3. Button updates without full page reload

---

## Rating & Review Flow (Livewire)

### Components Overview

**Three Livewire Components:**
1. **ReviewSection** (`app/Http/Livewire/ReviewSection.php`): Shows average rating and "Leave Review" button
2. **ReviewModal** (`app/Http/Livewire/ReviewModal.php`): Modal form for submitting reviews
3. **ReviewsList** (`app/Http/Livewire/ReviewsList.php`): Displays list of all reviews

### Flow Sequence:

```
User Click "Leave Review" → Modal Opens → Fill Form → Submit → Database → 
Dispatch Event → Update All Components → Re-render
```

### Detailed Flow:

#### 1. ReviewSection Component

**Component:** `ReviewSection` (`app/Http/Livewire/ReviewSection.php`)

**A. Mount (`app/Http/Livewire/ReviewSection.php:18-25`)**

```php
public function mount(Movie $movie, $userReview = null, $avgRating = 0, $totalReviews = 0)
{
    $this->movie = $movie;
    $this->movieId = (int) $movie->id;
    $this->userReview = $userReview;
    $this->avgRating = $avgRating;
    $this->totalReviews = $totalReviews;
}
```

**Properties:**
- `$movie`: Movie model
- `$userReview`: User's existing review (if any)
- `$avgRating`: Average rating (0-5)
- `$totalReviews`: Total number of reviews

**B. View (`resources/views/livewire/review-section.blade.php`)**

```blade
<div class="flex items-center gap-4">
    <!-- Star display -->
    <div class="text-center space-y-2">
        @for($i = 1; $i <= 5; $i++)
            <i class="bx {{ $i <= floor($avgRating) ? 'bxs-star text-yellow-400' : 'bx-star text-gray-400' }}"></i>
        @endfor
        <div>{{ number_format($avgRating, 1) }}/5</div>
        <div>{{ $totalReviews }} reviews</div>
    </div>
    
    @if (Auth::check() && Auth::user()->role !== 'admin')
        @if($userReview)
            <button disabled>Already Reviewed</button>
        @else
            <button wire:click="$dispatch('openReviewModal')">
                Leave a Review
            </button>
            @push('modals')
                <livewire:review-modal :movie="$movie" />
            @endpush
        @endif
    @endif
</div>
```

**Key Points:**
- Shows star rating visualization
- Displays average rating and count
- "Leave Review" button dispatches `openReviewModal` event
- If user already reviewed, button is disabled

**C. Refresh Handler (`app/Http/Livewire/ReviewSection.php:27-39`)**

```php
#[On('reviewUpdated')]
public function refreshReviews($updatedMovieId)
{
    if ($this->movieId !== (int) $updatedMovieId) return;
    
    $movieId = $this->movieId;
    
    // Reload user's review
    $this->userReview = RatingReview::where('movie_id', $movieId)
        ->where('user_id', auth()->id())
        ->first();
    
    // Recalculate average and count
    $this->avgRating = RatingReview::where('movie_id', $movieId)->avg('rating') ?? 0;
    $this->totalReviews = RatingReview::where('movie_id', $movieId)->count();
}
```

**What Happens:**
- Listens for `reviewUpdated` event
- If event is for this movie, refresh review data
- Updates average rating and count

#### 2. ReviewModal Component

**Component:** `ReviewModal` (`app/Http/Livewire/ReviewModal.php`)

**A. Mount (`app/Http/Livewire/ReviewModal.php:28-40`)**

```php
public function mount($movie)
{
    $this->movie = $movie;
    
    // Load existing review if user has one
    $this->userReview = RatingReview::where('user_id', Auth::id())
        ->where('movie_id', $movie->id)
        ->first();
    
    if ($this->userReview) {
        $this->rating = $this->userReview->rating;
        $this->review = $this->userReview->review;
    }
}
```

**Properties:**
- `$movie`: Movie model
- `$rating`: Integer (1-5)
- `$review`: String (optional text review)
- `$isOpen`: Boolean (modal visibility)
- `$userReview`: Existing review (if any)

**B. Validation Rules (`app/Http/Livewire/ReviewModal.php:23-26`)**

```php
protected $rules = [
    'rating' => 'required|integer|min:1|max:5',
    'review' => 'nullable|string|max:2000',
];
```

**C. Open Modal (`app/Http/Livewire/ReviewModal.php:42-46`)**

```php
#[On('openReviewModal')]
public function openModal()
{
    $this->isOpen = true;
}
```

**Triggers:** When `openReviewModal` event is dispatched (from ReviewSection button)

**D. Submit Review (`app/Http/Livewire/ReviewModal.php:53-82`)**

**Function:** `submitReview()`

```php
public function submitReview()
{
    $this->validate();
    
    try {
        DB::beginTransaction();
        
        // Create or update review
        $record = RatingReview::updateOrCreate(
            ['user_id' => Auth::id(), 'movie_id' => $this->movie->id],
            ['rating' => $this->rating, 'review' => $this->review]
        );
        
        DB::commit();
    } catch (\Exception $e) {
        DB::rollBack();
        Log::error('Review submit failed: '.$e->getMessage());
        $this->dispatch('toast', type: 'error', message: 'Could not save review.');
        return;
    }
    
    $this->userReview = $record;
    $this->isOpen = false;
    
    // Dispatch events to update other components
    $this->dispatch('reviewUpdated', $this->movie->id)->to(ReviewSection::class);
    $this->dispatch('reviewUpdated', $this->movie->id)->to(ReviewsList::class);
    
    $this->dispatch('toast', type: 'success', message: 'Review saved.');
}
```

**Process:**
1. **Validate** input (rating 1-5, review max 2000 chars)
2. **Transaction** (ensures data consistency)
3. **Update or Create:**
   - If user has existing review: update it
   - If no review: create new one
4. **Dispatch Events:**
   - `reviewUpdated` to `ReviewSection` component
   - `reviewUpdated` to `ReviewsList` component
5. **Close modal** and show success message

**Database Operation:**
```sql
INSERT INTO ratings_reviews (user_id, movie_id, rating, review, created_at, updated_at)
VALUES (?, ?, ?, ?, ?, ?)
ON DUPLICATE KEY UPDATE rating = ?, review = ?, updated_at = ?
```

**Note:** `updateOrCreate` uses unique constraint on `(user_id, movie_id)` to ensure one review per user per movie.

#### 3. ReviewsList Component

**Component:** `ReviewsList` (`app/Http/Livewire/ReviewsList.php`)

**A. Mount (`app/Http/Livewire/ReviewsList.php:15-20`)**

```php
public function mount($movie, $reviews, $realReviewCount = 0)
{
    $this->movie = $movie;
    $this->reviews = $reviews;
    $this->realReviewCount = $realReviewCount;
}
```

**B. Refresh Handler (`app/Http/Livewire/ReviewsList.php:22-29`)**

```php
#[On('reviewUpdated')]
public function refreshReviews($movieId)
{
    if ($this->movie->id != $movieId) return;
    
    // Reload all reviews
    $this->reviews = RatingReview::where('movie_id', $movieId)->latest()->get();
    $this->realReviewCount = $this->reviews->count();
}
```

**What Happens:**
- Listens for `reviewUpdated` event
- Reloads all reviews from database
- Updates count

---

## Livewire Component Communication

### Event Flow Diagram

```
┌──────────────────┐
│  ReviewSection   │
│   (Displays      │
│    rating)       │
└────────┬─────────┘
         │
         │ User clicks "Leave Review"
         │ $dispatch('openReviewModal')
         │
         ▼
┌──────────────────┐
│   ReviewModal    │
│   (Form opens)   │
└────────┬─────────┘
         │
         │ User submits review
         │ submitReview()
         │
         │ Database: INSERT/UPDATE
         │
         │ $dispatch('reviewUpdated', movieId)
         │   ├──> ReviewSection (refresh)
         │   └──> ReviewsList (refresh)
         │
         ▼
┌──────────────────┐     ┌──────────────────┐
│  ReviewSection   │     │   ReviewsList    │
│   (Updated)      │     │    (Updated)     │
└──────────────────┘     └──────────────────┘
```

### Event Types

1. **`openReviewModal`**
   - **Dispatcher:** ReviewSection button click
   - **Listener:** ReviewModal `openModal()`
   - **Purpose:** Open review form

2. **`reviewUpdated`**
   - **Dispatcher:** ReviewModal `submitReview()`
   - **Listeners:**
     - ReviewSection `refreshReviews()`
     - ReviewsList `refreshReviews()`
   - **Purpose:** Notify components to refresh review data

### Wire Directives

| Directive | Purpose | Example |
|-----------|---------|---------|
| `wire:click` | Trigger method on click | `wire:click="toggleFavorite"` |
| `wire:model` | Two-way data binding | `wire:model="rating"` |
| `$dispatch()` | Dispatch Livewire event | `$dispatch('openReviewModal')` |
| `#[On('event')]` | Listen for event | `#[On('reviewUpdated')]` |
| `->to(Component::class)` | Target specific component | `$dispatch('event')->to(Component::class)` |

---

## Related Movies Flow

### Algorithm Explanation

**Location:** `app/Helpers/MovieHelper.php:86-118`

**Step-by-Step:**

1. **Get Current Movie Genres:**
   ```php
   $genreIds = $movie->genres->pluck('id')->toArray();
   ```

2. **Find Movies with Matching Genres:**
   ```php
   $relatedIds = DB::table('movie_genres')
       ->select('movie_id', DB::raw('COUNT(*) as match_genres'))
       ->whereIn('genre_id', $genreIds)
       ->where('movie_id', '!=', $movie->id)
       ->groupBy('movie_id')
       ->orderByDesc('match_genres')
       ->limit(50)
       ->pluck('movie_id');
   ```

3. **Load Full Movie Details:**
   ```php
   $relatedMovies = Movie::with(['genres', 'country', 'language', 'ratings'])
       ->whereIn('id', $relatedIds)
       ->get();
   ```

4. **Calculate Match Score:**
   ```php
   $m->match_genres = $m->genres->pluck('id')->intersect($genreIds)->count();
   $m->avg_rating = $m->ratings->count() ? round($m->ratings->avg('rating'), 1) : 0;
   ```

5. **Sort and Limit:**
   ```php
   ->sortByDesc(fn($m) => [
       $m->match_genres,      // Primary: More matching genres
       $m->avg_rating ?? 0,   // Secondary: Higher rating
       $m->release_year ?? 0   // Tertiary: Newer year
   ])
   ->take(5)  // Return top 5
   ```

**Example:**
- Current movie: "Avengers" (Action, Adventure, Sci-Fi)
- Related movie 1: "Iron Man" (Action, Adventure, Sci-Fi) → 3 matches
- Related movie 2: "Thor" (Action, Adventure) → 2 matches
- Related movie 3: "Inception" (Action, Thriller) → 1 match

**Result:** Sorted by match count, then rating, then year.

---

## MVC Communication Flow

### Complete Request Flow

```
┌─────────┐      ┌──────────┐      ┌──────────────┐      ┌──────────┐      ┌──────────┐
│  User   │ ───> │  Route   │ ───> │  Controller │ ───> │  Helper  │ ───> │  Models  │
│  (Click)│      │ (web.php)│      │ (MovieCtrl) │      │(MovieHlp)│      │ (Movie,  │
└─────────┘      └──────────┘      └──────────────┘      └──────────┘      │ Rating,  │
     │                  │                   │                  │             │  etc.)   │
     │ GET /viewMovie/1  │                  │                  │             └──────────┘
     │                  │                   │                  │                  │
     │                  │                   │                  │            Load movie    │
     │                  │                   │                  │            with relations│
     │                  │                   │                  │                  │
     │                  │                   │                  │            Get reviews   │
     │                  │                   │                  │            Get related   │
     │                  │                   │                  │            Split cast    │
     │                  │                   │                  │                  │
     │ <────────────────┴───────────────────┴──────────────────┴──────────────────┘
     │                          Return view with data
     │
     │                          Render Blade with Livewire components
     │
     │                          ┌─────────────────┐
     │                          │ Livewire        │
     │                          │ Components      │
     │                          │ (Reactive)      │
     │                          └─────────────────┘
```

---

## Database Operations

### Tables Involved

1. **movies** (main table)
   - Movie details (title, description, year, URLs, etc.)

2. **ratings_reviews** (reviews table)
   - `user_id`, `movie_id`, `rating`, `review`, `created_at`, `updated_at`
   - **Unique constraint:** `(user_id, movie_id)` (one review per user per movie)

3. **user_favorites** (pivot table)
   - `user_id`, `movie_id`, `created_at`, `updated_at`
   - **Unique constraint:** `(user_id, movie_id)` (no duplicate favorites)

4. **movie_genres** (pivot table)
   - `movie_id`, `genre_id`
   - Used for finding related movies

5. **movie_cast** (pivot table)
   - `movie_id`, `person_id`, `role`
   - Used for displaying directors and actors

### Relationships Used

**Movie Model:**
```php
// Many-to-Many: Movie ↔ User (via user_favorites)
public function favoritedBy()
{
    return $this->belongsToMany(User::class, 'user_favorites', 'movie_id', 'user_id');
}

// One-to-Many: Movie → RatingReview
public function ratings()
{
    return $this->hasMany(RatingReview::class, 'movie_id', 'id');
}
```

**User Model:**
```php
// Many-to-Many: User ↔ Movie (via user_favorites)
public function favorites()
{
    return $this->belongsToMany(Movie::class, 'user_favorites', 'user_id', 'movie_id')
        ->withTimestamps();
}

// One-to-Many: User → RatingReview
public function ratings()
{
    return $this->hasMany(RatingReview::class, 'user_id', 'id');
}
```

---

## Key Functions Reference

### MovieController Functions

| Function | Purpose | Parameters | Returns |
|----------|---------|------------|---------|
| `show($id)` | Display movie details page | `int $id` | `View` |

### MovieHelper Functions (Used in ViewMovie)

| Function | Purpose | Parameters | Returns |
|----------|---------|------------|---------|
| `getMovieWithDetails()` | Load movie with relationships | `int $movieId` | `Movie\|null` |
| `getMovieReviews()` | Get reviews for movie | `int $movieId` | `array` |
| `getRelatedMovies()` | Find related movies by genre | `Movie $movie` | `Collection` |
| `splitCastRoles()` | Separate directors and actors | `Movie $movie` | `array` |
| `formatMovies()` | Format movie data for display | `Collection $movies` | `Collection` |

### Livewire Components

| Component | Purpose | Key Methods | Key Events |
|-----------|---------|-------------|------------|
| `FavoriteButton` | Toggle favorite status | `toggleFavorite()` | None |
| `ReviewSection` | Display rating summary | `refreshReviews()` | Listens: `reviewUpdated` |
| `ReviewModal` | Submit review form | `submitReview()`, `openModal()` | Dispatches: `reviewUpdated`, `openReviewModal` |
| `ReviewsList` | Display all reviews | `refreshReviews()` | Listens: `reviewUpdated` |

---

## Common Patterns

### 1. Livewire Component Mount

```php
public function mount(Model $model, $data = null)
{
    $this->model = $model;
    $this->data = $data;
    // Initialize component state
}
```

### 2. Event Dispatching

```php
// Dispatch to all components
$this->dispatch('eventName', $data);

// Dispatch to specific component
$this->dispatch('eventName', $data)->to(ComponentClass::class);
```

### 3. Event Listening

```php
#[On('eventName')]
public function handleEvent($data)
{
    // Handle event
}
```

### 4. Pivot Table Operations

```php
// Attach (favorite)
$user->favorites()->attach($movieId);

// Detach (unfavorite)
$user->favorites()->detach($movieId);

// Check existence
$user->favorites()->wherePivot('movie_id', $movieId)->exists();
```

### 5. Update or Create

```php
RatingReview::updateOrCreate(
    ['user_id' => $userId, 'movie_id' => $movieId],
    ['rating' => $rating, 'review' => $review]
);
```

---

## Error Handling

### Movie Not Found

- **Location:** `app/Http/Controllers/MovieController.php:46-48`
- **Response:** `abort(404, 'Movie not found.')`
- **Display:** Laravel's default 404 page

### Review Validation Errors

- **Location:** `app/Http/Livewire/ReviewModal.php:55`
- **Response:** Validation errors shown in modal
- **Display:** Livewire automatically displays validation errors

### Database Errors

- **Location:** `app/Http/Livewire/ReviewModal.php:67-70`
- **Response:** Transaction rolled back, error logged
- **Display:** Toast notification (error message)

---

## Security Considerations

1. **Authentication:**
   - Favorites and reviews require authentication
   - Checked in Livewire components

2. **Authorization:**
   - Admins cannot favorite or review (checked in components)

3. **Validation:**
   - Rating: 1-5 integer
   - Review: Max 2000 characters

4. **SQL Injection:**
   - Protected via Eloquent ORM

5. **XSS Protection:**
   - Blade escaping: `{{ $variable }}`
   - User reviews should be escaped when displayed

---

## Next Steps

After viewing a movie, users can:
- **Navigate to related movies** (click on movie card)
- **Return to home** (`route('home')`)
- **View profile** (`route('profile')`) to see favorites and reviews
- **Manage movies** (if admin) (`route('movies.manage.create')`)

See other documentation:
- [Profile Flow](./04-Profile-Flow.md)
- [Home Flow](./05-Home-Flow.md)
- [Livewire Component Flow](./07-Livewire-Component-Flow.md)

