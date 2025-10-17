<?php
require_once __DIR__ . '/../db/config.php';

class Movie
{
    private $db;
    private $uploadDir;
    private $backgroundUploadDir;

    public function __construct()
    {
        $database = new Database();
        $this->db = $database->conn;

        // create if not existing
        $this->uploadDir = __DIR__ . '/../uploads/posters/';
        if (!is_dir($this->uploadDir)) mkdir($this->uploadDir, 0777, true);

        $this->backgroundUploadDir = __DIR__ . '/../uploads/backgrounds/';
        if (!is_dir($this->backgroundUploadDir)) mkdir($this->backgroundUploadDir, 0777, true);
    }

    public function getAllMovies()
    {
        $sql = "
            SELECT movies.*, countries.name AS country_name, languages.name AS language_name,
                    AVG(ratings_reviews.rating) AS avg_rating, COUNT(ratings_reviews.id) AS total_reviews,
                    GROUP_CONCAT(DISTINCT genres.id) AS genre_ids,
                    GROUP_CONCAT(DISTINCT genres.name) AS genre_names
            FROM movies
            LEFT JOIN ratings_reviews ON ratings_reviews.movie_id = movies.id
            LEFT JOIN countries ON movies.country_id = countries.id
            LEFT JOIN languages ON movies.language_id = languages.id
            LEFT JOIN movie_genres ON movie_genres.movie_id = movies.id
            LEFT JOIN genres ON genres.id = movie_genres.genre_id
            GROUP BY movies.id
            ORDER BY movies.release_year DESC
        ";
        $result = $this->db->query($sql);
        return $result->fetch_all(MYSQLI_ASSOC);
    }

    public function getMovieById($id)
    {
        $sql = "
            SELECT movies.*, countries.name AS country_name, languages.name AS language_name
            FROM movies
            LEFT JOIN countries ON movies.country_id = countries.id
            LEFT JOIN languages ON movies.language_id = languages.id
            WHERE movies.id = ?
        ";
        $query = $this->db->prepare($sql);
        $query->bind_param("i", $id);
        $query->execute();
        $result = $query->get_result();
        return $result->fetch_assoc();
    }

    // ==========================
    // manageMovie.php
    // =========================== 
    public function addMovie($title, $description, $releaseYear, $posterFile, $backgroundFile, $trailerUrl, $countryName, $languageName, $genreIds = [])
    {
        $countryId = $this->getCountryId($countryName);
        $languageId = $this->getLanguageId($languageName);
        $posterPath = $this->handleUpload($posterFile, $title, $releaseYear);
        $backgroundPath = (isset($backgroundFile['error']) && $backgroundFile['error'] === UPLOAD_ERR_OK)
            ? $this->handleUploadTo($backgroundFile, $title, $releaseYear, 'backgrounds')
            : null;

        $sql = "
            INSERT INTO movies (title, description, release_year,
                                poster_url, background_url, trailer_url, 
                                country_id, language_id)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ";
        $query = $this->db->prepare($sql);
        $query->bind_param("ssisssii", $title, $description, $releaseYear, 
                                    $posterPath, $backgroundPath, $trailerUrl, 
                                    $countryId, $languageId);
        $query->execute();

        $movieId = $query->insert_id;
        if (!empty($genreIds)) $this->updateMovieGenres($movieId, $genreIds);

        return $movieId;
    }

    public function updateMovie($id, $title, $description, $releaseYear, 
                                $posterFile, $backgroundFile, $trailerUrl, 
                                $countryName, $languageName, $genreIds = [])
    {
        $countryId = $this->getCountryId($countryName);
        $languageId = $this->getLanguageId($languageName);
        $current = $this->getMovieById($id);

        // Determine poster path; if a new poster is provided, upload and delete old
        $posterPath = $current['poster_url'];
        if ($posterFile['error'] === UPLOAD_ERR_OK) {
            $newPosterPath = $this->handleUpload($posterFile, $title, $releaseYear);
            if ($newPosterPath) {
                if (!empty($current['poster_url']) && $current['poster_url'] !== $newPosterPath) {
                    $oldPosterFs = __DIR__ . '/../' . $current['poster_url'];
                    if (file_exists($oldPosterFs)) @unlink($oldPosterFs);
                }
                $posterPath = $newPosterPath;
            }
        }

        // Determine background path; if a new background is provided, upload and delete old
        $backgroundPath = $current['background_url'] ?? null;
        if (isset($backgroundFile['error']) && $backgroundFile['error'] === UPLOAD_ERR_OK) {
            $newBgPath = $this->handleUploadTo($backgroundFile, $title, $releaseYear, 'backgrounds');
            if ($newBgPath) {
                if (!empty($current['background_url']) && $current['background_url'] !== $newBgPath) {
                    $oldBgFs = __DIR__ . '/../' . $current['background_url'];
                    if (file_exists($oldBgFs)) @unlink($oldBgFs);
                }
                $backgroundPath = $newBgPath;
            }
        }

        $sql = "
            UPDATE movies 
            SET title=?, description=?, release_year=?, 
                poster_url=?, background_url=?, trailer_url=?, 
                country_id=?, language_id=?
            WHERE id=?
        ";
        $query = $this->db->prepare($sql);
        $query->bind_param("ssisssiii", $title, $description, $releaseYear, 
                                        $posterPath, $backgroundPath, $trailerUrl,
                                        $countryId, $languageId, $id);
        $query->execute();

        $this->updateMovieGenres($id, $genreIds);
        return true;
    }

