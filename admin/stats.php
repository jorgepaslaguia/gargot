<?php
session_start();
if (!isset($_SESSION["id_usuario"]) || $_SESSION["rol"] !== "admin") {
    header("Location: ../login.php");
    exit();
}

require_once "../includes/conexion.php";

function fetchAll($conexion, $sql, $types = '', $params = []) {
    $stmt = $conexion->prepare($sql);
    if ($types && $params) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $res = $stmt->get_result();
    $rows = [];
    while ($row = $res->fetch_assoc()) {
        $rows[] = $row;
    }
    $stmt->close();
    return $rows;
}

function fetchOne($conexion, $sql, $types = '', $params = [], $default = []) {
    $rows = fetchAll($conexion, $sql, $types, $params);
    return $rows[0] ?? $default;
}

// KPI principales
$totalPedidos = (int)fetchOne($conexion, "SELECT COUNT(*) AS total FROM pedidos")['total'];
$ingresosTotales = (float)fetchOne(
    $conexion,
    "SELECT COALESCE(SUM(total),0) AS ingresos FROM pedidos WHERE estado IN ('paid','shipped')"
)['ingresos'];
$pedidosPagados = (int)fetchOne(
    $conexion,
    "SELECT COUNT(*) AS num FROM pedidos WHERE estado IN ('paid','shipped')"
)['num'];
$ticketMedioReal = $pedidosPagados > 0 ? $ingresosTotales / $pedidosPagados : 0;
$ticketMedioGlobal = $totalPedidos > 0 ? $ingresosTotales / $totalPedidos : 0;

// Ingresos 30d y previos
$win30 = fetchOne(
    $conexion,
    "SELECT COALESCE(SUM(total),0) AS ingresos, COUNT(*) AS pedidos FROM pedidos WHERE fecha_pedido >= (NOW() - INTERVAL 30 DAY) AND estado IN ('paid','shipped')"
);
$ingresos30 = (float)($win30['ingresos'] ?? 0);
$pedidos30  = (int)($win30['pedidos'] ?? 0);
$prev30 = fetchOne(
    $conexion,
    "SELECT COALESCE(SUM(total),0) AS ingresos FROM pedidos WHERE fecha_pedido >= (NOW() - INTERVAL 60 DAY) AND fecha_pedido < (NOW() - INTERVAL 30 DAY) AND estado IN ('paid','shipped')"
);
$ingresosPrev30 = (float)($prev30['ingresos'] ?? 0);
$growth30 = $ingresosPrev30 > 0 ? (($ingresos30 - $ingresosPrev30) / $ingresosPrev30) * 100 : null;

// Totales pedidos 30d (para precio percentiles)
$totales30 = fetchAll(
    $conexion,
    "SELECT total FROM pedidos WHERE fecha_pedido >= (NOW() - INTERVAL 30 DAY) AND estado IN ('paid','shipped')"
);
$precioStats = ['avg' => null, 'p25' => null, 'p75' => null];
if (!empty($totales30)) {
    $vals = array_map(fn($r) => (float)$r['total'], $totales30);
    sort($vals);
    $countVals = count($vals);
    $precioStats['avg'] = array_sum($vals) / $countVals;
    $precioStats['p25'] = $vals[(int)floor(0.25 * ($countVals - 1))];
    $precioStats['p75'] = $vals[(int)floor(0.75 * ($countVals - 1))];
}

// Top marcas 30d
$topMarcas = fetchAll(
    $conexion,
    "SELECT COALESCE(p.marca,'unknown') AS marca,
            COUNT(DISTINCT p.id_producto) AS piezas,
            SUM(d.cantidad) AS unidades,
            SUM(d.cantidad * d.precio_unitario) AS ingresos
     FROM detalle_pedido d
     JOIN pedidos pe ON pe.id_pedido = d.id_pedido
     JOIN productos p ON d.id_producto = p.id_producto
     WHERE pe.fecha_pedido >= (NOW() - INTERVAL 30 DAY) AND pe.estado IN ('paid','shipped')
     GROUP BY marca
     ORDER BY ingresos DESC
     LIMIT 10"
);

