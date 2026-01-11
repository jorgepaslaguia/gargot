<?php
session_start();
include("../includes/conexion.php");
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Renting | Gargot</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../css/estilos.css?v=3">
</head>
<body>

<?php include '../includes/header.php'; ?>

<main class="contenedor page-mono">
    <h1>renting - how it works</h1>

    <h2>how it works</h2>
    <p>
        Select the pieces you are interested in and send your rental request to
        <a href="mailto:arxiugargot@gmail.com">arxiugargot@gmail.com</a>.
    </p>
    <p>We will send you an updated catalog including:</p>
    <ul>
        <li>The rental prices for each piece,</li>
        <li>The availability of every garment,</li>
        <li>Any existing imperfections, so that items are returned in the same condition in which they were received.</li>
    </ul>
    <p>
        Once the rental agreement is confirmed, we will arrange delivery or pick-up of the garments.
    </p>

    <h2>rental terms</h2>
    <p>
        Rental fees are calculated at <strong>25% of the original price</strong> of each piece <strong>per day</strong>.
    </p>
    <p>
        If the rental period needs to be extended <strong>after</strong> the garments have already been delivered,
        the daily rate increases to <strong>35% of the original value</strong>.
    </p>
    <p>
        Any changes to dates must be communicated <strong>no later than the day of delivery or pick-up</strong>.
    </p>
    <p>
        Rented pieces must be returned in the same condition as they were received.
    </p>

    <h2>damages and loss</h2>
    <p>
        In case of loss or irreparable damage, the full value of the garment will be charged.
    </p>
    <p>
        For minor damages (for example, a loose button or a removable stain), Gargot will cover the repair costs.
    </p>
</main>

</body>
</html>
