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

// Visibilidad
$hasVisibility = false;
$colCheck = $pdo->query("SHOW COLUMNS FROM productos LIKE 'is_visible'");
$colRows = $colCheck ? $colCheck->fetchAll() : [];
if (count($colRows) > 0) {
    $hasVisibility = true;
}

// Filtros saneados
$familiasPermitidas = ["TOPS","BOTTOMS","DRESSES","OUTERWEAR","SKIRTS","TWO-PIECE","ACCESSORIES","SHOES"];
$tallasPermitidas   = ["XS","S","M","L","XL","UNIQUE"];
$preciosPermitidos  = ["<50","50-100","100-150","150-200","200-250",">250"];
$ordenPermitido     = ["price_asc","price_desc","discount_desc"];

$familia = strtoupper(trim($_GET['familia'] ?? ''));
if (!in_array($familia, $familiasPermitidas, true)) {
    $familia = '';
}

$talla = strtoupper(trim($_GET['talla'] ?? ''));
if (!in_array($talla, $tallasPermitidas, true)) {
    $talla = '';
}

$marca = trim($_GET['marca'] ?? '');
if (mb_strlen($marca) > 80) {
    $marca = mb_substr($marca, 0, 80);
}

$precio = trim($_GET['precio'] ?? '');
if (!in_array($precio, $preciosPermitidos, true)) {
    $precio = '';
}

$orden = trim($_GET['orden'] ?? '');
if (!in_array($orden, $ordenPermitido, true)) {
    $orden = '';
}

// Lista marcas
$resMarcas = $pdo->query("SELECT DISTINCT marca FROM productos WHERE marca IS NOT NULL AND marca <> '' ORDER BY marca ASC");
$marcas = $resMarcas ? $resMarcas->fetchAll() : [];

// Query base
$where = ["c.nombre = 'CLOTHING'"];
$params = [];

if ($hasVisibility) {
    $where[] = "p.is_visible = 1";
}
if ($familia !== '') {
    $where[] = "p.familia = :familia";
    $params["familia"] = $familia;
}
if ($talla !== '') {
    $where[] = "p.talla = :talla";
    $params["talla"] = $talla;
}
if ($marca !== '') {
    $where[] = "p.marca LIKE :marca";
    $params["marca"] = "%" . $marca . "%";
}
// Filtro precio
if ($precio === '<50')      { $where[] = "p.precio < 50"; }
elseif ($precio === '50-100')  { $where[] = "p.precio BETWEEN 50 AND 100"; }
elseif ($precio === '100-150') { $where[] = "p.precio BETWEEN 100 AND 150"; }
elseif ($precio === '150-200') { $where[] = "p.precio BETWEEN 150 AND 200"; }
elseif ($precio === '200-250') { $where[] = "p.precio BETWEEN 200 AND 250"; }
elseif ($precio === '>250')    { $where[] = "p.precio > 250"; }

$ordenSQL = " ORDER BY p.fecha_creacion DESC";
if ($orden === 'price_asc') {
    $ordenSQL = " ORDER BY p.precio ASC";
} elseif ($orden === 'price_desc') {
    $ordenSQL = " ORDER BY p.precio DESC";
} elseif ($orden === 'discount_desc') {
    $ordenSQL = " ORDER BY p.descuento DESC";
}

$sql = "SELECT p.*, c.nombre AS categoria
        FROM productos p
        JOIN categorias c ON p.id_categoria = c.id_categoria
        WHERE ".implode(" AND ", $where). $ordenSQL;

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Shop - Clothing | Gargot</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="css/estilos.css?v=3">
</head>
<body>
<?php include 'includes/header.php'; ?>