// Piezas únicas 30d
$piezasUnicas30 = (int)fetchOne(
    $conexion,
    "SELECT COUNT(DISTINCT d.id_producto) AS piezas
     FROM detalle_pedido d
     JOIN pedidos pe ON pe.id_pedido = d.id_pedido
     WHERE pe.fecha_pedido >= (NOW() - INTERVAL 30 DAY)
       AND pe.estado IN ('paid','shipped')"
)['piezas'];

// Heatmap horas/días (bloques)
$heatmap = fetchAll(
    $conexion,
    "SELECT DATE_FORMAT(fecha_pedido, '%w') AS dow,
            DATE_FORMAT(fecha_pedido, '%H') AS hour,
            COUNT(*) AS pedidos
     FROM pedidos
     WHERE fecha_pedido >= (NOW() - INTERVAL 30 DAY) AND estado IN ('paid','shipped')
     GROUP BY dow, hour"
);

// Tallas 30d
$tallas30 = fetchAll(
    $conexion,
    "SELECT COALESCE(p.talla,'unknown') AS talla,
            SUM(d.cantidad) AS unidades,
            SUM(d.cantidad * d.precio_unitario) AS ingresos
     FROM detalle_pedido d
     JOIN pedidos pe ON pe.id_pedido = d.id_pedido
     JOIN productos p ON d.id_producto = p.id_producto
     WHERE pe.fecha_pedido >= (NOW() - INTERVAL 30 DAY) AND pe.estado IN ('paid','shipped')
     GROUP BY talla
     ORDER BY unidades DESC"
);

// Pedidos por estado
$pedidosPorEstado = fetchAll(
    $conexion,
    "SELECT estado, COUNT(*) AS num, COALESCE(SUM(total),0) AS ingresos FROM pedidos GROUP BY estado ORDER BY ingresos DESC"
);

// Métodos de pago
$porMetodoPago = fetchAll(
    $conexion,
    "SELECT COALESCE(metodo_pago,'unknown') AS metodo, COUNT(*) AS num, COALESCE(SUM(total),0) AS ingresos FROM pedidos GROUP BY metodo ORDER BY ingresos DESC"
);

// Tendencia mensual 12m
$mensual = fetchAll(
    $conexion,
    "SELECT DATE_FORMAT(fecha_pedido, '%Y-%m') AS ym,
            DATE_FORMAT(fecha_pedido, '%b %Y') AS etiqueta,
            COUNT(*) AS num,
            COALESCE(SUM(total),0) AS ingresos
     FROM pedidos
     WHERE fecha_pedido >= DATE_SUB(CURDATE(), INTERVAL 11 MONTH)
       AND estado IN ('paid','shipped')
     GROUP BY ym, etiqueta
     ORDER BY ym"
);

// Top productos 30d
$topProductos = fetchAll(
    $conexion,
    "SELECT p.id_producto, p.nombre, COALESCE(p.marca,'') AS marca,
            SUM(d.cantidad) AS unidades, SUM(d.cantidad * d.precio_unitario) AS ingresos
     FROM detalle_pedido d
     JOIN pedidos pe ON pe.id_pedido = d.id_pedido
     JOIN productos p ON d.id_producto = p.id_producto
     WHERE pe.fecha_pedido >= (NOW() - INTERVAL 30 DAY) AND pe.estado IN ('paid','shipped')
     GROUP BY p.id_producto, p.nombre, p.marca
     ORDER BY ingresos DESC
     LIMIT 8"
);

