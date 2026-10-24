<?php
class Database {
    private $host = "localhost";
    private $port = "3307"; 
    private $db_name = "portal_cursos";
    private $username = "root";
    private $password = "";
    public $conn;

    public function getConnection() {
        $this->conn = null;
        try {
            $this->conn = new PDO("mysql:host=" . $this->host . ";port=" . $this->port . ";dbname=" . $this->db_name, $this->username, $this->password);
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->conn->exec("set names utf8");
        } catch(PDOException $exception) {
            echo json_encode(["error" => "Error de conexión: " . $exception->getMessage()]);
            exit();
        }
        return $this->conn;
    }
}
?>