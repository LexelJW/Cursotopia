<?php
class User {
    private $conn;
    private $table_name = "usuarios";

    public function __construct($db) {
        $this->conn = $db;
    }

    public function registrar($nombre, $email, $password, $rol) {
        $query = "INSERT INTO " . $this->table_name . " (nombre, email, password, rol) VALUES (?, ?, ?, ?)";
        $stmt = $this->conn->prepare($query);

        $passwordHash = password_hash($password, PASSWORD_BCRYPT);

        $stmt->bindParam(1, $nombre);
        $stmt->bindParam(2, $email);
        $stmt->bindParam(3, $passwordHash);
        $stmt->bindParam(4, $rol);

        if($stmt->execute()) {
            return true;
        }
        return false;
    }

    public function login($email, $password) {
        $query = "SELECT id, nombre, email, password, activo, intentos_fallidos, rol FROM " . $this->table_name . " WHERE email = ? LIMIT 0,1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $email);
        $stmt->execute();

        if($stmt->rowCount() > 0) {
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if($row['activo'] == 0) {
                return ["status" => 403, "message" => "Tu cuenta está bloqueada por múltiples intentos fallidos."];
            }

            if(password_verify($password, $row['password'])) {
                $this->resetIntentos($row['id']);
                return ["status" => 200, "message" => "Login exitoso", "user" => $row];
            } else {
                $this->incrementarIntento($row['id'], $row['intentos_fallidos']);
                return ["status" => 401, "message" => "Contraseña incorrecta."];
            }
        }
        return ["status" => 404, "message" => "El usuario no existe."];
    }

    private function incrementarIntento($id, $intentosActuales) {
        $nuevosIntentos = $intentosActuales + 1;
        $activo = ($nuevosIntentos >= 3) ? 0 : 1; 

        $query = "UPDATE " . $this->table_name . " SET intentos_fallidos = ?, activo = ? WHERE id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $nuevosIntentos);
        $stmt->bindParam(2, $activo);
        $stmt->bindParam(3, $id);
        $stmt->execute();
    }

    private function resetIntentos($id) {
        $query = "UPDATE " . $this->table_name . " SET intentos_fallidos = 0 WHERE id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $id);
        $stmt->execute();
    }
}
?>