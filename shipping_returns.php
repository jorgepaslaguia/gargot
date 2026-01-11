<?php
session_start();
include("includes/conexion.php");
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Shipping & Returns | Gargot</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="css/estilos.css?v=3">
</head>
<body>

<?php include 'includes/header.php'; ?>

<main class="contenedor page-mono">
    <h1>shipping & returns</h1>

    <h2>delivery & shipping</h2>
    <p>We currently offer delivery only within Barcelona.</p>
    <p>Outside Barcelona, all rentals must be collected via pick-up or shipping.
       Any shipping costs outside the city are the responsibility of the client.</p>

    <h2>returns</h2>
    <p>Rented pieces must be returned in the same condition in which they were received.</p>
    <p>Any changes to the agreed rental dates must be communicated no later than the day of delivery or pick-up.</p>
    <p>In case of loss or irreparable damage, the full value of the garment will be charged.
       For minor damages (such as a loose button or a removable stain), Gargot will cover the repair costs.</p>

    <p class="descripcion-pequena">
        For questions about shipping or returns, email:
        <a href="mailto:arxiugargot@gmail.com">arxiugargot@gmail.com</a>.
    </p>
</main>

</body>
</html>
