<?php
session_start();
include("../includes/conexion.php");

// Comprobar login
if (!isset($_SESSION["id_usuario"])) {
    $isAjax = (
        !empty($_SERVER["HTTP_X_REQUESTED_WITH"]) &&
        strtolower($_SERVER["HTTP_X_REQUESTED_WITH"]) === "xmlhttprequest"
    ) || (isset($_POST["ajax"]) && $_POST["ajax"] === "1");

    if ($isAjax) {
        header("Content-Type: application/json; charset=utf-8");
        echo json_encode([
            "success" => false,
            "error"   => "not_logged_in"
        ]);
        exit();
    }

    header("Location: ../login.php");
    exit();
}

// Validar ID de producto
if (!isset($_POST["id_producto"])) {
    header("Location: ../shop.php");
    exit();
}

$idProducto = (int)$_POST["id_producto"];
$idUsuario  = (int)$_SESSION["id_usuario"];

// 1) Comprobar que el producto existe
$sql = "SELECT id_producto FROM productos WHERE id_producto = :id LIMIT 1";
$stmt = $pdo->prepare($sql);
$stmt->execute(["id" => $idProducto]);
$row = $stmt->fetch();

if ($row === false) {
    header("Location: ../shop.php");
    exit();
}

// 2) Insertar en wishlist (evitar duplicados con INSERT IGNORE)
$sqlW = "INSERT IGNORE INTO wishlist (id_usuario, id_producto) VALUES (:id_usuario, :id_producto)";
$stmtW = $pdo->prepare($sqlW);
$stmtW->execute([
    "id_usuario" => $idUsuario,
    "id_producto" => $idProducto,
]);

// Copia en sesion para contador
if (!isset($_SESSION["wishlist"])) {
    $_SESSION["wishlist"] = [];
}
$_SESSION["wishlist"][$idProducto] = true;

// Recalcular contador persistente
$wishlistCount = 0;
$sqlCount = "SELECT COUNT(*) AS total FROM wishlist WHERE id_usuario = :id_usuario";
$stmtC = $pdo->prepare($sqlCount);
$stmtC->execute(["id_usuario" => $idUsuario]);
$rowC = $stmtC->fetch();
if ($rowC) {
    $wishlistCount = (int)$rowC["total"];
}

// Respuesta segun tipo
$isAjax = (
    !empty($_SERVER["HTTP_X_REQUESTED_WITH"]) &&
    strtolower($_SERVER["HTTP_X_REQUESTED_WITH"]) === "xmlhttprequest"
) || (isset($_POST["ajax"]) && $_POST["ajax"] === "1");

if ($isAjax) {
    header("Content-Type: application/json; charset=utf-8");
    echo json_encode([
        "success"        => true,
        "wishlist_count" => $wishlistCount,
        "product_id"     => $idProducto
    ]);
    exit();
}

// Peticion normal: volver a la pagina previa si existe; si no, a la tienda
$referer = $_SERVER['HTTP_REFERER'] ?? null;
if ($referer) {
    header("Location: " . $referer);
} else {
    header("Location: ../shop.php");
}
exit();
