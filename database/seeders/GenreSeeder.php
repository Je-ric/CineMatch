<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class GenreSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void {
        $genres = [
            'Action','Adventure','Animation','Biography','Comedy',
            'Crime','Dance','Documentary','Drama','Family',
            'Fantasy','History','Horror','Kids','Military',
            'Music','Musical','Mystery','Nature','Period',
            'Political','Science Fiction','Soap Opera','Talk Show',
            'Thriller','TV Movie','War','Short'
        ];

        foreach ($genres as $genre) {
            DB::table('genres')->insert(['name' => $genre]);
        }
    }
}
