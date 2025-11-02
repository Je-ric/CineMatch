<div>
    @if($isOpen)
        <div class="fixed inset-0 flex items-center justify-center z-[9999]">
            <div class="absolute inset-0 bg-black/70 backdrop-blur-sm transition-opacity animate-fade-in"></div>

            <div class="relative bg-slate-800/95 border border-accent/30 rounded-2xl shadow-[0_0_25px_rgba(0,0,0,0.5)]
                        p-6 w-full max-w-lg transform transition-all duration-300 scale-95 animate-modal-pop">

                <button class="absolute top-3 right-3 text-gray-300 hover:text-white transition" wire:click="closeModal">
                    <i class="bx bx-x text-2xl"></i>
                </button>

                <h3 class="text-2xl font-bold text-accent mb-6 text-center">
                    <i class="bx bx-star"></i> Review:
                    <span class="text-white">
                        {{ \Illuminate\Support\Str::limit($movie->title, 35, '...') }}
                    </span>
                </h3>

                {{-- ReviewModal.php::submitReview() --}}
                <form wire:submit.prevent="submitReview" class="space-y-6">
                    {{-- Rating --}}
                    <div class="text-center">
                        <label class="block text-gray-300 mb-2 font-medium">Rating (1–5)</label>
                        <div class="flex justify-center gap-2">
                            @for ($i = 1; $i <= 5; $i++)
                                <i class="bx {{ $i <= $rating ? 'bxs-star text-yellow-400' : 'bx-star text-gray-500 hover:text-yellow-300' }}
                                            text-3xl cursor-pointer transition-colors duration-200"
                                    wire:click="$set('rating', {{ $i }})"></i>
                            @endfor
                        </div>
                        @error('rating') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                    </div>

                    {{-- Review --}}
                    <div>
                        <label class="block text-gray-300 mb-2 font-medium">Your Review</label>
                        <textarea wire:model.defer="review"
                                    placeholder="Share your thoughts..."
                                    class="w-full h-28 bg-slate-700/70 border border-slate-600 rounded-lg px-3 py-2 text-white
                                            focus:outline-none focus:ring-2 focus:ring-accent/60 resize-none placeholder-gray-400"></textarea>
                        @error('review') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                    </div>

                    {{-- Buttons --}}
                    <div class="flex justify-center gap-4 mt-4">
                        <button type="button"
                                class="btn border border-accent text-accent hover:bg-accent/20 transition"
                                wire:click="closeModal">
                            Cancel
                        </button>

                        <button type="submit"
                                class="btn btn-accent shadow-lg hover:shadow-accent/40 transition-all"
                                wire:loading.attr="disabled"
                                wire:target="submitReview">
                            <span wire:loading.remove wire:target="submitReview">
                                {{ $userReview ? 'Update Review' : 'Submit Review' }}
                            </span>
                            <span wire:loading wire:target="submitReview">Saving...</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif
</div>

@push('styles')
<style>
    @keyframes fade-in {
        from { opacity: 0; }
        to { opacity: 1; }
    }

    @keyframes modal-pop {
        from { opacity: 0; transform: scale(0.95) translateY(10px); }
        to { opacity: 1; transform: scale(1) translateY(0); }
    }

    .animate-fade-in {
        animation: fade-in 0.3s ease forwards;
    }

    .animate-modal-pop {
        animation: modal-pop 0.3s ease forwards;
    }
</style>
@endpush
