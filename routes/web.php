<?php

use App\Http\Controllers\Socialite\ProviderCallbackController;
use App\Http\Controllers\Socialite\ProviderRedirectController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\MovieController;
use App\Http\Controllers\PeopleController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use App\Models\Movie;
use App\Models\Genre;

Route::get('/', [MovieController::class, 'index'])
    ->name('home');

// Route::get('/', function () {
//     return view('home', ['user' => Auth::user()]);
// })->name('home');


//hindi ko sure if pano yung gagawin here sa login HAHAHAHAAAHAH, may error after login if hindi third party ginamit

Route::get('/auth', function () {
    return view('auth');
})->name('auth');

// Redirect to provider (Google/Facebook)
Route::get('/auth/{provider}/redirect', ProviderRedirectController::class)->name('auth.redirect');

// Callback from provider
Route::get('/auth/{provider}/callback', ProviderCallbackController::class)->name('auth.callback');


Route::post('/register', [AuthController::class, 'register'])
    ->name('register');

Route::post('/login', [AuthController::class, 'login'])
    ->name('login');

Route::post('/logout', [AuthController::class, 'logout'])
    ->name('logout');

Route::get('/login', [AuthController::class, 'login'])
    ->name('login');


Route::get('/viewMovie/{id}', [MovieController::class, 'show'])->name('movie.show');

// Manage Movie (Blade)
Route::get('/movies/manage', function () {
    $allGenres = Genre::orderBy('name')->get();
    return view('manageMovie', [
        'editing' => false,
        'movie' => null,
        'allGenres' => $allGenres,
    ]);
})->name('movies.manage.create');

Route::get('/movies/manage/{id}', function ($id) {
    $movie = Movie::with(['genres', 'countries', 'languages'])->findOrFail($id);
    $allGenres = Genre::orderBy('name')->get();
    return view('manageMovie', [
        'editing' => true,
        'movie' => $movie,
        'allGenres' => $allGenres,
    ]);
})->name('movies.manage.edit');

// Movies CRUD
Route::post('/movies', [MovieController::class, 'store'])->name('movies.store');
Route::post('/movies/{id}', [MovieController::class, 'update'])->name('movies.update');
Route::delete('/movies/{id}', [MovieController::class, 'destroy'])->name('movies.destroy');

// People endpoints (AJAX)
Route::post('/people/fetch', [PeopleController::class, 'fetch'])->name('people.fetch');
Route::post('/people/add', [PeopleController::class, 'add'])->name('people.add');
Route::post('/people/remove', [PeopleController::class, 'remove'])->name('people.remove');
Route::post('/people/search', [PeopleController::class, 'search'])->name('people.search');

Route::get('/profile', function () {
    return view('pages.profile');
})->name('profile');
