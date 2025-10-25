<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Movie;
use App\Models\MoviePerson;

class PeopleController extends Controller
{
    public function fetch(Request $request)
    {
        $validated = $request->validate([
            'movie_id' => 'required|integer|exists:movies,id',
            'role' => 'required|in:Director,Cast',
        ]);

        $movie = Movie::findOrFail($validated['movie_id']);

        $people = $movie->cast()
            ->wherePivot('role', $validated['role'])
            ->orderBy('name')
            ->get(['movie_people.id', 'movie_people.name']);

        return response()->json(['success' => true, 'data' => $people]);
    }

    public function add(Request $request)
    {
        $validated = $request->validate([
            'movie_id' => 'required|integer|exists:movies,id',
            'name' => 'required|string|max:255',
            'role' => 'required|in:Director,Cast',
        ]);

        $movie = Movie::findOrFail($validated['movie_id']);
        $person = MoviePerson::firstOrCreate(['name' => trim($validated['name'])]);

        // Check if already attached with same role
        $alreadyExists = $movie->cast()
            ->where('movie_people.id', $person->id)
            ->wherePivot('role', $validated['role'])
            ->exists();

        if ($alreadyExists) {
            return response()->json([
                'success' => false,
                'message' => "{$person->name} is already added as {$validated['role']}.",
            ]);
        }

        $movie->cast()->attach($person->id, ['role' => $validated['role']]);

        return response()->json(['success' => true, 'data' => ['person_id' => $person->id]]);
    }

    public function remove(Request $request)
    {
        $validated = $request->validate([
            'movie_id' => 'required|integer|exists:movies,id',
            'person_id' => 'required|integer|exists:movie_people,id',
            'role' => 'nullable|in:Director,Cast',
        ]);

        $movie = Movie::findOrFail($validated['movie_id']);
        $query = $movie->cast();

        if (!empty($validated['role'])) {
            $query->wherePivot('role', $validated['role']);
        }

        $query->detach($validated['person_id']);

        return response()->json(['success' => true]);
    }

    public function search(Request $request)
    {
        $validated = $request->validate([
            'query' => 'nullable|string|max:255',
        ]);

        $term = trim($validated['query'] ?? '');
        if ($term === '') {
            return response()->json(['success' => true, 'data' => []]);
        }

        $results = MoviePerson::where('name', 'like', "%{$term}%")
            ->orderBy('name')
            ->limit(15)
            ->get(['id', 'name']);

        return response()->json(['success' => true, 'data' => $results]);
    }
}
