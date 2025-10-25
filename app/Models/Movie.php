<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

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
        'country_id',  
        'language_id', 
    ];

    public function genres()
    {
        return $this->belongsToMany(
            Genre::class,
            'movie_genres',
            'movie_id',
            'genre_id'
        );
    }

    public function country() {
        return $this->belongsTo(Country::class);
    }

    public function language() {
        return $this->belongsTo(Language::class);
    }

    // public function languages()
    // {
    //     return $this->belongsToMany(
    //         Language::class,
    //         'movie_languages',
    //         'movie_id',
    //         'language_id'
    //     );
    // }

    // public function countries()
    // {
    //     return $this->belongsToMany(
    //         Country::class,
    //         'movie_countries',
    //         'movie_id',
    //         'country_id'
    //     );
    // }

    public function cast()
    {
        return $this->belongsToMany(
            MoviePerson::class,
            'movie_cast',
            'movie_id',
            'person_id'
        )
        ->withPivot('role')
        ->withTimestamps();
    }

    public function ratings()
    {
        return $this->hasMany(
                RatingReview::class, 
                        'movie_id', 
                        'id'
                );
    }

    public function favoritedBy()
    {
        return $this->belongsToMany(
            User::class,
            'user_favorites',
            'movie_id',
            'user_id'
        );
    }

    // --- Accessors ---
    // When we do $movie->poster_url
    // Laravel first gets the raw value of poster_url from the database row.
    // Each movie has a unique poster path

    // $movie = Movie::find(1);
    //  [
    //     'id' => 1,
    //     'title' => 'Avengers',
    //     'poster_url' => 'uploads/posters/avengers.jpg'
    // ]
    // The database knows which poster belongs to the movie
    // $movie->poster_url == getPosterUrlAttribute($value), same to the other accessor

    public function getPosterUrlAttribute($value)
    {
        if (empty($value)) {
            return asset('images/placeholders/sample1.jpg');
        }

        if (!Str::startsWith($value, ['http://', 'https://'])) {
            return asset($value);
        }

        return $value;
    }

    public function getBackgroundUrlAttribute($value)
    {
        if (empty($value)) {
            return asset('images/placeholders/background.jpg');
        }

        if (!Str::startsWith($value, ['http://', 'https://'])) {
            return asset($value);
        }

        return $value;
    }

    // Returns full trailer URL (original) and exposes youtube_id computed property
    public function getTrailerUrlAttribute($value)
    {
        return $value;
    }

    public function getYoutubeIdAttribute()
    {
        $url = $this->attributes['trailer_url'] ?? null;
        if (empty($url)) {
            return null;
        }

        if (preg_match('/(?:youtube\.com\/watch\?v=|youtube\.com\/embed\/|youtu\.be\/)([^&\s\/]+)/', $url, $m)) {
            return $m[1];
        }

        return null;
    }
}
