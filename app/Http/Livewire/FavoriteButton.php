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

    public function mount(Movie $movie)
    {
        $this->movie = $movie;
        $user = Auth::user();
        if ($user) {
            $this->isFavorited = $user->favorites()->wherePivot('movie_id', $movie->id)->exists();
        }
        $this->favoriteCount = $movie->favoritedBy()->count();
    }

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

    public function render()
    {
        return view('livewire.favorite-button');
    }
}
