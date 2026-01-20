<?php
session_start();

if (!isset($_SESSION["id_usuario"]) || $_SESSION["rol"] !== "admin") {
    header("Location: ../login.php");
    exit();
}

require_once "../includes/conexion.php";

// CSRF token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(16));
}
$csrfToken = $_SESSION['csrf_token'];

/* ============================
   CONFIG ESTADOS
============================ */
$estadosPermitidos = ["pending", "paid", "shipped", "cancelled"];

// Filtros
$statusFilter = isset($_GET["status"]) && in_array($_GET["status"], $estadosPermitidos, true)
    ? $_GET["status"]
    : "";
$payFilter = trim($_GET["pago"] ?? "");
$search    = trim($_GET["q"] ?? "");

/* ============================
   ACTUALIZAR ESTADO PEDIDO
============================ */
if (
    $_SERVER["REQUEST_METHOD"] === "POST"
    && isset($_POST["action"])
    && $_POST["action"] === "update_status"
) {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        header("Location: shipments.php?updated=0");
        exit();
    }

    $id_pedido = isset($_POST["id_pedido"]) ? (int)$_POST["id_pedido"] : 0;
    $estado    = isset($_POST["estado"]) ? trim($_POST["estado"]) : "";

    if ($id_pedido > 0 && in_array($estado, $estadosPermitidos, true)) {
        $sqlUpd  = "UPDATE pedidos SET estado = :estado WHERE id_pedido = :id_pedido";
        $stmtUpd = $pdo->prepare($sqlUpd);
        $stmtUpd->execute([
            "estado" => $estado,
            "id_pedido" => $id_pedido,
        ]);
    }

    // Redirección para evitar reenvío de formulario
    header("Location: shipments.php?updated=1");
    exit();
}

/* ============================
   LISTADO DE PEDIDOS
============================ */
$where  = [];
$params = [];

if ($statusFilter !== "") {
    $where[] = "p.estado = :estado";
    $params["estado"] = $statusFilter;
}

if ($payFilter !== "") {
    $where[] = "p.metodo_pago = :metodo_pago";
    $params["metodo_pago"] = $payFilter;
}

if ($search !== "") {
    $where[] = "(p.nombre LIKE :search_like OR p.apellidos LIKE :search_like OR p.email LIKE :search_like OR p.id_pedido = :search_id)";
    $params["search_like"] = "%" . $search . "%";
    $params["search_id"] = (int)$search;
}

$sql = "
    SELECT 
        p.id_pedido,
        p.fecha_pedido,
        p.nombre,
        p.apellidos,
        p.email,
        p.direccion,
        p.ciudad,
        p.codigo_postal AS cp,
        p.provincia,
        p.pais,
        p.telefono,
        p.notas,
        p.total,
        p.estado,
        p.metodo_pago,
        COALESCE(SUM(d.cantidad), 0) AS items
    FROM pedidos p
    LEFT JOIN detalle_pedido d ON p.id_pedido = d.id_pedido
";

if (!empty($where)) {
    $sql .= " WHERE ".implode(" AND ", $where);
}

$sql .= "
    GROUP BY 
        p.id_pedido,
        p.fecha_pedido,
        p.nombre,
        p.apellidos,
        p.email,
        p.direccion,
        p.ciudad,
        p.codigo_postal,
        p.provincia,
        p.pais,
        p.telefono,
        p.notas,
        p.total,
        p.estado,
        p.metodo_pago
    ORDER BY p.fecha_pedido DESC, p.id_pedido DESC
";

$stmtList = $pdo->prepare($sql);
$stmtList->execute($params);
$rows = $stmtList->fetchAll();

// Conteo por estado
$statusCounts = array_fill_keys($estadosPermitidos, 0);
$cntRes = $pdo->query("SELECT estado, COUNT(*) as c FROM pedidos GROUP BY estado");
$cntRows = $cntRes ? $cntRes->fetchAll() : [];
foreach ($cntRows as $rowC) {
    $estadoKey = $rowC["estado"];
    if (isset($statusCounts[$estadoKey])) {
        $statusCounts[$estadoKey] = (int)$rowC["c"];
    }
}

// Metodos de pago disponibles
$metodosPago = [];
$resPago = $pdo->query("SELECT DISTINCT metodo_pago FROM pedidos WHERE metodo_pago IS NOT NULL AND metodo_pago <> '' ORDER BY metodo_pago ASC");
$rowsPago = $resPago ? $resPago->fetchAll() : [];
foreach ($rowsPago as $rowP) {
    $metodosPago[] = $rowP["metodo_pago"];
}

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Admin | orders</title>
    <link rel="stylesheet" href="../css/estilos.css">
</head>
<body>
<?php include '../includes/header.php'; ?>

