@extends('layouts.app')

@section('title', $editing ? 'Edit Movie' : 'Add Movie')

@section('content')
@php
    $actionText = $editing ? "Update Movie" : "Add Movie";
    $movieData = $editing ? [
        'id' => 1,
        'title' => 'Inception',
        'description' => 'A thief who steals corporate secrets...',
        'release_year' => 2010,
        'poster_url' => 'uploads/posters/inception_2010.jpg',
        'background_url' => 'uploads/backgrounds/inception_2010.jpg',
        'trailer_url' => 'https://www.youtube.com/watch?v=YoHD9XEInc0',
        'country_name' => 'United States',
        'language_name' => 'English'
    ] : [
        'id' => '',
        'title' => '',
        'description' => '',
        'release_year' => '',
        'poster_url' => '',
        'background_url' => '',
        'trailer_url' => ''
    ];
    
    $allGenres = [
        ['id' => 1, 'name' => 'Action'],
        ['id' => 2, 'name' => 'Adventure'],
        ['id' => 3, 'name' => 'Animation'],
        ['id' => 9, 'name' => 'Drama'],
        ['id' => 24, 'name' => 'Science Fiction'],
        ['id' => 27, 'name' => 'Thriller'],
    ];
    
    $selectedGenres = $editing ? [1, 24, 27] : [];
    
    // Mock countries and languages
    $countries = [
        'United States', 'United Kingdom', 'Canada', 'Australia', 
        'France', 'Germany', 'Japan', 'South Korea', 'Philippines'
    ];
    
    $languages = [
        'English', 'Spanish', 'French', 'German', 'Japanese', 
        'Korean', 'Mandarin', 'Tagalog', 'Hindi'
    ];
@endphp