<main class="contenedor">
    <h1 class="titulo-pagina">SHOP</h1>

    <form class="filtros" method="get" action="shop.php">
        <details class="filters-panel" <?php if ($familia || $talla || $marca || $precio) echo 'open'; ?>>
            <summary>filters</summary>
            <div class="filters-content">
                <label>
                    garment family
                    <select name="familia">
                        <option value="">all</option>
                        <?php
                        $familias = ["TOPS","BOTTOMS","DRESSES","OUTERWEAR","SKIRTS","TWO-PIECE","ACCESSORIES","SHOES"];
                        foreach ($familias as $f) {
                            $sel = ($familia === $f) ? 'selected' : '';
                            echo "<option value=\"$f\" $sel>" . strtolower($f) . "</option>";
                        }
                        ?>
                    </select>
                </label>

                <label>
                    size
                    <select name="talla">
                        <option value="">all</option>
                        <?php
                        $tallas = ["XS","S","M","L","XL","UNIQUE"];
                        foreach ($tallas as $t) {
                            $sel = ($talla === $t) ? 'selected' : '';
                            echo "<option value=\"$t\" $sel>" . strtolower($t) . "</option>";
                        }
                        ?>
                    </select>
                </label>

                <label>
                    brand
                    <input type="text" name="marca" list="lista_marcas" value="<?php echo htmlspecialchars($marca); ?>">
                    <datalist id="lista_marcas">
                        <?php if (count($marcas) > 0): ?>
                            <?php foreach ($marcas as $m): ?>
                                <option value="<?php echo htmlspecialchars($m["marca"]); ?>">
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </datalist>
                </label>

                <label>
                    price
                    <select name="precio">
                        <?php
                        $precios = [
                            "" => "all",
                            "<50" => "< 50 &euro;",
                            "50-100" => "50 - 100 &euro;",
                            "100-150" => "100 - 150 &euro;",
                            "150-200" => "150 - 200 &euro;",
                            "200-250" => "200 - 250 &euro;",
                            ">250" => "> 250 &euro;"
                        ];
                        foreach ($precios as $val => $label) {
                            $sel = ($precio === $val) ? 'selected' : '';
                            echo "<option value=\"$val\" $sel>$label</option>";
                        }
                        ?>
                    </select>
                </label>
            </div>
        </details>

        <details class="order-panel" <?php if ($orden) echo 'open'; ?>>
            <summary>order by</summary>
            <div class="order-content">
                <label><input type="radio" name="orden" value="price_asc" <?php if ($orden==='price_asc') echo 'checked'; ?>>price: low to high</label>
                <label><input type="radio" name="orden" value="price_desc" <?php if ($orden==='price_desc') echo 'checked'; ?>>price: high to low</label>
                <label><input type="radio" name="orden" value="discount_desc" <?php if ($orden==='discount_desc') echo 'checked'; ?>>discount: high to low</label>
                <label><input type="radio" name="orden" value="" <?php if ($orden==='') echo 'checked'; ?>>default (newest)</label>
            </div>
        </details>

        <button type="submit" class="btn-filtros-apply">apply</button>
        <a href="shop.php" class="btn-filtros-clear">clear</a>
    </form>

    <?php if (count($rows) > 0): ?>
        <div class="grid-productos">
            <?php foreach ($rows as $p): ?>
                <?php $isSoldOut = ((int)$p["stock"] <= 0); ?>
                <div class="producto-card">
                    <a href="product_detail.php?id=<?php echo (int)$p["id_producto"]; ?>" style="color: inherit; text-decoration: none;">
                        <div class="producto-imagen-wrap">
                            <?php if (!empty($p["imagen"])): ?>
                                <img src="<?php echo htmlspecialchars($p["imagen"]); ?>" alt="<?php echo htmlspecialchars($p["nombre"]); ?>">
                            <?php else: ?>
                                <div class="placeholder-img">SIN IMAGEN</div>
                            <?php endif; ?>

                            <?php if ($isSoldOut): ?>
                                <span class="badge-soldout">sold out</span>
                            <?php endif; ?>

                            <div class="overlay">
                                <div class="overlay-info descripcion-pequena">
                                    <div class="overlay-precio"><?php echo number_format($p["precio"], 2); ?> €</div>

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
                            <div class="linea2 marca"><?php echo htmlspecialchars($p["marca"]); ?></div>
                            <div class="linea3 nombre"><?php echo htmlspecialchars($p["nombre"]); ?></div>
                        </div>
                    </a>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <p>No hay productos en Clothing.</p>
    <?php endif; ?>
</main>

</body>
</html>


