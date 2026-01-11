<?php
session_start();
include("includes/conexion.php");
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Contact | Gargot</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="css/estilos.css?v=3">
</head>
<body>

<?php include 'includes/header.php'; ?>

<main class="contenedor page-mono">
    <h1>contact</h1>

    <p>
        For rental requests, collaborations or any questions about Gargot pieces:
    </p>

    <p>
        <strong>Email</strong><br>
        <a href="mailto:arxiugargot@gmail.com">arxiugargot@gmail.com</a>
    </p>

    <p>
        <strong>Instagram</strong><br>
        <a href="https://instagram.com/arxiu_gargot" target="_blank" rel="noopener">
            @arxiu_gargot
        </a>
    </p>

    <p class="descripcion-pequena">
        We will get back to you as soon as possible.
    </p>
</main>

</body>
</html>
