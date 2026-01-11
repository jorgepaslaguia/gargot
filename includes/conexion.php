<?php
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

$host = "localhost";
$usuario = "root";
$clave = "";
$bd = "gargot";

try {
    $conexion = new mysqli($host, $usuario, $clave, $bd);
    $conexion->set_charset("utf8mb4");
} catch (mysqli_sql_exception $e) {
    error_log("DB connection failed: " . $e->getMessage());
    http_response_code(500);
    exit("Database connection error.");
}
?>
