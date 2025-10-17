<?php
session_start();
require_once __DIR__ . '/../classes/RateReview.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../pages/loginRegister.php");
    exit;
}

if (!isset($_SESSION['user_id']) || (isset($_SESSION['role']) && $_SESSION['role'] !== 'user')) {
    echo "Only users can submit reviews.";
    exit;
}

$rate = new RateReview();

function respond($success, $data = null, $message = null, $code = 200)
{
    http_response_code($code);
    echo json_encode([
        'success' => $success,
        'data'    => $data,
        'message' => $message
    ]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');

    $movieId = isset($_POST['movie_id']) ? (int)$_POST['movie_id'] : 0;
    $rating = isset($_POST['rating']) ? (int)$_POST['rating'] : 0;
    $review = trim($_POST['review'] ?? '');
    $userId = (int)$_SESSION['user_id'];

    if ($movieId <= 0 || $rating < 1 || $rating > 5 || $review === '') {
        echo json_encode([ 'success' => false, 'message' => 'Invalid input.' ]);
        exit;
    }

        $reviewAdded = $rate->addReview(
                $userId, 
                $movieId, 
                $rating, 
                $review);
        if ($reviewAdded) {
            $avg = $rate->getAverageRating($movieId);
            respond(true, 
                    ['average' => $avg], 
                    'Review submitted.');
        }
        respond(false, 
                null, 
                'Failed to submit review.');
}
