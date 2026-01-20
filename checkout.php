<?php
session_start();
require_once "includes/conexion.php";

// Helper fetchOne si no existe
if (!function_exists('fetchOne')) {
    function fetchOne(PDO $pdo, string $sql, array $params = []) {
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $row = $stmt->fetch();
        return $row ?: [];
    }
}

// Si el carrito está vacío, volver al cart
if (!isset($_SESSION["carrito"]) || empty($_SESSION["carrito"])) {
    header("Location: cart.php");
    exit();
}

$carrito = $_SESSION["carrito"];
$total = 0;
$itemsCarrito = 0;
$checkoutIssues = [];

// Revalidar stock/visibilidad antes de checkout
$ids = array_keys($carrito);
$hasVisibility = false;
$colCheck = $pdo->query("SHOW COLUMNS FROM productos LIKE 'is_visible'");
$colRows = $colCheck ? $colCheck->fetchAll() : [];
if (count($colRows) > 0) $hasVisibility = true;

$stockMap = [];
if (!empty($ids)) {
    $placeholders = [];
    $params = [];
    foreach ($ids as $index => $pid) {
        $key = "id" . $index;
        $placeholders[] = ":" . $key;
        $params[$key] = (int)$pid;
    }
    $sql = "SELECT id_producto, stock" . ($hasVisibility ? ", is_visible" : "") . " FROM productos WHERE id_producto IN (" . implode(",", $placeholders) . ")";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $rows = $stmt->fetchAll();
    foreach ($rows as $row) {
        $stockMap[$row['id_producto']] = $row;
    }
}

foreach ($carrito as $pid => &$item) {
    $pidInt = (int)$pid;
    if (!isset($stockMap[$pidInt])) {
        $checkoutIssues[] = $item["nombre"] . " ya no está disponible.";
        unset($carrito[$pid]);
        continue;
    }
    $row = $stockMap[$pidInt];
    if ($hasVisibility && (int)$row['is_visible'] !== 1) {
        $checkoutIssues[] = $item["nombre"] . " está oculto y se ha retirado.";
        unset($carrito[$pid]);
        continue;
    }
    $stockBD = (int)$row['stock'];
    if ($stockBD <= 0) {
        $checkoutIssues[] = $item["nombre"] . " está sold out.";
        unset($carrito[$pid]);
        continue;
    }
    $qty = (int)$item["cantidad"];
    if ($qty > $stockBD) {
        $item["cantidad"] = $stockBD;
        $checkoutIssues[] = $item["nombre"] . ": cantidad ajustada a stock (" . $stockBD . ").";
    }
    $total += $item["precio"] * $item["cantidad"];
    $itemsCarrito += $item["cantidad"];
}
unset($item);
$_SESSION["carrito"] = $carrito;

