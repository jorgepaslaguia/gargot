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
if (count($colRows) > 0) $hasVisibility = true;

// CSRF
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(16));
}
$csrfToken = $_SESSION['csrf_token'];

// Categorías
$resCat = $pdo->query("SELECT id_categoria, nombre FROM categorias ORDER BY nombre ASC");
$categorias = $resCat ? $resCat->fetchAll() : [];

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$producto = [
    'nombre'       => '',
    'familia'      => '',
    'talla'        => '',
    'id_categoria' => '',
    'marca'        => '',
    'precio'       => '',
    'descuento'    => 0,
    'stock'        => 0,
    'imagen'       => '',
    'descripcion'  => '',
    'is_visible'   => 1,
];
$imagenes_existentes = [];
$errores = [];

// Cargar producto e imágenes
if ($id > 0 && $_SERVER['REQUEST_METHOD'] === 'GET') {
    $sql = "SELECT * FROM productos WHERE id_producto = :id_producto";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(["id_producto" => $id]);
    $fila = $stmt->fetch();
    if ($fila) {
        $producto = array_merge($producto, $fila);
    }

    $sqlImg = "SELECT id, image_path, orden FROM producto_imagenes WHERE id_producto = :id_producto ORDER BY orden ASC, id ASC";
    $stmtImg = $pdo->prepare($sqlImg);
    $stmtImg->execute(["id_producto" => $id]);
    $rowsImg = $stmtImg->fetchAll();
    foreach ($rowsImg as $row) {
        $imagenes_existentes[] = $row;
    }
}

