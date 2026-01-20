<?php
session_start();
include("includes/conexion.php");

if (!isset($_GET["id"])) {
    header("Location: shop.php");
    exit();
}

$id = intval($_GET["id"]);

// Visibilidad
$hasVisibility = false;
$colCheck = $pdo->query("SHOW COLUMNS FROM productos LIKE 'is_visible'");
$colRows = $colCheck ? $colCheck->fetchAll() : [];
if (count($colRows) > 0) {
    $hasVisibility = true;
}

// Obtener producto
$sql = "SELECT p.*, c.nombre AS categoria
        FROM productos p
        JOIN categorias c ON p.id_categoria = c.id_categoria
        WHERE p.id_producto = :id_producto";
$stmt = $pdo->prepare($sql);
$stmt->execute(["id_producto" => $id]);
$prod = $stmt->fetch();

if ($prod === false) {
    header("Location: shop.php");
    exit();
}

if ($hasVisibility && isset($prod['is_visible']) && (int)$prod['is_visible'] !== 1) {
    header("Location: shop.php");
    exit();
}

$isSoldOut = ((int)$prod['stock'] <= 0);

// Obtener imÃ¡genes
$imagenes = [];
$sqlImg = "SELECT image_path FROM producto_imagenes WHERE id_producto = :id_producto ORDER BY orden ASC, id ASC";
$stmtImg = $pdo->prepare($sqlImg);
$stmtImg->execute(["id_producto" => $id]);
$rowsImg = $stmtImg->fetchAll();
foreach ($rowsImg as $row) {
    if (!empty($row["image_path"])) $imagenes[] = $row["image_path"];
}

if (empty($imagenes) && !empty($prod["imagen"])) {
    $imagenes[] = $prod["imagen"];
}

// Contadores iconos
$itemsCarrito = 0;
if (!empty($_SESSION['carrito'])) {
    foreach ($_SESSION['carrito'] as $item) {
        $itemsCarrito += $item['cantidad'];
    }
}
$wishlistCount = !empty($_SESSION["wishlist"]) ? count($_SESSION["wishlist"]) : 0;
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title><?php echo htmlspecialchars($prod["nombre"]); ?> | Gargot</title>
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <link rel="stylesheet" href="css/estilos.css?v=3">
</head>
<body>

<?php include 'includes/header.php'; ?>

<main class="contenedor product-page">
    <div class="product-gallery">
        <?php if (!empty($imagenes)): ?>
            <div class="product-gallery-main">
                <img id="product-main-img" src="<?php echo htmlspecialchars($imagenes[0]); ?>" alt="<?php echo htmlspecialchars($prod["nombre"]); ?>">
                <?php if ($isSoldOut): ?>
                    <span class="badge-soldout">sold out</span>
                <?php endif; ?>
                <?php if (count($imagenes) > 1): ?>
                    <button class="product-gallery-arrow product-gallery-arrow--prev" data-role="gallery-prev">&#8249;</button>
                    <button class="product-gallery-arrow product-gallery-arrow--next" data-role="gallery-next">&#8250;</button>
                <?php endif; ?>
            </div>
            <?php if (count($imagenes) > 1): ?>
                <div class="product-gallery-thumbs">
                    <?php foreach ($imagenes as $i => $img): ?>
                        <button class="product-thumb-btn<?php echo $i===0?' active':'';?>" data-role="product-thumb" data-image="<?php echo htmlspecialchars($img); ?>">
                            <img src="<?php echo htmlspecialchars($img); ?>" alt="">
                        </button>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>

    <div class="info">
        <h1><?php echo htmlspecialchars($prod["nombre"]); ?></h1>

        <p class="descripcion-pequena">
            [<?php echo htmlspecialchars($prod["familia"]); ?>]
            <?php if (!empty($prod["talla"])): ?>
                (<?php echo htmlspecialchars($prod["talla"]); ?>)
            <?php endif; ?>
             · <?php echo htmlspecialchars($prod["marca"]); ?>
        </p>

        <p class="price">
            <?php echo number_format($prod["precio"],2); ?> €
            <?php if ($isSoldOut): ?>
                <span class="soldout-text" style="margin-left:8px;">sold out</span>
            <?php endif; ?>
        </p>

        <div class="product-actions">
            <?php if ($isSoldOut): ?>
                <button class="btn-product" id="addToCartBtn" disabled>sold out</button>
            <?php else: ?>
                <form method="post" action="user/add_to_cart.php" id="addToCartForm">
                    <input type="hidden" name="id_producto" value="<?php echo $id; ?>">
                    <button type="submit" class="btn-product" id="addToCartBtn">add to cart</button>
                </form>
            <?php endif; ?>

            <form method="post" action="user/add_to_wishlist.php" id="addToWishlistForm">
                <input type="hidden" name="id_producto" value="<?php echo $id; ?>">
                <button type="submit" class="btn-product" id="addToWishlistBtn">add to wishlist</button>
            </form>
        </div>

        <p class="description"><?php echo nl2br(htmlspecialchars($prod["descripcion"])); ?></p>

        <p class="descripcion-pequena">
            Stock disponible: <?php echo (int)$prod["stock"]; ?>
        </p>
    </div>
</main>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const mainImg = document.getElementById('product-main-img');
    const thumbs  = [...document.querySelectorAll('[data-role="product-thumb"]')];
    const prevBtn = document.querySelector('[data-role="gallery-prev"]');
    const nextBtn = document.querySelector('[data-role="gallery-next"]');

    if (!mainImg || thumbs.length === 0) return;

    let index = 0;

    function change(i){
        index = i;
        mainImg.src = thumbs[i].dataset.image;
        thumbs.forEach(t=>t.classList.remove('active'));
        thumbs[i].classList.add('active');
    }

    thumbs.forEach((b,i)=>b.addEventListener('click',()=>change(i)));

    if(prevBtn) prevBtn.addEventListener('click',()=> change((index-1+thumbs.length)%thumbs.length));
    if(nextBtn) nextBtn.addEventListener('click',()=> change((index+1)%thumbs.length));
});
</script>

</body>
</html>


