<?php

namespace App\Http\Livewire;

use Livewire\Component;
use App\Models\RatingReview;
use Livewire\Attributes\On;

class ReviewsList extends Component
{
    public $movie;
    public $reviews;
    public $realReviewCount = 0;

    public function mount($movie, $reviews, $realReviewCount = 0)
    {
        $this->movie = $movie;
        $this->reviews = $reviews;
        $this->realReviewCount = $realReviewCount;
    }

    #[On('reviewUpdated')] // Listen for the 'reviewUpdated' event sa ReviewModal.php::submitReview()
    public function refreshReviews($movieId)
    {
        if ($this->movie->id != $movieId) return;

        $this->reviews = RatingReview::where('movie_id', $movieId)->latest()->get();
        $this->realReviewCount = $this->reviews->count();
    }

    public function render()
    {
        return view('livewire.reviews-list');
    }
}
