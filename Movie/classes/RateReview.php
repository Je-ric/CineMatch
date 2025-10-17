<?php
require_once __DIR__ . '/../db/config.php';

class RateReview
{
    private $db;

    public function __construct()
    {
        $database = new Database();
        $this->db = $database->conn;
    }

    public function getReviewsByMovie($movieId)
    {
        $sql = "
            SELECT ratings_reviews.rating,
                ratings_reviews.review,
                ratings_reviews.created_at,
                users.username
            FROM ratings_reviews
            JOIN users ON ratings_reviews.user_id = users.id
            WHERE ratings_reviews.movie_id = ?
            ORDER BY ratings_reviews.created_at DESC
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("i", $movieId);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC); // pages/viewMovie.php ($reviews) - all reviews
    }

    public function addReview($userId, $movieId, $rating, $review)
    {
        $sql = "
            INSERT INTO ratings_reviews (user_id, movie_id, rating, review)
            VALUES (?, ?, ?, ?)
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("iiis", $userId, $movieId, $rating, $review);
        return $stmt->execute(); // boolean para sa success/failure response for AJAX
    }

    public function hasReviewed($userId, $movieId)
    {
        $sql = "
            SELECT ratings_reviews.id
            FROM ratings_reviews
            WHERE ratings_reviews.user_id = ? AND ratings_reviews.movie_id = ?
            LIMIT 1
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("ii", $userId, $movieId);
        $stmt->execute();
        return $stmt->get_result()->num_rows > 0; 
        // pages/viewMovie.php - controls "Leave a Review" button and skipping user's review in the list
        // inshort hide yung button if review na
    }

    public function getUserReview($userId, $movieId)
    {
        $sql = "
            SELECT ratings_reviews.rating, ratings_reviews.review
            FROM ratings_reviews
            WHERE ratings_reviews.user_id = ? AND ratings_reviews.movie_id = ?
            LIMIT 1
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("ii", $userId, $movieId);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc(); // pages/viewMovie.php - for own user review card
    }

    public function getAverageRating($movieId)
    {
        $sql = "
        SELECT AVG(ratings_reviews.rating) AS avg_rating, COUNT(*) AS total
        FROM ratings_reviews
        WHERE ratings_reviews.movie_id = ?
    ";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("i", $movieId);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();

        // Format average rating as float or null if no ratings
        $avg = $result['avg_rating'] ? floatval($result['avg_rating']) : null;
        $total = intval($result['total']);

        return ['avg' => $avg, 'total' => $total]; // pages/viewMovie.php ($ratingInfo) and para sa AJAX response
    }
}
