<?php
session_start();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../cart.php");
    exit();
}

if (empty($_SESSION['csrf_token']) || ($_POST['csrf_token'] ?? '') !== $_SESSION['csrf_token']) {
    header("Location: ../cart.php");
    exit();
}

if (!isset($_POST["id"])) {
    header("Location: ../cart.php");
    exit();
}

$id = (int)$_POST["id"];

// Solo actuar si el carrito existe
if (isset($_SESSION["carrito"]) && isset($_SESSION["carrito"][$id])) {
    unset($_SESSION["carrito"][$id]);

    // Si el carrito queda vacio, eliminar la variable completa
    if (empty($_SESSION["carrito"])) {
        unset($_SESSION["carrito"]);
    }
}

// Volver al carrito
header("Location: ../cart.php");
exit();
