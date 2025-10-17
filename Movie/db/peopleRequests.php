<?php
session_start();
require_once __DIR__ . '/../classes/People.php';

header('Content-Type: application/json; charset=utf-8');

// Small helper to send JSON and exit
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

// Allow POST only
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    respond(false, null, 'Only POST is allowed', 405);
}

// Read inputs
$action  = $_POST['action'] ?? '';
$movieId = isset($_POST['movie_id']) ? (int)$_POST['movie_id'] : 0;
$role    = $_POST['role'] ?? '';
$name    = isset($_POST['name']) ? trim($_POST['name']) : '';
$id      = isset($_POST['id']) ? (int)$_POST['id'] : 0;
$query   = isset($_POST['query']) ? trim($_POST['query']) : '';

$people = new People();

switch ($action) {
    // Get people for a movie by role
    case 'fetch': {
        if ($movieId <= 0 || ($role !== 'Director' && $role !== 'Cast')) {
            respond(false, null, 'Invalid params', 400);
        }
        $data = $people->getMoviePeople($movieId, $role);
        respond(true, $data);
    }

    // Add a person and attach to a movie
    case 'add': {
        if ($movieId <= 0 || $name === '' || !in_array($role, ['Director', 'Cast'], true)) {
            respond(false, null, 'Invalid input', 400);
        }

        $personId = $people->addPerson($name);
        if ($personId <= 0) {
            respond(false, null, 'Failed to create person', 500);
        }

        $linked = $people->attachToMovie($movieId, $personId, $role);
        if (!$linked) {
            respond(false, null, 'This person is already added as ' . strtolower($role));
        }

        respond(true, ['person_id' => $personId], ucfirst(strtolower($role)) . ' added successfully');
    }

    // Remove a person from a movie
    case 'remove': {
        if ($id <= 0) {
            respond(false, null, 'Invalid link ID', 400);
        }
        $deleted = $people->removeFromMovie($id);
        if (!$deleted) {
            respond(false, null, 'Failed to remove person');
        }
        respond(true, null, 'Person removed successfully');
    }

    // Search people by name (for autocomplete)
    case 'search': {
        if ($query === '') {
            respond(true, []);
        }
        $results = $people->search($query) ?? [];
        respond(true, $results);
    }

    default:
        respond(false, null, 'Unknown action', 400);
}
