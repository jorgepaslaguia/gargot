<?php
session_start();
include("includes/conexion.php");

$errores = [];
$exito = "";

// CSRF token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(16));
}
$csrfToken = $_SESSION['csrf_token'];

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $errores[] = "Sesión inválida. Recarga la página.";
    }

    $nombre    = trim($_POST["nombre"] ?? "");
    $apellidos = trim($_POST["apellidos"] ?? "");
    $email     = trim($_POST["email"] ?? "");
    $telefono  = trim($_POST["telefono"] ?? "");
    $password  = $_POST["password"] ?? "";
    $password2 = $_POST["password2"] ?? "";

    if ($nombre === "" || $apellidos === "" || $email === "" || $password === "" || $password2 === "") {
        $errores[] = "Todos los campos marcados con * son obligatorios.";
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errores[] = "El email no tiene un formato válido.";
    }
    if ($password !== $password2) {
        $errores[] = "Las contraseñas no coinciden.";
    }

    if (empty($errores)) {
        $stmt = $conexion->prepare("SELECT id_usuario FROM usuarios WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows > 0) {
            $errores[] = "Ya existe un usuario registrado con ese email.";
        }
        $stmt->close();
    }

    if (empty($errores)) {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $sql = "INSERT INTO usuarios (nombre, apellidos, email, password, telefono, rol) VALUES (?, ?, ?, ?, ?, 'cliente')";
        $stmt = $conexion->prepare($sql);
        $stmt->bind_param("sssss", $nombre, $apellidos, $email, $hash, $telefono);
        if ($stmt->execute()) {
            $exito = "Usuario registrado correctamente. Ya puedes iniciar sesión.";
        } else {
            $errores[] = "Error al registrar el usuario.";
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
    <title>Register | Gargot</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="css/estilos.css?v=3">
</head>
<body class="page-register">
<?php include 'includes/header.php'; ?>

<main class="contenedor page-mono">
    <h1>register</h1>

    <?php if (!empty($errores)): ?>
        <div class="admin-errors">
            <ul>
                <?php foreach ($errores as $e): ?>
                    <li><?php echo htmlspecialchars($e); ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <?php if ($exito !== ""): ?>
        <p class="wishlist-empty"><?php echo htmlspecialchars($exito); ?></p>
    <?php endif; ?>

    <form method="post" action="register.php" class="admin-form" style="max-width: 520px;">
        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
        <div class="admin-form-row">
            <label>name*</label>
            <input type="text" name="nombre" value="<?php echo htmlspecialchars($nombre ?? ""); ?>" required>
        </div>

        <div class="admin-form-row">
            <label>surname*</label>
            <input type="text" name="apellidos" value="<?php echo htmlspecialchars($apellidos ?? ""); ?>" required>
        </div>

        <div class="admin-form-row">
            <label>email*</label>
            <input type="email" name="email" value="<?php echo htmlspecialchars($email ?? ""); ?>" required>
        </div>

        <div class="admin-form-row">
            <label>phone</label>
            <input type="text" name="telefono" value="<?php echo htmlspecialchars($telefono ?? ""); ?>">
        </div>

        <div class="admin-form-row">
            <label>password*</label>
            <input type="password" name="password" required>
        </div>

        <div class="admin-form-row">
            <label>repeat password*</label>
            <input type="password" name="password2" required>
        </div>

        <div class="admin-buttons">
            <button type="submit" class="btn-product">register</button>
            <a href="login.php" class="btn-admin-secondary">log in</a>
        </div>
    </form>
</main>

</body>
</html>
