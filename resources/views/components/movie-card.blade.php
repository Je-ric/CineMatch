@props(['movie', 'isAdmin' => false])

@php
    $poster = $movie->poster_url ?? '';
    if ($poster && !preg_match('/^https?:\/\//', $poster)) {
        $poster = asset($poster);
    }
@endphp

<div
    class="group rounded-xl overflow-hidden bg-neutral-900 border border-neutral-800 hover:border-green-500/70 transition transform hover:-translate-y-2 hover:shadow-xl hover:shadow-green-500/20 flex flex-col">

    <div class="relative">
        <a href="{{ url('viewMovie', $movie->id) }}">
            <img
                src="{{ $movie->poster_url
                    ? (preg_match('/^https?:\/\//', $movie->poster_url)
                        ? $movie->poster_url
                        : asset($movie->poster_url))
                    : 'https://placehold.co/300x450?text=No+Poster'
                }}"
                alt="{{ $movie->title }}"
                class="w-full h-80 object-cover transition-transform duration-300 group-hover:scale-105"
            >
        </a>

        <div class="absolute top-2 right-2">
            <span class="bg-green-600/90 text-white font-semibold text-xs px-2 py-1 rounded-md flex items-center gap-1">
                <i class='bx bxs-star text-yellow-300'></i>
                {{ $movie->avg_rating !== null ? number_format($movie->avg_rating, 1) : 'N/A' }}
            </span>
        </div>
    </div>

    <div class="p-4 flex flex-col flex-grow">
        <h5 class="font-semibold text-base mb-1 text-white leading-tight">
            {{ $movie->title }}
            @if(!empty($movie->release_year))
                <small class="text-gray-400 font-normal">({{ $movie->release_year }})</small>
            @endif
        </h5>

        <div class="text-gray-400 text-xs flex flex-wrap gap-2 mb-3">
            <span class="px-2 py-0.5 rounded bg-neutral-800/70 border border-neutral-700">
                {{ $movie->country_name ?? '' }}
            </span>
            <span class="px-2 py-0.5 rounded bg-neutral-800/70 border border-neutral-700">
                {{ $movie->language_name ?? '' }}
            </span>
        </div>

        @if ($isAdmin)
            <div class="mt-auto pt-2">
                <div class="flex gap-2">
                    <a href="{{ route('movies.manage.edit', ['id' => $movie->id]) }}"
                       class="btn btn-xs btn-outline btn-info flex-1">
                        <i class='bx bx-edit'></i> Edit
                    </a>

                    <form method="POST" action="{{ route('movies.destroy', ['id' => $movie->id]) }}" class="flex-1">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-xs btn-outline btn-error w-full"
                            onclick="return confirm('Delete this movie?')">
                            <i class='bx bx-trash'></i> Delete
                        </button>
                    </form>
                </div>
            </div>
        @endif
    </div>
</div>
