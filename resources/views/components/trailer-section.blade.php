@props(['movie'])

@if(!empty($movie->trailer_url))
    <div id="trailer-section" class="bg-secondary-bg/90 backdrop-blur-sm border border-border-color rounded-lg p-6">
        <h2 class="text-2xl font-bold mb-6 text-accent flex items-center gap-3">
            <i class='bx bx-play-circle'></i> Trailer
        </h2>
        <div class="aspect-video rounded-lg overflow-hidden bg-base-300 max-w-lg mx-auto">
           @if(isset($movie->youtube_id))
                <iframe class="w-full h-full"
                        src="https://www.youtube.com/embed/{{ $movie->youtube_id }}"
                        allowfullscreen></iframe>
            @else
                <div class="flex items-center justify-center h-full text-gray-400">
                    <p>Trailer not available</p>
                </div>
            @endif
        </div>
    </div>
@endif
