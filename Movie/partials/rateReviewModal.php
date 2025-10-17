<dialog id="review_modal" class="modal">
    <div class="modal-box bg-secondary-bg border border-border-color flex flex-col items-center">
        <form method="dialog" class="self-end">
            <button class="btn btn-sm btn-circle btn-ghost">✕</button>
        </form>

        <h3 class="font-bold text-lg mb-4 text-accent text-center flex items-center gap-2">
            <i class='bx bx-star'></i> Review: <?= htmlspecialchars($m['title']) ?>
        </h3>

        <form id="review-form" action="../db/rateRequests.php" method="POST" class="w-full space-y-4">
            <input type="hidden" name="movie_id" value="<?= (int)$m['id'] ?>">

            <div class="form-control">
                <label class="label text-center">
                    <span class="label-text">Rating (1-5 stars)</span>
                </label>
                <div class="rating rating-lg justify-center">
                    <input type="radio" name="rating" value="1" required class="mask mask-star bg-gray-200 hover:bg-yellow-500 checked:bg-warning" aria-label="1 star" />
                    <input type="radio" name="rating" value="2" class="mask mask-star bg-gray-200 hover:bg-yellow-500 checked:bg-warning" aria-label="2 star" />
                    <input type="radio" name="rating" value="3" class="mask mask-star bg-gray-200 hover:bg-yellow-500 checked:bg-warning" aria-label="3 star" />
                    <input type="radio" name="rating" value="4" class="mask mask-star bg-gray-200 hover:bg-yellow-500 checked:bg-warning" aria-label="4 star" />
                    <input type="radio" name="rating" value="5" class="mask mask-star bg-gray-200 hover:bg-yellow-500 checked:bg-warning" aria-label="5 star" />
                </div>
                <p class="text-center mt-2 text-sm text-text-secondary">Selected: <span id="rating-selected" class="font-semibold">0</span>/5</p>
            </div>

            <div class="form-control">
                <label class="label">
                    <span class="label-text">Your Review</span>
                </label>
                <textarea name="review" class="textarea textarea-bordered bg-card-bg border-border-color h-24" placeholder="Share your thoughts about this movie..." required></textarea>
            </div>

            <div class="modal-action justify-center gap-4">
                <button type="button" class="btn btn-outline" onclick="review_modal.close()">Cancel</button>
                <button type="submit" id="submit-review" class="btn btn-accent">Submit Review</button>
            </div>
        </form>
    </div>
</dialog>

<script>
$(function(){

    // to all user reviews
    function refreshAverageRating(value) {
        if (value == null) return;
        const formatted = parseFloat(value).toFixed(1); // 3.456 => 3.5
        $('#average-rating').text(formatted); // getting the avg rating (viewMovie)

        // determine kung ilan ang filled and empty
        const $iconContainer = $('#average-stars');
        if ($iconContainer.length) {
            const full = Math.floor(value); // kung 3 ang full
            const empty = 5 - full;  // 5-3 = 2 ang empty
            let icons = '';
            for (let i = 0; i < full; i++) icons += "<i class='bx bxs-star star'></i>";
            for (let i = 0; i < empty; i++) icons += "<i class='bx bx-star star empty'></i>";
            $iconContainer.html(icons); // then return/insert the icons to the #average-stars
        }

        $('#no-ratings-placeholder').hide();
    }

    function refreshReviewCount(count) {
        const total = parseInt(count, 10) || 0;
        $('#total-reviews').text(total);
        $('#reviews-count-number').text(total);
        $('#no-reviews-placeholder').hide();
    }

    // just your own
    function insertUserReview(rating, comment) {
        const $section = $('#user-review-section');
        if (!$section.length) return;

        // build star icons
        let icons = '';
        for (let i = 1; i <= 5; i++) {
            icons += `<i class='bx ${i <= rating ? "bxs-star text-yellow-400" : "bx-star empty"} star'></i>`;
        }

        $section.html(`
            <div class="bg-accent/10 border border-accent/30 rounded-lg p-4 mb-6">
                <div class="flex justify-between items-start">
                    <div class="flex-1">
                        <h3 class="font-bold text-accent mb-2">Your Review</h3>
                        <p class="text-text-secondary">${comment}</p>
                    </div>
                    <div class="flex items-center gap-2 ml-4">
                        <div class="star-rating">${icons}</div>
                        <span class="text-accent font-semibold">${rating}</span>
                    </div>
                </div>
            </div>
        `);
    }

    function resetAndCloseDialog(form) {
        form[0].reset();
        $('#rating-selected').text('0'); // ofcourse, default 0
        if (window.review_modal?.close) {
            review_modal.close();
        } else {
            const dialogEl = document.getElementById('review_modal');
            if (dialogEl?.close) dialogEl.close();
        }
    }

    // live update rating counter, kapag kiniclick yung star
    $(document).on('change', '#review-form input[name="rating"]', function() {
        // find/determin which radio button is currently checked in the modal review form
        const val = $('input[name="rating"]:checked', '#review-form').val() || '0';
        $('#rating-selected').text(val); // dinidisplay yung actual count ng star 
    });

    $('#review-form').on('submit', function(e) {
        e.preventDefault();

        const $form = $(this); //reference sa form
        const $btn = $('#submit-review');
        $btn.prop('disabled', true).text('Submitting...');

        $.ajax({
            url: $form.attr('action'), // "../db/rateRequests.php" na kinukuha nalang sa form
            method: 'POST',
            data: $form.serialize(),  // all (movie_id, rating, review) 
            dataType: 'json'
        })
        .done(function(response) {
            if (!response?.success) {
                console.warn('Error submitting review:', response?.message || 'Unknown error');
                return;
            }

            // update or like rerender review counter and and avg
            if (response.average) {
                refreshAverageRating(response.average.avg);
                if (typeof response.average.total !== 'undefined') {
                    refreshReviewCount(response.average.total);
                }
            }
            // find and get the value of star radio count, then convert string to number
            const ratingVal = parseInt($('input[name="rating"]:checked', $form).val(), 10) || 0; // fallback incase walang selected, instead NaN is 0
            const commentText = $form.find('textarea[name="review"]').val(); // search inside form what the user type
            insertUserReview(ratingVal, commentText); // display in the user review section

            resetAndCloseDialog($form);
        })
        .fail(function(xhr) {
            console.error('Request failed:', xhr.status, xhr.statusText, xhr.responseText);
        })
        .always(function() {
            $btn.prop('disabled', false).text('Submit Review');
        });
    });
});
</script>
