<?php
session_start();
include("../includes/conexion.php");
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Renting Policy | Gargot</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../css/estilos.css?v=3">
</head>
<body>

<?php include '../includes/header.php'; ?>

<main class="contenedor page-mono">
    <h1>renting policy</h1>

    <p>These are the standard terms we apply to rentals. Adjust copy as needed for your campaigns.</p>

    <h2>responsibilities</h2>
    <ul>
        <li>Garments must be returned in the same condition they were delivered.</li>
        <li>Loss or irreparable damage will be charged at full garment value.</li>
        <li>Minor damages (loose buttons, removable stains) are repaired by Gargot.</li>
    </ul>

    <h2>timing</h2>
    <ul>
        <li>Extensions requested after delivery are billed at 35% of the garment value per extra day.</li>
        <li>Date changes must be communicated no later than the day of delivery or pick-up.</li>
    </ul>

    <h2>logistics</h2>
    <ul>
        <li>Pick-up and returns are available in Barcelona; shipping outside the city is client-covered.</li>
        <li>Use the rental inbox <a href="mailto:arxiugargot@gmail.com">arxiugargot@gmail.com</a> for confirmations.</li>
    </ul>

    <p><a href="how_it_works.php">Back to how it works</a></p>
    <p><a href="../index.php">Back to home</a></p>
</main>

</body>
</html>
