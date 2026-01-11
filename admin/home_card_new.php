<?php
session_start();

// Solo admins
if (!isset($_SESSION["id_usuario"]) || $_SESSION["rol"] !== "admin") {
    header("Location: ../login.php");
    exit();
}

require_once "../includes/conexion.php";

// --------------------------------------------------
// BORRAR CARD (GET ?id=..&delete=1)
// --------------------------------------------------
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($id > 0 && isset($_GET['delete']) && $_GET['delete'] == 1) {
    $sqlDel = "DELETE FROM home_cards WHERE id = ?";
    $stmtDel = $conexion->prepare($sqlDel);
    $stmtDel->bind_param("i", $id);
    $stmtDel->execute();
    header("Location: home_cards.php");
    exit();
}

// --------------------------------------------------
// CARGAR CARD EXISTENTE (si id > 0)
// --------------------------------------------------
$card = [
    'title'      => '',
    'subtitle'   => '',
    'image_path' => '',
    'link_url'   => '',
    'sort_order' => 1,
    'active'     => 1,
];

if ($id > 0) {
    $sql = "SELECT id, title, subtitle, image_path, link_url, sort_order, active
            FROM home_cards
            WHERE id = ?";
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($fila = $res->fetch_assoc()) {
        $card = $fila;
    }
}

// --------------------------------------------------
// GUARDAR (POST) CON SUBIDA DE IMAGEN
// --------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title      = trim($_POST['title']      ?? '');
    $subtitle   = trim($_POST['subtitle']   ?? '');
    $link_url   = trim($_POST['link_url']   ?? '');
    $sort_order = intval($_POST['sort_order'] ?? 1);
    $active     = isset($_POST['active']) ? 1 : 0;

    // Por defecto, mantenemos la imagen actual
    $imagePath = $card['image_path'];

    // Si el admin sube una imagen nueva
    if (!empty($_FILES['image_file']['name']) && $_FILES['image_file']['error'] === UPLOAD_ERR_OK) {

        $tmpName = $_FILES['image_file']['tmp_name'];
        $name    = basename($_FILES['image_file']['name']);

        // Carpeta donde se guardarán las imágenes de la home
        $uploadDir = __DIR__ . "/../img/home/";
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0775, true);
        }

        // Nombre seguro + único
        $ext      = pathinfo($name, PATHINFO_EXTENSION);
        $base     = pathinfo($name, PATHINFO_FILENAME);
        $safeBase = preg_replace('/[^a-zA-Z0-9_\-]/', '_', $base);
        $newName  = $safeBase . '_' . time() . '.' . $ext;

        $destPathFs = $uploadDir . $newName;        // ruta en el sistema de ficheros
        $destPathDb = "img/home/" . $newName;       // ruta relativa guardada en BD

        if (move_uploaded_file($tmpName, $destPathFs)) {
            $imagePath = $destPathDb;
        }
    }

    if ($id > 0) {
        $sql = "UPDATE home_cards
                SET title = ?, subtitle = ?, image_path = ?, link_url = ?, sort_order = ?, active = ?
                WHERE id = ?";
        $stmt = $conexion->prepare($sql);
        $stmt->bind_param(
            "ssssiii",
            $title, $subtitle, $imagePath, $link_url, $sort_order, $active, $id
        );
    } else {
        $sql = "INSERT INTO home_cards (title, subtitle, image_path, link_url, sort_order, active)
                VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = $conexion->prepare($sql);
        $stmt->bind_param(
            "ssssii",
            $title, $subtitle, $imagePath, $link_url, $sort_order, $active
        );
    }

    $stmt->execute();
    header("Location: home_cards.php");
    exit();
}

