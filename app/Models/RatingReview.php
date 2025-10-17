<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RatingReview extends Model
{
    use HasFactory;

    protected $table = 'ratings_reviews';

    protected $fillable = ['user_id', 'movie_id', 'rating', 'review'];

    public function user()
    {
        return $this->belongsTo(
            User::class,
            'user_id',
            'id'
        );
    }

    public function movie()
    {
        return $this->belongsTo(
            Movie::class,
            'movie_id',
            'id'
        );
    }
}
