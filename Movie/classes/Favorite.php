<?php
require_once __DIR__ . '/../db/config.php';

class Favorite {
    private $conn;

    public function __construct() {
        $db = new Database();
        $this->conn = $db->conn;
    }

    // to check if already favorited of user
    // 1 because we only need to know if it exists
    public function isFavorited(int $userId, int $movieId): bool {
        if ($userId <= 0 || $movieId <= 0) return false;
        $sql = "SELECT 1 FROM user_favorites WHERE user_id = ? AND movie_id = ? LIMIT 1";
        $stmt = $this->conn->prepare($sql);
        if (!$stmt) return false;
        $stmt->bind_param('ii', $userId, $movieId);
        $stmt->execute();
        $stmt->store_result();
        $exists = $stmt->num_rows > 0;
        $stmt->close();
        return $exists; 
    }

    // add
    // on duplicate because we want to avoid errors if the entry already exists
    public function addFavorite(int $userId, int $movieId): bool {
        if ($userId <= 0 || $movieId <= 0) return false; // invalid input (malabong mangyare, but incase)
        $sql = "INSERT INTO user_favorites (user_id, movie_id) 
                VALUES (?, ?)
                ON DUPLICATE KEY UPDATE user_id = user_id";
        $stmt = $this->conn->prepare($sql);
        if (!$stmt) return false; // returns to toggleFavorite()
        $stmt->bind_param('ii', $userId, $movieId);
        $ok = $stmt->execute();
        $stmt->close();
        return $ok; //  toggleFavorite() to confirm add
    }

    // remove
    public function removeFavorite(int $userId, int $movieId): bool {
        if ($userId <= 0 || $movieId <= 0) return false;
        $sql = "DELETE FROM user_favorites WHERE user_id = ? AND movie_id = ?";
        $stmt = $this->conn->prepare($sql);
        if (!$stmt) return false; // returns to toggleFavorite()
        $stmt->bind_param('ii', $userId, $movieId);
        $ok = $stmt->execute();
        $stmt->close();
        return $ok; // toggleFavorite() to confirm remove
    }

    // flip the state, favorited or unfavorited
    public function toggleFavorite(int $userId, int $movieId): bool {
        if ($this->isFavorited($userId, $movieId)) {
            $this->removeFavorite($userId, $movieId);
            return false; // JSON "favorited": false (unfavorited)
        } else {
            $this->addFavorite($userId, $movieId);
            return true; // JSON "favorited": true (favorited)
        }
    }

    // get all favorite per movie using movie_id
    public function countByMovie(int $movieId): int {
        if ($movieId <= 0) return 0;
        $sql = "SELECT COUNT(*) AS cnt FROM user_favorites WHERE movie_id = ?";
        $stmt = $this->conn->prepare($sql);
        if (!$stmt) return 0;
        $stmt->bind_param('i', $movieId);
        $stmt->execute();
        $res = $stmt->get_result();
        $row = $res->fetch_assoc() ?: ['cnt' => 0];
        $stmt->close();
        return (int)$row['cnt'];
    }
}