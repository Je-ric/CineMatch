<?php

namespace App\Http\Livewire;

use Livewire\Component;
use App\Models\RatingReview;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Livewire\Attributes\On;
use App\Http\Livewire\ReviewSection;
use App\Http\Livewire\ReviewsList;

class ReviewModal extends Component
{
    public $movie;
    public $rating = 0;
    public $review = '';
    public $userReview;

    public $isOpen = false;
    protected $rules = [
        'rating' => 'required|integer|min:1|max:5',
        'review' => 'nullable|string|max:2000',
    ];

    public function mount($movie)
    {
        $this->movie = $movie;

        $this->userReview = RatingReview::where('user_id', Auth::id())
            ->where('movie_id', $movie->id)
            ->first();

        if ($this->userReview) {
            $this->rating = $this->userReview->rating;
            $this->review = $this->userReview->review;
        }
    }

    #[On('openReviewModal')]
    public function openModal()
    {
        $this->isOpen = true;
    }

    public function closeModal()
    {
        $this->isOpen = false;
    }

    public function submitReview()
    {
        $this->validate();

        try {
            DB::beginTransaction();

            $record = RatingReview::updateOrCreate(
                ['user_id' => Auth::id(), 'movie_id' => $this->movie->id],
                ['rating' => $this->rating, 'review' => $this->review]
            );

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Review submit failed: '.$e->getMessage(), ['exception' => $e]);
            $this->dispatch('toast', type: 'error', message: 'Could not save review.');
            return;
        }

        $this->userReview = $record;
        $this->isOpen = false;

        // Livewire v3: dispatch events to specific components
        $this->dispatch('reviewUpdated', $this->movie->id)->to(ReviewSection::class);
        $this->dispatch('reviewUpdated', $this->movie->id)->to(ReviewsList::class);

        $this->dispatch('toast', type: 'success', message: 'Review saved.');
    }

    public function render()
    {
        return view('livewire.review-modal');
    }
}