// Guardar
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $errores[] = "Sesión inválida. Recarga la página.";
    }

    $nombre       = trim($_POST['nombre'] ?? '');
    $familia      = trim($_POST['familia'] ?? '');
    $talla        = trim($_POST['talla'] ?? '');
    $idCategoria  = intval($_POST['id_categoria'] ?? 0);
    $marca        = trim($_POST['marca'] ?? '');
    $precio       = floatval($_POST['precio'] ?? 0);
    $descuento    = floatval($_POST['descuento'] ?? 0);
    $stock        = intval($_POST['stock'] ?? 0);
    $descripcion  = trim($_POST['descripcion'] ?? '');
    $isVisible    = $hasVisibility ? (isset($_POST['is_visible']) ? 1 : 0) : 1;

    $producto = array_merge($producto, [
        'nombre' => $nombre,
        'familia' => $familia,
        'talla' => $talla,
        'id_categoria' => $idCategoria,
        'marca' => $marca,
        'precio' => $precio,
        'descuento' => $descuento,
        'stock' => $stock,
        'descripcion' => $descripcion,
        'is_visible' => $isVisible,
    ]);

    if ($nombre === '')    $errores[] = "name is required.";
    if ($familia === '')   $errores[] = "garment family is required.";
    if ($idCategoria <= 0) $errores[] = "category is required.";
    if ($precio <= 0)      $errores[] = "price must be greater than 0.";

    // Imágenes existentes
    $existingImages = [];
    if ($id > 0) {
        $sqlImg = "SELECT id, image_path, orden FROM producto_imagenes WHERE id_producto = :id_producto ORDER BY orden ASC, id ASC";
        $stmtImg = $pdo->prepare($sqlImg);
        $stmtImg->execute(["id_producto" => $id]);
        $rowsImg = $stmtImg->fetchAll();
        foreach ($rowsImg as $row) {
            $existingImages[] = $row;
        }
    }

    $deleteIds = $_POST['delete_images'] ?? [];
    $deleteIds = array_map('intval', $deleteIds);

    $imageOrderInput = $_POST['image_order'] ?? [];
    $newImageOrderInput = $_POST['new_image_order'] ?? [];
    $existingOrderMap = [];
    $maxOrder = 0;
    $imagenPrincipal = '';
    $principalOrder = PHP_INT_MAX;

    $imagenes = [];
    foreach ($existingImages as $img) {
        $imgId = (int)$img['id'];
        if (in_array($imgId, $deleteIds, true)) {
            continue;
        }

        $ordenRaw = isset($imageOrderInput[$imgId]) ? trim((string)$imageOrderInput[$imgId]) : '';
        if ($ordenRaw === '') {
            $orden = (int)$img['orden'];
        } elseif (ctype_digit($ordenRaw)) {
            $orden = (int)$ordenRaw;
        } else {
            $errores[] = "image order must be an integer (0 or greater).";
            $orden = (int)$img['orden'];
        }

        $existingOrderMap[$imgId] = $orden;
        if ($orden > $maxOrder) {
            $maxOrder = $orden;
        }
        if ($orden < $principalOrder) {
            $principalOrder = $orden;
            $imagenPrincipal = $img['image_path'];
        }

        $imagenes[] = $img['image_path'];
    }

    $imagenes_nuevas = [];
    $imagenes_nuevas_orden = [];
    $maxFiles = 10;
    $haySubidaNueva = (
        isset($_FILES['imagenes']) &&
        isset($_FILES['imagenes']['name']) &&
        is_array($_FILES['imagenes']['name']) &&
        $_FILES['imagenes']['name'][0] !== ''
    );

    if ($haySubidaNueva) {
        $uploadDir = __DIR__ . "/../img/products/";
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0775, true);
        }
        $fileNames = $_FILES['imagenes']['name'];
        $fileTmp   = $_FILES['imagenes']['tmp_name'];
        $fileErr   = $_FILES['imagenes']['error'];
        $fileCount = count($fileNames);

        if (count($imagenes) + $fileCount > $maxFiles) {
            $errores[] = "maximum 10 images per product.";
        } else {
            foreach ($fileNames as $idx => $nameOriginal) {
                if (!isset($fileErr[$idx]) || $fileErr[$idx] !== UPLOAD_ERR_OK) continue;
                $tmpName = $fileTmp[$idx];
                $name    = basename($nameOriginal);
                if ($name === '' || !is_uploaded_file($tmpName)) continue;

                $ext      = pathinfo($name, PATHINFO_EXTENSION);
                $base     = pathinfo($name, PATHINFO_FILENAME);
                $safeBase = preg_replace('/[^a-zA-Z0-9_\-]/', '_', $base);

                $newName  = $safeBase . '_' . time() . '_' . $idx . '.' . $ext;
                $destPath = $uploadDir . $newName;

                if (move_uploaded_file($tmpName, $destPath)) {
                    $rutaRelativa = "img/products/" . $newName;
                    $ordenRaw = isset($newImageOrderInput[$idx]) ? trim((string)$newImageOrderInput[$idx]) : '';
                    if ($ordenRaw === '') {
                        $maxOrder++;
                        $orden = $maxOrder;
                    } elseif (ctype_digit($ordenRaw)) {
                        $orden = (int)$ordenRaw;
                    } else {
                        $errores[] = "image order must be an integer (0 or greater).";
                        continue;
                    }

                    $imagenes[] = $rutaRelativa;
                    $imagenes_nuevas[] = $rutaRelativa;
                    $imagenes_nuevas_orden[] = $orden;

                    if ($orden > $maxOrder) {
                        $maxOrder = $orden;
                    }
                    if ($orden < $principalOrder) {
                        $principalOrder = $orden;
                        $imagenPrincipal = $rutaRelativa;
                    }
                }
            }
        }
    }

    if (count($imagenes) === 0) {
        $errores[] = "at least one image is required.";
    }
    if ($imagenPrincipal === '' && !empty($imagenes)) {
        $imagenPrincipal = $imagenes[0];
    }
    if (empty($errores)) {
        $allOrders = array_values($existingOrderMap);
        foreach ($imagenes_nuevas_orden as $orden) {
            $allOrders[] = $orden;
        }
        if (count($allOrders) !== count(array_unique($allOrders))) {
            $errores[] = "image order values must be unique.";
        }
    }

    if (empty($errores)) {
        $pdo->beginTransaction();
        try {
            if ($id > 0) {
                $sql = "UPDATE productos
                        SET nombre = :nombre, familia = :familia, talla = :talla, id_categoria = :id_categoria, marca = :marca, precio = :precio, descuento = :descuento, stock = :stock, descripcion = :descripcion";
                if ($hasVisibility) {
                    $sql .= ", is_visible = :is_visible";
                }
                $sql .= " WHERE id_producto = :id_producto";
                $params = [
                    "nombre" => $nombre,
                    "familia" => $familia,
                    "talla" => $talla,
                    "id_categoria" => $idCategoria,
                    "marca" => $marca,
                    "precio" => $precio,
                    "descuento" => $descuento,
                    "stock" => $stock,
                    "descripcion" => $descripcion,
                    "id_producto" => $id,
                ];
                if ($hasVisibility) {
                    $params["is_visible"] = $isVisible;
                }
                $stmt = $pdo->prepare($sql);
                $stmt->execute($params);
            } else {
                $sql = "INSERT INTO productos
                        (nombre, familia, talla, id_categoria, marca, precio, descuento, stock, imagen, descripcion";
                if ($hasVisibility) {
                    $sql .= ", is_visible";
                }
                $sql .= ", fecha_creacion) VALUES (:nombre, :familia, :talla, :id_categoria, :marca, :precio, :descuento, :stock, :imagen, :descripcion";
                if ($hasVisibility) {
                    $sql .= ", :is_visible";
                }
                $sql .= ", NOW())";

                $imagenVacia = '';
                $params = [
                    "nombre" => $nombre,
                    "familia" => $familia,
                    "talla" => $talla,
                    "id_categoria" => $idCategoria,
                    "marca" => $marca,
                    "precio" => $precio,
                    "descuento" => $descuento,
                    "stock" => $stock,
                    "imagen" => $imagenVacia,
                    "descripcion" => $descripcion,
                ];
                if ($hasVisibility) {
                    $params["is_visible"] = $isVisible;
                }
                $stmt = $pdo->prepare($sql);
                $stmt->execute($params);
                $id = (int)$pdo->lastInsertId();
            }

            if (!empty($deleteIds)) {
                $stmtDel = $pdo->prepare("DELETE FROM producto_imagenes WHERE id = :id AND id_producto = :id_producto");
                foreach ($deleteIds as $delId) {
                    $stmtDel->execute([
                        "id" => $delId,
                        "id_producto" => $id,
                    ]);
                }
            }

            if (!empty($existingOrderMap)) {
                $stmtOrd = $pdo->prepare("UPDATE producto_imagenes SET orden = :orden WHERE id = :id AND id_producto = :id_producto");
                foreach ($existingOrderMap as $imgId => $orden) {
                    $stmtOrd->execute([
                        "orden" => $orden,
                        "id" => $imgId,
                        "id_producto" => $id,
                    ]);
                }
            }

            if (!empty($imagenes_nuevas)) {
                $stmtIns = $pdo->prepare("INSERT INTO producto_imagenes (id_producto, image_path, orden) VALUES (:id_producto, :image_path, :orden)");
                foreach ($imagenes_nuevas as $idx => $ruta) {
                    $orden = $imagenes_nuevas_orden[$idx] ?? ($idx + 1);
                    $stmtIns->execute([
                        "id_producto" => $id,
                        "image_path" => $ruta,
                        "orden" => $orden,
                    ]);
                }
            }

            $imagen_principal = $imagenPrincipal;
            $stmtUpdImg = $pdo->prepare("UPDATE productos SET imagen = :imagen WHERE id_producto = :id_producto");
            $stmtUpdImg->execute([
                "imagen" => $imagen_principal,
                "id_producto" => $id,
            ]);

            $pdo->commit();
            header("Location: products.php");
            exit();
        } catch (Exception $ex) {
            $pdo->rollBack();
            $errores[] = $ex->getMessage();
        }
    }

    if ($id > 0) {
        $imagenes_existentes = [];
        $sqlImg = "SELECT id, image_path, orden FROM producto_imagenes WHERE id_producto = :id_producto ORDER BY orden ASC, id ASC";
        $stmtImg = $pdo->prepare($sqlImg);
        $stmtImg->execute(["id_producto" => $id]);
        $rowsImg = $stmtImg->fetchAll();
        foreach ($rowsImg as $row) {
            $imagenes_existentes[] = $row;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title><?php echo $id > 0 ? 'Admin - edit product' : 'Admin - new product'; ?> | Gargot</title>
    <link rel="stylesheet" href="../css/estilos.css">
</head>
<body>
<?php include '../includes/header.php'; ?>

<main class="contenedor admin-main">
    <h1 class="titulo-pagina"><?php echo $id > 0 ? 'admin - edit product' : 'admin - new product'; ?></h1>

    <?php if (!empty($errores)): ?>
        <div class="admin-errors">
            <?php foreach ($errores as $e): ?>
                <div>- <?php echo htmlspecialchars($e); ?></div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <form method="post" enctype="multipart/form-data" class="admin-form">
        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
        <div class="form-row">
            <label>name</label>
            <input type="text" name="nombre" required value="<?php echo htmlspecialchars($producto['nombre']); ?>">
        </div>

        <div class="form-row">
            <label>garment family</label>
            <input type="text" name="familia" value="<?php echo htmlspecialchars($producto['familia']); ?>">
        </div>

        <div class="form-row">
            <label>size</label>
            <input type="text" name="talla" value="<?php echo htmlspecialchars($producto['talla']); ?>">
        </div>

        <div class="form-row">
            <label>category</label>
            <select name="id_categoria">
                <option value="">-- select --</option>
                <?php foreach ($categorias as $cat): ?>
                    <option value="<?php echo (int)$cat['id_categoria']; ?>" <?php if ($producto['id_categoria'] == $cat['id_categoria']) echo 'selected'; ?>>
                        <?php echo htmlspecialchars($cat['nombre']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-row">
            <label>brand</label>
            <input type="text" name="marca" value="<?php echo htmlspecialchars($producto['marca']); ?>">
        </div>

        <div class="form-row">
            <label>price (€)</label>
            <input type="number" step="0.01" name="precio" required value="<?php echo htmlspecialchars($producto['precio']); ?>">
        </div>

        <div class="form-row">
            <label>discount (%)</label>
            <input type="number" step="0.01" name="descuento" value="<?php echo htmlspecialchars($producto['descuento']); ?>">
        </div>

        <div class="form-row">
            <label>stock</label>
            <input type="number" name="stock" value="<?php echo htmlspecialchars($producto['stock']); ?>">
        </div>

        <?php if ($hasVisibility): ?>
        <div class="form-row">
            <label>
                <input type="checkbox" name="is_visible" <?php if ((int)$producto['is_visible'] === 1) echo 'checked'; ?>>
                visible (mostrar en la tienda)
            </label>
        </div>
        <?php endif; ?>

        <div class="form-row">
            <label>description</label>
            <textarea name="descripcion" rows="4"><?php echo htmlspecialchars($producto['descripcion']); ?></textarea>
        </div>

        <div class="form-row">
            <label>current images</label>
            <?php if (!empty($imagenes_existentes)): ?>
                <div class="product-images-grid">
                    <?php foreach ($imagenes_existentes as $img): ?>
                        <div class="product-images-grid-item">
                            <img src="../<?php echo htmlspecialchars($img['image_path']); ?>" alt="product image">
                            <label>
                                <input type="checkbox" name="delete_images[]" value="<?php echo (int)$img['id']; ?>"> delete
                            </label>
                            <label>
                                order (lower = first)
                                <input type="number" name="image_order[<?php echo (int)$img['id']; ?>]" min="0" step="1"
                                       value="<?php echo htmlspecialchars($img['orden']); ?>">
                            </label>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <p class="dz-info">no images yet.</p>
            <?php endif; ?>
            <p class="dz-info">mark delete to remove on save.</p>
        </div>

        <div class="form-row">
            <label>upload images (max 10 total)</label>
            <div class="dropzone" id="dropzone-images">
                <p class="dz-title">drag & drop images here</p>
                <p class="dz-subtitle">or</p>
                <button type="button" class="btn-admin-secondary" id="btn-select-images">select images</button>
                <input type="file" name="imagenes[]" id="input-images" accept="image/*" multiple hidden>
                <p class="dz-info">max 20 mb · up to 10 files</p>
            </div>
            <p class="dz-info">image order (lower = first)</p>
            <div class="dz-filenames" id="dz-filenames">no files selected</div>
        </div>

        <div class="form-actions">
            <button type="submit" class="btn-admin-primary">save</button>
            <a href="products.php" class="btn-admin-link">cancel</a>
        </div>
    </form>
</main>

<script>
const dz = document.getElementById('dropzone-images');
const input = document.getElementById('input-images');
const btn = document.getElementById('btn-select-images');
const filenames = document.getElementById('dz-filenames');
const dtGlobal = new DataTransfer();

function refreshFileList() {
    while (filenames.firstChild) filenames.removeChild(filenames.firstChild);
    if (dtGlobal.files && dtGlobal.files.length > 0) {
        for (let i = 0; i < dtGlobal.files.length; i++) {
            const row = document.createElement('div');
            row.className = 'dz-file-row';

            const name = document.createElement('span');
            name.textContent = dtGlobal.files[i].name;

            const order = document.createElement('input');
            order.type = 'number';
            order.name = 'new_image_order[]';
            order.min = '0';
            order.step = '1';
            order.value = String(i + 1);

            row.appendChild(name);
            row.appendChild(order);
            filenames.appendChild(row);
        }
    } else {
        filenames.textContent = 'no files selected';
    }
}

btn.addEventListener('click', () => input.click());
if (dz) dz.addEventListener('click', () => input.click());

input.addEventListener('change', () => {
    if (input.files && input.files.length > 0) {
        for (let i = 0; i < input.files.length; i++) dtGlobal.items.add(input.files[i]);
        input.files = dtGlobal.files;
        refreshFileList();
    }
});

['dragenter','dragover'].forEach(ev =>
    dz.addEventListener(ev, e => { e.preventDefault(); e.stopPropagation(); dz.classList.add('dz-hover'); })
);
['dragleave','drop'].forEach(ev =>
    dz.addEventListener(ev, e => { e.preventDefault(); e.stopPropagation(); dz.classList.remove('dz-hover'); })
);
dz.addEventListener('drop', e => {
    const files = e.dataTransfer.files;
    if (files && files.length > 0) {
        for (let i = 0; i < files.length; i++) dtGlobal.items.add(files[i]);
        input.files = dtGlobal.files;
        refreshFileList();
    }
});
</script>

</body>
</html>