// Top categorías 30d
$topCategorias = fetchAll(
    $conexion,
    "SELECT c.nombre AS categoria, SUM(d.cantidad) AS unidades, SUM(d.cantidad * d.precio_unitario) AS ingresos
     FROM detalle_pedido d
     JOIN pedidos pe ON pe.id_pedido = d.id_pedido
     JOIN productos p ON d.id_producto = p.id_producto
     JOIN categorias c ON p.id_categoria = c.id_categoria
     WHERE pe.fecha_pedido >= (NOW() - INTERVAL 30 DAY) AND pe.estado IN ('paid','shipped')
     GROUP BY c.id_categoria, c.nombre
     ORDER BY ingresos DESC
     LIMIT 8"
);

// Stock vs demanda 30d
$stockCobertura = fetchAll(
    $conexion,
    "SELECT c.nombre AS categoria,
            COALESCE(SUM(p.stock),0) AS stock_total,
            COALESCE(SUM(d.cant_30),0) AS ventas_30
     FROM categorias c
     JOIN productos p ON p.id_categoria = c.id_categoria
     LEFT JOIN (
        SELECT dp.id_producto, SUM(dp.cantidad) AS cant_30
        FROM detalle_pedido dp
        JOIN pedidos pe ON pe.id_pedido = dp.id_pedido
        WHERE pe.fecha_pedido >= (NOW() - INTERVAL 30 DAY) AND pe.estado IN ('paid','shipped')
        GROUP BY dp.id_producto
     ) d ON d.id_producto = p.id_producto
     GROUP BY c.id_categoria, c.nombre
     ORDER BY stock_total DESC"
);

