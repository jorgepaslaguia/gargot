<?php
session_start();
if (!isset($_SESSION["id_usuario"]) || $_SESSION["rol"] !== "admin") {
    header("Location: ../login.php");
    exit();
}

require_once "../includes/conexion.php";

// CSRF
if (empty($_SESSION["csrf_token"])) {
    $_SESSION["csrf_token"] = bin2hex(random_bytes(16));
}
$csrfToken = $_SESSION["csrf_token"];

function is_plain_password($hash) {
    return strpos($hash, '$') === false || strlen($hash) < 20;
}

$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'], $_POST['id']) && $_POST['action'] === 'force_reset') {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $message = "Sesión inválida";
    } else {
        $id = (int)$_POST['id'];
        $newHash = password_hash(bin2hex(random_bytes(8)), PASSWORD_DEFAULT);
        $stmt = $conexion->prepare("UPDATE usuarios SET password = ? WHERE id_usuario = ?");
        if ($stmt) {
            $stmt->bind_param("si", $newHash, $id);
            if ($stmt->execute()) {
                $message = "Reset aplicado al usuario ID $id";
            } else {
                $message = "Error al resetear ID $id";
            }
            $stmt->close();
        }
    }
}

$sospechosos = [];
$res = $conexion->query("SELECT id_usuario, email, password FROM usuarios");
if ($res) {
    while ($row = $res->fetch_assoc()) {
        if (is_plain_password($row['password'])) {
            $sospechosos[] = $row;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Password audit</title>
    <link rel="stylesheet" href="../css/estilos.css">
</head>
<body>
<?php include '../includes/header.php'; ?>
<main class="contenedor admin-wrapper">
    <h1 class="admin-title">password audit</h1>
    <nav class="admin-subnav">
        <a href="products.php">products</a>
        <a href="home_cards.php">home images</a>
        <a href="shipments.php">orders</a>
        <a href="stats.php">stats</a>
        <a href="password_audit.php" class="active">security</a>
    </nav>

    <?php if ($message): ?>
        <p class="admin-subtitle"><?php echo htmlspecialchars($message); ?></p>
    <?php endif; ?>

    <?php if (empty($sospechosos)): ?>
        <p class="txt-secundario">No se encontraron contraseñas sospechosas.</p>
    <?php else: ?>
        <table class="admin-table">
            <thead>
                <tr><th>ID</th><th>Email</th><th>Hash</th><th>Acción</th></tr>
            </thead>
            <tbody>
            <?php foreach ($sospechosos as $u): ?>
                <tr>
                    <td><?php echo (int)$u['id_usuario']; ?></td>
                    <td><?php echo htmlspecialchars($u['email']); ?></td>
                    <td style="word-break: break-all; font-size:11px;"><?php echo htmlspecialchars($u['password']); ?></td>
                    <td>
                        <form method="post" style="display:inline;">
                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
                            <input type="hidden" name="id" value="<?php echo (int)$u['id_usuario']; ?>">
                            <input type="hidden" name="action" value="force_reset">
                            <button type="submit" class="btn-admin-secondary">force reset</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</main>
</body>
</html>
