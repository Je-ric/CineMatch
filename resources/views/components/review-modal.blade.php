@props(['movie', 'userReview' => null, 'isLoggedIn' => null])

<dialog id="review-modal-{{ $movie->id }}" class="modal">
    <div class="modal-box bg-slate-800 border border-slate-600">
        {{-- Close button --}}
        <form method="dialog" class="self-end">
            <button class="btn btn-sm btn-circle btn-ghost">✕</button>
        </form>

        <h3 class="font-bold text-lg mb-4 text-accent text-center">
            <i class='bx bx-star'></i> Review: {{ $movie->title }}
        </h3>

        <form id="review-form-{{ $movie->id }}" class="w-full space-y-4">
            @csrf
            <input type="hidden" name="movie_id" value="{{ $movie->id }}">

            {{-- Rating --}}
            <div class="form-control">
                <label class="label text-center">
                    <span class="label-text">Rating (1-5)</span>
                </label>
                <div class="rating rating-lg justify-center">
                    @for($i = 1; $i <= 5; $i++)
                        <input
                            type="radio"
                            name="rating"
                            value="{{ $i }}"
                            {{ $userReview && $userReview->rating == $i ? 'checked' : '' }}
                            class="mask mask-star bg-gray-200"
                        />
                    @endfor
                </div>
                <p class="text-center mt-2 text-sm text-gray-400">
                    Selected: <span id="rating-selected-{{ $movie->id }}" class="font-semibold">{{ $userReview ? $userReview->rating : '0' }}</span>/5
                </p>
            </div>

            {{-- Review text --}}
            <div class="form-control">
                <label class="label">
                    <span class="label-text">Your Review</span>
                </label>
                <textarea
                    name="review"
                    class="textarea textarea-bordered bg-slate-700 border-slate-600 h-24"
                    placeholder="Share your thoughts..."
                >{{ $userReview ? $userReview->review : '' }}</textarea>
            </div>

            <div class="modal-action justify-center gap-4">
                <button type="button" class="btn btn-outline" onclick="document.getElementById('review-modal-{{ $movie->id }}').close()">
                    Cancel
                </button>
                <button type="submit" id="submit-review-{{ $movie->id }}" class="btn btn-accent">
                    {{ $userReview ? 'Update Review' : 'Submit Review' }}
                </button>
            </div>
        </form>
    </div>
</dialog>

@if($isLoggedIn)
<script>
document.addEventListener('DOMContentLoaded', function() {
    const modal = document.getElementById('review-modal-{{ $movie->id }}');
    const form = document.getElementById('review-form-{{ $movie->id }}');
    const openBtn = document.getElementById('open-review-modal-{{ $movie->id }}');
    const ratingSelected = document.getElementById('rating-selected-{{ $movie->id }}');
    const submitBtn = document.getElementById('submit-review-{{ $movie->id }}');

    // Open modal
    openBtn.addEventListener('click', function() {
        modal.showModal();
    });

    // Update rating display
    form.querySelectorAll('input[name="rating"]').forEach(input => {
        input.addEventListener('change', function() {
            ratingSelected.textContent = this.value;
        });
    });

    // Submit review
    form.addEventListener('submit', function(e) {
        e.preventDefault();

        const formData = new FormData(this);
        submitBtn.disabled = true;
        submitBtn.textContent = 'Submitting...';

        fetch('{{ route("reviews.store") }}', {
            method: 'POST',
            body: formData,
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Update rating summary
                const avgRating = data.average.avg;
                const totalReviews = data.average.total;

                // Update stars
                const starsContainer = document.getElementById('average-stars-{{ $movie->id }}');
                starsContainer.innerHTML = '';
                for (let i = 1; i <= 5; i++) {
                    const star = document.createElement('i');
                    star.className = i <= Math.floor(avgRating) ? 'bx bxs-star text-yellow-400 text-lg' : 'bx bx-star text-gray-400 text-lg';
                    starsContainer.appendChild(star);
                }

                // Update numbers
                document.getElementById('average-rating-{{ $movie->id }}').textContent = avgRating.toFixed(1);
                document.getElementById('total-reviews-{{ $movie->id }}').textContent = totalReviews;

                // Update button to show "Already Reviewed"
                openBtn.innerHTML = '<i class="bx bx-check"></i> Already Reviewed';
                openBtn.className = 'btn btn-outline btn-disabled';
                openBtn.disabled = true;

                // Update main view rating summary
                const mainAvgStars = document.getElementById('average-stars');
                const mainAvgRating = document.getElementById('average-rating');
                const mainTotalReviews = document.getElementById('total-reviews');

                if (mainAvgStars && mainAvgRating && mainTotalReviews) {
                    // Update stars
                    mainAvgStars.innerHTML = '';
                    for (let i = 0; i < Math.floor(avgRating); i++) {
                        mainAvgStars.innerHTML += '<i class="bx bxs-star star text-yellow-400"></i>';
                    }
                    for (let i = 0; i < (5 - Math.floor(avgRating)); i++) {
                        mainAvgStars.innerHTML += '<i class="bx bx-star star empty"></i>';
                    }

                    // Update numbers
                    mainAvgRating.textContent = avgRating.toFixed(1);
                    mainTotalReviews.textContent = totalReviews;
                }

                // Close modal and reset form
                modal.close();
                form.reset();
                ratingSelected.textContent = '0';

                console.log('Review submitted successfully');
            } else {
                alert('Failed to submit review');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Failed to submit review');
        })
        .finally(() => {
            submitBtn.disabled = false;
            submitBtn.textContent = '{{ $userReview ? "Update Review" : "Submit Review" }}';
        });
    });
});
</script>
@endif