// contador carrito para el header
$itemsCarrito = 0;
if (isset($_SESSION["carrito"])) {
    foreach ($_SESSION["carrito"] as $item) {
        $itemsCarrito += $item["cantidad"];
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Admin – <?php echo $id > 0 ? 'edit' : 'add'; ?> home card | Gargot</title>
    <link rel="stylesheet" href="../css/estilos.css">
</head>
<body>

<header class="header">
    <div class="logo">
        <a href="../index.php">
            <img src="../img/logo.jpeg" alt="Gargot logo" class="logo-img">
        </a>
    </div>

    <nav class="menu">
        <a href="../new_in.php">NEW IN</a>
        <a href="../shop.php">SHOP</a>
        <a href="../renting/how_it_works.php">RENTING</a>
        <a href="../shipping_returns.php">SHIPPING & RETURNS</a>
        <a href="../contact.php">CONTACT</a>
        <a href="../user/wishlist.php">WISHLIST</a>
        <a href="../cart.php">CART (<?php echo $itemsCarrito; ?>)</a>
        <a href="index.php" class="activo">ADMIN</a>
    </nav>
</header>

<main class="contenedor admin-main">
    <h1 class="titulo-pagina">
        admin – <?php echo $id > 0 ? 'edit home card' : 'add home card'; ?>
    </h1>

    <!-- IMPORTANTE: enctype para subir archivos -->
    <form method="post" enctype="multipart/form-data" class="admin-form">

        <div class="form-row">
            <label>title</label>
            <input type="text" name="title"
                   value="<?php echo htmlspecialchars($card['title']); ?>">
        </div>

        <div class="form-row">
            <label>subtitle</label>
            <input type="text" name="subtitle"
                   value="<?php echo htmlspecialchars($card['subtitle']); ?>">
        </div>

        <div class="form-row">
            <label>link url</label>
            <input type="text" name="link_url"
                   placeholder="ej: shop.php o https://instagram.com/..."
                   value="<?php echo htmlspecialchars($card['link_url']); ?>">
        </div>

        <div class="form-row">
            <label>order</label>
            <select name="sort_order">
                <?php for ($i = 1; $i <= 12; $i++): ?>
                    <option value="<?php echo $i; ?>"
                        <?php if ((int)$card['sort_order'] === $i) echo 'selected'; ?>>
                        <?php echo $i; ?>
                    </option>
                <?php endfor; ?>
            </select>
        </div>

        <div class="form-row checkbox-row">
            <label>
                <input type="checkbox" name="active" value="1"
                    <?php if ($card['active']) echo 'checked'; ?>>
                active
            </label>
        </div>

        <!-- IMAGEN: PREVIEW + DROPZONE -->
        <div class="form-row">
            <label>image</label>

            <?php if (!empty($card['image_path'])): ?>
                <div class="home-card-preview">
                    <img src="../<?php echo htmlspecialchars($card['image_path']); ?>" alt="preview">
                </div>
            <?php endif; ?>

            <div class="dropzone" id="dropzone-image">
                <div class="dz-icon">⬇</div>
                <p class="dz-title">puedes arrastrar y soltar una imagen aquí</p>
                <p class="dz-subtitle">o</p>
                <button type="button" class="btn-admin-secondary" id="btn-select-image">
                    seleccionar archivo
                </button>
                <input type="file" name="image_file" id="input-image" accept="image/*" hidden>
                <p class="dz-info">tamaño máx. 20 mb · 1 archivo</p>
            </div>

            <p class="dz-filename" id="dz-filename">
                <?php echo !empty($card['image_path'])
                    ? basename($card['image_path'])
                    : 'ningún archivo seleccionado'; ?>
            </p>
        </div>

        <div class="form-actions">
            <button type="submit" class="btn-admin-primary">save</button>
            <a href="home_cards.php" class="btn-admin-link">cancel</a>
        </div>
    </form>
</main>

<script>
// JS sencillo para el dropzone
const dz        = document.getElementById('dropzone-image');
const input     = document.getElementById('input-image');
const btn       = document.getElementById('btn-select-image');
const filename  = document.getElementById('dz-filename');

if (btn)   btn.addEventListener('click', () => input.click());
if (dz)    dz.addEventListener('click', () => input.click());

if (input) {
    input.addEventListener('change', () => {
        if (input.files && input.files[0]) {
            filename.textContent = input.files[0].name;
        }
    });
}

['dragenter','dragover'].forEach(ev => {
    dz.addEventListener(ev, e => {
        e.preventDefault();
        e.stopPropagation();
        dz.classList.add('dz-hover');
    });
});

['dragleave','drop'].forEach(ev => {
    dz.addEventListener(ev, e => {
        e.preventDefault();
        e.stopPropagation();
        dz.classList.remove('dz-hover');
    });
});

dz.addEventListener('drop', e => {
    const files = e.dataTransfer.files;
    if (files && files[0]) {
        input.files = files;
        filename.textContent = files[0].name;
    }
});
</script>

</body>
</html>
