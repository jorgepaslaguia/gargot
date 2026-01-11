<?php
session_start();
if (!isset($_SESSION["id_usuario"]) || $_SESSION["rol"] !== "admin") {
    header("Location: ../login.php");
    exit();
}

require_once "../includes/conexion.php";

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete') {
    $deleteId = intval($_POST['id'] ?? 0);
    if ($deleteId > 0) {
        $imagePath = '';
        $stmtSel = $conexion->prepare("SELECT image_path FROM home_cards WHERE id = ?");
        $stmtSel->bind_param("i", $deleteId);
        $stmtSel->execute();
        $resSel = $stmtSel->get_result();
        if ($rowSel = $resSel->fetch_assoc()) {
            $imagePath = $rowSel['image_path'] ?? '';
        }
        $stmtSel->close();

        $stmtDel = $conexion->prepare("DELETE FROM home_cards WHERE id = ?");
        $stmtDel->bind_param("i", $deleteId);
        $stmtDel->execute();
        $stmtDel->close();

        $baseDir = realpath(__DIR__ . "/../img/home");
        if ($baseDir && $imagePath !== '') {
            $target = realpath(__DIR__ . "/../" . $imagePath);
            if ($target && strpos($target, $baseDir . DIRECTORY_SEPARATOR) === 0 && is_file($target)) {
                unlink($target);
            }
        }
    }
    header("Location: home_cards.php?deleted=1");
    exit();
}

$hasCardSize = false;
$colCheck = $conexion->query("SHOW COLUMNS FROM home_cards LIKE 'card_size'");
if ($colCheck && $colCheck->num_rows > 0) {
    $hasCardSize = true;
}

$sql = $hasCardSize
    ? "SELECT id, title, subtitle, image_path, link_url, sort_order, active, card_size
       FROM home_cards
       ORDER BY sort_order ASC, id ASC"
    : "SELECT id, title, subtitle, image_path, link_url, sort_order, active
       FROM home_cards
       ORDER BY sort_order ASC, id ASC";

$res = $conexion->query($sql);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Admin – home images | Gargot</title>
    <link rel="stylesheet" href="../css/estilos.css">
</head>
<body>
<?php include '../includes/header.php'; ?>

<main class="contenedor admin-wrapper">
    <h1 class="admin-title">admin – home images</h1>

    <nav class="admin-subnav">
        <a href="products.php">products</a>
        <a href="home_cards.php" class="active">home images</a>
        <a href="shipments.php">shipments</a>
        <a href="stats.php">stats</a>
    </nav>

    <section class="admin-section">
        <div class="admin-header-row">
            <p class="admin-subtitle">
                aquí configuras las imágenes, gifs y bloques que aparecen en la página de inicio.
            </p>
            <a href="home_card_edit.php" class="btn-admin">add_new_card</a>
        </div>

        <?php if ($res && $res->num_rows > 0): ?>
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>title</th>
                        <th>subtitle</th>
                        <th>image</th>
                        <th>link</th>
        <th>order</th>
        <?php if ($hasCardSize): ?>
            <th>size</th>
        <?php endif; ?>
        <th>active</th>
        <th>actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php while ($c = $res->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo $c["id"]; ?></td>
                        <td><?php echo htmlspecialchars($c["title"]); ?></td>
                        <td><?php echo htmlspecialchars($c["subtitle"]); ?></td>
                    <td><?php echo htmlspecialchars($c["image_path"]); ?></td>
                    <td><?php echo htmlspecialchars($c["link_url"]); ?></td>
                    <td><?php echo (int)$c["sort_order"]; ?></td>
                    <?php if ($hasCardSize): ?>
                        <td><?php echo htmlspecialchars($c["card_size"] ?? 'md'); ?></td>
                    <?php endif; ?>
                    <td><?php echo $c["active"] ? "yes" : "no"; ?></td>
                        <td class="admin-actions">
                            <a href="home_card_edit.php?id=<?php echo $c["id"]; ?>">edit</a>
                            <form method="post" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" style="display:inline;">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id" value="<?php echo (int)$c["id"]; ?>">
                                <a href="#" onclick="if (!confirm('Delete this home card?')) return false; this.closest('form').submit(); return false;">delete</a>
                            </form>
                        </td>
                    </tr>
                <?php endwhile; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p class="txt-secundario">no home cards configured yet.</p>
        <?php endif; ?>
    </section>
</main>

</body>
</html>