$errores = [];
$csrfToken = $_SESSION['csrf_token'] ?? bin2hex(random_bytes(16));
$_SESSION['csrf_token'] = $csrfToken;
$pedidoCreado = false;
$idPedidoNuevo = null;

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $errores[] = "Sesión inválida. Recarga la página.";
    }
    // Datos
    $nombre        = trim($_POST["nombre"] ?? "");
    $apellidos     = trim($_POST["apellidos"] ?? "");
    $email         = trim($_POST["email"] ?? "");
    $telefono      = trim($_POST["telefono"] ?? "");
    $direccion     = trim($_POST["direccion"] ?? "");
    $codigo_postal = trim($_POST["codigo_postal"] ?? "");
    $ciudad        = trim($_POST["ciudad"] ?? "");
    $provincia     = trim($_POST["provincia"] ?? "");
    $pais          = trim($_POST["pais"] ?? "España");
    $notas         = trim($_POST["notas"] ?? "");
    $metodo_pago   = trim($_POST["metodo_pago"] ?? "offline");

    // Validaciones
    if ($nombre === "")        $errores[] = "Nombre es obligatorio.";
    if ($apellidos === "")     $errores[] = "Apellidos son obligatorios.";
    if ($email === "" || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errores[] = "Email no es válido.";
    if ($direccion === "")     $errores[] = "Dirección es obligatoria.";
    if ($codigo_postal === "") $errores[] = "Código postal es obligatorio.";
    if ($ciudad === "")        $errores[] = "Ciudad es obligatoria.";
    if ($pais === "")          $errores[] = "País es obligatorio.";

    if (empty($errores) && empty($checkoutIssues)) {
        // Verificar stock de cada ítem antes de crear pedido
        foreach ($carrito as $item) {
            $idProd = (int)$item["id"];
            $cantidad = (int)$item["cantidad"];
            $rowStock = fetchOne($pdo, "SELECT stock FROM productos WHERE id_producto = :id_producto", [
                "id_producto" => $idProd,
            ]);
            $stockProd = isset($rowStock['stock']) ? (int)$rowStock['stock'] : 0;
            if ($cantidad > $stockProd) {
                $errores[] = "No queda stock suficiente de " . $item["nombre"] . " (quedan " . $stockProd . ").";
            }
        }
    }

    if (empty($errores)) {
        $pdo->beginTransaction();
        try {
            $id_usuario = $_SESSION["id_usuario"] ?? null;

            $sqlPedido = "INSERT INTO pedidos
                          (id_usuario, nombre, apellidos, email, telefono,
                           direccion, codigo_postal, ciudad, provincia, pais,
                           notas, fecha_pedido, estado, total, metodo_pago)
                          VALUES
                          (:id_usuario, :nombre, :apellidos, :email, :telefono, :direccion, :codigo_postal, :ciudad, :provincia, :pais, :notas, NOW(), 'pending', :total, :metodo_pago)";
            $stmtPedido = $pdo->prepare($sqlPedido);
            $stmtPedido->execute([
                "id_usuario" => $id_usuario,
                "nombre" => $nombre,
                "apellidos" => $apellidos,
                "email" => $email,
                "telefono" => $telefono,
                "direccion" => $direccion,
                "codigo_postal" => $codigo_postal,
                "ciudad" => $ciudad,
                "provincia" => $provincia,
                "pais" => $pais,
                "notas" => $notas,
                "total" => $total,
                "metodo_pago" => $metodo_pago,
            ]);

            $idPedidoNuevo = (int)$pdo->lastInsertId();

            $sqlLinea = "INSERT INTO detalle_pedido (id_pedido, id_producto, cantidad, precio_unitario) VALUES (:id_pedido, :id_producto, :cantidad, :precio_unitario)";
            $stmtLinea = $pdo->prepare($sqlLinea);

            foreach ($carrito as $item) {
                $idProd   = (int)$item["id"];
                $cantidad = (int)$item["cantidad"];
                $precio   = (float)$item["precio"];
                $stmtLinea->execute([
                    "id_pedido" => $idPedidoNuevo,
                    "id_producto" => $idProd,
                    "cantidad" => $cantidad,
                    "precio_unitario" => $precio,
                ]);
            }

            // Decrementar stock
            $stmtStock = $pdo->prepare("UPDATE productos SET stock = GREATEST(stock - :cantidad, 0) WHERE id_producto = :id_producto");
            foreach ($carrito as $item) {
                $idProd   = (int)$item["id"];
                $cantidad = (int)$item["cantidad"];
                $stmtStock->execute([
                    "cantidad" => $cantidad,
                    "id_producto" => $idProd,
                ]);
            }

            $pdo->commit();
            $pedidoCreado = true;
            unset($_SESSION["carrito"]);
            $carrito = [];
        } catch (Exception $e) {
            $pdo->rollBack();
            $errores[] = $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Checkout | Gargot</title>
    <link rel="stylesheet" href="css/estilos.css">
</head>
<body>
<?php include 'includes/header.php'; ?>

<main class="contenedor">
    <h1 class="titulo-pagina">CHECKOUT</h1>

    <?php if ($pedidoCreado): ?>
        <p class="descripcion-pequena">
            Gracias por tu pedido. Nº <?php echo (int)$idPedidoNuevo; ?>.<br>
            Estado actual: <strong>pending</strong>.
        </p>
    <?php else: ?>

        <!-- Sin bloque de errores en rojo para limpiar la vista -->

        <!-- Resumen del carrito -->
        <h2 class="subtitulo">Order summary</h2>
        <table class="cart-table checkout-summary">
            <?php foreach ($carrito as $p): ?>
                <tr>
                    <td class="cart-product" style="text-align:center;">
                        <?php if (!empty($p["imagen"])): ?>
                            <img src="<?php echo htmlspecialchars($p["imagen"]); ?>" alt="" style="max-width:80px; display:block; margin:0 auto 6px;">
                        <?php endif; ?>
                        <div style="text-align:center;"><?php echo htmlspecialchars($p["nombre"]); ?></div>
                    </td>
                    <td style="text-align:center;"><?php echo (int)$p["cantidad"]; ?></td>
                    <td style="text-align:center;"><?php echo number_format($p["precio"] * $p["cantidad"], 2); ?> €</td>
                </tr>
            <?php endforeach; ?>
        </table>

        <div class="cart-total">
            <span>Total: <?php echo number_format($total, 2); ?> €</span>
        </div>

        <!-- Formulario -->
        <h2 class="subtitulo" style="margin-top:30px;">Shipping / billing details</h2>
        <form method="post" class="checkout-form" novalidate>
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
            <div class="checkout-row">
                <label>
                    <span>Nombre <span class="required-asterisk">*</span></span>
                    <input type="text" name="nombre" required value="<?php echo htmlspecialchars($_POST["nombre"] ?? ""); ?>">
                </label>
                <label>
                    <span>Apellidos <span class="required-asterisk">*</span></span>
                    <input type="text" name="apellidos" required value="<?php echo htmlspecialchars($_POST["apellidos"] ?? ""); ?>">
                </label>
            </div>

            <div class="checkout-row">
                <label>
                    <span>Email <span class="required-asterisk">*</span></span>
                    <input type="email" name="email" required value="<?php echo htmlspecialchars($_POST["email"] ?? ""); ?>">
                </label>
                <label>
                    <span>Teléfono</span>
                    <input type="text" name="telefono" value="<?php echo htmlspecialchars($_POST["telefono"] ?? ""); ?>">
                </label>
            </div>

            <div class="checkout-row">
                <label>
                    <span>Dirección <span class="required-asterisk">*</span></span>
                    <input type="text" name="direccion" required value="<?php echo htmlspecialchars($_POST["direccion"] ?? ""); ?>">
                </label>
            </div>

            <div class="checkout-row">
                <label>
                    <span>Código postal <span class="required-asterisk">*</span></span>
                    <input type="text" name="codigo_postal" required value="<?php echo htmlspecialchars($_POST["codigo_postal"] ?? ""); ?>">
                </label>
                <label>
                    <span>Ciudad <span class="required-asterisk">*</span></span>
                    <input type="text" name="ciudad" required value="<?php echo htmlspecialchars($_POST["ciudad"] ?? ""); ?>">
                </label>
            </div>

            <div class="checkout-row">
                <label>
                    <span>Provincia</span>
                    <input type="text" name="provincia" value="<?php echo htmlspecialchars($_POST["provincia"] ?? ""); ?>">
                </label>
                <label>
                    <span>País</span>
                    <input type="text" name="pais" value="<?php echo htmlspecialchars($_POST["pais"] ?? "España"); ?>">
                </label>
            </div>

            <div class="checkout-row">
                <label>
                    <span>Notas del pedido</span>
                    <textarea name="notas" rows="3"><?php echo htmlspecialchars($_POST["notas"] ?? ""); ?></textarea>
                </label>
            </div>

            <div class="checkout-row">
                <label>
                    <span>Método de pago</span>
                    <select name="metodo_pago">
                        <option value="offline" <?php echo (($_POST["metodo_pago"] ?? "") === "offline") ? "selected" : ""; ?>>Pago offline / transferencia</option>
                    </select>
                </label>
            </div>

            <div class="cart-actions" style="margin-top: 24px;">
                <a href="cart.php" class="btn-product">back to cart</a>
                <button type="submit" class="btn-product">confirm order</button>
            </div>
        </form>

    <?php endif; ?>
</main>

</body>
</html>
