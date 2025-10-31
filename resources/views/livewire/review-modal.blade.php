<div>
    {{-- Modal --}}
    @if($isOpen)
        <div class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
            <div class="bg-slate-800 border border-slate-600 rounded-lg p-6 w-full max-w-lg relative">
                <button class="absolute top-2 right-2 text-white" wire:click="closeModal">✕</button>

                <h3 class="text-lg font-bold text-accent mb-4 text-center">
                    <i class="bx bx-star"></i> Review: {{ $movie->title }}
                </h3>

                <form wire:submit.prevent="submitReview" class="space-y-4">
                    {{-- Rating --}}
                    <div class="text-center">
                        <label class="label-text mb-2 block text-white">Rating (1-5)</label>
                        <div class="flex justify-center gap-1">
                            @for ($i = 1; $i <= 5; $i++)
                                <i class="bx {{ $i <= $rating ? 'bxs-star text-yellow-400' : 'bx-star text-gray-400' }} text-2xl cursor-pointer"
                                   wire:click="$set('rating', {{ $i }})"></i>
                            @endfor
                        </div>
                        @error('rating') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                    </div>

                    {{-- Review --}}
                    <div>
                        <label class="label-text text-white">Your Review</label>
                        <textarea wire:model.defer="review" class="textarea textarea-bordered w-full h-24 bg-slate-700 border-slate-600 text-white"
                                  placeholder="Share your thoughts..."></textarea>
                        @error('review') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                    </div>

                    <div class="flex justify-center gap-4">
                        <button type="button" class="btn border border-accent" wire:click="closeModal">Cancel</button>
                        <button type="submit"
                                class="btn btn-accent"
                                wire:loading.attr="disabled"
                                wire:target="submitReview">
                            <span wire:loading.remove wire:target="submitReview">{{ $userReview ? 'Update Review' : 'Submit Review' }}</span>
                            <span wire:loading wire:target="submitReview">Saving...</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif
</div>
