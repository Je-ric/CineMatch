<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\User;
use App\Models\Movie;

class UserFavorite extends Model
{
    use HasFactory;

    protected $table = 'user_favorites';

    protected $fillable = ['user_id', 'movie_id'];

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
            'id');
    }
}
