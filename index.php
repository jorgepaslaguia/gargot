<?php
session_start();
require_once "includes/conexion.php";

$BASE_PATH = rtrim(dirname($_SERVER['SCRIPT_NAME'] ?? ''), '/\\');
$BASE_PATH = $BASE_PATH === '' ? '/' : $BASE_PATH . '/';

function normalize_link_url($url, $basePath) {
    $url = trim((string)$url);
    while (strpos($url, '../') === 0 || strpos($url, '..\\') === 0) {
        $url = substr($url, 3);
    }
    while (strpos($url, './') === 0 || strpos($url, '.\\') === 0) {
        $url = substr($url, 2);
    }
    if ($url === '' || strpos($url, '..') !== false) {
        return $basePath . 'index.php';
    }

    $lower = strtolower($url);
    if (strpos($lower, 'localhost/') === 0 || strpos($lower, '127.0.0.1/') === 0) {
        $url = substr($url, strpos($url, '/'));
        $lower = strtolower($url);
    }
    if (strpos($lower, 'http://') === 0 || strpos($lower, 'https://') === 0) {
        return $url;
    }

    $parts = parse_url($url);
    $path = $parts['path'] ?? '';
    $query = isset($parts['query']) ? '?' . $parts['query'] : '';
    $fragment = isset($parts['fragment']) ? '#' . $parts['fragment'] : '';

    $path = str_replace('\\', '/', $path);
    if ($path === '' || strpos($path, '..') !== false) {
        return $basePath . 'index.php';
    }

    $isRoot = $path[0] === '/';
    $pathTrim = ltrim($path, '/');
    $baseTrim = trim($basePath, '/');
    if ($baseTrim !== '' && strpos($pathTrim, $baseTrim . '/') === 0) {
        $pathTrim = substr($pathTrim, strlen($baseTrim) + 1);
        $isRoot = false;
    } elseif ($baseTrim !== '' && $pathTrim === $baseTrim) {
        $pathTrim = 'index.php';
        $isRoot = false;
    }

    if ($pathTrim === '') {
        $pathTrim = 'index.php';
    }

    $docRoot = rtrim($_SERVER['DOCUMENT_ROOT'] ?? '', '/\\');
    $fsPath = '';
    if ($isRoot) {
        if ($docRoot !== '') {
            $fsPath = $docRoot . $path;
        }
    } else {
        $fsPath = __DIR__ . '/' . $pathTrim;
    }

    if ($fsPath === '' || !is_file($fsPath)) {
        if ($isRoot && is_file(__DIR__ . '/' . $pathTrim)) {
            return $basePath . $pathTrim . $query . $fragment;
        }
        return $basePath . 'index.php';
    }

    if ($isRoot) {
        return $path . $query . $fragment;
    }

    return $basePath . $pathTrim . $query . $fragment;
}

/* =========================
   CONTADORES CARRITO / WISHLIST
   ========================= */
$itemsCarrito = 0;
if (!empty($_SESSION['carrito'])) {
    foreach ($_SESSION['carrito'] as $item) {
        $itemsCarrito += (int)$item['cantidad'];
    }
}

$wishlistCount = 0;
if (!empty($_SESSION['wishlist'])) {
    $wishlistCount = count($_SESSION['wishlist']);
}

/* =========================
   HOME CARDS (página inicio)
   ========================= */
$hasCardSize = false;
$colCheck = $pdo->query("SHOW COLUMNS FROM home_cards LIKE 'card_size'");
$colRows = $colCheck ? $colCheck->fetchAll() : [];
if (count($colRows) > 0) {
    $hasCardSize = true;
}

$sqlHomeCards = $hasCardSize
    ? "SELECT id, title, subtitle, link_url, image_path, card_size
       FROM home_cards
       WHERE active = 1
       ORDER BY sort_order ASC, id ASC"
    : "SELECT id, title, subtitle, link_url, image_path
       FROM home_cards
       WHERE active = 1
       ORDER BY sort_order ASC, id ASC";
$stmtHomeCards = $pdo->query($sqlHomeCards);
$homeCards = $stmtHomeCards ? $stmtHomeCards->fetchAll() : [];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Gargot – upcycled clothing & renting</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="css/estilos.css?v=3">
</head>
<body class="page-index">

<?php include 'includes/header.php'; ?>

<!-- =========================
     CONTENIDO ORIGINAL (SIN CAMBIOS)
     ========================= -->
<main class="home">

    <!-- HERO PRINCIPAL -->
    <section class="home-hero">
        <div class="home-hero-inner">
            <p class="hero-kicker">gargot</p>
            <h1>upcycled clothing &amp; renting</h1>
            <p class="hero-text">
                curated drops, one-of-a-kind pieces and a renting line for special days.
            </p>
            <a href="new_in.php" class="btn-hero">shop new in</a>
        </div>
    </section>

    <!-- GRID DE BLOQUES (dinámico desde home_cards) -->
    <section class="home-grid">
        <?php if (count($homeCards) > 0): ?>
            <?php foreach ($homeCards as $card): ?>
                <?php
                    $sizes = ['sm','md','lg','wide','tall'];
                    $cardSize = in_array($card['card_size'] ?? 'md', $sizes, true) ? $card['card_size'] : 'md';
                    if (!$hasCardSize) {
                        $cardSize = 'md';
                    }
                    $safeLink = normalize_link_url($card['link_url'] ?? '', $BASE_PATH);
                ?>
                <a href="<?php echo htmlspecialchars($safeLink); ?>" class="home-card home-card--<?php echo $cardSize; ?>">
                    <div class="home-card-media">
                        <img src="<?php echo htmlspecialchars($card['image_path']); ?>"
                             alt="<?php echo htmlspecialchars($card['title']); ?>">
                    </div>
                    <div class="home-card-overlay">
                        <span class="home-card-kicker">
                            <?php echo htmlspecialchars($card['subtitle']); ?>
                        </span>
                        <span class="home-card-title">
                            <?php echo htmlspecialchars($card['title']); ?>
                        </span>
                    </div>
                </a>
            <?php endforeach; ?>
        <?php else: ?>
            <p class="txt-secundario">
                no home cards configured yet. go to admin → home images.
            </p>
        <?php endif; ?>
    </section>

</main>

</body>
</html>
