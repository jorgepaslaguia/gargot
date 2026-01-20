<?php
$host = "localhost";
$usuario = "root";
$clave = "";
$bd = "gargot";

$dsn = "mysql:host={$host};dbname={$bd};charset=utf8mb4";

try {
    $pdo = new PDO($dsn, $usuario, $clave, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
} catch (PDOException $e) {
    error_log("DB connection failed: " . $e->getMessage());
    http_response_code(500);
    exit("Database connection error.");
}
?>
