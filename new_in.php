<?php
session_start();
include("includes/conexion.php");

// Contadores iconos
$itemsCarrito = 0;
if (!empty($_SESSION["carrito"])) {
    foreach ($_SESSION["carrito"] as $item) {
        $itemsCarrito += (int)$item["cantidad"];
    }
}
$wishlistCount = !empty($_SESSION["wishlist"]) ? count($_SESSION["wishlist"]) : 0;

// Visibilidad (si existe la columna)
$hasVisibility = false;
$colCheck = $pdo->query("SHOW COLUMNS FROM productos LIKE 'is_visible'");
$colRows = $colCheck ? $colCheck->fetchAll() : [];
if (count($colRows) > 0) {
    $hasVisibility = true;
}

// Query new in
$sql = "SELECT p.*, c.nombre AS categoria
        FROM productos p
        JOIN categorias c ON p.id_categoria = c.id_categoria
        WHERE c.nombre = 'CLOTHING'";

if ($hasVisibility) {
    $sql .= " AND p.is_visible = 1";
}

$sql .= " ORDER BY p.fecha_creacion DESC LIMIT 12";
$stmt = $pdo->query($sql);
$rows = $stmt ? $stmt->fetchAll() : [];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>New in | Gargot</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="css/estilos.css?v=3">
</head>
<body>

<?php include 'includes/header.php'; ?>

<main class="contenedor">
    <h1 class="titulo-pagina">NEW IN</h1>

    <?php if (count($rows) > 0): ?>
        <div class="grid-productos">
            <?php foreach ($rows as $p): ?>
                <?php $isSoldOut = ((int)$p["stock"] <= 0); ?>
                <div class="producto-card">
                    <a href="product_detail.php?id=<?php echo (int)$p["id_producto"]; ?>"
                       style="color: inherit; text-decoration: none;">

                        <div class="producto-imagen-wrap">
                            <?php if (!empty($p["imagen"])): ?>
                                <img src="<?php echo htmlspecialchars($p["imagen"]); ?>"
                                     alt="<?php echo htmlspecialchars($p["nombre"]); ?>">
                            <?php else: ?>
                                <div class="placeholder-img">SIN IMAGEN</div>
                            <?php endif; ?>

                            <?php if ($isSoldOut): ?>
                                <span class="badge-soldout">sold out</span>
                            <?php endif; ?>

                            <div class="overlay">
                                <div class="overlay-info descripcion-pequena">
                                    <div class="overlay-precio">
                                        <?php echo number_format($p["precio"], 2); ?> €
                                    </div>

                                    <?php if ($isSoldOut): ?>
                                        <div class="soldout-text">sold out</div>
                                    <?php else: ?>
                                        <form method="post" action="user/add_to_cart.php" style="margin-bottom:6px;">
                                            <input type="hidden" name="id_producto" value="<?php echo (int)$p["id_producto"]; ?>">
                                            <button type="submit" class="btn-add">ADD TO CART</button>
                                        </form>
                                    <?php endif; ?>

                                    <form method="post" action="user/add_to_wishlist.php">
                                        <input type="hidden" name="id_producto" value="<?php echo (int)$p["id_producto"]; ?>">
                                        <button type="submit" class="btn-wishlist">ADD TO WISHLIST</button>
                                    </form>
                                </div>
                            </div>
                        </div>

                        <div class="producto-info">
                            <div class="linea1">
                                <span class="tipo">[<?php echo $p["familia"]; ?>]</span>
                                <?php if (!empty($p["talla"])): ?>
                                    <span class="talla">(<?php echo $p["talla"]; ?>)</span>
                                <?php endif; ?>
                            </div>

                            <div class="linea2 marca">
                                <?php echo htmlspecialchars($p["marca"]); ?>
                            </div>

                            <div class="linea3 nombre">
                                <?php echo htmlspecialchars($p["nombre"]); ?>
                            </div>
                        </div>

                    </a>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <p>No hay productos nuevos por ahora.</p>
    <?php endif; ?>
</main>

</body>
</html>