<main class="max-w-6xl mx-auto py-8 px-4 md:px-8">
    <div class="bg-slate-800 border border-slate-600 rounded-2xl shadow-2xl overflow-hidden">
        <form onsubmit="event.preventDefault(); alert('{{ $actionText }} functionality - backend not implemented');">
            
            <!-- Basic Info -->
            <details class="accordion-section border-b border-slate-600" open>
                <summary class="bg-gradient-to-r from-slate-700 to-slate-600 hover:from-slate-600 hover:to-slate-500 cursor-pointer p-6 flex items-center gap-3 text-lg font-semibold text-white transition-all duration-300">
                    <i class="bx bx-chevron-right accordion-icon text-xl"></i>
                    <i class="bx bx-info-circle text-cyan-400"></i>
                    Basic Information
                </summary>
                <div class="p-8 bg-slate-800">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="space-y-2">
                            <label class="flex items-center gap-2 text-slate-300 font-medium text-sm">
                                <i class="bx bx-movie text-cyan-400"></i>
                                Title <span class="text-red-400">*</span>
                            </label>
                            <input type="text" name="title" value="{{ $movieData['title'] }}" 
                                   class="w-full px-4 py-3 bg-slate-700 border-2 border-slate-600 rounded-lg text-white placeholder-slate-400 focus:border-cyan-500 focus:ring-0 focus:outline-none transition-all duration-300" 
                                   placeholder="Enter movie title..." required />
                        </div>
                        
                        <div class="space-y-2">
                            <label class="flex items-center gap-2 text-slate-300 font-medium text-sm">
                                <i class="bx bx-calendar text-cyan-400"></i>
                                Release Year
                            </label>
                            <input type="number" name="release_year" value="{{ $movieData['release_year'] }}" 
                                   min="1960" max="{{ date('Y') }}" 
                                   class="w-full px-4 py-3 bg-slate-700 border-2 border-slate-600 rounded-lg text-white placeholder-slate-400 focus:border-cyan-500 focus:ring-0 focus:outline-none transition-all duration-300" 
                                   placeholder="{{ date('Y') }}" />
                        </div>
                        
                        <div class="space-y-2">
                            <label class="flex items-center gap-2 text-slate-300 font-medium text-sm">
                                <i class="bx bx-world text-cyan-400"></i>
                                Country <span class="text-red-400">*</span>
                            </label>
                            <select name="countryName" 
                                    class="w-full px-4 py-3 bg-slate-700 border-2 border-slate-600 rounded-lg text-white focus:border-cyan-500 focus:ring-0 focus:outline-none transition-all duration-300" required>
                                <option value="">-- Select Country --</option>
                                @foreach($countries as $country)
                                    <option value="{{ $country }}" {{ ($editing && $movieData['country_name'] === $country) ? 'selected' : '' }}>
                                        {{ $country }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        
                        <div class="space-y-2">
                            <label class="flex items-center gap-2 text-slate-300 font-medium text-sm">
                                <i class="bx bx-message text-cyan-400"></i>
                                Language <span class="text-red-400">*</span>
                            </label>
                            <select name="languageName" 
                                    class="w-full px-4 py-3 bg-slate-700 border-2 border-slate-600 rounded-lg text-white focus:border-cyan-500 focus:ring-0 focus:outline-none transition-all duration-300" required>
                                <option value="">-- Select Language --</option>
                                @foreach($languages as $language)
                                    <option value="{{ $language }}" {{ ($editing && $movieData['language_name'] === $language) ? 'selected' : '' }}>
                                        {{ $language }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>
            </details>

            <!-- Media -->
            <details class="accordion-section border-b border-slate-600">
                <summary class="bg-gradient-to-r from-slate-700 to-slate-600 hover:from-slate-600 hover:to-slate-500 cursor-pointer p-6 flex items-center gap-3 text-lg font-semibold text-white transition-all duration-300">
                    <i class="bx bx-chevron-right accordion-icon text-xl"></i>
                    <i class="bx bx-image text-emerald-400"></i>
                    Media Files
                </summary>
                <div class="p-8 bg-slate-800">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                        <div class="space-y-2">
                            <label class="flex items-center gap-2 text-slate-300 font-medium text-sm">
                                <i class="bx bx-image text-emerald-400"></i>
                                Poster Image @if(!$editing)<span class="text-red-400">*</span>@endif
                            </label>
                            <input type="file" name="poster_file" {{ $editing ? '' : 'required' }}
                                   class="w-full px-4 py-3 bg-slate-700 border-2 border-slate-600 rounded-lg text-white file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-cyan-500 file:text-white hover:file:bg-cyan-600 focus:border-cyan-500 focus:ring-0 focus:outline-none transition-all duration-300" />
                            @if($editing && !empty($movieData['poster_url']))
                                <div class="mt-4">
                                    <img src="/{{ $movieData['poster_url'] }}" alt="Current Poster" 
                                         class="max-h-48 object-cover rounded-xl border-2 border-slate-600 shadow-lg" />
                                </div>
                            @endif
                        </div>
                        
                        <div class="space-y-2">
                            <label class="flex items-center gap-2 text-slate-300 font-medium text-sm">
                                <i class="bx bx-landscape text-emerald-400"></i>
                                Background Image
                            </label>
                            <input type="file" name="background_file" 
                                   class="w-full px-4 py-3 bg-slate-700 border-2 border-slate-600 rounded-lg text-white file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-cyan-500 file:text-white hover:file:bg-cyan-600 focus:border-cyan-500 focus:ring-0 focus:outline-none transition-all duration-300" />
                            @if($editing && !empty($movieData['background_url']))
                                <div class="mt-4">
                                    <img src="/{{ $movieData['background_url'] }}" alt="Current Background" 
                                         class="max-h-48 object-cover rounded-xl border-2 border-slate-600 shadow-lg" />
                                </div>
                            @endif
                        </div>
                        
                        <div class="md:col-span-2 space-y-2">
                            <label class="flex items-center gap-2 text-slate-300 font-medium text-sm">
                                <i class="bx bx-play-circle text-emerald-400"></i>
                                Trailer URL
                            </label>
                            <input type="text" name="trailer_url" value="{{ $movieData['trailer_url'] ?? '' }}" 
                                   class="w-full px-4 py-3 bg-slate-700 border-2 border-slate-600 rounded-lg text-white placeholder-slate-400 focus:border-cyan-500 focus:ring-0 focus:outline-none transition-all duration-300" 
                                   placeholder="https://www.youtube.com/watch?v=..." />
                        </div>
                    </div>
                </div>
            </details>

            <!-- Cast & Crew -->
            <details class="accordion-section border-b border-slate-600">
                <summary class="bg-gradient-to-r from-slate-700 to-slate-600 hover:from-slate-600 hover:to-slate-500 cursor-pointer p-6 flex items-center gap-3 text-lg font-semibold text-white transition-all duration-300">
                    <i class="bx bx-chevron-right accordion-icon text-xl"></i>
                    <i class="bx bx-group text-purple-400"></i>
                    Cast & Crew
                </summary>
                <div class="p-8 bg-slate-800">
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                        <div class="bg-slate-700 border-2 border-slate-600 rounded-xl p-6 space-y-4">
                            <h5 class="flex items-center gap-3 text-purple-400 font-semibold text-lg">
                                <i class="bx bx-user-voice"></i>
                                Directors
                            </h5>
                            <div id="directors-list" class="min-h-[80px] p-4 bg-slate-800 rounded-lg border-2 border-slate-600">
                                <div class="text-slate-400 text-center italic py-4">Backend not implemented</div>
                            </div>
                            <input type="text" id="director-input" 
                                   class="w-full px-4 py-3 bg-slate-600 border-2 border-slate-500 rounded-lg text-white placeholder-slate-300 focus:border-purple-400 focus:ring-0 focus:outline-none transition-all duration-300" 
                                   placeholder="Type director name..." />
                        </div>
                        
                        <div class="bg-slate-700 border-2 border-slate-600 rounded-xl p-6 space-y-4">
                            <h5 class="flex items-center gap-3 text-purple-400 font-semibold text-lg">
                                <i class="bx bx-group"></i>
                                Cast (Actors & Actresses)
                            </h5>
                            <div id="actors-list" class="min-h-[80px] p-4 bg-slate-800 rounded-lg border-2 border-slate-600">
                                <div class="text-slate-400 text-center italic py-4">Backend not implemented</div>
                            </div>
                            <input type="text" id="actor-input" 
                                   class="w-full px-4 py-3 bg-slate-600 border-2 border-slate-500 rounded-lg text-white placeholder-slate-300 focus:border-purple-400 focus:ring-0 focus:outline-none transition-all duration-300" 
                                   placeholder="Type actor name..." />
                        </div>
                    </div>
                </div>
            </details>

            <!-- Description & Genres -->
            <details class="accordion-section">
                <summary class="bg-gradient-to-r from-slate-700 to-slate-600 hover:from-slate-600 hover:to-slate-500 cursor-pointer p-6 flex items-center gap-3 text-lg font-semibold text-white transition-all duration-300">
                    <i class="bx bx-chevron-right accordion-icon text-xl"></i>
                    <i class="bx bx-text text-orange-400"></i>
                    Description & Genres
                </summary>
                <div class="p-8 bg-slate-800 space-y-8">
                    <div class="space-y-2">
                        <label class="flex items-center gap-2 text-slate-300 font-medium text-sm">
                            <i class="bx bx-text text-orange-400"></i>
                            Description
                        </label>
                        <textarea name="description" rows="6" 
                                  class="w-full px-4 py-3 bg-slate-700 border-2 border-slate-600 rounded-lg text-white placeholder-slate-400 focus:border-cyan-500 focus:ring-0 focus:outline-none transition-all duration-300 resize-none" 
                                  placeholder="Enter movie description...">{{ $movieData['description'] ?? '' }}</textarea>
                    </div>
                    
                    <div class="space-y-4">
                        <label class="flex items-center gap-2 text-slate-300 font-medium text-sm">
                            <i class="bx bx-category text-orange-400"></i>
                            Genres
                        </label>
                        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-3">
                            @foreach($allGenres as $genre)
                                <label class="flex items-center gap-3 p-4 bg-slate-700 border-2 border-slate-600 rounded-lg cursor-pointer hover:border-orange-400 hover:bg-slate-600 transition-all duration-300 group">
                                    <input type="checkbox" name="genres[]" value="{{ $genre['id'] }}" 
                                           {{ in_array($genre['id'], $selectedGenres) ? 'checked' : '' }}
                                           class="w-4 h-4 text-orange-400 bg-slate-800 border-slate-500 rounded focus:ring-orange-400 focus:ring-2">
                                    <span class="text-left text-slate-200 group-hover:text-orange-300 font-medium">{{ $genre['name'] }}</span>
                                </label>
                            @endforeach
                        </div>
                    </div>
                </div>
            </details>

            <!-- Submit Buttons -->
            <div class="flex flex-col sm:flex-row gap-4 p-8 bg-slate-700 border-t border-slate-600">
                <button type="submit" class="btn btn-accent">
                    <i class="bx {{ $editing ? 'bx-save' : 'bx-plus' }} text-xl"></i>
                    {{ $actionText }}
                </button>
            </div>
        </form>
    </div>
</main>
@endsection