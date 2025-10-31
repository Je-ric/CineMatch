<?php

namespace App\Http\Livewire;

use Livewire\Component;
use App\Models\RatingReview;
use Livewire\Attributes\On;
use App\Models\Movie;

class ReviewSection extends Component
{
    public $movie;
    public $userReview;
    public $avgRating = 0;
    public $totalReviews = 0;
    public $movieId = 0;

    public function mount(Movie $movie, $userReview = null, $avgRating = 0, $totalReviews = 0)
    {
        $this->movie = $movie;
        $this->movieId = (int) $movie->id;
        $this->userReview = $userReview;
        $this->avgRating = $avgRating;
        $this->totalReviews = $totalReviews;
    }

    #[On('reviewUpdated')]
    public function refreshReviews($updatedMovieId)
    {
        if ($this->movieId !== (int) $updatedMovieId) return;

        $movieId = $this->movieId;

        $this->userReview = RatingReview::where('movie_id', $movieId)
                            ->where('user_id', auth()->id())
                            ->first();

        $this->avgRating = RatingReview::where('movie_id', $movieId)->avg('rating') ?? 0;
        $this->totalReviews = RatingReview::where('movie_id', $movieId)->count();
    }

    public function render()
    {
        return view('livewire.review-section');
    }
}
