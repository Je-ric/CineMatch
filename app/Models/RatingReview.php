<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RatingReview extends Model
{
    use HasFactory;

    protected $table = 'ratings_reviews';

    protected $fillable = ['user_id', 'movie_id', 'rating', 'review'];

    // Each rating review belongs to one user (the reviewer).
    // model User::user()
    public function user()
    {
        return $this->belongsTo(
            User::class,
            'user_id',
            'id'
        );
    }

    // Each rating review belongs to one movie (the reviewed movie)
    // model Movie::movie()
    public function movie()
    {
        return $this->belongsTo(
            Movie::class,
            'movie_id',
            'id'
        );
    }
}
