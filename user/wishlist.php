<?php
session_start();
include("../includes/conexion.php");

// CSRF token para formularios POST
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(16));
}
$csrfToken = $_SESSION['csrf_token'];

// Requiere login
if (!isset($_SESSION["id_usuario"])) {
    header("Location: ../login.php");
    exit();
}

$id = (int)$_SESSION["id_usuario"];

// Contadores iconos
$itemsCarrito = 0;
if (!empty($_SESSION["carrito"])) {
    foreach ($_SESSION["carrito"] as $item) {
        $itemsCarrito += (int)$item["cantidad"];
    }
}
$wishlistCount = !empty($_SESSION["wishlist"]) ? count($_SESSION["wishlist"]) : 0;

// Visibilidad
$hasVisibility = false;
$colCheck = $pdo->query("SHOW COLUMNS FROM productos LIKE 'is_visible'");
$colRows = $colCheck ? $colCheck->fetchAll() : [];
if (count($colRows) > 0) {
    $hasVisibility = true;
}

// Consulta wishlist
$sql = "SELECT w.id_producto, p.nombre, p.precio, p.imagen, p.stock";
if ($hasVisibility) {
    $sql .= ", p.is_visible";
}
$sql .= " FROM wishlist w
          JOIN productos p ON w.id_producto = p.id_producto
          WHERE w.id_usuario = :id_usuario";
if ($hasVisibility) {
    $sql .= " AND p.is_visible = 1";
}
$stmt = $pdo->prepare($sql);
$stmt->execute(["id_usuario" => $id]);
$rows = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Wishlist | Gargot</title>
    <link rel="stylesheet" href="../css/estilos.css?v=3">
</head>
<body>
<?php include '../includes/header.php'; ?>

<main class="contenedor">
    <h1 class="titulo-pagina">WISHLIST</h1>

    <?php if (count($rows) === 0): ?>
        <p class="wishlist-empty">No items in your wishlist.</p>
    <?php else: ?>

        <table class="cart-table wishlist-table">
            <?php foreach ($rows as $p): ?>
                <?php $isSoldOut = ((int)$p["stock"] <= 0); ?>
                <tr>
                    <td class="cart-product">
                        <a href="../product_detail.php?id=<?php echo (int)$p["id_producto"]; ?>" style="color: inherit; text-decoration: none;">
                            <?php if (!empty($p["imagen"])): ?>
                                <img src="../<?php echo htmlspecialchars($p["imagen"]); ?>" alt="<?php echo htmlspecialchars($p["nombre"]); ?>">
                            <?php else: ?>
                                <div class="placeholder-img">SIN IMAGEN</div>
                            <?php endif; ?>
                            <?php echo htmlspecialchars($p["nombre"]); ?>
                        </a>
                    </td>

                    <td>1</td>

                    <td>
                        <?php echo number_format($p["precio"], 2); ?> €
                    </td>

                    <td style="text-align:right; white-space: nowrap;">
                        <?php if (!$isSoldOut): ?>
                            <form method="post" action="add_to_cart.php" style="display:inline;">
                                <input type="hidden" name="id_producto" value="<?php echo (int)$p["id_producto"]; ?>">
                                <button type="submit" class="btn-product">add to cart</button>
                            </form>
                        <?php else: ?>
                            <button class="btn-product soldout-text" style="margin-right:6px; border:1px solid #c00; color:#c00; background:transparent;" disabled>sold out</button>
                        <?php endif; ?>

                        <form method="post" action="remove_from_wishlist.php" style="display:inline; margin-left:0;">
                            <input type="hidden" name="id" value="<?php echo (int)$p["id_producto"]; ?>">
                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
                            <button type="submit" class="btn-product btn-cart-remove">remove</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
        </table>
    <?php endif; ?>
</main>

</body>
</html>
