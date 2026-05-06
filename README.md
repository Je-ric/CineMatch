# CineMatch — Movie Recommendation System

CineMatch is a movie discovery and recommendation platform built with **Laravel**, **Livewire**, and **Tailwind CSS**. Users can browse movies, manage favorites, leave ratings and reviews, and receive personalized recommendations based on their activity.

---

## What It Does

- Browse a movie database with full details (genres, cast, directors, ratings, country, language)
- Add movies to a personal favorites list
- Rate and review movies with real-time UI updates via Livewire
- Get personalized movie recommendations based on favorite and rated genres
- Manage movie cast and directors dynamically
- Admin panel for managing the movie database
- Social login via **Google** and **Facebook** (Laravel Socialite)

---

## Tech Stack

| Layer | Technology |
|---|---|
| Backend | Laravel 11 |
| Frontend | Blade Templates, Livewire 3, Tailwind CSS |
| Auth | Laravel Jetstream + Fortify + Socialite |
| Database | SQLite (local) / MySQL (production) |
| Testing | Pest PHP |

---

## Roles

| Role | Access |
|---|---|
| `user` | Browse movies, favorites, ratings, reviews, profile, recommendations |
| `admin` | All user access + manage movies (add, edit, delete, manage cast) |

---

## Getting Started

### Requirements
- PHP 8.2+
- Composer
- Node.js + npm

### Installation

```bash
git clone <repo-url>
cd CineMatch

composer install
npm install

cp .env.example .env
php artisan key:generate

# Configure your database in .env, then:
php artisan migrate
php artisan db:seed

php artisan storage:link
npm run dev
php artisan serve
```

### Seeded Accounts

| Role | Email | Password |
|---|---|---|
| Admin | *(see AdminUserSeeder)* | *(see AdminUserSeeder)* |

---

## Environment Variables

```env
APP_URL=http://localhost

DB_CONNECTION=sqlite

GOOGLE_CLIENT_ID=
GOOGLE_CLIENT_SECRET=
GOOGLE_REDIRECT_URI=http://localhost:8000/auth/google/callback

FACEBOOK_CLIENT_ID=
FACEBOOK_CLIENT_SECRET=
FACEBOOK_REDIRECT_URI=http://localhost:8000/auth/facebook/callback
```

---

## Project Structure

```
app/
├── Http/
│   ├── Controllers/
│   │   ├── AuthController.php          # Login, register, logout, social auth
│   │   ├── MovieController.php         # Movie detail page
│   │   ├── ProfileController.php       # Profile page with favorites, ratings, recommendations
│   │   └── ManageMovieController.php   # Admin movie management
│   └── Livewire/
│       ├── FavoriteButton.php          # Toggle favorite (real-time)
│       ├── ReviewSection.php           # Review modal trigger
│       ├── ReviewModal.php             # Submit/update review
│       ├── ReviewsList.php             # Display reviews list
│       └── MoviePeople.php             # Add/remove directors and cast
├── Models/
│   ├── User.php
│   ├── Movie.php
│   ├── Genre.php
│   ├── Country.php
│   ├── Language.php
│   ├── MoviePerson.php
│   ├── RatingReview.php
│   └── UserFavorite.php
├── Helpers/
│   └── MovieHelper.php                 # Centralized movie data logic
resources/views/
├── home.blade.php                      # Movie browsing home page
├── viewMovie.blade.php                 # Single movie detail page
├── profile.blade.php                   # User profile (favorites, rated, recommendations)
├── manageMovie.blade.php               # Admin movie management
├── auth.blade.php                      # Login / register page
├── livewire/                           # Livewire component views
├── components/                         # Blade components (movie-card, etc.)
└── includes/                           # Shared partials
documentation/
├── md/
│   ├── Authentication.md               # Auth and social login flow
│   ├── Models.md                       # Model relationships and data flows
│   ├── Livewire.md                     # How Livewire works in this project
│   ├── MoviePeople.md                  # Directors and cast management
│   ├── Profile.md                      # Profile tabs and recommendation logic
│   ├── Recommendation.md               # Full recommendation system flow
│   └── Others.md                       # Misc notes
├── how-it-works.md                     # Complete system flow documentation
└── future-works.md                     # Roadmap, improvements, known bugs
```

---

## Documentation

| File | Description |
|---|---|
| `documentation/how-it-works.md` | Full system flow — auth, movies, favorites, reviews, recommendations |
| `documentation/future-works.md` | Roadmap, improvements by priority, known bugs |
| `documentation/md/Authentication.md` | Auth and social login detailed flow |
| `documentation/md/Models.md` | Model relationships and Livewire event flows |
| `documentation/md/Livewire.md` | How Livewire works in this project |
| `documentation/md/MoviePeople.md` | Directors and cast management component |
| `documentation/md/Profile.md` | Profile page tabs and recommendation logic |
| `documentation/md/Recommendation.md` | Full recommendation system with MovieHelper |

---

## Key Design Decisions

**Why Livewire?**
Livewire allows reactive UI (favorites, reviews, cast management) without writing custom JavaScript. Components communicate via events (`$dispatch` / `#[On]`).

**Why MovieHelper?**
All movie data logic is centralized in `MovieHelper` to avoid duplication across controllers and keep controllers thin.

**Why separate favorites and ratings for recommendations?**
Favorites and ratings represent different levels of user intent. Recommendations are built from each source separately and displayed in distinct shelves.

---

## License

For educational and portfolio use. All rights reserved.
