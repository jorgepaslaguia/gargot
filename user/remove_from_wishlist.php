<?php
session_start();
include("../includes/conexion.php");

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: wishlist.php");
    exit();
}

if (empty($_SESSION['csrf_token']) || ($_POST['csrf_token'] ?? '') !== $_SESSION['csrf_token']) {
    header("Location: wishlist.php");
    exit();
}

if (!isset($_SESSION["id_usuario"])) {
    header("Location: ../login.php");
    exit();
}

if (!isset($_POST["id"])) {
    header("Location: wishlist.php");
    exit();
}

$id_usuario  = (int)$_SESSION["id_usuario"];
$id_producto = (int)$_POST["id"];

$sql = "DELETE FROM wishlist 
        WHERE id_usuario = ? AND id_producto = ?";
$stmt = $conexion->prepare($sql);

if ($stmt) {
    $stmt->bind_param("ii", $id_usuario, $id_producto);
    $stmt->execute();
    $stmt->close();
}

header("Location: wishlist.php");
exit();
