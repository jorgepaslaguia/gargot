<?php
session_start();

// Redirigir si no está logueado
if (!isset($_SESSION["id_usuario"])) {
    header("Location: ../login.php");
    exit();
}

// CONTADOR CARRITO
$itemsCarrito = 0;
if (!empty($_SESSION["carrito"])) {
    foreach ($_SESSION["carrito"] as $item) {
        $itemsCarrito += (int)$item["cantidad"];
    }
}

// CONTADOR WISHLIST
$wishlistCount = 0;
if (!empty($_SESSION["wishlist"])) {
    $wishlistCount = count($_SESSION["wishlist"]);
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>My account | Gargot</title>
    <link rel="stylesheet" href="../css/estilos.css?v=3">
</head>
<body>

<?php include '../includes/header.php'; ?>

<main class="contenedor">
    <h1 class="titulo-pagina">MY ACCOUNT</h1>

    <p class="descripcion-pequena">User ID: <?php echo (int)$_SESSION["id_usuario"]; ?></p>

    <ul style="margin-top:20px; line-height: 1.8; font-size: 13px;">
        <li><a href="../cart.php">My Cart (<?php echo $itemsCarrito; ?>)</a></li>
        <li><a href="wishlist.php">My Wishlist</a></li>
        <li><a href="../logout.php">Logout</a></li>
    </ul>
</main>

</body>
</html>
