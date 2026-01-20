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
        WHERE id_usuario = :id_usuario AND id_producto = :id_producto";
$stmt = $pdo->prepare($sql);
$stmt->execute([
    "id_usuario" => $id_usuario,
    "id_producto" => $id_producto,
]);

header("Location: wishlist.php");
exit();
