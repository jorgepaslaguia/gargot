<?php
session_start();
include("includes/conexion.php");

// CSRF token para acciones del carrito
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(16));
}
$csrfToken = $_SESSION['csrf_token'];

// Contadores iconos
$itemsCarrito = 0;
$total = 0.0;
$cartIssues = [];

$carrito = $_SESSION["carrito"] ?? [];
$ids = array_keys($carrito);
$hasVisibility = false;
$colCheck = $conexion->query("SHOW COLUMNS FROM productos LIKE 'is_visible'");
if ($colCheck && $colCheck->num_rows > 0) $hasVisibility = true;

$stockMap = [];
if (!empty($ids)) {
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $types = str_repeat('i', count($ids));
    $sql = "SELECT id_producto, stock" . ($hasVisibility ? ", is_visible" : "") . " FROM productos WHERE id_producto IN ($placeholders)";
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param($types, ...$ids);
    $stmt->execute();
    $res = $stmt->get_result();
    while ($row = $res->fetch_assoc()) {
        $stockMap[$row['id_producto']] = $row;
    }
    $stmt->close();
}

// Revalidar stock y visibilidad
foreach ($carrito as $pid => &$item) {
    $pidInt = (int)$pid;
    if (!isset($stockMap[$pidInt])) {
        $cartIssues[] = $item["nombre"] . " ya no está disponible.";
        unset($carrito[$pid]);
        continue;
    }
    $row = $stockMap[$pidInt];
    if ($hasVisibility && (int)$row['is_visible'] !== 1) {
        $cartIssues[] = $item["nombre"] . " está oculto y se ha retirado del carrito.";
        unset($carrito[$pid]);
        continue;
    }
    $stockBD = (int)$row['stock'];
    if ($stockBD <= 0) {
        $cartIssues[] = $item["nombre"] . " está sold out.";
        unset($carrito[$pid]);
        continue;
    }
    $qty = (int)$item["cantidad"];
    if ($qty > $stockBD) {
        $item["cantidad"] = $stockBD;
        $cartIssues[] = $item["nombre"] . ": cantidad ajustada a stock disponible (" . $stockBD . ").";
    }
    $itemsCarrito += $item["cantidad"];
    $total += ((float)$item["precio"]) * $item["cantidad"];
}
unset($item);

// Actualizar sesión carrito con los ajustes
$_SESSION["carrito"] = $carrito;

$wishlistCount = !empty($_SESSION["wishlist"]) ? count($_SESSION["wishlist"]) : 0;
$checkoutDisabled = !empty($cartIssues) || $itemsCarrito === 0;
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Cart | Gargot</title>
    <link rel="stylesheet" href="css/estilos.css?v=3">
</head>
<body>
<?php include 'includes/header.php'; ?>

<main class="contenedor">
    <h1 class="titulo-pagina">CART</h1>

    <?php if (!empty($cartIssues)): ?>
        <div class="admin-errors">
            <ul>
                <?php foreach ($cartIssues as $issue): ?>
                    <li><?php echo htmlspecialchars($issue); ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <?php if ($itemsCarrito === 0): ?>
        <p class="descripcion-pequena">Your cart is empty.</p>
    <?php else: ?>

        <table class="cart-table">
            <?php foreach ($carrito as $p): ?>
                <tr>
                    <td class="cart-product">
                        <a href="product_detail.php?id=<?php echo (int)$p["id"]; ?>" style="color: inherit; text-decoration: none;">
                            <img src="<?php echo htmlspecialchars($p["imagen"]); ?>" alt="<?php echo htmlspecialchars($p["nombre"]); ?>">
                            <?php echo htmlspecialchars($p["nombre"]); ?>
                        </a>
                    </td>

                    <td><?php echo (int)$p["cantidad"]; ?></td>

                    <td><?php echo number_format($p["precio"] * $p["cantidad"], 2); ?> €</td>

                    <td class="cart-remove">
                        <form method="post" action="user/remove_from_cart.php">
                            <input type="hidden" name="id" value="<?php echo (int)$p["id"]; ?>">
                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
                            <button type="submit" class="btn-product btn-cart-remove">remove</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
        </table>

        <div class="cart-total">
            <span>Total: <?php echo number_format($total, 2); ?> €</span>
        </div>
        <div class="cart-actions">
            <a href="shop.php" class="btn-product">continue shopping</a>

            <form method="post" action="checkout.php" style="display:inline;">
                <button type="submit" class="btn-product" <?php if ($checkoutDisabled) echo 'disabled'; ?>>checkout</button>
            </form>
        </div>

    <?php endif; ?>
</main>

</body>
</html>
