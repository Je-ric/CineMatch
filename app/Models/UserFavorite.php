<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserFavorite extends Model
{
    protected $fillable = [
        'user_id',
        'favoriteable_id',
        'favoriteable_type',
    ];

    public function favoriteable()
    {
        return $this->morphTo();
    }
}
