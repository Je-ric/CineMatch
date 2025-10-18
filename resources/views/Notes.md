php artisan make:migration create_users_table
php artisan make:migration create_genres_table
php artisan make:migration create_countries_table
php artisan make:migration create_languages_table
php artisan make:migration create_movies_table
php artisan make:migration create_movie_genres_table
php artisan make:migration create_movie_people_table
php artisan make:migration create_movie_cast_table
php artisan make:migration create_ratings_reviews_table
php artisan make:migration create_user_favorites_table
php artisan make:migration create_movie_languages_table
php artisan make:migration create_movie_countries_table


php artisan make:seeder GenreSeeder
    - php artisan db:seed --class=GenreSeeder
php artisan make:seeder AdminUserSeeder
    - php artisan db:seed --class=AdminUserSeeder

php artisan make:migration update_users_table_add_role_and_username_columns --table=users



refresh, then do this or clear the database in phpMyAdmin
    php artisan config:clear
    php artisan route:clear
    php artisan view:clear
    php artisan migrate

php artisan make:model Movie
php artisan make:model Genre
php artisan make:model Language
php artisan make:model Country
php artisan make:model MoviePerson
php artisan make:model RatingReview
php artisan make:model UserFavorite

Note: The other tables are just pivot
    - Movie Genres 
    - Movie Language
    - Movie Country
    - Movie Cast


php artisan make:Controller FavoriteController
php artisan make:Controller PeopleController
php artisan make:Controller RateReviewController
php artisan make:Controller RecommendController


php artisan make:livewire favorite-button
composer require livewire/livewire
php artisan make:livewire movie-reviews



https://filamentapps.com/blog/troubleshooting-laravel-livewire-resolving-the-unable-to-find-component-error