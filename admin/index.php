<?php
session_start();

// Solo admins
if (!isset($_SESSION["id_usuario"]) || $_SESSION["rol"] !== "admin") {
    header("Location: ../login.php");
    exit();
}

require_once "../includes/conexion.php";

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

// Métricas rápidas
$hasVisibility = false;
$colCheck = $conexion->query("SHOW COLUMNS FROM productos LIKE 'is_visible'");
if ($colCheck && $colCheck->num_rows > 0) {
    $hasVisibility = true;
}

$totalProductos = (int)($conexion->query("SELECT COUNT(*) AS c FROM productos")->fetch_assoc()["c"] ?? 0);
$ocultos = 0;
if ($hasVisibility) {
    $ocultos = (int)($conexion->query("SELECT COUNT(*) AS c FROM productos WHERE is_visible = 0")->fetch_assoc()["c"] ?? 0);
}
$stockBajo = (int)($conexion->query("SELECT COUNT(*) AS c FROM productos WHERE stock <= 2")->fetch_assoc()["c"] ?? 0);
$pedidosPend = (int)($conexion->query("SELECT COUNT(*) AS c FROM pedidos WHERE estado = 'pending'")->fetch_assoc()["c"] ?? 0);
$montoPendiente = (float)($conexion->query("SELECT COALESCE(SUM(total),0) AS t FROM pedidos WHERE estado = 'pending'")->fetch_assoc()["t"] ?? 0);
$monto7dias = (float)($conexion->query("SELECT COALESCE(SUM(total),0) AS t FROM pedidos WHERE fecha_pedido >= DATE_SUB(NOW(), INTERVAL 7 DAY)")->fetch_assoc()["t"] ?? 0);
$soldOutVisible = 0;
if ($hasVisibility) {
    $soldOutVisible = (int)($conexion->query("SELECT COUNT(*) AS c FROM productos WHERE stock <= 0 AND is_visible = 1")->fetch_assoc()["c"] ?? 0);
} else {
    $soldOutVisible = (int)($conexion->query("SELECT COUNT(*) AS c FROM productos WHERE stock <= 0")->fetch_assoc()["c"] ?? 0);
}

$lowStockRows = [];
$resLow = $conexion->query("SELECT id_producto, nombre, stock FROM productos ORDER BY stock ASC, id_producto ASC LIMIT 5");
if ($resLow && $resLow->num_rows > 0) {
    while ($r = $resLow->fetch_assoc()) {
        $lowStockRows[] = $r;
    }
}

