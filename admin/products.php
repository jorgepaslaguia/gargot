<?php
session_start();
if (!isset($_SESSION["id_usuario"]) || $_SESSION["rol"] !== "admin") {
    header("Location: ../login.php");
    exit();
}

require_once "../includes/conexion.php";

// Visibilidad
$hasVisibility = false;
$colCheck = $pdo->query("SHOW COLUMNS FROM productos LIKE 'is_visible'");
$colRows = $colCheck ? $colCheck->fetchAll() : [];
if (count($colRows) > 0) {
    $hasVisibility = true;
}

// CSRF
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(16));
}
$csrfToken = $_SESSION['csrf_token'];

$sql = "SELECT id_producto, nombre, familia, talla, marca, precio, stock, imagen";
if ($hasVisibility) {
    $sql .= ", is_visible";
}
$sql .= " FROM productos ORDER BY id_producto DESC";
$stmt = $pdo->query($sql);
$rows = $stmt ? $stmt->fetchAll() : [];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Admin - products | Gargot</title>
    <link rel="stylesheet" href="../css/estilos.css">
    <style>
        .product-thumb { max-width: 60px; max-height: 60px; object-fit: cover; border-radius: 4px; }
        .pill { display:inline-block; padding:2px 8px; border-radius: 10px; font-size:11px; background:#f2f2f2; }
        .pill.green { background:#e7f7ed; color:#0b8a3f; }
        .pill.red { background:#fbe7e7; color:#a02323; }
    </style>
</head>
<body>
<?php include '../includes/header.php'; ?>

<main class="contenedor admin-wrapper">
    <h1 class="admin-title">admin - products</h1>

    <nav class="admin-subnav">
        <a href="products.php" class="active">products</a>
        <a href="home_cards.php">home images</a>
        <a href="shipments.php">orders</a>
        <a href="stats.php">stats</a>
    </nav>

    <section class="admin-section">
        <div class="admin-header-row">
            <p class="admin-subtitle">revisa, añade, edita o elimina productos. puedes ocultarlos sin borrarlos.</p>
            <a href="product_edit.php" class="btn-admin">add_new_product</a>
        </div>

        <?php if (count($rows) > 0): ?>
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>image</th>
                        <th>name</th>
                        <th>brand</th>
                        <th>family/size</th>
                        <th>price (€)</th>
                        <th>stock</th>
                        <?php if ($hasVisibility): ?><th>visible</th><?php endif; ?>
                        <th>actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($rows as $p): ?>
                    <tr>
                        <td><?php echo (int)$p["id_producto"]; ?></td>
                        <td>
                            <?php if (!empty($p["imagen"])): ?>
                                <img class="product-thumb" src="../<?php echo htmlspecialchars($p["imagen"]); ?>" alt="">
                            <?php else: ?>
                                <span class="pill">no img</span>
                            <?php endif; ?>
                        </td>
                        <td><?php echo htmlspecialchars($p["nombre"]); ?></td>
                        <td><?php echo htmlspecialchars($p["marca"]); ?></td>
                        <td><?php echo htmlspecialchars($p["familia"]); ?><?php if (!empty($p["talla"])) echo " (".htmlspecialchars($p["talla"]).")"; ?></td>
                        <td><?php echo number_format($p["precio"], 2); ?></td>
                        <td>
                            <?php echo (int)$p["stock"]; ?>
                            <?php if ((int)$p["stock"] <= 0): ?>
                                <span class="pill red">sold out</span>
                            <?php endif; ?>
                        </td>
                        <?php if ($hasVisibility): ?>
                            <td>
                                <?php if ((int)$p["is_visible"] === 1): ?>
                                    <span class="pill green">visible</span>
                                <?php else: ?>
                                    <span class="pill">hidden</span>
                                <?php endif; ?>
                            </td>
                        <?php endif; ?>
                        <td class="admin-actions">
                            <a href="product_edit.php?id=<?php echo (int)$p["id_producto"]; ?>">edit</a>
                            <form method="post" action="product_delete.php" style="display:inline;" onsubmit="return confirm('Delete this product?');">
                                <input type="hidden" name="id_producto" value="<?php echo (int)$p["id_producto"]; ?>">
                                <button type="submit" class="btn-admin-link">delete</button>
                            </form>
                            <?php if ($hasVisibility): ?>
                            <?php if ((int)$p["is_visible"] === 1): ?>
                                <form method="post" action="product_visibility.php" style="display:inline;">
                                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
                                    <input type="hidden" name="id" value="<?php echo (int)$p['id_producto']; ?>">
                                    <input type="hidden" name="visible" value="0">
                                    <button type="submit" class="btn-admin-secondary" style="padding:4px 8px;">hide</button>
                                </form>
                            <?php else: ?>
                                <form method="post" action="product_visibility.php" style="display:inline;">
                                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
                                    <input type="hidden" name="id" value="<?php echo (int)$p['id_producto']; ?>">
                                    <input type="hidden" name="visible" value="1">
                                    <button type="submit" class="btn-admin-secondary" style="padding:4px 8px;">show</button>
                                </form>
                            <?php endif; ?>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p class="txt-secundario">no products yet.</p>
        <?php endif; ?>
    </section>
</main>

</body>
</html>
