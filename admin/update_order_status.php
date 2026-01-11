<?php
session_start();

// Solo admins
if (!isset($_SESSION["id_usuario"]) || $_SESSION["rol"] !== "admin") {
    header("Location: ../login.php");
    exit();
}

require_once "../includes/conexion.php";

/* ===================================
   ACTUALIZAR ESTADO DEL PEDIDO
=================================== */
if (
    $_SERVER["REQUEST_METHOD"] === "POST" &&
    isset($_POST["action"]) &&
    $_POST["action"] === "update_status"
) {
    $id_pedido = isset($_POST["id_pedido"]) ? (int)$_POST["id_pedido"] : 0;
    $estado    = trim($_POST["estado"] ?? "");

    // Estados permitidos
    $estados_validos = ["pending", "paid", "shipped", "cancelled"];

    if ($id_pedido > 0 && in_array($estado, $estados_validos, true)) {

        $sql = "UPDATE pedidos SET estado = ? WHERE id_pedido = ?";
        $stmt = $conexion->prepare($sql);

        if ($stmt) {
            $stmt->bind_param("si", $estado, $id_pedido);
            $stmt->execute();
            $stmt->close();
        }
    }
}

/* ===================================
   REDIRECCIÓN DE VUELTA AL LISTADO
=================================== */
/*
   IMPORTANTE: aquí pon la página donde ves la tabla de pedidos.
   Si tu listado está en admin/shipments.php:
*/
header("Location: shipments.php?updated=1");
exit;


