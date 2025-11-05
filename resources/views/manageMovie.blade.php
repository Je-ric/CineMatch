@extends('layouts.app')

@section('page-content')
    <style>
        body {
            font-family: 'Oswald', sans-serif;
        }

        .accordion-icon {
            transition: transform 0.3s ease;
        }

        .accordion-section[open] .accordion-icon {
            transform: rotate(90deg);
        }

        .ui-autocomplete {
            background: #334155 !important;
            border: 2px solid #475569 !important;
            border-radius: 8px !important;
            color: #f8fafc !important;
            max-height: 200px;
            overflow-y: auto;
        }

        .ui-autocomplete .ui-menu-item {
            padding: 8px 12px !important;
            border-bottom: 1px solid #475569 !important;
        }

        .ui-autocomplete .ui-menu-item:hover,
        .ui-autocomplete .ui-menu-item.ui-state-focus {
            background: #0891b2 !important;
            color: white !important;
        }

        .person-badge {
            background: linear-gradient(135deg, #0891b2 0%, #10b981 100%);
            color: white;
            padding: 8px 14px;
            border-radius: 20px;
            margin: 4px;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-size: 13px;
            font-weight: 500;
        }

        .remove-person {
            background: rgba(255, 255, 255, 0.2);
            border: none;
            color: white;
            font-size: 14px;
            cursor: pointer;
            width: 18px;
            height: 18px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .empty-state {
            color: #64748b;
            font-style: italic;
            text-align: center;
            padding: 20px;
            background: #475569;
            border-radius: 8px;
            border: 2px dashed #64748b;
        }
    </style>
    <main class="max-w-6xl mx-auto py-8 px-4 md:px-8">
        <div class="bg-slate-800 border border-slate-600 rounded-2xl shadow-2xl overflow-hidden">
            <form action="{{ $editing ? route('movies.update', $movie->id) : route('movies.store') }}" method="POST"
                enctype="multipart/form-data">
                @csrf
                @if ($editing)
                    @method('PUT')
                @else
                    @method('POST')
                @endif



                <details class="accordion-section border-b border-slate-600" open>
                    <summary
                        class="bg-gradient-to-r from-slate-700 to-slate-600 hover:from-slate-600 hover:to-slate-500 cursor-pointer p-6 flex items-center gap-3 text-lg font-semibold text-white transition-all duration-300">
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
                                <input type="text" name="title"
                                    value="{{ old('title', $editing ? $movie->title : '') }}"
                                    class="w-full px-4 py-3 bg-slate-700 border-2 border-slate-600 rounded-lg text-white placeholder-slate-400 focus:border-cyan-500 focus:ring-0 focus:outline-none transition-all duration-300"
                                    placeholder="Enter movie title..." required />
                            </div>

                            <div class="space-y-2">
                                <label class="flex items-center gap-2 text-slate-300 font-medium text-sm">
                                    <i class="bx bx-calendar text-cyan-400"></i>
                                    Release Year
                                </label>
                                <input type="number" name="release_year"
                                    value="{{ old('release_year', $editing ? $movie->release_year : '') }}" min="1960"
                                    max="{{ date('Y') }}"
                                    class="w-full px-4 py-3 bg-slate-700 border-2 border-slate-600 rounded-lg text-white placeholder-slate-400 focus:border-cyan-500 focus:ring-0 focus:outline-none transition-all duration-300"
                                    placeholder="{{ date('Y') }}" />
                            </div>

                            <div class="space-y-2">
                                <label class="flex items-center gap-2 text-slate-300 font-medium text-sm">
                                    <i class="bx bx-world text-cyan-400"></i>
                                    Country <span class="text-red-400">*</span>
                                </label>
                                <input list="countryList" name="countryName" id="country-input"
                                    value="{{ old('countryName', $editing && $movie && $movie->country ? $movie->country->name : '') }}"
                                    class="w-full px-4 py-3 bg-slate-700 border-2 border-slate-600 rounded-lg text-white placeholder-slate-400 focus:border-cyan-500 focus:ring-0 focus:outline-none transition-all duration-300"
                                    placeholder="Type or select a country..." required />
                                <datalist id="countryList"></datalist>
                            </div>

                            <div class="space-y-2">
                                <label class="flex items-center gap-2 text-slate-300 font-medium text-sm">
                                    <i class="bx bx-message text-cyan-400"></i>
                                    Language <span class="text-red-400">*</span>
                                </label>
                                <input list="languageList" name="languageName" id="language-input"
                                    value="{{ old('languageName', $editing && $movie && $movie->language ? $movie->language->name : '') }}"
                                    class="w-full px-4 py-3 bg-slate-700 border-2 border-slate-600 rounded-lg text-white placeholder-slate-400 focus:border-cyan-500 focus:ring-0 focus:outline-none transition-all duration-300"
                                    placeholder="Type or select a language..." required />
                                <datalist id="languageList"></datalist>
                            </div>

                        </div>
                    </div>
                </details>




                {{--  --}}
                <details class="accordion-section border-b border-slate-600">
                    <summary
                        class="bg-gradient-to-r from-slate-700 to-slate-600 hover:from-slate-600 hover:to-slate-500 cursor-pointer p-6 flex items-center gap-3 text-lg font-semibold text-white transition-all duration-300">
                        <i class="bx bx-chevron-right accordion-icon text-xl"></i>
                        <i class="bx bx-image text-emerald-400"></i>
                        Media Files
                    </summary>
                    <div class="p-8 bg-slate-800">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                            <div class="space-y-2">
                                <label class="flex items-center gap-2 text-slate-300 font-medium text-sm">
                                    <i class="bx bx-image text-emerald-400"></i>
                                    Poster Image {!! $editing ? '' : '<span class="text-red-400">*</span>' !!}
                                </label>
                                <input type="file" name="poster_file" {{ $editing ? '' : 'required' }}
                                    class="w-full px-4 py-3 bg-slate-700 border-2 border-slate-600 rounded-lg text-white file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-cyan-500 file:text-white hover:file:bg-cyan-600 focus:border-cyan-500 focus:ring-0 focus:outline-none transition-all duration-300" />
                                @if ($editing && !empty($movie->getRawOriginal('poster_url')))
                                    <div class="mt-4">
                                        <img src="{{ asset($movie->getRawOriginal('poster_url')) }}" alt="Current Poster"
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
                                @if ($editing && !empty($movie->getRawOriginal('background_url')))
                                    <div class="mt-4">
                                        <img src="{{ asset($movie->getRawOriginal('background_url')) }}"
                                            alt="Current Background"
                                            class="max-h-48 object-cover rounded-xl border-2 border-slate-600 shadow-lg" />
                                    </div>
                                @endif
                            </div>

                            <div class="md:col-span-2 space-y-2">
                                <label class="flex items-center gap-2 text-slate-300 font-medium text-sm">
                                    <i class="bx bx-play-circle text-emerald-400"></i>
                                    Trailer URL
                                </label>
                                <input type="text" name="trailer_url"
                                    value="{{ old('trailer_url', $editing ? $movie->trailer_url : '') }}"
                                    class="w-full px-4 py-3 bg-slate-700 border-2 border-slate-600 rounded-lg text-white placeholder-slate-400 focus:border-cyan-500 focus:ring-0 focus:outline-none transition-all duration-300"
                                    placeholder="https://www.youtube.com/watch?v=..." />
                            </div>
                        </div>
                    </div>
                </details>





                {{--  --}}
                <details class="accordion-section border-b border-slate-600">
                    <summary
                        class="bg-gradient-to-r from-slate-700 to-slate-600 hover:from-slate-600 hover:to-slate-500 cursor-pointer p-6 flex items-center gap-3 text-lg font-semibold text-white transition-all duration-300">
                        <i class="bx bx-chevron-right accordion-icon text-xl"></i>
                        <i class="bx bx-group text-purple-400"></i>
                        Cast & Crew
                    </summary>
                    <div class="p-8 bg-slate-800">
                        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                            {{--  --}}
                            <div class="bg-slate-700 border-2 border-slate-600 rounded-xl p-6 space-y-4">
                                <h5 class="flex items-center gap-3 text-purple-400 font-semibold text-lg">
                                    <i class="bx bx-user-voice"></i> Directors
                                </h5>
                                @if(isset($movie) && $movie)
                                    @livewire('movie-people',
                                                    ['movie' => $movie,
                                                    'role' => 'Director'],
                                                    key('director-'.$movie->id))
                                @endif
                            </div>

                            <div class="bg-slate-700 border-2 border-slate-600 rounded-xl p-6 space-y-4">
                                <h5 class="flex items-center gap-3 text-purple-400 font-semibold text-lg">
                                    <i class="bx bx-group"></i> Casts: (Actors & Actresses)
                                </h5>
                                @if(isset($movie) && $movie)
                                    @livewire('movie-people',
                                            ['movie' => $movie,
                                                    'role' => 'Cast'], 
                                                    key('cast-'.$movie->id))
                                @endif
                            </div>
                            {{--  --}}

                        </div>
                    </div>
                </details>





                {{--  --}}
                <details class="accordion-section">
                    <summary
                        class="bg-gradient-to-r from-slate-700 to-slate-600 hover:from-slate-600 hover:to-slate-500 cursor-pointer p-6 flex items-center gap-3 text-lg font-semibold text-white transition-all duration-300">
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
                                placeholder="Enter movie description...">{{ old('description', $editing ? $movie->description : '') }}</textarea>
                        </div>

                        <div class="space-y-4">
                            <label class="flex items-center gap-2 text-slate-300 font-medium text-sm">
                                <i class="bx bx-category text-orange-400"></i>
                                Genres
                            </label>
                            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-3">
                                @php
                                    $selected = $editing ? $movie->genres->pluck('id')->toArray() : [];
                                @endphp
                                @foreach ($allGenres as $genre)
                                    <label
                                        class="flex items-center gap-3 p-4 bg-slate-700 border-2 border-slate-600 rounded-lg cursor-pointer hover:border-orange-400 hover:bg-slate-600 transition-all duration-300 group">
                                        <input type="checkbox" name="genres[]" value="{{ $genre->id }}"
                                            {{ in_array($genre->id, $selected) ? 'checked' : '' }}
                                            class="w-4 h-4 text-orange-400 bg-slate-800 border-slate-500 rounded focus:ring-orange-400 focus:ring-2">
                                        <span
                                            class="text-left text-slate-200 group-hover:text-orange-300 font-medium">{{ $genre->name }}</span>
                                    </label>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </details>



                

                <div class="flex flex-col sm:flex-row gap-4 p-8 bg-slate-700 border-t border-slate-600">
                    <button type="submit" class="btn btn-accent">
                        <i class="bx {{ $editing ? 'bx-save' : 'bx-plus' }} text-xl"></i>
                        {{ $editing ? 'Update Movie' : 'Add Movie' }}
                    </button>
                </div>
            </form>
        </div>
    </main>

    <script>
        const editing = {{ $editing ? 'true' : 'false' }};
        const movieId = {{ $editing && $movie ? (int) $movie->id : 0 }};

        // Load Countries
        fetch("{{ asset('JSON/countries.json') }}")
            .then(r => r.ok ? r.json() : [])
            .catch(() => [])
            .then(list => {
                const dataList = document.getElementById('countryList');
                (list || []).forEach(item => {
                    const name = item.country || item.name;
                    if (!name) return;
                    const opt = document.createElement('option');
                    opt.value = name;
                    dataList.appendChild(opt);
                });
            });

        // Load Languages
        fetch("{{ asset('JSON/language.json') }}")
            .then(r => r.ok ? r.json() : [])
            .catch(() => [])
            .then(list => {
                const dataList = document.getElementById('languageList');
                (list || []).forEach(item => {
                    const name = item.name || item.language;
                    if (!name) return;
                    const opt = document.createElement('option');
                    opt.value = name;
                    dataList.appendChild(opt);
                });
            });
    </script>
@endsection
