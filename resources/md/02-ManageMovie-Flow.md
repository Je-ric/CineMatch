# Manage Movie Flow Documentation

## Overview
This document explains how movie management works in CineMatch, including creating, editing, and deleting movies. This feature is available to admin users.

---

## Table of Contents
1. [Routes & Entry Points](#routes--entry-points)
2. [Create Movie Flow](#create-movie-flow)
3. [Edit Movie Flow](#edit-movie-flow)
4. [Delete Movie Flow](#delete-movie-flow)
5. [People Management (Cast/Directors)](#people-management-castdirectors)
6. [MVC Communication Flow](#mvc-communication-flow)
7. [Database Operations](#database-operations)
8. [File Upload Handling](#file-upload-handling)

---

## Routes & Entry Points

### Routes (routes/web.php)

| Route | Method | Controller/Action | Purpose | Middleware |
|-------|--------|-------------------|---------|------------|
| `/movies/manage` | GET | `MovieController@create` | Show create form | Admin only (implied) |
| `/movies/manage/{id}` | GET | `MovieController@edit` | Show edit form | Admin only (implied) |
| `/movies` | POST | `MovieController@store` | Save new movie | Admin only (implied) |
| `/movies/{id}` | PUT | `MovieController@update` | Update existing movie | Admin only (implied) |
| `/movies/{id}` | DELETE | `MovieController@destroy` | Delete movie | Admin only (implied) |
| `/people/fetch` | POST | `PeopleController@fetch` | Get cast/directors for movie | Admin only (implied) |
| `/people/add` | POST | `PeopleController@add` | Add person to movie | Admin only (implied) |
| `/people/remove` | POST | `PeopleController@remove` | Remove person from movie | Admin only (implied) |
| `/people/search` | POST | `PeopleController@search` | Search for people | Admin only (implied) |

---

## Create Movie Flow

### Step-by-Step Sequence:

```
Admin → Navigate to /movies/manage → View (manageMovie.blade.php) 
→ Fill Form → Submit → Route → Controller → Validation → File Upload 
→ Database → Pivot Tables → Redirect
```

### Detailed Flow:

#### 1. Navigation (`routes/web.php:42`)
```php
Route::get('/movies/manage', [MovieController::class, 'create'])
    ->name('movies.manage.create');
```

#### 2. Controller: Create Method (`app/Http/Controllers/MovieController.php:324-332`)

**Function:** `create()`

**Process:**
```php
public function create()
{
    $allGenres = Genre::orderBy('name')->get();
    return view('manageMovie', [
        'editing' => false,
        'movie' => null,
        'allGenres' => $allGenres,
    ]);
}
```

**What Happens:**
1. Fetch all genres from database (for genre selection dropdown)
2. Return view with:
   - `editing = false` (indicates create mode)
   - `movie = null` (no existing movie)
   - `allGenres` (for dropdown)

#### 3. View: Form Display (`resources/views/manageMovie.blade.php`)

**Key Sections:**
- Movie basic info form (title, description, year, trailer URL)
- File upload sections (poster, background)
- Genre selection (checkboxes or multi-select)
- Country and language selection
- People management section (cast/directors)

**Form Structure:**
```blade
<form action="{{ route('movies.store') }}" method="POST" enctype="multipart/form-data">
    @csrf
    <!-- Form fields -->
</form>
```

**Note:** `enctype="multipart/form-data"` is required for file uploads.

#### 4. Form Submission (`routes/web.php:45`)
```php
Route::post('/movies', [MovieController::class, 'store'])
    ->name('movies.store');
```

#### 5. Controller: Store Method (`app/Http/Controllers/MovieController.php:69-144`)

**Function:** `store(Request $request)`

**Step-by-Step Process:**

**A. Validation (lines 71-82)**
```php
$validated = $request->validate([
    'title' => 'required|string|max:255',
    'description' => 'nullable|string',
    'release_year' => 'nullable|integer|min:1900|max:' . date('Y'),
    'trailer_url' => 'nullable|string|max:2048',
    'poster_file' => 'required|image|max:5120',  // 5MB max
    'background_file' => 'nullable|image|max:8192',  // 8MB max
    'genres' => 'array',
    'genres.*' => 'integer|exists:genres,id',
    'countryName' => 'required|string',
    'languageName' => 'required|string',
]);
```

**B. Handle File Uploads (lines 84-99)**
```php
$posterRelative = $this->storeImageToPublic(
    $request->file('poster_file'),
    'uploads/posters',
    $validated['title'],
    $validated['release_year'] ?? null
);

$bgRelative = null;
if ($request->hasFile('background_file')) {
    $bgRelative = $this->storeImageToPublic(
        $request->file('background_file'),
        'uploads/backgrounds',
        $validated['title'],
        $validated['release_year'] ?? null
    );
}
```

**File Upload Process:**
- **Location:** `app/Http/Controllers/MovieController.php:225-253`
- **Function:** `storeImageToPublic($file, $folder, $movieTitle, $releaseYear)`

**Steps:**
1. Generate filename: `{title}_{year}_{timestamp}.{ext}`
   - Uses `Str::slug()` to sanitize title
   - Example: `avengers_endgame_2019_1699123456.jpg`
2. Create directory if doesn't exist: `public/uploads/posters/` or `public/uploads/backgrounds/`
3. Move uploaded file to directory
4. Return relative path: `uploads/posters/filename.jpg`

**C. Find or Create Country/Language (lines 101-102)**
```php
$countryId = $this->findOrCreateCountryFromJson($validated['countryName']);
$languageId = $this->findOrCreateLanguageFromJson($validated['languageName']);
```

**Helper Functions:**
- **Location:** `app/Http/Controllers/MovieController.php:274-316`
- **Function:** `findOrCreateCountryFromJson(string $countryName): ?int`
- **Function:** `findOrCreateLanguageFromJson(string $languageName): ?int`

**Process:**
1. Check JSON files for reference (optional, for suggestions)
2. Use `Country::firstOrCreate(['name' => $countryName])`
3. Return the ID

**D. Database Transaction (lines 104-143)**
```php
DB::beginTransaction();
try {
    // Create movie
    $movie = new Movie([...]);
    $movie->save();
    
    // Sync genres (pivot table)
    if (!empty($validated['genres'])) {
        $movie->genres()->sync($validated['genres']);
    }
    
    DB::commit();
} catch (\Throwable $e) {
    DB::rollBack();
    // Handle error
}
```

**Movie Creation:**
```php
$movie = new Movie([
    'title' => $validated['title'],
    'description' => $validated['description'] ?? null,
    'release_year' => $validated['release_year'] ?? null,
    'trailer_url' => $validated['trailer_url'] ?? null,
    'poster_url' => $posterRelative,
    'background_url' => $bgRelative,
    'country_id' => $countryId,
    'language_id' => $languageId,
]);
$movie->save();
```

**Genre Sync (Pivot Table):**
```php
$movie->genres()->sync($validated['genres']);
```

**What `sync()` Does:**
- Removes all existing genre relationships
- Adds new relationships based on provided array
- Updates `movie_genres` pivot table

**E. Redirect (line 137)**
```php
return redirect()->route('movies.manage.edit', ['id' => $movie->id])
    ->with('success', 'Movie created. You can now add people and media.');
```

---

## Edit Movie Flow

### Step-by-Step Sequence:

```
Admin → Navigate to /movies/manage/{id} → Controller → View (with existing data) 
→ Modify Form → Submit → Route → Controller → Validation → File Upload 
→ Database Update → Pivot Tables → Redirect
```

### Detailed Flow:

#### 1. Navigation (`routes/web.php:43`)
```php
Route::get('/movies/manage/{id}', [MovieController::class, 'edit'])
    ->name('movies.manage.edit');
```

#### 2. Controller: Edit Method (`app/Http/Controllers/MovieController.php:334-343`)

**Function:** `edit($id)`

```php
public function edit($id)
{
    $movie = Movie::with(['genres'])->findOrFail($id);
    $allGenres = Genre::orderBy('name')->get();
    return view('manageMovie', [
        'editing' => true,
        'movie' => $movie,
        'allGenres' => $allGenres,
    ]);
}
```

**What Happens:**
1. Load movie with genres relationship (eager loading)
2. Fetch all genres for dropdown
3. Return view with:
   - `editing = true` (indicates edit mode)
   - `movie` object (existing movie data)
   - `allGenres`

#### 3. View: Pre-filled Form (`resources/views/manageMovie.blade.php`)

**Form displays:**
- Existing movie data in input fields
- Selected genres checked
- Current poster/background images (if exists)
- Existing cast/directors list

**Form Structure:**
```blade
<form action="{{ route('movies.update', ['id' => $movie->id]) }}" method="POST" enctype="multipart/form-data">
    @csrf
    @method('PUT')
    <!-- Pre-filled form fields -->
</form>
```

#### 4. Form Submission (`routes/web.php:46`)
```php
Route::put('/movies/{id}', [MovieController::class, 'update'])
    ->name('movies.update');
```

#### 5. Controller: Update Method (`app/Http/Controllers/MovieController.php:146-204`)

**Function:** `update(Request $request, $id)`

**Process:**

**A. Load Movie (line 148)**
```php
$movie = Movie::findOrFail($id);
```

**B. Validation (lines 150-160)**
- Similar to store, but files are nullable (can keep existing)

**C. Handle File Uploads (lines 162-180)**
```php
$posterRelative = $movie->poster_url;  // Keep existing if no new file
if ($request->hasFile('poster_file')) {
    $posterRelative = $this->storeImageToPublic(...);
    // Optionally delete old file
}
```

**D. Update Movie (lines 185-194)**
```php
$movie->update([
    'title' => $validated['title'],
    'description' => $validated['description'] ?? null,
    'release_year' => $validated['release_year'] ?? null,
    'trailer_url' => $validated['trailer_url'] ?? null,
    'poster_url' => $posterRelative ?? $movie->poster_url,
    'background_url' => $bgRelative ?? $movie->background_url,
    'country_id' => $countryId,
    'language_id' => $languageId,
]);
```

**E. Sync Genres (lines 196-200)**
```php
if (!empty($validated['genres'])) {
    $movie->genres()->sync($validated['genres']);
} else {
    $movie->genres()->detach();  // Remove all if empty
}
```

**F. Redirect (lines 202-203)**
```php
return redirect()->route('movies.manage.edit', ['id' => $movie->id])
    ->with('success', 'Movie details updated successfully.');
```

---

## Delete Movie Flow

### Step-by-Step Sequence:

```
Admin → Click Delete Button → Confirm → Route → Controller → Delete Files 
→ Detach Pivot Tables → Delete Movie → JSON Response
```

### Detailed Flow:

#### 1. Delete Route (`routes/web.php:47`)
```php
Route::delete('/movies/{id}', [MovieController::class, 'destroy'])
    ->name('movies.destroy');
```

#### 2. Delete Button (`resources/views/components/movie-card.blade.php:60-67`)

**Button Structure:**
```blade
<form method="POST" action="{{ route('movies.destroy', ['id' => $movie->id]) }}" class="flex-1">
    @csrf
    @method('DELETE')
    <button type="submit" class="btn btn-xs btn-outline btn-error w-full"
        onclick="return confirm('Delete this movie?')">
        <i class='bx bx-trash'></i> Delete
    </button>
</form>
```

#### 3. Controller: Destroy Method (`app/Http/Controllers/MovieController.php:208-219`)

**Function:** `destroy($id)`

**Process:**

```php
public function destroy($id)
{
    $movie = Movie::findOrFail($id);
    
    // Delete physical files
    $this->deletePublicFile($movie->getRawOriginal('poster_url'));
    $this->deletePublicFile($movie->getRawOriginal('background_url'));
    
    // Detach relationships
    $movie->genres()->detach();
    $movie->cast()->detach();
    
    // Delete movie record
    $movie->delete();
    
    return response()->json(['success' => true]);
}
```

**A. Delete Files (lines 211-212)**
- Uses `getRawOriginal('poster_url')` to get database value (not accessor)
- Deletes from `public/uploads/posters/` or `public/uploads/backgrounds/`

**B. Detach Relationships (lines 213-216)**
- `genres()->detach()` - Remove all genre relationships
- `cast()->detach()` - Remove all cast/director relationships

**C. Delete Movie (line 217)**
- Deletes movie record from `movies` table
- Foreign key constraints handle `ratings_reviews` and `user_favorites` (cascade delete)

**D. Response (line 218)**
- Returns JSON response for AJAX requests

---

## People Management (Cast/Directors)

### Overview

People (actors and directors) are managed separately from movie creation/editing. They're added/removed via AJAX requests.

### Models Involved

- **MoviePerson** (`app/Models/MoviePerson.php`): Stores person name
- **Movie** (`app/Models/Movie.php`): Has many-to-many relationship with MoviePerson via `movie_cast` pivot table
- **Pivot Table:** `movie_cast` (columns: `movie_id`, `person_id`, `role`, `created_at`, `updated_at`)

### Relationships

**Movie Model:**
```php
public function cast()
{
    return $this->belongsToMany(
        MoviePerson::class,
        'movie_cast',
        'movie_id',
        'person_id'
    )
    ->withPivot('role')  // Include role column
    ->withTimestamps();
}
```

### PeopleController Functions

#### 1. Fetch People (`app/Http/Controllers/PeopleController.php:12-27`)

**Function:** `fetch(Request $request)`

**Purpose:** Get all cast or directors for a specific movie

**Route:** `POST /people/fetch`

**Request Parameters:**
- `movie_id`: Required, integer, must exist in movies table
- `role`: Required, enum ('Director' or 'Cast')

**Process:**
```php
$movie = Movie::findOrFail($validated['movie_id']);

$people = $movie->cast()
    ->wherePivot('role', $validated['role'])
    ->orderBy('name')
    ->get(['movie_people.id', 'movie_people.name']);

return response()->json(['success' => true, 'data' => $people]);
```

**Response:**
```json
{
    "success": true,
    "data": [
        {"id": 1, "name": "John Doe"},
        {"id": 2, "name": "Jane Smith"}
    ]
}
```

#### 2. Add Person (`app/Http/Controllers/PeopleController.php:29-56`)

**Function:** `add(Request $request)`

**Purpose:** Add a person (actor or director) to a movie

**Route:** `POST /people/add`

**Request Parameters:**
- `movie_id`: Required, integer
- `name`: Required, string, max 255
- `role`: Required, enum ('Director' or 'Cast')

**Process:**
```php
$movie = Movie::findOrFail($validated['movie_id']);
$person = MoviePerson::firstOrCreate(['name' => trim($validated['name'])]);

// Check if already attached with same role
$alreadyExists = $movie->cast()
    ->where('movie_people.id', $person->id)
    ->wherePivot('role', $validated['role'])
    ->exists();

if ($alreadyExists) {
    return response()->json([
        'success' => false,
        'message' => "{$person->name} is already added as {$validated['role']}.",
    ]);
}

$movie->cast()->attach($person->id, ['role' => $validated['role']]);

return response()->json(['success' => true, 'data' => ['person_id' => $person->id]]);
```

**Key Points:**
- Uses `firstOrCreate()` to avoid duplicates in `movie_people` table
- Checks if person already attached with same role (prevents duplicates)
- Uses `attach()` with pivot data: `['role' => 'Director' or 'Cast']`

#### 3. Remove Person (`app/Http/Controllers/PeopleController.php:58-76`)

**Function:** `remove(Request $request)`

**Purpose:** Remove a person from a movie

**Route:** `POST /people/remove`

**Request Parameters:**
- `movie_id`: Required, integer
- `person_id`: Required, integer
- `role`: Optional, enum ('Director' or 'Cast')

**Process:**
```php
$movie = Movie::findOrFail($validated['movie_id']);
$query = $movie->cast();

if (!empty($validated['role'])) {
    $query->wherePivot('role', $validated['role']);
}

$query->detach($validated['person_id']);

return response()->json(['success' => true]);
```

**Key Points:**
- Can remove by role (only remove if role matches)
- Or remove regardless of role (if role not specified)

#### 4. Search People (`app/Http/Controllers/PeopleController.php:78-95`)

**Function:** `search(Request $request)`

**Purpose:** Search for people by name (for autocomplete)

**Route:** `POST /people/search`

**Request Parameters:**
- `query`: Optional, string, max 255

**Process:**
```php
$term = trim($validated['query'] ?? '');
if ($term === '') {
    return response()->json(['success' => true, 'data' => []]);
}

$results = MoviePerson::where('name', 'like', "%{$term}%")
    ->orderBy('name')
    ->limit(15)
    ->get(['id', 'name']);

return response()->json(['success' => true, 'data' => $results]);
```

**Response:**
```json
{
    "success": true,
    "data": [
        {"id": 1, "name": "John Doe"},
        {"id": 3, "name": "Johnny Depp"}
    ]
}
```

---

## MVC Communication Flow

### Create Movie Sequence Diagram

```
┌─────────┐      ┌──────────┐      ┌──────────────┐      ┌─────────┐      ┌──────────┐
│  Admin │ ───> │  Route   │ ───> │  Controller │ ───> │  Model  │ ───> │ Database │
│  (View) │      │ (web.php)│      │ (MovieCtrl) │      │ (Movie) │      │  (movies)│
└─────────┘      └──────────┘      └──────────────┘      └─────────┘      └──────────┘
     │                  │                   │                  │                  │
     │ GET /movies/manage                    │                  │                  │
     │                  │                   │                  │                  │
     │ <────────────────┴───────────────────┴──────────────────┴──────────────────┘
     │                          Return view with form
     │
     │ POST /movies (form submit)
     │                  │                   │                  │                  │
     │                  │                   │            Validate input           │
     │                  │                   │            Upload files             │
     │                  │                   │                  │                  │
     │                  │                   │            CREATE movie             │
     │                  │                   │                  │                  │
     │                  │                   │            SYNC genres             │
     │                  │                   │            (pivot table)           │
     │                  │                   │                  │                  │
     │ <────────────────┴───────────────────┴──────────────────┴──────────────────┘
     │                          Redirect to edit page
```

### Edit Movie Sequence Diagram

```
┌─────────┐      ┌──────────┐      ┌──────────────┐      ┌─────────┐      ┌──────────┐
│  Admin │ ───> │  Route   │ ───> │  Controller │ ───> │  Model  │ ───> │ Database │
│  (View) │      │ (web.php)│      │ (MovieCtrl) │      │ (Movie) │      │  (movies)│
└─────────┘      └──────────┘      └──────────────┘      └─────────┘      └──────────┘
     │                  │                   │                  │                  │
     │ GET /movies/manage/{id}               │                  │                  │
     │                  │                   │            LOAD movie               │
     │                  │                   │            with genres             │
     │ <────────────────┴───────────────────┘                  │                  │
     │                          Return view with pre-filled form
     │
     │ PUT /movies/{id} (form submit)
     │                  │                   │                  │                  │
     │                  │                   │            Validate input           │
     │                  │                   │            Upload files (if new)    │
     │                  │                   │                  │                  │
     │                  │                   │            UPDATE movie             │
     │                  │                   │                  │                  │
     │                  │                   │            SYNC genres             │
     │                  │                   │            (pivot table)             │
     │                  │                   │                  │                  │
     │ <────────────────┴───────────────────┘                  │                  │
     │                          Redirect to edit page with success
```

### Add Person Sequence Diagram

```
┌─────────┐      ┌──────────┐      ┌──────────────┐      ┌──────────────┐      ┌──────────┐
│  View   │ ───> │  Route   │ ───> │  Controller  │ ───> │   Model      │ ───> │ Database │
│ (AJAX)  │      │ (web.php)│      │ (PeopleCtrl) │      │ (MoviePerson)│      │ (pivots) │
└─────────┘      └──────────┘      └──────────────┘      └──────────────┘      └──────────┘
     │                  │                   │                  │                  │
     │ POST /people/add                     │                  │                  │
     │                  │                   │            Find or create person   │
     │                  │                   │                  │                  │
     │                  │                   │            Check if exists          │
     │                  │                   │            in pivot table           │
     │                  │                   │                  │                  │
     │                  │                   │            ATTACH person            │
     │                  │                   │            with role                 │
     │                  │                   │                  │                  │
     │ <────────────────┴───────────────────┴──────────────────┴──────────────────┘
     │                          JSON response
```

---

## Database Operations

### Tables Involved

1. **movies** (main table)
   - `id`, `title`, `description`, `release_year`, `poster_url`, `background_url`, `trailer_url`, `country_id`, `language_id`, `created_at`, `updated_at`

2. **movie_genres** (pivot table)
   - `movie_id` (foreign key)
   - `genre_id` (foreign key)
   - No timestamps (simple pivot)

3. **movie_cast** (pivot table)
   - `movie_id` (foreign key)
   - `person_id` (foreign key)
   - `role` (enum: 'Director' or 'Cast')
   - `created_at`, `updated_at`

4. **movie_people** (people table)
   - `id`, `name`, `created_at`, `updated_at`

5. **countries** (lookup table)
   - `id`, `name`, `created_at`, `updated_at`

6. **languages** (lookup table)
   - `id`, `name`, `created_at`, `updated_at`

### Relationships

**Movie Model:**
```php
// Many-to-Many: Movie ↔ Genre
public function genres()
{
    return $this->belongsToMany(Genre::class, 'movie_genres', 'movie_id', 'genre_id');
}

// Many-to-Many: Movie ↔ MoviePerson (with pivot data)
public function cast()
{
    return $this->belongsToMany(MoviePerson::class, 'movie_cast', 'movie_id', 'person_id')
        ->withPivot('role')
        ->withTimestamps();
}

// Belongs To: Movie → Country
public function country()
{
    return $this->belongsTo(Country::class);
}

// Belongs To: Movie → Language
public function language()
{
    return $this->belongsTo(Language::class);
}
```

### Pivot Table Operations

**Sync Genres:**
```php
$movie->genres()->sync([1, 2, 3]);  // Removes all, adds new
```

**Attach Person:**
```php
$movie->cast()->attach($personId, ['role' => 'Director']);
```

**Detach Person:**
```php
$movie->cast()->detach($personId);
```

**Filter by Pivot:**
```php
$directors = $movie->cast()->wherePivot('role', 'Director')->get();
```

---

## File Upload Handling

### Upload Directory Structure

```
public/
├── uploads/
│   ├── posters/
│   │   ├── avengers_endgame_2019_1699123456.jpg
│   │   └── ...
│   └── backgrounds/
│       ├── avengers_endgame_2019_1699123456.jpg
│       └── ...
```

### File Naming Convention

**Format:** `{title}_{year}_{timestamp}.{extension}`

**Example:** `avengers_endgame_2019_1699123456.jpg`

**Process:**
1. Sanitize title using `Str::slug($movieTitle, '_')`
2. Get release year or use current year
3. Get current timestamp
4. Get file extension
5. Combine: `{safeTitle}_{year}_{timestamp}.{ext}`

### File Storage Function

**Location:** `app/Http/Controllers/MovieController.php:225-253`

**Function:** `storeImageToPublic($file, $folder, $movieTitle, $releaseYear)`

**Steps:**
1. Sanitize folder path
2. Generate filename
3. Create directory if doesn't exist (`public_path($folder)`)
4. Move uploaded file
5. Return relative path (e.g., `uploads/posters/filename.jpg`)

### File Deletion Function

**Location:** `app/Http/Controllers/MovieController.php:255-268`

**Function:** `deletePublicFile($relativePath)`

**Steps:**
1. Normalize path (remove leading slashes)
2. Get absolute path (`public_path($cleanPath)`)
3. Delete file if exists

---

## Key Functions Reference

### MovieController Functions

| Function | Purpose | Parameters | Returns |
|----------|---------|------------|---------|
| `index()` | List all movies | None | `View` |
| `create()` | Show create form | None | `View` |
| `store()` | Save new movie | `Request $request` | `RedirectResponse` |
| `edit($id)` | Show edit form | `int $id` | `View` |
| `update()` | Update movie | `Request $request, int $id` | `RedirectResponse` |
| `destroy($id)` | Delete movie | `int $id` | `JsonResponse` |
| `storeImageToPublic()` | Upload file | `$file, $folder, $title, $year` | `string` (path) |
| `deletePublicFile()` | Delete file | `string $relativePath` | `void` |
| `findOrCreateCountryFromJson()` | Get country ID | `string $countryName` | `?int` |
| `findOrCreateLanguageFromJson()` | Get language ID | `string $languageName` | `?int` |

### PeopleController Functions

| Function | Purpose | Parameters | Returns |
|----------|---------|------------|---------|
| `fetch()` | Get cast/directors | `Request $request` | `JsonResponse` |
| `add()` | Add person to movie | `Request $request` | `JsonResponse` |
| `remove()` | Remove person from movie | `Request $request` | `JsonResponse` |
| `search()` | Search people by name | `Request $request` | `JsonResponse` |

---

## Common Patterns

### 1. Form with File Upload

```php
// In Blade
<form action="{{ route('movies.store') }}" method="POST" enctype="multipart/form-data">
    @csrf
    <input type="file" name="poster_file" accept="image/*">
</form>

// In Controller
$validated = $request->validate([
    'poster_file' => 'required|image|max:5120',
]);

if ($request->hasFile('poster_file')) {
    $path = $this->storeImageToPublic($request->file('poster_file'), ...);
}
```

### 2. Pivot Table Sync

```php
// Sync (replaces all)
$movie->genres()->sync([1, 2, 3]);

// Attach (adds new)
$movie->genres()->attach([4]);

// Detach (removes)
$movie->genres()->detach([1]);
```

### 3. Database Transaction

```php
DB::beginTransaction();
try {
    // Operations
    DB::commit();
} catch (\Throwable $e) {
    DB::rollBack();
    // Handle error
}
```

### 4. AJAX Response

```php
// Success
return response()->json(['success' => true, 'data' => $data]);

// Error
return response()->json(['success' => false, 'message' => 'Error message']);
```

---

## Error Handling

### Validation Errors

- Displayed automatically via Laravel's `@error` directive in Blade
- Redirects back with input and errors

### File Upload Errors

- Caught in try-catch blocks
- Logged to `storage/logs/laravel.log`
- User sees generic error message

### Database Errors

- Wrapped in transactions
- Rolled back on failure
- Logged for debugging

---

## Security Considerations

1. **File Upload Validation:**
   - Type: `image` only
   - Size: 5MB (poster), 8MB (background)
   - Filename sanitization

2. **Authorization:**
   - Admin-only access (implied, not enforced in routes shown)
   - Should add `middleware('auth')` and role check

3. **SQL Injection:**
   - Protected via Eloquent ORM
   - Parameterized queries

4. **XSS Protection:**
   - Blade escaping: `{{ $variable }}`
   - Raw output: `{!! $variable !!}` (use carefully)

---

## Next Steps

After managing movies, users can:
- **View movies** on home page (`route('home')`)
- **View individual movie** (`route('movie.show', $id)`)
- **Rate and review** movies (see [ViewMovie Flow](./03-ViewMovie-Flow.md))

