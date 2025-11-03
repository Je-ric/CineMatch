 <div id="rated" class="tab-content hidden mt-8">
     <h2 class="text-xl font-semibold text-accent mb-4 flex items-center gap-2">
         <i class="bx bx-star text-accent"></i>
         Rated Movies
         <span class="ml-2 bg-gray-700 text-white text-sm font-semibold px-2 py-1 rounded">
             {{ is_countable($rated) ? count($rated) : 0 }}
         </span>
     </h2>
     <div class="mb-6 space-y-2">
         @if (!empty($ratedGenres))
             <div class="flex flex-wrap gap-2">
                 @foreach ($ratedGenres as $genre)
                     <span class="badge badge-outline border-neutral-700 text-gray-300 mr-2 mb-2">
                         {{ $genre->name }}:
                         <span class="ml-1 font-semibold text-white">{{ $genre->cnt }}</span>
                     </span>
                 @endforeach
             </div>
         @endif
     </div>

     <section class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 gap-6">
         @forelse ($rated as $movie)
             <x-movie-card :movie="(object) $movie" />
         @empty
             <div class="text-center py-10 text-gray-400 col-span-full">
                 <i class="bx bx-star text-5xl mb-2"></i>
                 <div class="text-lg">You haven't rated any movies yet.</div>
             </div>
         @endforelse
     </section>
 </div>
