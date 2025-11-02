# Review Flow Sequence

1. **User clicks "Leave a Review"**  
   → `$dispatch('openReviewModal')` is fired.

2. **`ReviewModal` receives `openReviewModal`**  
   → `$isOpen = true` → modal appears on the UI.

3. **User submits rating & review**  
   → `submitReview()` is triggered:
   - Saves the review to the database.
   - Closes the modal (`$isOpen = false`).
   - Dispatches `reviewUpdated` event to `ReviewSection` & `ReviewsList`.

4. **Other components listen for `reviewUpdated`** via `#[On('reviewUpdated')]`  
   → Refresh their data (user review, average rating, total reviews, list of reviews).

5. **UI updates dynamically**  
   → Components re-render automatically without a full page reload.

---

# Favorites Flow Sequence

1. **User clicks the Favorite Button**  
   → Button triggers `wire:click="toggleFavorite"` in the `FavoriteButton` Livewire component.

2. **`FavoriteButton::toggleFavorite()` runs**  
   - Checks if the user is logged in → redirects to login if not.  
   - Checks if user role is not `admin` → admins cannot favorite.  
   - If already favorited → detaches the movie from user's favorites.  
   - If not yet favorited → attaches the movie to user's favorites.

3. **Update local component state**  
   - `$isFavorited` is updated to reflect the current status.  
   - `$favoriteCount` is recalculated using `$movie->favoritedBy()->count()`.

4. **Livewire re-renders component**  
   → Only the Favorite button HTML is updated (AJAX), page does not reload.

5. **UI shows updated state**  
   - Button changes appearance (`btn-accent` vs `btn-outline btn-accent`).  
   - Favorite count updates dynamically next to
