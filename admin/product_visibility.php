<?php
session_start();
if (!isset($_SESSION["id_usuario"]) || $_SESSION["rol"] !== "admin") {
    header("Location: ../login.php");
    exit();
}

require_once "../includes/conexion.php";

$id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
$visible = isset($_POST['visible']) ? (int)$_POST['visible'] : 0;

// CSRF
if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== ($_SESSION['csrf_token'] ?? '')) {
    header("Location: products.php");
    exit();
}

// Check column
$colCheck = $conexion->query("SHOW COLUMNS FROM productos LIKE 'is_visible'");
if (!$colCheck || $colCheck->num_rows === 0) {
    header("Location: products.php");
    exit();
}

if ($id > 0 && ($visible === 0 || $visible === 1)) {
    $stmt = $conexion->prepare("UPDATE productos SET is_visible = ? WHERE id_producto = ?");
    $stmt->bind_param("ii", $visible, $id);
    $stmt->execute();
    $stmt->close();
}

header("Location: products.php");
exit();
