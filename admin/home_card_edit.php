<?php
session_start();
include("../includes/conexion.php");

if (!isset($_SESSION["id_usuario"]) || $_SESSION["rol"] !== "admin") {
    header("Location: ../login.php");
    exit();
}

$BASE_PATH = rtrim(dirname($_SERVER['SCRIPT_NAME'] ?? ''), '/\\');
$BASE_PATH = $BASE_PATH === '' ? '/' : $BASE_PATH . '/';

function normalize_card_link($url, $basePath) {
    $url = trim((string)$url);
    while (strpos($url, '../') === 0 || strpos($url, '..\\') === 0) {
        $url = substr($url, 3);
    }
    while (strpos($url, './') === 0 || strpos($url, '.\\') === 0) {
        $url = substr($url, 2);
    }
    if ($url === '' || strpos($url, '..') !== false) {
        return 'index.php';
    }

    $lower = strtolower($url);
    if (strpos($lower, 'localhost/') === 0 || strpos($lower, '127.0.0.1/') === 0) {
        $url = substr($url, strpos($url, '/'));
    }

    if (strpos($lower, 'http://') === 0 || strpos($lower, 'https://') === 0) {
        $parts = parse_url($url);
        $host = strtolower($parts['host'] ?? '');
        $currentHost = strtolower($_SERVER['HTTP_HOST'] ?? '');
        if ($host !== '' && $currentHost !== '' && $host !== $currentHost && $host !== 'localhost' && $host !== '127.0.0.1') {
            return $url;
        }
        $path = $parts['path'] ?? '';
        $query = isset($parts['query']) ? '?' . $parts['query'] : '';
        $fragment = isset($parts['fragment']) ? '#' . $parts['fragment'] : '';
        $url = $path . $query . $fragment;
    }

    $parts = parse_url($url);
    $path = $parts['path'] ?? '';
    $query = isset($parts['query']) ? '?' . $parts['query'] : '';
    $fragment = isset($parts['fragment']) ? '#' . $parts['fragment'] : '';

    $path = str_replace('\\', '/', $path);
    if ($path === '' || strpos($path, '..') !== false) {
        return 'index.php';
    }

    $path = ltrim($path, '/');
    $baseTrim = trim($basePath, '/');
    if ($baseTrim !== '' && strpos($path, $baseTrim . '/') === 0) {
        $path = substr($path, strlen($baseTrim) + 1);
    } elseif ($baseTrim !== '' && $path === $baseTrim) {
        $path = 'index.php';
    }

    if ($path === '') {
        $path = 'index.php';
    }

    return $path . $query . $fragment;
}

// Helper: detectar si existe la columna card_size (para degradar sin romper)
$hasCardSize = false;
$colCheck = $pdo->query("SHOW COLUMNS FROM home_cards LIKE 'card_size'");
$colRows = $colCheck ? $colCheck->fetchAll() : [];
if (count($colRows) > 0) {
    $hasCardSize = true;
}

// Datos apoyo: categorias y familias para el asistente de enlaces
$categorias = [];
$familias   = [];

$resCat = $pdo->query("SELECT id_categoria, nombre FROM categorias ORDER BY nombre ASC");
$categorias = $resCat ? $resCat->fetchAll() : [];

$resFam = $pdo->query("SELECT DISTINCT familia FROM productos WHERE familia IS NOT NULL AND familia <> '' ORDER BY familia ASC");
$familiasRows = $resFam ? $resFam->fetchAll() : [];
foreach ($familiasRows as $row) {
    $familias[] = $row["familia"];
}

// Card existente (si viene id)
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

$card = [
    'title'      => '',
    'subtitle'   => '',
    'image_path' => '',
    'link_url'   => '',
    'sort_order' => 1,
    'active'     => 1,
    'card_size'  => 'md',
];

if ($id > 0) {
    $sql = $hasCardSize
        ? "SELECT * FROM home_cards WHERE id = :id"
        : "SELECT id, title, subtitle, image_path, link_url, sort_order, active FROM home_cards WHERE id = :id";

    $stmt = $pdo->prepare($sql);
    $stmt->execute(["id" => $id]);
    $fila = $stmt->fetch();
    if ($fila) {
        $card = array_merge($card, $fila);
        if (!$hasCardSize) {
            $card['card_size'] = 'md';
        }
    }
}

