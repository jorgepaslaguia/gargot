<?php
session_start();
if (!isset($_SESSION["id_usuario"]) || $_SESSION["rol"] !== "admin") {
    header("Location: ../login.php");
    exit();
}

require_once "../includes/conexion.php";

// Visibilidad
$hasVisibility = false;
$colCheck = $conexion->query("SHOW COLUMNS FROM productos LIKE 'is_visible'");
if ($colCheck && $colCheck->num_rows > 0) $hasVisibility = true;

// CSRF
if (empty($_SESSION["csrf_token"])) {
    $_SESSION["csrf_token"] = bin2hex(random_bytes(16));
}
$csrfToken = $_SESSION["csrf_token"];

// Categorías
$resCat = $conexion->query("SELECT id_categoria, nombre FROM categorias ORDER BY nombre ASC");

$errores = [];

// Valores por defecto
$nombre       = "";
$familia      = "";
$talla        = "";
$id_categoria = 0;
$marca        = "";
$precio       = "";
$descuento    = "0";
$stock        = "0";
$descripcion  = "";
$isVisible    = 1;

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $errores[] = "Sesión inválida. Recarga la página.";
    }

    $nombre        = trim($_POST["nombre"] ?? "");
    $familia       = trim($_POST["familia"] ?? "");
    $talla         = trim($_POST["talla"] ?? "");
    $id_categoria  = intval($_POST["id_categoria"] ?? 0);
    $marca         = trim($_POST["marca"] ?? "");
    $precio        = floatval(str_replace(",", ".", $_POST["precio"] ?? ""));
    $descuento     = floatval(str_replace(",", ".", $_POST["descuento"] ?? "0"));
    $stock         = intval($_POST["stock"] ?? 0);
    $descripcion   = trim($_POST["descripcion"] ?? "");
    $isVisible     = $hasVisibility ? (isset($_POST["is_visible"]) ? 1 : 0) : 1;

    if ($nombre === "")        $errores[] = "name is required.";
    if ($familia === "")       $errores[] = "garment family is required.";
    if ($id_categoria <= 0)    $errores[] = "category is required.";
    if ($precio <= 0)          $errores[] = "price must be greater than 0.";

    // Subida de imágenes múltiples
    $maxFiles  = 10;
    $imagenes  = [];
    $imagenesOrden = [];
    $newImageOrderInput = $_POST['new_image_order'] ?? [];

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

        if ($fileCount > $maxFiles) {
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
                    $ordenRaw = isset($newImageOrderInput[$idx]) ? trim((string)$newImageOrderInput[$idx]) : '';
                    if ($ordenRaw === '') {
                        $orden = count($imagenes) + 1;
                    } elseif (ctype_digit($ordenRaw)) {
                        $orden = (int)$ordenRaw;
                    } else {
                        $errores[] = "image order must be an integer (0 or greater).";
                        continue;
                    }
                    $imagenes[] = "img/products/" . $newName;
                    $imagenesOrden[] = $orden;
                }
            }
        }
    }

    if (count($imagenes) === 0) {
        $errores[] = "at least one image is required.";
    } elseif (count($imagenesOrden) !== count(array_unique($imagenesOrden))) {
        $errores[] = "image order values must be unique.";
    }

    if (empty($errores)) {
        $conexion->begin_transaction();
        try {
            $sql = "INSERT INTO productos
                    (nombre, familia, talla, id_categoria, marca, precio, descuento, stock, imagen, descripcion";
            if ($hasVisibility) $sql .= ", is_visible";
            $sql .= ", fecha_creacion)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?";
            if ($hasVisibility) $sql .= ", ?";
            $sql .= ", NOW())";

            $stmt = $conexion->prepare($sql);
            $imagenPrincipal = $imagenes[0] ?? '';
            if (!empty($imagenesOrden)) {
                $minIdx = array_search(min($imagenesOrden), $imagenesOrden, true);
                if ($minIdx !== false && isset($imagenes[$minIdx])) {
                    $imagenPrincipal = $imagenes[$minIdx];
                }
            }
            if ($hasVisibility) {
                $stmt->bind_param("sssisddissi", $nombre, $familia, $talla, $id_categoria, $marca, $precio, $descuento, $stock, $imagenPrincipal, $descripcion, $isVisible);
            } else {
                $stmt->bind_param("sssisddiss", $nombre, $familia, $talla, $id_categoria, $marca, $precio, $descuento, $stock, $imagenPrincipal, $descripcion);
            }
            if (!$stmt->execute()) throw new Exception("db insert producto: " . $stmt->error);
            $idProducto = $stmt->insert_id;
            $stmt->close();

            if (count($imagenes) > 0) {
                $stmtImg = $conexion->prepare("INSERT INTO producto_imagenes (id_producto, image_path, orden) VALUES (?, ?, ?)");
                foreach ($imagenes as $idx => $ruta) {
                    $orden = $imagenesOrden[$idx] ?? ($idx + 1);
                    $stmtImg->bind_param("isi", $idProducto, $ruta, $orden);
                    $stmtImg->execute();
                }
                $stmtImg->close();
            }

            $conexion->commit();
            header("Location: products.php");
            exit();
        } catch (Exception $ex) {
            $conexion->rollback();
            $errores[] = $ex->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Admin - new product | Gargot</title>
    <link rel="stylesheet" href="../css/estilos.css">
</head>
<body>
<?php include '../includes/header.php'; ?>

<main class="contenedor admin-main">
    <h1 class="titulo-pagina">admin - new product</h1>

    <?php if (!empty($errores)): ?>
        <div class="admin-errors">
            <?php foreach ($errores as $e): ?>
                <div>- <?php echo htmlspecialchars($e); ?></div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <form method="post" enctype="multipart/form-data" class="admin-form">
        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">

        <div class="admin-form-row">
            <label>name</label>
            <input type="text" name="nombre" required value="<?php echo htmlspecialchars($nombre); ?>">
        </div>

        <div class="admin-form-row">
            <label>garment family</label>
            <input type="text" name="familia" value="<?php echo htmlspecialchars($familia); ?>">
        </div>

        <div class="admin-form-row">
            <label>size</label>
            <input type="text" name="talla" value="<?php echo htmlspecialchars($talla); ?>">
        </div>

        <div class="admin-form-row">
            <label>category</label>
            <select name="id_categoria">
                <option value="">-- select --</option>
                <?php if ($resCat): while ($cat = $resCat->fetch_assoc()): ?>
                    <option value="<?php echo (int)$cat['id_categoria']; ?>" <?php if ($id_categoria == $cat['id_categoria']) echo 'selected'; ?>>
                        <?php echo htmlspecialchars($cat['nombre']); ?>
                    </option>
                <?php endwhile; endif; ?>
            </select>
        </div>

        <div class="admin-form-row">
            <label>brand</label>
            <input type="text" name="marca" value="<?php echo htmlspecialchars($marca); ?>">
        </div>

        <div class="admin-form-row">
            <label>price (€)</label>
            <input type="number" step="0.01" name="precio" required value="<?php echo htmlspecialchars($precio); ?>">
        </div>

        <div class="admin-form-row">
            <label>discount (%)</label>
            <input type="number" step="0.01" name="descuento" value="<?php echo htmlspecialchars($descuento); ?>">
        </div>

        <div class="admin-form-row">
            <label>stock</label>
            <input type="number" name="stock" value="<?php echo htmlspecialchars($stock); ?>">
        </div>

        <?php if ($hasVisibility): ?>
        <div class="admin-form-row">
            <label>
                <input type="checkbox" name="is_visible" <?php if ($isVisible) echo 'checked'; ?>>
                visible (mostrar en la tienda)
            </label>
        </div>
        <?php endif; ?>

        <div class="admin-form-row">
            <label>description</label>
            <textarea name="descripcion" rows="4"><?php echo htmlspecialchars($descripcion); ?></textarea>
        </div>

        <div class="admin-form-row">
            <label>upload images (max 10)</label>
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
