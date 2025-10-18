<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Country extends Model
{
    use HasFactory;

    protected $fillable = ['name'];

    public function movies()
    {
        return $this->hasMany(Movie::class);
    }

    // public function movies()
    // {
    //     return $this->belongsToMany(
    //         Movie::class,
    //         'movie_countries',
    //         'country_id',
    //         'movie_id'
    //     );
    // }
}
