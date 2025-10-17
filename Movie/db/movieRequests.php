<?php
session_start();
require_once __DIR__ . '/../classes/Movie.php';

$movie = new Movie();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $genre_Ids = $_POST['genres'] ?? [];

    switch ($action) {
        case 'add':
            $movie->addMovie(
                $_POST['title'],
                $_POST['description'],
                $_POST['release_year'],
                $_FILES['poster_file'],
                $_FILES['background_file'] ?? [ 'error' => UPLOAD_ERR_NO_FILE ],
                $_POST['trailer_url'],
                $_POST['countryName'],
                $_POST['languageName'],
                $genre_Ids
            );
            header("Location: ../index.php?success=movie_added");
            exit;

        case 'update':
            $movie->updateMovie(
                $_POST['id'],
                $_POST['title'],
                $_POST['description'],
                $_POST['release_year'],
                $_FILES['poster_file'], 
                $_FILES['background_file'] ?? [ 'error' => UPLOAD_ERR_NO_FILE ],
                $_POST['trailer_url'],
                $_POST['countryName'],
                $_POST['languageName'],
                $genre_Ids
            );
            header("Location: ../index.php?success=movie_updated");
            exit;

        case 'delete':
            $movie->deleteMovie($_POST['id']);
            header("Location: ../index.php?success=movie_deleted");
            exit;
    }
}
