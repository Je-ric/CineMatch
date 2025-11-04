<?php

use App\Http\Controllers\Socialite\ProviderCallbackController;
use App\Http\Controllers\Socialite\ProviderRedirectController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\MovieController;
use App\Http\Controllers\PeopleController;
use App\Http\Controllers\RecommendController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\RateReviewController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::get('/', [MovieController::class, 'index'])->name('home');

Route::get('/auth', function () {
    return view('auth');
})->name('auth');


Route::get('/login', function () {
    return redirect()->route('auth');
})->name('login.redirect');


Route::get('/auth/google/redirect', [AuthController::class, 'redirectGoogle'])->name('auth.google.redirect');
Route::get('/auth/google/callback', [AuthController::class, 'handleGoogleCallback'])->name('auth.google.callback');
Route::get('/auth/facebook/redirect', [AuthController::class, 'redirectFacebook'])->name('auth.facebook.redirect');
Route::get('/auth/facebook/callback', [AuthController::class, 'handleFacebookCallback'])->name('auth.facebook.callback');
Route::get('/auth/github/redirect', [AuthController::class, 'redirectGithub'])->name('auth.github.redirect');
Route::get('/auth/github/callback', [AuthController::class, 'handleGithubCallback'])->name('auth.github.callback');

Route::post('/register', [AuthController::class, 'register'])->name('auth.register');
Route::post('/login', [AuthController::class, 'login'])->name('login');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

Route::get('/viewMovie/{id}', [MovieController::class, 'show'])->name('movie.show');

Route::get('/movies/manage', [MovieController::class, 'create'])->name('movies.manage.create');
Route::get('/movies/manage/{id}', [MovieController::class, 'edit'])->name('movies.manage.edit');

Route::post('/movies', [MovieController::class, 'store'])->name('movies.store');
Route::put('/movies/{id}', [MovieController::class, 'update'])->name('movies.update');
Route::delete('/movies/{id}', [MovieController::class, 'destroy'])->name('movies.destroy');

Route::post('/people/fetch', [PeopleController::class, 'fetch'])->name('people.fetch');
Route::post('/people/add',   [PeopleController::class, 'add'])->name('people.add');
Route::post('/people/remove',[PeopleController::class, 'remove'])->name('people.remove');
Route::post('/people/search',[PeopleController::class, 'search'])->name('people.search');

Route::get('/profile', [ProfileController::class, 'show'])->name('profile')->middleware('auth');

Route::get('/force-logout', function () {
    Auth::logout();
    session()->invalidate();
    session()->regenerateToken();
    return redirect()->route('home')->with('success', 'Logged out successfully.');
});
