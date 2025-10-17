<?php

use App\Http\Controllers\Socialite\ProviderCallbackController;
use App\Http\Controllers\Socialite\ProviderRedirectController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\MovieController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

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

Route::get('/movie/add', function () {
    return view('pages.manage-movie', ['editing' => false]);
})->name('movie.add');

Route::get('/movie/edit/{id}', function ($id) {
    return view('pages.manage-movie', ['editing' => true, 'movieId' => $id]);
})->name('movie.edit');

Route::get('/profile', function () {
    return view('pages.profile');
})->name('profile');
