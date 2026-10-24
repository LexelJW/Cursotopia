<?php
ini_set('display_errors', 0);
error_reporting(0);
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once __DIR__ . '/controllers/ApiController.php';

$action = isset($_GET['action']) ? $_GET['action'] : '';
$method = $_SERVER['REQUEST_METHOD'];

$controller = new ApiController();

if ($action === 'usuarios') {
    if ($method === 'POST') {
        $controller->registrarUsuario();
    } else if ($method === 'GET') {
        $controller->obtenerUsuarios();
    } else {
        http_response_code(405);
        echo json_encode(["status" => 405, "error" => "Método HTTP no permitido"]);
    }
} else if ($action === 'login') {
    if ($method === 'POST') {
        $controller->loginUsuario();
    } else {
        http_response_code(405);
        echo json_encode(["status" => 405, "error" => "Método HTTP no permitido"]);
    }
} else if ($action === 'pago-stripe') {
    if ($method === 'POST') {
        $controller->procesarPagoStripe();
    } else {
        http_response_code(405);
        echo json_encode(["status" => 405, "error" => "Método HTTP no permitido"]);
    }
} else if ($action === 'cursos') {
    $controller->obtenerCursos();
} else {
    http_response_code(404);
    echo json_encode(["status" => 404, "error" => "Endpoint no encontrado"]);
}