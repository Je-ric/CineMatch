<?php

namespace App\Http\Livewire;

use Livewire\Component;
use App\Models\Movie;
use App\Models\MoviePerson;

class MoviePeople extends Component
{
    public $movie;
    public $role; // 'Director' or 'Cast'
    public $people = [];
    public $searchName = '';

    public function mount(Movie $movie, $role)
    {
        $this->movie = $movie;
        $this->role = $role;
        $this->loadPeople();
    }

    public function loadPeople()
    {
        if (!$this->movie || !$this->movie->id) {
            $this->people = [];
            return;
        }

        $this->people = $this->movie->cast()
            ->wherePivot('role', $this->role)
            ->orderBy('name')
            ->get();
    }

    public function addPerson()
    {
        if (!$this->movie || !$this->movie->id) return;

        $name = trim($this->searchName);
        if ($name === '') return;

        $person = MoviePerson::firstOrCreate(['name' => $name]);

        $alreadyAttached = $this->movie->cast()
            ->where('movie_people.id', $person->id)
            ->wherePivot('role', $this->role)
            ->exists();

        if (!$alreadyAttached) {
            $this->movie->cast()->attach($person->id, ['role' => $this->role]);
        }

        $this->searchName = '';
        $this->loadPeople();
    }

    public function removePerson($personId)
    {
        if (!$this->movie || !$this->movie->id) return;

        $this->movie->cast()->wherePivot('role', $this->role)->detach($personId);
        $this->loadPeople();
    }

    public function render()
    {
        return view('livewire.movie-people');
    }
}
