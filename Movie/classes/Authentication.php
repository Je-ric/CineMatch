<?php
require_once __DIR__ . '/../db/config.php';

class Authentication {
    private $db;

    public function __construct() {
        $database = new Database();
        $this->db = $database->conn;
    }

    public function registerUser($username, $password, $role = 'user') {
        $stmt = $this->db->prepare("SELECT id FROM users WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            return false; // username exists
        }

        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $this->db->prepare("INSERT INTO users (username, password, role) VALUES (?, ?, ?)");
        $stmt->bind_param("sss", $username, $hashed_password, $role);   

        return $stmt->execute();
    }

    public function loginUser($username, $password) {
        $stmt = $this->db->prepare("SELECT id, username, password, role FROM users WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();

            if (password_verify($password, $row['password'])) {
                $_SESSION['user_id'] = $row['id'];
                $_SESSION['username'] = $row['username'];
                $_SESSION['role'] = $row['role'];

                return true;
            } else {
                return false; // wrong pass
            }
        } else {
            return false; // not found
        }
    }


    public function logoutUser() {
        session_destroy();
    }
}
