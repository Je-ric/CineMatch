<?php
session_start();
require_once __DIR__ . '/../classes/Authentication.php';

$auth = new Authentication();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    switch ($action) {
        case 'register':
            $username = trim($_POST['username']);
            $password = trim($_POST['password']);
            if ($auth->registerUser($username, $password)) {
                header("Location: ../index.php");
                exit;
            } else {
                header("Location: ../pages/loginRegister.php?error=register_failed");
                exit;
            }
            // break;

        case 'login':
            $username = trim($_POST['username']);
            $password = trim($_POST['password']);
            if ($auth->loginUser($username, $password)) {
                header("Location: ../index.php");
                exit;
            } else {
                header("Location: ../pages/loginRegister.php?error=login_failed");
                exit;
            }
            // break;

        case 'logout':
            $auth->logoutUser();
            header("Location: ../pages/loginRegister.php");
            exit;
    }
}
