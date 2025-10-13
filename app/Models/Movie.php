<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Movie extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'description',
        'release_year',
        'poster_url',
        'background_url',
        'trailer_url',
    ];

    public function genres()
    {
        return $this->belongsToMany(Genre::class, 'movie_genres');
    }

    public function languages()
    {
        return $this->belongsToMany(Language::class, 'movie_languages');
    }

    public function countries()
    {
        return $this->belongsToMany(Country::class, 'movie_countries');
    }

    public function cast()
    {
        return $this->belongsToMany(MoviePerson::class, 'movie_cast')
            ->withPivot('role')
            ->withTimestamps();
    }

    public function ratings()
    {
        return $this->hasMany(RatingReview::class);
    }

    public function favoritedBy()
    {
        return $this->belongsToMany(User::class, 'user_favorites');
    }
}
