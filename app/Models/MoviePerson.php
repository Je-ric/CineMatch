<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MoviePerson extends Model
{
    use HasFactory;

    protected $fillable = ['name'];

    public function movies()
    {
        return $this->belongsToMany(Movie::class, 'movie_cast')
            ->withPivot('role')
            ->withTimestamps();
    }
}
