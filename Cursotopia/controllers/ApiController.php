<?php
class ApiController {
    private function getDB() {
        try {
            $db = new PDO("mysql:host=localhost;port=3307;dbname=portal_cursos;charset=utf8", "root", "");
            $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            return $db;
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(["status" => 500, "error" => "Error de conexión a la BD: " . $e->getMessage()]);
            exit();
        }
    }

    private function verificarAccesoRol($rolRequerido) {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (!isset($_SESSION['usuario']) || !isset($_SESSION['rol'])) {
            http_response_code(401);
            echo json_encode([
                "status" => 401,
                "error" => "Acceso denegado. Se requieren credenciales válidas."
            ]);
            exit();
        }

        if ($_SESSION['rol'] !== $rolRequerido && $_SESSION['rol'] !== 'administrador') {
            http_response_code(403);
            echo json_encode([
                "status" => 403,
                "error" => "Permisos insuficientes. No cuentas con el rol requerido."
            ]);
            exit();
        }
    }

    public function registrarUsuario() {
        $input = json_decode(file_get_contents('php://input'), true);

        if (!$input || empty($input['nombre']) || empty($input['email']) || empty($input['password'])) {
            http_response_code(400);
            echo json_encode(["status" => 400, "error" => "Faltan datos obligatorios"]);
            return;
        }

        $nombre = $input['nombre'];
        $email = $input['email'];
        $password = password_hash($input['password'], PASSWORD_DEFAULT);
        $rol = isset($input['rol']) ? $input['rol'] : 'student';

        try {
            $db = $this->getDB();
            
            $stmtCheck = $db->prepare("SELECT id FROM usuarios WHERE email = ?");
            $stmtCheck->execute([$email]);
            if ($stmtCheck->rowCount() > 0) {
                http_response_code(400);
                echo json_encode(["status" => 400, "error" => "El correo ya está registrado"]);
                return;
            }

            $stmt = $db->prepare("INSERT INTO usuarios (nombre, email, password, rol, intentos_fallidos, bloqueado) VALUES (?, ?, ?, ?, 0, 0)");
            $stmt->execute([$nombre, $email, $password, $rol]);

            http_response_code(200);
            echo json_encode(["status" => 200, "mensaje" => "Usuario registrado con éxito"]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(["status" => 500, "error" => $e->getMessage()]);
        }
    }

    public function loginUsuario() {
        $input = json_decode(file_get_contents('php://input'), true);

        if (!$input || empty($input['email']) || empty($input['password'])) {
            http_response_code(400);
            echo json_encode(["status" => 400, "error" => "Faltan datos obligatorios"]);
            return;
        }

        $email = $input['email'];
        $password = $input['password'];

        try {
            $db = $this->getDB();
            
            $stmt = $db->prepare("SELECT * FROM usuarios WHERE email = ?");
            $stmt->execute([$email]);
            $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$usuario) {
                http_response_code(401);
                echo json_encode(["status" => 401, "error" => "Correo o contraseña incorrectos"]);
                return;
            }

            if ($usuario['bloqueado'] == 1) {
                http_response_code(403);
                echo json_encode(["status" => 403, "error" => "Su cuenta está bloqueada por múltiples intentos fallidos. Contacte al administrador."]);
                return;
            }

            if (password_verify($password, $usuario['password'])) {
                if (session_status() === PHP_SESSION_NONE) {
                    session_start();
                }
                $_SESSION['usuario'] = $usuario['email'];
                $_SESSION['rol'] = $usuario['rol'];

                $stmtReset = $db->prepare("UPDATE usuarios SET intentos_fallidos = 0 WHERE id = ?");
                $stmtReset->execute([$usuario['id']]);

                http_response_code(200);
                echo json_encode([
                    "status" => 200, 
                    "mensaje" => "Login exitoso",
                    "usuario" => [
                        "name" => $usuario['nombre'],
                        "email" => $usuario['email'],
                        "role" => $usuario['rol']
                    ]
                ]);
            } else {
                $intentos = $usuario['intentos_fallidos'] + 1;
                
                if ($intentos >= 3) {
                    $stmtBlock = $db->prepare("UPDATE usuarios SET intentos_fallidos = ?, bloqueado = 1 WHERE id = ?");
                    $stmtBlock->execute([$intentos, $usuario['id']]);

                    http_response_code(403);
                    echo json_encode(["status" => 403, "error" => "Contraseña incorrecta. Ha superado 3 intentos; su cuenta ha sido bloqueada."]);
                } else {
                    $stmtUpdate = $db->prepare("UPDATE usuarios SET intentos_fallidos = ? WHERE id = ?");
                    $stmtUpdate->execute([$intentos, $usuario['id']]);

                    http_response_code(401);
                    echo json_encode(["status" => 401, "error" => "Correo o contraseña incorrectos. Intentos fallidos: " . $intentos . "/3"]);
                }
            }
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(["status" => 500, "error" => $e->getMessage()]);
        }
    }

    public function panelProtegido() {
        $this->verificarAccesoRol('student');
        
        http_response_code(200);
        echo json_encode([
            "status" => 200,
            "mensaje" => "Acceso autorizado al panel protegido por middleware."
        ]);
    }

    public function procesarPagoStripe() {
        $input = json_decode(file_get_contents('php://input'), true);

        if (!$input || empty($input['monto']) || empty($input['curso_id'])) {
            http_response_code(400);
            echo json_encode(["status" => 400, "error" => "Datos de pago incompletos"]);
            return;
        }

        $transaccion_id = "pi_" . uniqid();
        
        http_response_code(200);
        echo json_encode([
            "status" => 200,
            "pasarela" => "Stripe",
            "mensaje" => "Pago procesado y confirmado exitosamente",
            "transaccion" => [
                "id" => $transaccion_id,
                "curso_id" => $input['curso_id'],
                "monto" => $input['monto'],
                "moneda" => "MXN",
                "estado" => "succeeded",
                "fecha" => date("d M Y H:i:s")
            ]
        ]);
    }

    public function obtenerUsuarios() {
        try {
            $db = $this->getDB();
            $stmt = $db->query("SELECT id, nombre, email, rol, bloqueado FROM usuarios");
            $usuarios = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode($usuarios);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(["status" => 500, "error" => $e->getMessage()]);
        }
    }

    public function obtenerCursos() {
        $cursos = [
            ["id" => 101, "titulo" => "Introducción al Dibujo", "precio" => 450.00, "instructor" => "Prof. Karim Pérez"],
            ["id" => 102, "titulo" => "Bases de Datos Multimedia", "precio" => 650.00, "instructor" => "Prof. Juan Montoya"]
        ];
        echo json_encode($cursos);
    }
}