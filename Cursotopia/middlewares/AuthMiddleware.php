<?php
class AuthMiddleware {
    public static function verificarSesion() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (!isset($_SESSION['user_id'])) {
            http_response_code(401); 
            echo json_encode([
                "status" => 401,
                "error" => "Acceso no autorizado. Debe iniciar sesión."
            ]);
            exit();
        }
    }
}
?>