// Guardar cambios
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title      = trim($_POST['title']      ?? '');
    $subtitle   = trim($_POST['subtitle']   ?? '');
    $link_url   = normalize_card_link($_POST['link_url'] ?? '', $BASE_PATH);
    $sort_order = intval($_POST['sort_order'] ?? 1);
    $active     = isset($_POST['active']) ? 1 : 0;
    $sizeRaw    = $_POST['card_size'] ?? 'md';

    $allowedSizes = ['sm','md','lg','wide','tall'];
    $card_size = in_array($sizeRaw, $allowedSizes, true) ? $sizeRaw : 'md';

    // Imagen: mantenemos la actual salvo que suban otra
    $imagePath = $card['image_path'] ?? '';

    if (!empty($_FILES['image']['name']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $tmpName = $_FILES['image']['tmp_name'];
        $name    = basename($_FILES['image']['name']);

        $uploadDir = __DIR__ . "/../img/home/";
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0775, true);
        }

        $ext      = pathinfo($name, PATHINFO_EXTENSION);
        $safeName = preg_replace('/[^a-zA-Z0-9_\-]/', '_', pathinfo($name, PATHINFO_FILENAME));
        $newName  = $safeName . '_' . time() . '.' . $ext;
        $destPath = $uploadDir . $newName;

        if (move_uploaded_file($tmpName, $destPath)) {
            $imagePath = "img/home/" . $newName;
        }
    }

    if ($id > 0) {
        if ($hasCardSize) {
            $sql = "UPDATE home_cards 
                    SET title = :title, subtitle = :subtitle, image_path = :image_path, link_url = :link_url, sort_order = :sort_order, active = :active, card_size = :card_size
                    WHERE id = :id";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                "title" => $title,
                "subtitle" => $subtitle,
                "image_path" => $imagePath,
                "link_url" => $link_url,
                "sort_order" => $sort_order,
                "active" => $active,
                "card_size" => $card_size,
                "id" => $id,
            ]);
        } else {
            $sql = "UPDATE home_cards 
                    SET title = :title, subtitle = :subtitle, image_path = :image_path, link_url = :link_url, sort_order = :sort_order, active = :active
                    WHERE id = :id";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                "title" => $title,
                "subtitle" => $subtitle,
                "image_path" => $imagePath,
                "link_url" => $link_url,
                "sort_order" => $sort_order,
                "active" => $active,
                "id" => $id,
            ]);
        }
    } else {
        if ($hasCardSize) {
            $sql = "INSERT INTO home_cards (title, subtitle, image_path, link_url, sort_order, active, card_size)
                    VALUES (:title, :subtitle, :image_path, :link_url, :sort_order, :active, :card_size)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                "title" => $title,
                "subtitle" => $subtitle,
                "image_path" => $imagePath,
                "link_url" => $link_url,
                "sort_order" => $sort_order,
                "active" => $active,
                "card_size" => $card_size,
            ]);
        } else {
            $sql = "INSERT INTO home_cards (title, subtitle, image_path, link_url, sort_order, active)
                    VALUES (:title, :subtitle, :image_path, :link_url, :sort_order, :active)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                "title" => $title,
                "subtitle" => $subtitle,
                "image_path" => $imagePath,
                "link_url" => $link_url,
                "sort_order" => $sort_order,
                "active" => $active,
            ]);
        }
    }

    header("Location: home_cards.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Admin - edit home card | Gargot</title>
    <link rel="stylesheet" href="../css/estilos.css">
</head>
<body>
<?php include '../includes/header.php'; ?>

<main class="contenedor admin-main">
    <h1 class="titulo-pagina">Admin - edit home card</h1>

    <form method="post" enctype="multipart/form-data" class="admin-form">

        <div class="form-row">
            <label>title</label>
            <input type="text" name="title" value="<?php echo htmlspecialchars($card['title']); ?>">
        </div>

        <div class="form-row">
            <label>subtitle</label>
            <input type="text" name="subtitle" value="<?php echo htmlspecialchars($card['subtitle']); ?>">
        </div>

        <div class="form-row">
            <label>link url</label>
            <input type="text" name="link_url" value="<?php echo htmlspecialchars($card['link_url']); ?>" id="linkUrl">
            <small class="admin-help">Enlace final que usarás en la tarjeta.</small>
            <small class="admin-help">Ej: shop.php | renting/how_it_works.php</small>
        </div>

        <?php if ($hasCardSize): ?>
            <div class="form-row">
                <label>tamaño de la tarjeta</label>
                <select name="card_size">
                    <?php
                        $sizeOptions = [
                            'sm'   => 'small (1x1)',
                            'md'   => 'medium (1x1 - por defecto)',
                            'wide' => 'wide (2 columnas x 1 fila)',
                            'tall' => 'tall (1 columna x 2 filas)',
                            'lg'   => 'large (2x2)'
                        ];
                        $currentSize = $card['card_size'] ?? 'md';
                    ?>
                    <?php foreach ($sizeOptions as $value => $label): ?>
                        <option value="<?php echo $value; ?>" <?php if ($currentSize === $value) echo 'selected'; ?>>
                            <?php echo $label; ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <small class="admin-help">Elige cuánto espacio ocupa en el grid (auto ajusta en móvil).</small>
            </div>
        <?php endif; ?>

        <div class="form-row">
            <label>link builder (elige destino y se rellena solo)</label>
            <div class="admin-link-builder">
                <div class="builder-grid">
                    <div class="builder-field">
                        <span class="builder-label">destino rápido</span>
                        <select id="presetLink">
                            <option value="">-- elegir --</option>
                            <option value="index.php#hero">home hero</option>
                            <option value="new_in.php">new in</option>
                            <option value="shop.php">shop (all)</option>
                            <option value="renting/how_it_works.php">renting</option>
                            <option value="shipping_returns.php">shipping & returns</option>
                            <option value="contact.php">contact</option>
                            <option value="login.php">log in</option>
                            <option value="register.php">register</option>
                        </select>
                        <small class="builder-hint">Selecciona un destino habitual y lo rellenamos por ti.</small>
                    </div>

                    <?php if (!empty($familias)): ?>
                    <div class="builder-field">
                        <span class="builder-label">coleccion/familia (shop)</span>
                        <select id="familiaLink">
                            <option value="">-- elegir --</option>
                            <?php foreach ($familias as $fam): ?>
                                <option value="<?php echo htmlspecialchars($fam); ?>">
                                    <?php echo htmlspecialchars($fam); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <small class="builder-hint">Crea un enlace a shop filtrando por esa familia.</small>
                    </div>
                    <?php endif; ?>

                    <?php if (!empty($categorias)): ?>
                    <div class="builder-field">
                        <span class="builder-label">categoria (landing manual)</span>
                        <select id="categoriaLink">
                            <option value="">-- elegir --</option>
                            <?php foreach ($categorias as $cat): ?>
                                <option value="<?php echo (int)$cat['id_categoria']; ?>">
                                    <?php echo htmlspecialchars($cat['nombre']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <small class="builder-hint">Crea un enlace tipo <code>shop.php?categoria=ID</code>.</small>
                    </div>
                    <?php endif; ?>

                    <div class="builder-field builder-inline">
                        <span class="builder-label">target personalizado</span>
                        <div class="builder-inline-row">
                            <input type="text" id="customPath" placeholder="shop.php o https://...">
                            <button type="button" class="btn-admin-secondary btn-inline" id="applyCustom">usar</button>
                        </div>
                        <small class="builder-hint">Pega cualquier URL manual si necesitas algo fuera de las opciones.</small>
                    </div>
                </div>

                <div class="builder-chips">
                    <button type="button" class="builder-chip" data-url="shop.php?precio=%3C50">&lt; 50 EUR</button>
                    <button type="button" class="builder-chip" data-url="shop.php?precio=50-100">50-100 EUR</button>
                    <button type="button" class="builder-chip" data-url="shop.php?orden=discount_desc">mayor descuento</button>
                    <button type="button" class="builder-chip" data-url="shop.php?talla=s">talla S</button>
                    <button type="button" class="builder-chip" data-url="shop.php?talla=m">talla M</button>
                    <button type="button" class="builder-chip" data-url="shop.php?talla=l">talla L</button>
                </div>

                <div class="builder-preview">
                    destino final: <span id="linkPreview"><?php echo htmlspecialchars($card['link_url']) ?: 'elige una opcion'; ?></span>
                </div>
            </div>
        </div>

        <div class="form-row">
            <label>order</label>
            <input type="number" name="sort_order" min="1" value="<?php echo (int)$card['sort_order']; ?>">
        </div>

        <div class="form-row">
            <label>
                <input type="checkbox" name="active" <?php echo $card['active'] ? 'checked' : ''; ?>>
                active
            </label>
        </div>

        <!-- Imagen -->
        <div class="form-row">
            <label>image</label>

            <?php if (!empty($card['image_path'])): ?>
                <div class="home-card-preview">
                    <img src="../<?php echo htmlspecialchars($card['image_path']); ?>" alt="preview">
                </div>
            <?php endif; ?>

            <div class="dropzone" id="dropzone-image">
                <p class="dz-title">drag & drop image here</p>
                <p class="dz-subtitle">or</p>
                <button type="button" class="btn-admin-secondary" id="btn-select-image">
                    select image
                </button>
                <input type="file" name="image" id="input-image" accept="image/*" hidden>
                <p class="dz-info">max 20 mb · 1 file</p>
            </div>

            <p class="dz-filename" id="dz-filename">
                <?php echo !empty($card['image_path']) ? basename($card['image_path']) : 'no file selected'; ?>
            </p>
        </div>

        <div class="form-actions">
            <button type="submit" class="btn-admin-primary">save</button>
            <a href="home_cards.php" class="btn-admin-link">cancel</a>
        </div>
    </form>
</main>

<script>
const dz = document.getElementById('dropzone-image');
const input = document.getElementById('input-image');
const btn = document.getElementById('btn-select-image');
const filename = document.getElementById('dz-filename');

const linkInput = document.getElementById('linkUrl');
const presetLink = document.getElementById('presetLink');
const familiaLink = document.getElementById('familiaLink');
const categoriaLink = document.getElementById('categoriaLink');
const customPath = document.getElementById('customPath');
const applyCustom = document.getElementById('applyCustom');
const chips = document.querySelectorAll('.builder-chip');
const linkPreview = document.getElementById('linkPreview');

btn.addEventListener('click', () => input.click());
dz.addEventListener('click', () => input.click());

input.addEventListener('change', () => {
    if (input.files && input.files[0]) {
        filename.textContent = input.files[0].name;
    }
});

['dragenter','dragover'].forEach(ev =>
    dz.addEventListener(ev, e => {
        e.preventDefault();
        e.stopPropagation();
        dz.classList.add('dz-hover');
    })
);
['dragleave','drop'].forEach(ev =>
    dz.addEventListener(ev, e => {
        e.preventDefault();
        e.stopPropagation();
        dz.classList.remove('dz-hover');
    })
);

dz.addEventListener('drop', e => {
    const files = e.dataTransfer.files;
    if (files && files[0]) {
        input.files = files;
        filename.textContent = files[0].name;
    }
});

function setLink(url) {
    linkInput.value = url;
    if (linkPreview) {
        linkPreview.textContent = url || 'elige una opcion';
    }
}

if (presetLink) {
    presetLink.addEventListener('change', e => {
        const value = e.target.value || '';
        if (value) setLink(value);
    });
}

if (familiaLink) {
    familiaLink.addEventListener('change', e => {
        const val = e.target.value || '';
        if (val) {
            const url = 'shop.php?familia=' + encodeURIComponent(val);
            setLink(url);
        }
    });
}

if (categoriaLink) {
    categoriaLink.addEventListener('change', e => {
        const val = e.target.value || '';
        if (val) {
            const url = 'shop.php?categoria=' + encodeURIComponent(val);
            setLink(url);
        }
    });
}

if (applyCustom) {
    applyCustom.addEventListener('click', () => {
        const val = (customPath.value || '').trim();
        if (val) setLink(val);
    });
}

chips.forEach(chip => {
    chip.addEventListener('click', () => {
        const url = chip.dataset.url || '';
        setLink(url);
    });
});
</script>

</body>
</html>
