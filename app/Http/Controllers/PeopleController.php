<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Movie;
use App\Models\MoviePerson;

class PeopleController extends Controller
{
    // Fetch people by role for a movie
    public function fetch(Request $request)
    {
        $validated = $request->validate([
            'movie_id' => 'required|integer|exists:movies,id',
            'role' => 'required|in:Director,Cast',
        ]);

        $movie = Movie::findOrFail($validated['movie_id']);

        // Do a case-insensitive match on the pivot.role to be robust
        $roleLower = strtolower($validated['role']);

        $people = MoviePerson::join('movie_cast', 'movie_people.id', '=', 'movie_cast.person_id')
            ->where('movie_cast.movie_id', $movie->id)
            ->whereRaw('LOWER(movie_cast.role) = ?', [$roleLower])
            ->orderBy('movie_people.name')
            ->get(['movie_people.id', 'movie_people.name']);

        return response()->json(['success' => true, 'data' => $people]);
    }

    // Add a person (create if not exists) and attach to movie with role
    public function add(Request $request)
    {
        $validated = $request->validate([
            'movie_id' => 'required|integer|exists:movies,id',
            'name' => 'required|string|max:255',
            'role' => 'required|in:Director,Cast',
        ]);

        $movie = Movie::findOrFail($validated['movie_id']);
        $person = MoviePerson::firstOrCreate(['name' => trim($validated['name'])]);

        // Avoid duplicate attach for same role
        $already = $movie->cast()->wherePivot('role', $validated['role'])->where('movie_people.id', $person->id)->exists();
        if ($already) {
            return response()->json(['success' => false, 'message' => 'This person is already added as ' . strtolower($validated['role'])]);
        }
        $movie->cast()->attach($person->id, ['role' => $validated['role']]);

        return response()->json(['success' => true, 'data' => ['person_id' => $person->id]]);
    }

    // Remove a person from a movie by pivot id or by person+movie+role
    public function remove(Request $request)
    {
        $validated = $request->validate([
            'movie_id' => 'required|integer|exists:movies,id',
            'person_id' => 'nullable|integer|exists:movie_people,id',
            'role' => 'nullable|in:Director,Cast',
        ]);

        $movie = Movie::findOrFail($validated['movie_id']);
        if (!empty($validated['person_id'])) {
            $query = $movie->cast();
            if (!empty($validated['role'])) {
                $query = $query->wherePivot('role', $validated['role']);
            }
            $query->detach($validated['person_id']);
            return response()->json(['success' => true]);
        }

        return response()->json(['success' => false, 'message' => 'Invalid parameters']);
    }

    // Search people by name
    public function search(Request $request)
    {
        $validated = $request->validate([
            'query' => 'nullable|string|max:255',
        ]);
        $term = trim($validated['query'] ?? '');
        if ($term === '') {
            return response()->json(['success' => true, 'data' => []]);
        }
        $rows = MoviePerson::where('name', 'like', "%{$term}%")
            ->orderBy('name')
            ->limit(15)
            ->get(['id', 'name']);
        return response()->json(['success' => true, 'data' => $rows]);
    }
}
