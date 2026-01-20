<?php
session_start();
if (!isset($_SESSION["id_usuario"]) || $_SESSION["rol"] !== "admin") {
    header("Location: ../login.php");
    exit();
}

require_once "../includes/conexion.php";

$id = 0;
if (isset($_POST["id_producto"])) {
    $id = intval($_POST["id_producto"]);
} elseif (isset($_GET["id_producto"])) {
    $id = intval($_GET["id_producto"]);
}

if ($id > 0) {
    $imagenes = [];
    $stmtImg = $pdo->prepare("SELECT image_path FROM producto_imagenes WHERE id_producto = :id_producto");
    $stmtImg->execute(["id_producto" => $id]);
    $rowsImg = $stmtImg->fetchAll();
    foreach ($rowsImg as $row) {
        if (!empty($row["image_path"])) {
            $imagenes[] = $row["image_path"];
        }
    }

    $stmtDelImgs = $pdo->prepare("DELETE FROM producto_imagenes WHERE id_producto = :id_producto");
    $stmtDelImgs->execute(["id_producto" => $id]);

    $stmtDel = $pdo->prepare("DELETE FROM productos WHERE id_producto = :id_producto");
    $stmtDel->execute(["id_producto" => $id]);

    $baseDir = realpath(__DIR__ . "/../img/products");
    if ($baseDir) {
        foreach ($imagenes as $imgPath) {
            $target = realpath(__DIR__ . "/../" . $imgPath);
            if ($target && strpos($target, $baseDir . DIRECTORY_SEPARATOR) === 0 && is_file($target)) {
                unlink($target);
            }
        }
    }
}

$base = rtrim(dirname($_SERVER['SCRIPT_NAME'] ?? ''), '/\\');
$base = $base === '' ? '' : $base . '/';
header("Location: {$base}products.php?deleted=1");
exit();
