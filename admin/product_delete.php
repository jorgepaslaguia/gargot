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
    $stmtImg = $conexion->prepare("SELECT image_path FROM producto_imagenes WHERE id_producto = ?");
    $stmtImg->bind_param("i", $id);
    $stmtImg->execute();
    $resImg = $stmtImg->get_result();
    while ($row = $resImg->fetch_assoc()) {
        if (!empty($row["image_path"])) {
            $imagenes[] = $row["image_path"];
        }
    }
    $stmtImg->close();

    $stmtDelImgs = $conexion->prepare("DELETE FROM producto_imagenes WHERE id_producto = ?");
    $stmtDelImgs->bind_param("i", $id);
    $stmtDelImgs->execute();
    $stmtDelImgs->close();

    $stmtDel = $conexion->prepare("DELETE FROM productos WHERE id_producto = ?");
    $stmtDel->bind_param("i", $id);
    $stmtDel->execute();
    $stmtDel->close();

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