<main class="contenedor admin-wrapper">
    <h1 class="admin-title">admin | orders</h1>

    <nav class="admin-subnav">
        <a href="shipments.php" class="active">orders</a>
        <a href="products.php">products</a>
        <a href="home_cards.php">home images</a>
        <a href="stats.php">stats</a>
        <a href="password_audit.php">security</a>
    </nav>

    <section class="admin-section">
        <p class="admin-subtitle">
            overview of all orders placed in the store.
        </p>

        <?php if (!empty($_GET["updated"])): ?>
            <div class="admin-notice-success">
                order status updated.
            </div>
        <?php endif; ?>

        <div class="admin-metric-grid order-metrics">
            <?php foreach ($estadosPermitidos as $estadoBox): ?>
                <div class="admin-card admin-metric">
                    <div class="metric-label"><?php echo $estadoBox; ?></div>
                    <div class="metric-value"><?php echo $statusCounts[$estadoBox] ?? 0; ?></div>
                    <div class="metric-note">pedidos <?php echo $estadoBox; ?></div>
                </div>
            <?php endforeach; ?>
        </div>

        <form method="get" class="order-filters">
            <label>
                status
                <select name="status">
                    <option value="">all</option>
                    <?php foreach ($estadosPermitidos as $st): ?>
                        <option value="<?php echo $st; ?>" <?php if ($statusFilter === $st) echo "selected"; ?>>
                            <?php echo $st; ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </label>

            <label>
                payment
                <select name="pago">
                    <option value="">all</option>
                    <?php foreach ($metodosPago as $m): ?>
                        <option value="<?php echo htmlspecialchars($m); ?>" <?php if ($payFilter === $m) echo "selected"; ?>>
                            <?php echo htmlspecialchars($m); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </label>

            <label>
                search (id, name, email)
                <input type="text" name="q" value="<?php echo htmlspecialchars($search); ?>">
            </label>

            <button type="submit" class="btn-filtros-apply">apply</button>
            <a href="shipments.php" class="btn-filtros-clear">clear</a>
        </form>

        <?php if (count($rows) > 0): ?>
            <table class="admin-table">
                <thead>
                <tr>
                    <th>ID</th>
                    <th>DATE</th>
                    <th>USER</th>
                    <th>EMAIL</th>
                    <th>SHIP TO</th>
                    <th>ITEMS</th>
                    <th>TOTAL (&euro;)</th>
                    <th>PAYMENT</th>
                    <th>STATUS</th>
                </tr>
                </thead>
                <tbody>
<?php foreach ($rows as $row): ?>
    <?php
        $estadoBD = (string)$row["estado"];
        $estadoClass = in_array($estadoBD, $estadosPermitidos, true)
            ? $estadoBD
            : "pending";
    ?>
    <tr>
        <td>#<?php echo (int)$row["id_pedido"]; ?></td>
        <td><?php echo htmlspecialchars($row["fecha_pedido"]); ?></td>
        <td><?php echo htmlspecialchars($row["nombre"] . " " . $row["apellidos"]); ?></td>
        <td><?php echo htmlspecialchars($row["email"]); ?></td>
        <td class="shipto"><?php echo htmlspecialchars($row["ciudad"]); ?>, <?php echo htmlspecialchars($row["pais"]); ?></td>
        <td><?php echo (int)$row["items"]; ?></td>
        <td><?php echo number_format((float)$row["total"], 2); ?> &euro;</td>
        <td><?php echo htmlspecialchars($row["metodo_pago"]); ?></td>
        <td>
            <div class="order-status-wrapper">
                <span class="status-pill status-<?php echo htmlspecialchars($estadoClass); ?>">
                    <?php echo htmlspecialchars($estadoBD); ?>
                </span>

                <form method="post" class="order-status-form">
                    <input type="hidden" name="action" value="update_status">
                    <input type="hidden" name="id_pedido" value="<?php echo (int)$row["id_pedido"]; ?>">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">

                    <select name="estado" class="order-status-select">
                        <?php foreach ($estadosPermitidos as $estadoOpt): ?>
                            <option value="<?php echo $estadoOpt; ?>" <?php if ($estadoOpt === $estadoBD) echo "selected"; ?>>
                                <?php echo $estadoOpt; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>

                    <button type="submit" class="btn-status-update">
                        update
                    </button>
                </form>
            </div>
        </td>
    </tr>
    <tr class="order-detail-row">
        <td colspan="9">
            <div class="order-detail-grid">
                <div>
                    <div class="detail-label">direccion</div>
                    <div class="detail-value">
                        <?php echo htmlspecialchars($row["direccion"]); ?><br>
                        <?php echo htmlspecialchars($row["cp"]); ?> <?php echo htmlspecialchars($row["ciudad"]); ?><br>
                        <?php echo htmlspecialchars($row["provincia"]); ?>, <?php echo htmlspecialchars($row["pais"]); ?>
                    </div>
                </div>
                <div>
                    <div class="detail-label">contacto</div>
                    <div class="detail-value">
                        <?php echo htmlspecialchars($row["email"]); ?><br>
                        <?php echo htmlspecialchars($row["telefono"]); ?>
                    </div>
                </div>
                <div>
                    <div class="detail-label">notas del pedido</div>
                    <div class="detail-value">
                        <?php echo nl2br(htmlspecialchars($row["notas"])); ?>
                    </div>
                </div>
            </div>
        </td>
    </tr>
<?php endforeach; ?>
</tbody>
            </table>
        <?php else: ?>
            <p class="txt-secundario">no orders yet.</p>
        <?php endif; ?>
    </section>
</main>

</body>
</html>







