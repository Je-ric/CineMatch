<?php

namespace App\View\Components;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class MovieCard extends Component
{
    public $movie;
    public $isAdmin;
    /**
     * Create a new component instance.
     */
    public function __construct($movie, $isAdmin = false)
    {
        $this->movie = $movie;
        $this->isAdmin = $isAdmin;
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View|Closure|string
    {
        return view('components.movie-card');
    }
}
