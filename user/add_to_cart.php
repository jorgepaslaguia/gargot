<?php
session_start();
require_once "../includes/conexion.php";

// Detectar si es peticion AJAX (tambien por flag "ajax")
$isAjax = (
    !empty($_SERVER["HTTP_X_REQUESTED_WITH"]) &&
    strtolower($_SERVER["HTTP_X_REQUESTED_WITH"]) === "xmlhttprequest"
) || (isset($_POST["ajax"]) && $_POST["ajax"] === "1");

// Si no viene ID, volvemos a la shop
if (!isset($_POST["id_producto"])) {
    header("Location: ../shop.php");
    exit();
}

$id = (int) $_POST["id_producto"];

// =============================
// 1) Obtener producto
// =============================
$sql = "SELECT * FROM productos WHERE id_producto = :id LIMIT 1";
$stmt = $pdo->prepare($sql);
$stmt->execute(["id" => $id]);
$producto = $stmt->fetch();

if ($producto === false) {
    header("Location: ../shop.php");
    exit();
}

// Validar stock y visibilidad
$hasVisibility = false;
$colCheck = $pdo->query("SHOW COLUMNS FROM productos LIKE 'is_visible'");
$colRows = $colCheck ? $colCheck->fetchAll() : [];
if (count($colRows) > 0) {
    $hasVisibility = true;
}

$isVisible = !$hasVisibility || ((int)$producto['is_visible'] === 1);
$stockDisponible = (int)$producto['stock'];

if (!$isVisible || $stockDisponible <= 0) {
    if ($isAjax) {
        header("Content-Type: application/json; charset=utf-8");
        echo json_encode(["success" => false, "error" => "sold_out"]);
        exit();
    }
    header("Location: ../product_detail.php?id=" . $id);
    exit();
}

// =============================
// 2) Imagen principal
// =============================
$imagenPrincipal = $producto["imagen"];

if (empty($imagenPrincipal)) {
    $sqlImg = "SELECT image_path 
               FROM producto_imagenes 
               WHERE id_producto = :id 
               ORDER BY orden ASC, id ASC 
               LIMIT 1";
    $stmtImg = $pdo->prepare($sqlImg);
    $stmtImg->execute(["id" => $id]);
    $rowImg = $stmtImg->fetch();
    if ($rowImg) {
        $imagenPrincipal = $rowImg["image_path"];
    }
}

if (empty($imagenPrincipal)) {
    $imagenPrincipal = "";
}

// =============================
// 3) Crear carrito si no existe
// =============================
if (!isset($_SESSION["carrito"])) {
    $_SESSION["carrito"] = [];
}

// =============================
// 4) Añadir / incrementar producto
// =============================
if (isset($_SESSION["carrito"][$id])) {
    $nuevaCantidad = $_SESSION["carrito"][$id]["cantidad"] + 1;
    if ($nuevaCantidad > $stockDisponible) {
        if ($isAjax) {
            header("Content-Type: application/json; charset=utf-8");
            echo json_encode(["success" => false, "error" => "sold_out"]);
            exit();
        }
        header("Location: ../product_detail.php?id=" . $id);
        exit();
    }
    $_SESSION["carrito"][$id]["cantidad"] = $nuevaCantidad;
} else {
    $_SESSION["carrito"][$id] = [
        "id"       => $id,
        "nombre"   => $producto["nombre"],
        "precio"   => $producto["precio"],
        "imagen"   => $imagenPrincipal,
        "cantidad" => 1
    ];
}

// =============================
// 5) Respuesta según tipo de petición
// =============================
$totalItems = 0;
foreach ($_SESSION["carrito"] as $item) {
    $totalItems += (int)$item["cantidad"];
}

if ($isAjax) {
    header("Content-Type: application/json; charset=utf-8");
    echo json_encode([
        "success"     => true,
        "total_items" => $totalItems,
        "product_id"  => $id
    ]);
    exit();
}

// Peticion normal: volver a la pagina previa si existe; si no, al carrito
$referer = $_SERVER['HTTP_REFERER'] ?? null;
if ($referer) {
    header("Location: " . $referer);
} else {
    header("Location: ../cart.php");
}
exit();