    public function deleteMovie($id)
    {
        $movie = $this->getMovieById($id);

        if ($movie && !empty($movie['poster_url']) && file_exists(__DIR__ . '/../' . $movie['poster_url'])) {
            unlink(__DIR__ . '/../' . $movie['poster_url']);
        }
        if ($movie && !empty($movie['background_url']) && file_exists(__DIR__ . '/../' . $movie['background_url'])) {
            unlink(__DIR__ . '/../' . $movie['background_url']);
        }

        $sql = "DELETE FROM movies WHERE id=?";
        $query = $this->db->prepare($sql);
        $query->bind_param("i", $id);
        return $query->execute();
    }

    // ==========================
    // FILE UPLOAD HANDLER 
    // ===========================
    private function handleUpload($uploadedFile, $movieTitle, $releaseYear)
    {
        $safeTitle = preg_replace("/[^a-zA-Z0-9]/", "_", strtolower($movieTitle));
        $fileExtension = pathinfo($uploadedFile['name'], PATHINFO_EXTENSION);
        $newFileName = $safeTitle . '_' . $releaseYear . '.' . $fileExtension;

        $destinationPath = $this->uploadDir . $newFileName;
        if (move_uploaded_file($uploadedFile['tmp_name'], $destinationPath)) {
            return 'uploads/posters/' . $newFileName;
        }
        return null;
    }

    // delete poster and background, when movie deleted
    // remove the old images if something new (update) is uploaded
    private function handleUploadTo($uploadedFile, $movieTitle, $releaseYear, $subfolder)
    {
        $safeTitle = preg_replace("/[^a-zA-Z0-9]/", "_", strtolower($movieTitle));
        $fileExtension = pathinfo($uploadedFile['name'], PATHINFO_EXTENSION);
        $newFileName = $safeTitle . '_' . $releaseYear . '.' . $fileExtension;

        $subfolder = trim($subfolder, '/');
        $targetDir = __DIR__ . '/../uploads/' . $subfolder . '/';
        if (!is_dir($targetDir)) mkdir($targetDir, 0777, true);

        $destinationPath = $targetDir . $newFileName;
        if (move_uploaded_file($uploadedFile['tmp_name'], $destinationPath)) {
            return 'uploads/' . $subfolder . '/' . $newFileName;
        }
        return null;
    }

    // ==========================
    // Getter
    // ===========================
    public function getAllGenres()
    {
        $sql = "SELECT * FROM genres ORDER BY name ASC";
        $result = $this->db->query($sql);

        if ($result) {
            return $result->fetch_all(MYSQLI_ASSOC);
        }

        return [];
    }

    public function getGenresByMovie($movieId, $return = 'name')
    {
        // $return = 'name' or 'id'
                $sql = "
                    SELECT genres.id, genres.name
                    FROM movie_genres
                    JOIN genres ON movie_genres.genre_id = genres.id
                    WHERE movie_genres.movie_id = ?
            ";
        $query = $this->db->prepare($sql);
        $query->bind_param("i", $movieId);
        $query->execute();
        $result = $query->get_result();

        $genres = [];
        while ($row = $result->fetch_assoc()) {
            if ($return === 'id') {
                $genres[] = $row['id'];
            } else {
                $genres[] = $row['name'];
            }
        }
        return $genres;
    }

    public function updateMovieGenres($movieId, $genreIds)
    {
        $sql = "DELETE FROM movie_genres WHERE movie_id = ?";
        $query = $this->db->prepare($sql);
        $query->bind_param("i", $movieId);
        $query->execute();

        $sql = "INSERT INTO movie_genres (movie_id, genre_id) VALUES (?, ?)";
        $query = $this->db->prepare($sql);
        foreach ($genreIds as $genreId) {
            $query->bind_param("ii", $movieId, $genreId);
            $query->execute();
        }
    }

    private function getCountryId($countryName)
    {
        $sql = "SELECT id FROM countries WHERE name = ?";
        $query = $this->db->prepare($sql);
        $query->bind_param("s", $countryName);
        $query->execute();
        $result = $query->get_result();

        if ($row = $result->fetch_assoc()) return $row['id'];

        $sql = "INSERT INTO countries (name) VALUES (?)";
        $query = $this->db->prepare($sql);
        $query->bind_param("s", $countryName);
        $query->execute();
        return $query->insert_id;
    }

    private function getLanguageId($languageName)
    {
        $sql = "SELECT id FROM languages WHERE name = ?";
        $query = $this->db->prepare($sql);
        $query->bind_param("s", $languageName);
        $query->execute();
        $result = $query->get_result();

        if ($row = $result->fetch_assoc()) return $row['id'];

        $sql = "INSERT INTO languages (name) VALUES (?)";
        $query = $this->db->prepare($sql);
        $query->bind_param("s", $languageName);
        $query->execute();
        return $query->insert_id;
    }
}
