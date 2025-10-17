<?php
session_start();
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../classes/Favorite.php';

$userId  = $_SESSION['user_id'] ?? 0;
$action  = $_POST['action'] ?? '';
$movieId = isset($_POST['movie_id']) ? (int)$_POST['movie_id'] : 0;

try {
    if ($userId <= 0) {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Login required']);
        exit;
    }
    if ($movieId <= 0) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid movie id']);
        exit;
    }

    $fav = new Favorite();

    switch ($action) {
        case 'toggle':
            $favorited = $fav->toggleFavorite($userId, $movieId);
            $count = $fav->countByMovie($movieId);
            echo json_encode(['success' => true, 'favorited' => $favorited, 'count' => $count]);
            break;
        default:
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Unknown action']);
    }
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}