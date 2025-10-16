<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('movie.index');
})->name('home');

Route::get('/login', function () {
    return view('movie.login-register');
})->name('login');

Route::get('/movie/{id}', function ($id) {
    return view('movie.view-movie', ['movieId' => $id]);
})->name('movie.view');

Route::get('/movie/add', function () {
    return view('movie.manage-movie', ['editing' => false]);
})->name('movie.add');

Route::get('/movie/edit/{id}', function ($id) {
    return view('movie.manage-movie', ['editing' => true, 'movieId' => $id]);
})->name('movie.edit');

Route::get('/profile', function () {
    return view('movie.profile');
})->name('profile');
