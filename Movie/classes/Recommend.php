<?php
require_once __DIR__ . '/../db/config.php';

class Recommend
{
    private $db;

    public function __construct()
    {
        $database = new Database();
        $this->db = $database->conn;
    }

    /**
     * Trending based on average rating (then release year)
     * Used in: db/recommendRequests.php (action: 'trending'), 
     *          index.php (home page)
     */
    public function getTrending(int $limit = 12): array
    {
        $limit = $this->clampLimit($limit);
        $sql = "
            SELECT 
                movies.id,
                movies.title,
                movies.release_year,
                movies.poster_url,
                countries.name AS country_name,
                languages.name AS language_name,
                ROUND(COALESCE(AVG(ratings_reviews.rating), 0), 2) AS avg_rating,
                COUNT(ratings_reviews.id) AS total_reviews
            FROM movies
            LEFT JOIN ratings_reviews ON ratings_reviews.movie_id = movies.id
            LEFT JOIN countries ON movies.country_id = countries.id
            LEFT JOIN languages ON movies.language_id = languages.id
            GROUP BY movies.id
            ORDER BY (AVG(ratings_reviews.rating) IS NULL), AVG(ratings_reviews.rating) DESC, COUNT(ratings_reviews.id) DESC, movies.release_year DESC
            LIMIT ?
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('i', $limit);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC); // trending -> JSON for index.php
    }

    /**
     * Search by movie title
     * Used in: db/recommendRequests.php (action: 'search')
     *          index.php (home page)
     */
    public function searchByTitle(string $term, int $limit = 24): array
    {
        $limit = $this->clampLimit($limit, 60);
        $searchLike = '%' . $term . '%';
        $sql = "
            SELECT 
                movies.id,
                movies.title,
                movies.release_year,
                movies.poster_url,
                countries.name AS country_name,
                languages.name AS language_name,
                ROUND(COALESCE(AVG(ratings_reviews.rating), 0), 2) AS avg_rating,
                COUNT(ratings_reviews.id) AS total_reviews
            FROM movies
            LEFT JOIN ratings_reviews ON ratings_reviews.movie_id = movies.id
            LEFT JOIN countries ON movies.country_id = countries.id
            LEFT JOIN languages ON movies.language_id = languages.id
            WHERE movies.title LIKE ?
            GROUP BY movies.id
            ORDER BY movies.release_year DESC, movies.title ASC
            LIMIT ?
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('si', $searchLike, $limit);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC); // search -> JSON for home search UI
    }


    ////////////////////////////////////////////////////////////////////////////////////////
    ////////////////////////////////////////////////////////////////////////////////////////
    ////////////////////////////////////////////////////////////////////////////////////////
    ////////////////////////////////////////////////////////////////////////////////////////
    ////////////////////////////////////////////////////////////////////////////////////////

    /**
     * Movies the user favorited
     * Used in: db/recommendRequests.php (action: 'favorites'), 
     *          pages/profile.php (favorites tab)
     */
    public function getFavorites(int $userId, int $limit = 12): array
    {
        if ($userId <= 0) return [];
        $limit = $this->clampLimit($limit);

        $sql = "
            SELECT 
                movies.id,
                movies.title,
                movies.release_year,
                movies.poster_url,
                countries.name AS country_name,
                languages.name AS language_name,
                ROUND(COALESCE(AVG(ratings_reviews.rating), 0), 2) AS avg_rating,
                COUNT(ratings_reviews.id) AS total_reviews
            FROM user_favorites
            JOIN movies ON movies.id = user_favorites.movie_id
            LEFT JOIN ratings_reviews ON ratings_reviews.movie_id = movies.id
            LEFT JOIN countries ON movies.country_id = countries.id
            LEFT JOIN languages ON movies.language_id = languages.id
            WHERE user_favorites.user_id = ?
            GROUP BY movies.id
            ORDER BY movies.title ASC
            LIMIT ?
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('ii', $userId, $limit);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC); // favorites -> JSON for pages/profile.php (Favorites tab)
    }

    /**
     * Movies the user rated/reviewed
     * Used in: db/recommendRequests.php (action: 'rated'), 
     *          pages/profile.php (rated tab)
     */
    public function getRated(int $userId, int $limit = 12): array
    {
        if ($userId <= 0) return [];
        $limit = $this->clampLimit($limit);

        $sql = "
            SELECT 
                movies.id,
                movies.title,
                movies.release_year,
                movies.poster_url,
                countries.name AS country_name,
                languages.name AS language_name,
                ROUND(COALESCE(AVG(all_reviews.rating), 0), 2) AS avg_rating,
                COUNT(all_reviews.id) AS total_reviews
            FROM ratings_reviews AS user_reviews
            JOIN movies ON movies.id = user_reviews.movie_id
            LEFT JOIN ratings_reviews AS all_reviews ON all_reviews.movie_id = movies.id
            LEFT JOIN countries ON movies.country_id = countries.id
            LEFT JOIN languages ON movies.language_id = languages.id
            WHERE user_reviews.user_id = ?
            GROUP BY movies.id
            ORDER BY MAX(user_reviews.id) DESC
            LIMIT ?
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('ii', $userId, $limit);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC); // rated -> JSON for pages/profile.php (Rated tab)
    }

    ////////////////////////////////////////////////////////////////////////////////////////
    ////////////////////////////////////////////////////////////////////////////////////////
    ////////////////////////////////////////////////////////////////////////////////////////
    ////////////////////////////////////////////////////////////////////////////////////////
    ////////////////////////////////////////////////////////////////////////////////////////

    /**
     * Recommendations based on user's favorite genres
     * Used in: db/recommendRequests.php (action: 'favGenres'), 
     *          pages/profile.php (recommendation tab)
     *          "Because you like these genres"
     */
    public function basedOnFavoriteGenres(int $userId, int $limit = 12): array
    {
        if ($userId <= 0) return [];
        $limit = $this->clampLimit($limit);

        // get top genres id (limit 5)
        $sqlTopGenres = "
            SELECT movie_genres.genre_id, COUNT(*) AS cnt
            FROM user_favorites
            JOIN movie_genres ON movie_genres.movie_id = user_favorites.movie_id
            WHERE user_favorites.user_id = ?
            GROUP BY movie_genres.genre_id
            ORDER BY cnt DESC
            LIMIT 5
        ";
        $stmtTop = $this->db->prepare($sqlTopGenres);
        $stmtTop->bind_param('i', $userId);
        $stmtTop->execute();
        $topGenres = $stmtTop->get_result()->fetch_all(MYSQLI_ASSOC);
        if (empty($topGenres)) return [];
        $genreIds = array_column($topGenres, 'genre_id');

        $placeholders = implode(',', array_fill(0, count($genreIds), '?'));

        // get movies using the top genres, excluding (NOT IN) yung favorite na
        $sql = "
            SELECT 
                movies.id,
                movies.title,
                movies.release_year,
                movies.poster_url,
                countries.name AS country_name,
                languages.name AS language_name,
                ROUND(COALESCE(AVG(ratings_reviews.rating), 0), 2) AS avg_rating,
                COUNT(DISTINCT movie_genres.genre_id) AS match_genres
            FROM movies
            JOIN movie_genres ON movie_genres.movie_id = movies.id
            LEFT JOIN ratings_reviews ON ratings_reviews.movie_id = movies.id
            LEFT JOIN countries ON movies.country_id = countries.id
            LEFT JOIN languages ON movies.language_id = languages.id
            WHERE movie_genres.genre_id IN ($placeholders)
                AND movies.id NOT IN (SELECT movie_id FROM user_favorites WHERE user_id = ?)
            GROUP BY movies.id
            ORDER BY match_genres DESC, (AVG(ratings_reviews.rating) IS NULL), AVG(ratings_reviews.rating) DESC, movies.release_year DESC
            LIMIT ?
        ";
        $stmt = $this->db->prepare($sql);

        $types = str_repeat('i', count($genreIds) + 2); // N genre_ids + userId + limit
        $params = [ &$types ];
        foreach ($genreIds as $k => $gid) {
            $genreIds[$k] = (int)$gid;
            $params[] = &$genreIds[$k];
        }
        $params[] = &$userId;
        $params[] = &$limit;
        call_user_func_array([$stmt, 'bind_param'], $params);

        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC); // favGenres -> JSON for profile "Because you like these genres"
    }

    /**
     * Get user's top genres (with counts)
     * Used in: db/recommendRequests.php (action: 'topGenres'), 
     *          pages/profile.php (recommendation tab)
     * If logged in:
     *      Popular in favorites (5 top genre), proceed to getByGenre para hanapin na yung movie
     */
    public function getUserTopGenres(int $userId, int $limit = 5): array
    {
        if ($userId <= 0) return [];
        $limit = $this->clampLimit($limit, 10);

        $sql = "
            SELECT genres.id, genres.name, COUNT(*) AS cnt
            FROM user_favorites
            JOIN movie_genres ON movie_genres.movie_id = user_favorites.movie_id
            JOIN genres ON genres.id = movie_genres.genre_id
            WHERE user_favorites.user_id = ?
            GROUP BY genres.id, genres.name
            ORDER BY cnt DESC
            LIMIT ?
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('ii', $userId, $limit);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC); // topGenres -> used by user to request para sa byGenre shelves
    }

    /**
     * Get movies by genre (optionally excluding user's favorites)
     * Used in: db/recommendRequests.php (action: 'byGenre'), 
     *          pages/profile.php (genre shelves)
     * after makuha yung top 5 genre, 
     * nagfefetch ng movie from that genre and display sa "Popular in (genre)"
     */
    public function getByGenre(int $genreId, int $userId = 0, int $limit = 12): array
    {
        $limit = $this->clampLimit($limit);

        $sql = "
            SELECT 
                movies.id,
                movies.title,
                movies.release_year,
                movies.poster_url,
                countries.name AS country_name,
                languages.name AS language_name,
                ROUND(COALESCE(AVG(ratings_reviews.rating), 0), 2) AS avg_rating,
                COUNT(ratings_reviews.id) AS total_reviews
            FROM movies
            JOIN movie_genres ON movie_genres.movie_id = movies.id
            LEFT JOIN ratings_reviews ON ratings_reviews.movie_id = movies.id
            LEFT JOIN countries ON movies.country_id = countries.id
            LEFT JOIN languages ON movies.language_id = languages.id
            WHERE movie_genres.genre_id = ?
        ";

        if ($userId > 0) {
            $sql .= " AND movies.id NOT IN (SELECT movie_id FROM user_favorites WHERE user_id = ?)";
        }

        $sql .= "
            GROUP BY movies.id
            ORDER BY (AVG(ratings_reviews.rating) IS NULL), AVG(ratings_reviews.rating) DESC, movies.release_year DESC
            LIMIT ?
        ";

        $stmt = $this->db->prepare($sql);

        if ($userId > 0) {
            $stmt->bind_param('iii', $genreId, $userId, $limit);
        } else {
            $stmt->bind_param('ii', $genreId, $limit);
        }

        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC); // returns movies in a genre (excluding user's favorites) to byGenre -> JSON for "Popular in <genre>" shelves
    }

    /**
     * Recommendations based on user's favorite countries
     * Used in: db/recommendRequests.php (action: 'favCountries'), 
     *          pages/profile.php (recommendation tab) 
     *          "From countries you like"
     */
    public function basedOnFavoriteCountries(int $userId, int $limit = 12): array
    {
        if ($userId <= 0) return [];
        $limit = $this->clampLimit($limit);

        // fetch yung top 3 country id
        $sqlTopCountries = "
            SELECT movies.country_id, COUNT(*) AS cnt
            FROM user_favorites
            JOIN movies ON movies.id = user_favorites.movie_id
            WHERE user_favorites.user_id = ? AND movies.country_id IS NOT NULL
            GROUP BY movies.country_id
            ORDER BY cnt DESC
            LIMIT 3
        ";
        $stmtTop = $this->db->prepare($sqlTopCountries);
        $stmtTop->bind_param('i', $userId);
        $stmtTop->execute();
        $topCountryRows = $stmtTop->get_result()->fetch_all(MYSQLI_ASSOC);
        if (empty($topCountryRows)) return [];
        $countryIds = array_column($topCountryRows, 'country_id');

        $placeholders = implode(',', array_fill(0, count($countryIds), '?'));

        // fetch movie na gamit yung top 3 country id, excluding (NOT IN) yung favorite na 
        $sql = "
            SELECT 
                movies.id,
                movies.title,
                movies.release_year,
                movies.poster_url,
                countries.name AS country_name,
                languages.name AS language_name,
                ROUND(COALESCE(AVG(ratings_reviews.rating), 0), 2) AS avg_rating
            FROM movies
            LEFT JOIN ratings_reviews ON ratings_reviews.movie_id = movies.id
            LEFT JOIN countries ON movies.country_id = countries.id
            LEFT JOIN languages ON movies.language_id = languages.id
            WHERE movies.country_id IN ($placeholders)
                AND movies.id NOT IN (SELECT movie_id FROM user_favorites WHERE user_id = ?)
            GROUP BY movies.id
            ORDER BY (AVG(ratings_reviews.rating) IS NULL), AVG(ratings_reviews.rating) DESC, movies.release_year DESC
            LIMIT ?
        ";
        $stmt = $this->db->prepare($sql);

        $types = str_repeat('i', count($countryIds) + 2); // N country_ids + userId + limit
        $params = [ &$types ];
        foreach ($countryIds as $k => $cid) {
            $countryIds[$k] = (int)$cid;
            $params[] = &$countryIds[$k];
        }
        $params[] = &$userId;
        $params[] = &$limit;
        call_user_func_array([$stmt, 'bind_param'], $params);

        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC); // favCountries -> JSON for profile "From countries you like"
    }
    
    ////////////////////////////////////////////////////////////////////////////////////////
    ////////////////////////////////////////////////////////////////////////////////////////
    ////////////////////////////////////////////////////////////////////////////////////////
    ////////////////////////////////////////////////////////////////////////////////////////
    ////////////////////////////////////////////////////////////////////////////////////////
    // Counts
    /**
     * how many times a user has favorited movies of each genre
     * Used in: db/recommendRequests.php (action: 'favGenreCounts'), 
     *          pages/profile.php (Favorites pills)
     */
    public function getFavCountsByGenre(int $userId): array
    {
        if ($userId <= 0) return [];
        $sql = "
            SELECT genres.id, genres.name, COUNT(*) AS cnt
            FROM user_favorites
            JOIN movie_genres ON movie_genres.movie_id = user_favorites.movie_id
            JOIN genres ON genres.id = movie_genres.genre_id
            WHERE user_favorites.user_id = ?
            GROUP BY genres.id, genres.name
            HAVING cnt > 0
            ORDER BY cnt DESC, genres.name ASC
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('i', $userId);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC); // favGenreCounts -> pills in profile (Favorites)
    }

    /**
     * favorites grouped by movie country
     * Used in: db/recommendRequests.php (action: 'favCountryCounts'), 
     *          pages/profile.php (Favorites pills)
     */
    public function getFavCountsByCountry(int $userId): array
    {
        if ($userId <= 0) return [];
        $sql = "
            SELECT countries.id, countries.name, COUNT(*) AS cnt
            FROM user_favorites
            JOIN movies ON movies.id = user_favorites.movie_id
            JOIN countries ON countries.id = movies.country_id
            WHERE user_favorites.user_id = ? AND movies.country_id IS NOT NULL
            GROUP BY countries.id, countries.name
            HAVING cnt > 0
            ORDER BY cnt DESC, countries.name ASC
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('i', $userId);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC); // favCountryCounts -> pills in profile (Favorites)
    }

    /**
     * how many unique movies a user has rated/reviewed, grouped by genre
     * Used in: db/recommendRequests.php (action: 'ratedGenreCounts'), 
     *          pages/profile.php (Rated pills)
     */
    public function getRatedCountsByGenre(int $userId): array
    {
        if ($userId <= 0) return [];
        $sql = "
            SELECT genres.id, genres.name, COUNT(*) AS cnt
            FROM (
                SELECT DISTINCT ratings_reviews.movie_id FROM ratings_reviews WHERE ratings_reviews.user_id = ?
            ) user_movies
            JOIN movie_genres ON movie_genres.movie_id = user_movies.movie_id
            JOIN genres ON genres.id = movie_genres.genre_id
            GROUP BY genres.id, genres.name
            HAVING cnt > 0
            ORDER BY cnt DESC, genres.name ASC
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('i', $userId);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC); //ratedGenreCounts -> pills in profile (Rated)
    }

    /**
     * how many unique movies a user has rated/reviewed, grouped by the country
     * Used in: db/recommendRequests.php (action: 'ratedCountryCounts'), 
     *          pages/profile.php (Rated pills)
     */
    public function getRatedCountsByCountry(int $userId): array
    {
        if ($userId <= 0) return [];
        $sql = "
            SELECT countries.id, countries.name, COUNT(*) AS cnt
            FROM (
                SELECT DISTINCT ratings_reviews.movie_id FROM ratings_reviews WHERE ratings_reviews.user_id = ?
            ) user_movies
            JOIN movies ON movies.id = user_movies.movie_id
            JOIN countries ON countries.id = movies.country_id
            WHERE movies.country_id IS NOT NULL
            GROUP BY countries.id, countries.name
            HAVING cnt > 0
            ORDER BY cnt DESC, countries.name ASC
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('i', $userId);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC); // ratedCountryCounts -> pills in profile (Rated)
    }


    ////////////////////////////////////////////////////////////////////////////////////////
    ////////////////////////////////////////////////////////////////////////////////////////
    ////////////////////////////////////////////////////////////////////////////////////////
    ////////////////////////////////////////////////////////////////////////////////////////
    ////////////////////////////////////////////////////////////////////////////////////////
    /**
     * Movies related to a given movie (by genres)
     * Used in: db/recommendRequests.php (action: 'related'), 
     *          pages/viewMovie.php (related movies)
     * 
     * More shared genres first (atleast 1)
     * Movies with ratings come before unrated movies (IS NULL).
     * Highest average rating first.
     * Latest movies first.
     */

    public function getRelatedToMovie(int $movieId, int $limit = 12): array
    {
        if ($movieId <= 0) return [];
        $limit = $this->clampLimit($limit);

        $sql = "
            SELECT 
                movies.id,
                movies.title,
                movies.release_year,
                movies.poster_url,
                countries.name AS country_name,
                languages.name AS language_name,
                COUNT(DISTINCT movie_genres.genre_id) AS match_genres, 
                ROUND(COALESCE(AVG(ratings_reviews.rating), 0), 2) AS avg_rating,
                COUNT(ratings_reviews.id) AS total_reviews
            FROM movies
            JOIN movie_genres ON movie_genres.movie_id = movies.id
            JOIN movie_genres ref ON ref.genre_id = movie_genres.genre_id AND ref.movie_id = ?
            LEFT JOIN ratings_reviews ON ratings_reviews.movie_id = movies.id
            LEFT JOIN countries ON movies.country_id = countries.id
            LEFT JOIN languages ON movies.language_id = languages.id
            WHERE movies.id <> ?
            GROUP BY movies.id
            ORDER BY match_genres DESC, (AVG(ratings_reviews.rating) IS NULL), 
                                        AVG(ratings_reviews.rating) 
                                        DESC, movies.release_year DESC
            LIMIT ?
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('iii', $movieId, $movieId, $limit);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC); // related -> JSON for pages/viewMovie.php related grid
    }

    ////////////////////////////////////////////////////////////////////////////////////////
    ////////////////////////////////////////////////////////////////////////////////////////
    ////////////////////////////////////////////////////////////////////////////////////////
    ////////////////////////////////////////////////////////////////////////////////////////
    ////////////////////////////////////////////////////////////////////////////////////////
    
    //   Ensure the requested limit is within a safe range.
    //   - limit <= 0  => default to 12
    //   - limit > max => clamp down to max (default 48)
    private function clampLimit(int $limit, int $max = 48): int
    {
        if ($limit <= 0) $limit = 12;
        if ($limit > $max) $limit = $max;
        return $limit;
    }



















    ////////////////////////////////////////////////////////////////////////////////////////
    ////////////////////////////////////////////////////////////////////////////////////////
    ////////////////////////////////////////////////////////////////////////////////////////
    ////////////////////////////////////////////////////////////////////////////////////////
    ////////////////////////////////////////////////////////////////////////////////////////
    // Unused

    /**
     * Latest by release year
     * Used in: db/recommendRequests.php (action: 'latest')
     */
    public function getLatest(int $limit = 12): array
    {
        $limit = $this->clampLimit($limit);
        $sql = "
            SELECT 
                movies.id,
                movies.title,
                movies.release_year,
                movies.poster_url,
                countries.name AS country_name,
                languages.name AS language_name,
                ROUND(COALESCE(AVG(ratings_reviews.rating), 0), 2) AS avg_rating,
                COUNT(ratings_reviews.id) AS total_reviews
            FROM movies
            LEFT JOIN ratings_reviews ON ratings_reviews.movie_id = movies.id
            LEFT JOIN countries ON movies.country_id = countries.id
            LEFT JOIN languages ON movies.language_id = languages.id
            GROUP BY movies.id
            ORDER BY movies.release_year DESC, movies.id DESC
            LIMIT ?
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('i', $limit);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC); 
    }

    /**
     * Get overall top genres across all users
     * Used in: db/recommendRequests.php (action: 'topGenres')
     * 
     * If not logged in:
     * - Show popular genres based on overall favorites in movie
     */
    public function getTopGenresOverall(int $limit = 5): array
    {
        $limit = $this->clampLimit($limit, 10);

        $sql = "
        SELECT genres.id, genres.name, COUNT(*) AS cnt
        FROM user_favorites
        JOIN movie_genres ON movie_genres.movie_id = user_favorites.movie_id
        JOIN genres ON genres.id = movie_genres.genre_id
        GROUP BY genres.id, genres.name
        ORDER BY cnt DESC
        LIMIT ?
    ";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('i', $limit);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC); 
    }


}
