<?php
session_start();
include("includes/conexion.php");

$errores = [];

// CSRF token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(16));
}
$csrfToken = $_SESSION['csrf_token'];

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $errores[] = "Sesión inválida. Recarga la página.";
    }

    $email    = trim($_POST["email"] ?? "");
    $password = $_POST["password"] ?? "";

    if ($email === "" || $password === "") {
        $errores[] = "Debes introducir email y contraseña.";
    } else {
        $stmt = $conexion->prepare("SELECT id_usuario, nombre, rol, password FROM usuarios WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $resultado = $stmt->get_result();

        if ($fila = $resultado->fetch_assoc()) {
            $hashBD = $fila["password"];
            $loginOk = false;

            if (password_verify($password, $hashBD)) {
                $loginOk = true;
            } elseif ($password === $hashBD) {
                // Migration: hash antiguo en texto plano -> rehash y marcar ok
                $loginOk = true;
                $nuevoHash = password_hash($password, PASSWORD_DEFAULT);
                $upd = $conexion->prepare("UPDATE usuarios SET password = ? WHERE id_usuario = ?");
                if ($upd) {
                    $upd->bind_param("si", $nuevoHash, $fila["id_usuario"]);
                    $upd->execute();
                    $upd->close();
                }
            }

            if ($loginOk) {
                $_SESSION["id_usuario"] = $fila["id_usuario"];
                $_SESSION["nombre"]     = $fila["nombre"];
                $_SESSION["rol"]        = $fila["rol"];

                if ($fila["rol"] == "admin") {
                    header("Location: admin/index.php");
                } else {
                    header("Location: user/index.php");
                }
                exit();
            } else {
                $errores[] = "Credenciales incorrectas.";
            }
        } else {
            $errores[] = "Credenciales incorrectas.";
        }
        $stmt->close();
    }
}

$itemsCarrito = 0;
if (!empty($_SESSION["carrito"])) {
    foreach ($_SESSION["carrito"] as $item) {
        $itemsCarrito += (int)$item["cantidad"];
    }
}
$wishlistCount = !empty($_SESSION["wishlist"]) ? count($_SESSION["wishlist"]) : 0;
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Log in | Gargot</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="css/estilos.css?v=3">
</head>
<body class="page-login">
<?php include 'includes/header.php'; ?>

<main class="contenedor page-mono">
    <h1>log in</h1>

    <?php if (!empty($errores)): ?>
        <div class="admin-errors">
            <ul>
                <?php foreach ($errores as $e): ?>
                    <li><?php echo htmlspecialchars($e); ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <form method="post" action="login.php" class="admin-form" style="max-width: 420px;">
        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
        <div class="admin-form-row">
            <label>Email</label>
            <input type="email" name="email" required>
        </div>

        <div class="admin-form-row">
            <label>Password</label>
            <input type="password" name="password" required>
        </div>

        <div class="admin-buttons">
            <button type="submit" class="btn-product">log in</button>
            <a href="register.php" class="btn-admin-secondary">register</a>
        </div>
    </form>
</main>

</body>
</html>