// pedidos recientes
$recentOrders = [];
$resOrders = $conexion->query("SELECT id_pedido, nombre, apellidos, total, estado, fecha_pedido FROM pedidos ORDER BY fecha_pedido DESC, id_pedido DESC LIMIT 5");
if ($resOrders && $resOrders->num_rows > 0) {
    while ($o = $resOrders->fetch_assoc()) {
        $recentOrders[] = $o;
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Admin panel | Gargot</title>
    <link rel="stylesheet" href="../css/estilos.css?v=3">
</head>
<body>

<?php include '../includes/header.php'; ?>

<main class="contenedor admin-wrapper">
    <h1 class="admin-title">admin panel</h1>

    <nav class="admin-subnav">
        <a href="products.php" class="active">products</a>
        <a href="home_cards.php">home images</a>
        <a href="shipments.php">shipments</a>
        <a href="stats.php">stats</a>
        <a href="password_audit.php">security</a>
    </nav>

    <section class="admin-section">
        <p class="admin-subtitle">
            selecciona una seccion para gestionar la tienda.
        </p>

                <section class="admin-metrics">
            <h2 class="admin-subheading">estado rapido</h2>
            <div class="admin-metric-grid">
                <div class="admin-card admin-metric">
                    <div class="metric-label">total productos</div>
                    <div class="metric-value"><?php echo $totalProductos; ?></div>
                    <div class="metric-note">visibles: <?php echo $totalProductos - $ocultos; ?><?php echo $hasVisibility ? " / ocultos: {$ocultos}" : ""; ?></div>
                </div>

                <div class="admin-card admin-metric">
                    <div class="metric-label">stock bajo (<=2)</div>
                    <div class="metric-value"><?php echo $stockBajo; ?></div>
                    <div class="metric-note">prioriza reposicion</div>
                </div>

                <div class="admin-card admin-metric">
                    <div class="metric-label">pedidos pendientes</div>
                    <div class="metric-value"><?php echo $pedidosPend; ?></div>
                    <div class="metric-note">revisa shipments</div>
                </div>

                <div class="admin-card admin-metric">
                    <div class="metric-label">productos en sold out</div>
                    <div class="metric-value"><?php echo $soldOutVisible; ?></div>
                    <div class="metric-note">visibles sin stock</div>
                </div>

                <div class="admin-card admin-metric">
                    <div class="metric-label">importe pendiente</div>
                    <div class="metric-value"><?php echo number_format($montoPendiente, 2); ?> €</div>
                    <div class="metric-note">estado pending</div>
                </div>

                <div class="admin-card admin-metric">
                    <div class="metric-label">ventas 7 dias</div>
                    <div class="metric-value"><?php echo number_format($monto7dias, 2); ?> €</div>
                    <div class="metric-note">total cobrados</div>
                </div>

                <a class="admin-card admin-metric admin-cta" href="products.php">
                    <div class="metric-label">gestionar catalogo</div>
                    <div class="metric-value">abrir</div>
                    <div class="metric-note">productos + visibilidad + stock</div>
                </a>
            </div>

            <?php if (!empty($lowStockRows)): ?>
                <div class="low-stock-panel">
                    <div class="low-stock-head">
                        <span>top 5 con menos stock</span>
                        <a href="products.php" class="admin-link">ver todos</a>
                    </div>
                    <table class="low-stock-table">
                        <thead>
                            <tr>
                                <th>producto</th>
                                <th>stock</th>
                                <th>acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($lowStockRows as $row): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($row["nombre"]); ?></td>
                                    <td><?php echo (int)$row["stock"]; ?></td>
                                    <td>
                                        <a class="admin-link" href="edit_product.php?id=<?php echo (int)$row["id_producto"]; ?>">editar</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>

            <?php if (!empty($recentOrders)): ?>
                <div class="low-stock-panel recent-orders-panel">
                    <div class="low-stock-head">
                        <span>ultimos pedidos</span>
                        <a href="shipments.php" class="admin-link">ver todos</a>
                    </div>
                    <table class="low-stock-table">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>cliente</th>
                                <th>fecha</th>
                                <th>total</th>
                                <th>estado</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recentOrders as $order): ?>
                                <tr>
                                    <td><?php echo (int)$order["id_pedido"]; ?></td>
                                    <td><?php echo htmlspecialchars($order["nombre"] . " " . $order["apellidos"]); ?></td>
                                    <td><?php echo htmlspecialchars($order["fecha_pedido"]); ?></td>
                                    <td><?php echo number_format((float)$order["total"], 2); ?> €</td>
                                    <td><?php echo htmlspecialchars($order["estado"]); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </section>

            <?php if (!empty($lowStockRows)): ?>
                <div class="low-stock-panel">
                    <div class="low-stock-head">
                        <span>top 5 con menos stock</span>
                        <a href="products.php" class="admin-link">ver todos</a>
                    </div>
                    <table class="low-stock-table">
                        <thead>
                            <tr>
                                <th>producto</th>
                                <th>stock</th>
                                <th>acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($lowStockRows as $row): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($row["nombre"]); ?></td>
                                    <td><?php echo (int)$row["stock"]; ?></td>
                                    <td>
                                        <a class="admin-link" href="edit_product.php?id=<?php echo (int)$row["id_producto"]; ?>">editar</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>

            <?php if (!empty($recentOrders)): ?>
                <div class="low-stock-panel recent-orders-panel">
                    <div class="low-stock-head">
                        <span>ultimos pedidos</span>
                        <a href="shipments.php" class="admin-link">ver todos</a>
                    </div>
                    <table class="low-stock-table">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>cliente</th>
                                <th>fecha</th>
                                <th>total</th>
                                <th>estado</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recentOrders as $order): ?>
                                <tr>
                                    <td><?php echo (int)$order["id_pedido"]; ?></td>
                                    <td><?php echo htmlspecialchars($order["nombre"] . " " . $order["apellidos"]); ?></td>
                                    <td><?php echo htmlspecialchars($order["fecha_pedido"]); ?></td>
                                    <td><?php echo number_format((float)$order["total"], 2); ?> €</td>
                                    <td><?php echo htmlspecialchars($order["estado"]); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </section>

    </section>
</main>

</body>
</html>








