<?php
session_start();
include("../includes/conexion.php");

// =========================
//  CALCULAR CARRITO
// =========================
$itemsCarrito = 0;
$total = 0.0;

if (!empty($_SESSION["carrito"])) {
    foreach ($_SESSION["carrito"] as $item) {
        $cantidad = (int)$item["cantidad"];
        $itemsCarrito += $cantidad;
        $total += ((float)$item["precio"]) * $cantidad;
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
    <title>Cart | Gargot</title>
    <link rel="stylesheet" href="../css/estilos.css?v=3">
</head>
<body>

<?php include '../includes/header.php'; ?>

<main class="contenedor">
    <h1 class="titulo-pagina">CART</h1>

    <?php if ($itemsCarrito === 0): ?>
        <p class="descripcion-pequena">Your cart is empty.</p>
    <?php else: ?>

        <table class="cart-table">

            <?php foreach ($_SESSION["carrito"] as $p): ?>
                <tr>
                    <td class="cart-product">
                        <a href="../product_detail.php?id=<?php echo (int)$p["id"]; ?>"
                           style="color: inherit; text-decoration: none;">
                            <img src="../<?php echo htmlspecialchars($p["imagen"]); ?>"
                                 alt="<?php echo htmlspecialchars($p["nombre"]); ?>">
                            <?php echo htmlspecialchars($p["nombre"]); ?>
                        </a>
                    </td>

                    <td><?php echo (int)$p["cantidad"]; ?></td>

                    <td><?php echo number_format($p["precio"] * $p["cantidad"], 2); ?> €</td>

                    <td class="cart-remove">
                        <a href="remove_from_cart.php?id=<?php echo (int)$p["id"]; ?>"
                           class="btn-product btn-cart-remove">
                            remove
                        </a>
                    </td>
                </tr>
            <?php endforeach; ?>
        </table>

        <div class="cart-total">
            <span>Total: <?php echo number_format($total, 2); ?> €</span>
        </div>
        <div class="cart-actions">
            <a href="../shop.php" class="btn-product">continue shopping</a>

            <form method="post" action="../checkout.php" style="display:inline;">
                <button type="submit" class="btn-product">checkout</button>
            </form>
        </div>

    <?php endif; ?>
</main>

</body>
</html>
