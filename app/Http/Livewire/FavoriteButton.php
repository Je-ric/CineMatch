<?php

namespace App\Http\Livewire;

use Livewire\Component;
use Illuminate\Support\Facades\Auth;
use App\Models\Movie;

class FavoriteButton extends Component
{
    public $movie;
    public $isFavorited = false;
    public $favoriteCount = 0;

    // this runs when component is loaded
    // movie object, determine if user has favorited it and how many counts
    public function mount(Movie $movie)
    {
        $this->movie = $movie;
        $user = Auth::user();
        if ($user) {
            $this->isFavorited = $user->favorites()->wherePivot('movie_id', $movie->id)->exists();
        }
        $this->favoriteCount = $movie->favoritedBy()->count();
    }

    // wala namang binago, yung checker nasa taas yung isFavorited
    // dito if already favorited, detach; else attach
    public function toggleFavorite()
    {
        $user = Auth::user();
        if (!$user) return redirect()->route('login');
        if ($user->role === 'admin') return;

        if ($this->isFavorited) {
            $user->favorites()->detach($this->movie->id);
            $this->isFavorited = false;
        } else {
            $user->favorites()->attach($this->movie->id);
            $this->isFavorited = true;
        }

        $this->favoriteCount = $this->movie->favoritedBy()->count();
    }

    // livewire re-render without page reload
    // livewire uses AJAX (xhr) to communicate php and browser
    // and ito yung nagrerender ng HTML parts, in this case yung button
    public function render()
    {
        return view('livewire.favorite-button');
    }
}






// wire: connects front-end  (html) to back-end (php)
// (livewire sends request to laravel)
// server runs toggleFavorite()
// return yung updated HTML for the component
// livewire re-renders only the component's HTML (swapping old with new)

