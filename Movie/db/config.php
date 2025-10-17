<?php
class Database {
    private $servername = 'localhost';
    private $username = 'root';
    private $password = '';
    private $db_name = 'movie_recosys';
    public $conn;

    public function __construct(){
        try{
            $this->conn = new mysqli($this->servername, 
                                    $this->username, 
                                    $this->password, 
                                    $this->db_name);
            $this->conn->set_charset("utf8mb4");
        }catch(Exception $e){
            die("Database connection error. <br> " . $e);
        }
    }
}
?>