// Top clientes
$topClientes = fetchAll(
    $conexion,
    "SELECT COALESCE(email,'unknown') AS email, nombre, apellidos, COUNT(*) AS pedidos, COALESCE(SUM(total),0) AS ingresos
     FROM pedidos
     WHERE estado IN ('paid','shipped')
     GROUP BY email, nombre, apellidos
     ORDER BY ingresos DESC
     LIMIT 10"
);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Admin - stats | Gargot</title>
    <link rel="stylesheet" href="../css/estilos.css">
    <style>
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 14px; margin: 20px 0 12px; }
        .stats-card { border: 1px solid #e0e0e0; border-radius: 8px; padding: 12px 14px; background: #fafafa; }
        .stats-label { font-size: 11px; text-transform: uppercase; letter-spacing: 0.06em; color: #777; margin-bottom: 6px; }
        .stats-value { font-size: 20px; font-weight: 600; letter-spacing: 0.03em; color: #111; margin-bottom: 4px; }
        .stats-sub { font-size: 11px; color: #888; }
        .bar-row { display: flex; align-items: center; gap: 8px; margin: 6px 0; }
        .bar { height: 8px; border-radius: 999px; background: #e6e6e6; flex: 1; overflow: hidden; }
        .bar-fill { height: 100%; background: linear-gradient(90deg, #111, #444); }
        .section-title { font-size: 13px; text-transform: uppercase; letter-spacing: 0.08em; margin: 26px 0 10px; }
        .legend { font-size: 11px; color: #666; margin-top: 4px; }
        .table-inline { width: 100%; border-collapse: collapse; font-size: 12px; }
        .table-inline th, .table-inline td { padding: 8px 6px; border-bottom: 1px solid #eee; text-align: left; }
        .pill { display: inline-block; padding: 2px 8px; border-radius: 10px; font-size: 11px; background: #f2f2f2; }
        .growth-pos { color: #0b8a3f; }
        .growth-neg { color: #b00; }
        .heatmap { display: grid; grid-template-columns: 80px repeat(4, 1fr); gap: 4px; font-size: 11px; }
        .heat-cell { background: #f5f5f5; border-radius: 4px; padding: 6px; text-align: center; }
    </style>
</head>
<body>
<?php include '../includes/header.php'; ?>

<main class="contenedor admin-wrapper">
    <h1 class="admin-title">admin - stats</h1>

    <nav class="admin-subnav">
        <a href="products.php">products</a>
        <a href="home_cards.php">home images</a>
        <a href="shipments.php">orders</a>
        <a href="stats.php" class="active">stats</a>
    </nav>

    <section class="admin-section">
        <p class="admin-subtitle">
            enfoque vintage: marcas que rotan, piezas únicas y hábitos para ads y compras.
        </p>

        <div class="stats-grid">
            <div class="stats-card">
                <div class="stats-label">ingresos totales</div>
                <div class="stats-value">€ <?php echo number_format($ingresosTotales, 2); ?></div>
                <div class="stats-sub">pagados + enviados</div>
            </div>
            <div class="stats-card">
                <div class="stats-label">ingresos últimos 30d</div>
                <div class="stats-value">€ <?php echo number_format($ingresos30, 2); ?></div>
                <div class="stats-sub">
                    <?php if ($growth30 === null): ?>
                        sin histórico previo
                    <?php else: ?>
                        vs previos 30d:
                        <span class="<?php echo $growth30 >= 0 ? 'growth-pos' : 'growth-neg'; ?>">
                            <?php echo ($growth30 >= 0 ? '+' : '') . number_format($growth30, 1); ?>%
                        </span>
                    <?php endif; ?>
                </div>
            </div>
            <div class="stats-card">
                <div class="stats-label">pedidos 30d</div>
                <div class="stats-value"><?php echo $pedidos30; ?></div>
            </div>
            <div class="stats-card">
                <div class="stats-label">piezas únicas 30d</div>
                <div class="stats-value"><?php echo $piezasUnicas30; ?></div>
                <div class="stats-sub">cada pieza suele ser 1 unidad</div>
            </div>
            <div class="stats-card">
                <div class="stats-label">ticket medio (pagado)</div>
                <div class="stats-value">€ <?php echo number_format($ticketMedioReal, 2); ?></div>
                <div class="stats-sub">referencia para ROAS mínimo</div>
            </div>
        </div>

        <h2 class="section-title">marcas más fuertes (30d)</h2>
        <?php if (!empty($topMarcas)): ?>
            <?php $maxMarca = max(array_column($topMarcas, 'ingresos')) ?: 1; ?>
            <div>
                <?php foreach ($topMarcas as $m): ?>
                    <?php $pct = min(100, ($m['ingresos'] / $maxMarca) * 100); ?>
                    <div class="bar-row">
                        <div style="width:160px; font-size:12px;"><?php echo htmlspecialchars($m['marca']); ?></div>
                        <div class="bar"><div class="bar-fill" style="width: <?php echo $pct; ?>%;"></div></div>
                        <div style="width:90px; font-size:12px; text-align:right;">€ <?php echo number_format($m['ingresos'], 0); ?></div>
                        <div style="width:70px; font-size:11px; text-align:right;"><?php echo (int)$m['piezas']; ?> piezas</div>
                    </div>
                <?php endforeach; ?>
            </div>
            <div class="legend">Prioriza campañas o compras de las marcas que más ingresan.</div>
        <?php else: ?>
            <p class="txt-secundario">no brand data yet.</p>
        <?php endif; ?>

        <h2 class="section-title">precio medio 30d</h2>
        <?php if ($precioStats['avg'] !== null): ?>
            <div class="stats-grid">
                <div class="stats-card"><div class="stats-label">avg</div><div class="stats-value">€ <?php echo number_format($precioStats['avg'], 2); ?></div></div>
                <div class="stats-card"><div class="stats-label">p25</div><div class="stats-value">€ <?php echo number_format($precioStats['p25'], 2); ?></div></div>
                <div class="stats-card"><div class="stats-label">p75</div><div class="stats-value">€ <?php echo number_format($precioStats['p75'], 2); ?></div></div>
            </div>
            <div class="legend">Rango de precios para decidir PVP y promos sin bajar margen.</div>
        <?php else: ?>
            <p class="txt-secundario">no price data yet.</p>
        <?php endif; ?>

        <h2 class="section-title">heatmap semanal (pedidos 30d)</h2>
        <?php if (!empty($heatmap)): ?>
            <?php
                $blocks = [
                    ['label' => '00-05', 'range' => range(0,5)],
                    ['label' => '06-11', 'range' => range(6,11)],
                    ['label' => '12-17', 'range' => range(12,17)],
                    ['label' => '18-23', 'range' => range(18,23)],
                ];
                $dowLabels = ['Sun','Mon','Tue','Wed','Thu','Fri','Sat'];
                $matrix = [];
                foreach ($heatmap as $h) {
                    $d = (int)$h['dow']; $hour = (int)$h['hour']; $count = (int)$h['pedidos'];
                    foreach ($blocks as $bIndex => $b) {
                        if (in_array($hour, $b['range'])) {
                            if (!isset($matrix[$d])) $matrix[$d] = array_fill(0, count($blocks), 0);
                            $matrix[$d][$bIndex] += $count;
                        }
                    }
                }
                $maxHeat = 0;
                foreach ($matrix as $row) foreach ($row as $c) if ($c > $maxHeat) $maxHeat = $c;
                $maxHeat = $maxHeat ?: 1;
            ?>
            <div class="heatmap">
                <div></div>
                <?php foreach ($blocks as $b): ?>
                    <div style="text-align:center;"><?php echo $b['label']; ?></div>
                <?php endforeach; ?>
                <?php foreach ($dowLabels as $dIndex => $dLabel): ?>
                    <div><?php echo $dLabel; ?></div>
                    <?php for ($i=0; $i<count($blocks); $i++): ?>
                        <?php $val = $matrix[$dIndex][$i] ?? 0; $pct = min(100, ($val / $maxHeat) * 100); ?>
                        <div class="heat-cell" style="background: linear-gradient(90deg, rgba(17,17,17,<?php echo $pct/100; ?>), rgba(17,17,17,<?php echo $pct/200; ?>));">
                            <?php echo $val; ?>
                        </div>
                    <?php endfor; ?>
                <?php endforeach; ?>
            </div>
            <div class="legend">Horas/días con más pedidos: idea para lanzar drops o ads.</div>
        <?php else: ?>
            <p class="txt-secundario">no time data yet.</p>
        <?php endif; ?>

        <h2 class="section-title">tendencia mensual (12m)</h2>
        <?php if (!empty($mensual)): ?>
            <?php $maxIngresos = max(array_column($mensual, 'ingresos')) ?: 1; ?>
            <div>
                <?php foreach ($mensual as $m): ?>
                    <?php $pct = min(100, ($m['ingresos'] / $maxIngresos) * 100); ?>
                    <div class="bar-row">
                        <div style="width:120px; font-size:12px;"><?php echo htmlspecialchars($m['etiqueta']); ?></div>
                        <div class="bar"><div class="bar-fill" style="width: <?php echo $pct; ?>%;"></div></div>
                        <div style="width:80px; font-size:12px; text-align:right;">€ <?php echo number_format($m['ingresos'], 0); ?></div>
                    </div>
                <?php endforeach; ?>
            </div>
            <div class="legend">Detecta estacionalidad de ingresos.</div>
        <?php else: ?>
            <p class="txt-secundario">no monthly data yet.</p>
        <?php endif; ?>

        <h2 class="section-title">top categorías (30d)</h2>
        <?php if (!empty($topCategorias)): ?>
            <?php $maxCat = max(array_column($topCategorias, 'ingresos')) ?: 1; ?>
            <div>
                <?php foreach ($topCategorias as $cat): ?>
                    <?php $pct = min(100, ($cat['ingresos'] / $maxCat) * 100); ?>
                    <div class="bar-row">
                        <div style="width:150px; font-size:12px;"><?php echo htmlspecialchars($cat['categoria']); ?></div>
                        <div class="bar"><div class="bar-fill" style="width: <?php echo $pct; ?>%;"></div></div>
                        <div style="width:90px; font-size:12px; text-align:right;">€ <?php echo number_format($cat['ingresos'], 0); ?></div>
                    </div>
                <?php endforeach; ?>
            </div>
            <div class="legend">Refuerza stock y ads en categorías líderes.</div>
        <?php else: ?>
            <p class="txt-secundario">no category data yet.</p>
        <?php endif; ?>

        <h2 class="section-title">top productos (30d)</h2>
        <?php if (!empty($topProductos)): ?>
            <table class="table-inline">
                <thead><tr><th>producto</th><th>marca</th><th>unidades</th><th>ingresos (€)</th></tr></thead>
                <tbody>
                    <?php foreach ($topProductos as $prod): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($prod['nombre']); ?></td>
                            <td><?php echo htmlspecialchars($prod['marca']); ?></td>
                            <td><?php echo (int)$prod['unidades']; ?></td>
                            <td><?php echo number_format((float)$prod['ingresos'], 2); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <div class="legend">Útil para creatividades y remarketing dinámico.</div>
        <?php else: ?>
            <p class="txt-secundario">no sales data yet.</p>
        <?php endif; ?>

        <h2 class="section-title">tallas vendidas (30d)</h2>
        <?php if (!empty($tallas30)): ?>
            <?php $maxTalla = max(array_column($tallas30, 'unidades')) ?: 1; ?>
            <div>
                <?php foreach ($tallas30 as $t): ?>
                    <?php $pct = min(100, ($t['unidades'] / $maxTalla) * 100); ?>
                    <div class="bar-row">
                        <div style="width:80px; font-size:12px;"><?php echo htmlspecialchars($t['talla']); ?></div>
                        <div class="bar"><div class="bar-fill" style="width: <?php echo $pct; ?>%;"></div></div>
                        <div style="width:80px; font-size:12px; text-align:right;"><?php echo (int)$t['unidades']; ?> uds</div>
                    </div>
                <?php endforeach; ?>
            </div>
            <div class="legend">Planifica compras por talla según rotación.</div>
        <?php else: ?>
            <p class="txt-secundario">no size data yet.</p>
        <?php endif; ?>

        <h2 class="section-title">stock vs demanda (30d)</h2>
        <?php if (!empty($stockCobertura)): ?>
            <table class="table-inline">
                <thead><tr><th>categoría</th><th>stock total</th><th>ventas 30d</th><th>cobertura</th></tr></thead>
                <tbody>
                    <?php foreach ($stockCobertura as $row): ?>
                        <?php
                            $stock = (int)$row['stock_total'];
                            $ventas = (int)$row['ventas_30'];
                            $cobertura = $ventas > 0 ? round($stock / $ventas, 1) . 'x' : 'alta';
                        ?>
                        <tr>
                            <td><?php echo htmlspecialchars($row['categoria']); ?></td>
                            <td><?php echo $stock; ?></td>
                            <td><?php echo $ventas; ?></td>
                            <td><span class="pill"><?php echo $cobertura; ?></span></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <div class="legend">Cobertura: stock / ventas 30d. &lt;1x reponer, &gt;3x suficiente.</div>
        <?php else: ?>
            <p class="txt-secundario">no stock data yet.</p>
        <?php endif; ?>

        <h2 class="section-title">top clientes</h2>
        <?php if (!empty($topClientes)): ?>
            <table class="table-inline">
                <thead><tr><th>cliente</th><th>email</th><th>pedidos</th><th>ingresos (€)</th></tr></thead>
                <tbody>
                    <?php foreach ($topClientes as $cli): ?>
                        <tr>
                            <td><?php echo htmlspecialchars(trim($cli['nombre'].' '.$cli['apellidos'])); ?></td>
                            <td><?php echo htmlspecialchars($cli['email']); ?></td>
                            <td><?php echo (int)$cli['pedidos']; ?></td>
                            <td><?php echo number_format((float)$cli['ingresos'], 2); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <div class="legend">Fideliza y crea audiencias similares.</div>
        <?php else: ?>
            <p class="txt-secundario">no customer data yet.</p>
        <?php endif; ?>
    </section>
</main>

</body>
</html>
