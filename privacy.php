<?php
session_start();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Política de privacidad | Gargot</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="css/estilos.css?v=3">
    <style>
        .privacy-wrapper h1,
        .privacy-wrapper h2 {
            font-family: "GargotSans", system-ui, sans-serif;
            letter-spacing: 0.04em;
        }
        .privacy-wrapper h2 {
            margin-top: 28px;
        }
        .privacy-wrapper p,
        .privacy-wrapper li {
            font-family: "Source Code Pro", monospace;
            font-size: 15px;
            line-height: 1.55;
        }
        .privacy-wrapper ul {
            padding-left: 18px;
        }
    </style>
</head>
<body>
<?php include 'includes/header.php'; ?>

<main class="contenedor privacy-wrapper">
    <h1 class="titulo-pagina">Política de privacidad</h1>

    <section class="texto-legal">
        <h2>1. Responsable</h2>
        <p>Gargot (en adelante, “la tienda”). Para cualquier consulta: <a href="mailto:info@gargot.com">info@gargot.com</a>.</p>

        <h2>2. Datos que recogemos</h2>
        <p>Solo recopilamos los datos necesarios para prestar el servicio: nombre, apellidos, dirección de envío, email, teléfono, historial de pedidos, método de pago (sin almacenar los datos de tarjeta), preferencias de visibilidad (wishlist) y cookies técnicas/analíticas.</p>

        <h2>3. Finalidades</h2>
        <ul>
            <li>Procesar pedidos, envíos y devoluciones.</li>
            <li>Atención al cliente.</li>
            <li>Comunicaciones comerciales si las aceptas (newsletter).</li>
            <li>Mejora de la web mediante analítica anónima.</li>
        </ul>

        <h2>4. Base legal</h2>
        <ul>
            <li>Ejecución del contrato de compra.</li>
            <li>Consentimiento (newsletter y cookies analíticas).</li>
            <li>Interés legítimo (seguridad y prevención de fraude).</li>
        </ul>

        <h2>5. Conservación</h2>
        <p>Conservamos los datos mientras sea necesario para la relación contractual y obligaciones legales (facturación). Los consentimientos pueden retirarse en cualquier momento.</p>

        <h2>6. Cesiones y encargados</h2>
        <p>Solo compartimos datos con proveedores que nos ayudan a operar la tienda (hosting, pasarela de pago, logística) bajo contratos de encargado de tratamiento. No vendemos datos a terceros.</p>

        <h2>7. Derechos</h2>
        <p>Puedes ejercer acceso, rectificación, supresión, oposición, limitación y portabilidad escribiendo a <a href="mailto:info@gargot.com">info@gargot.com</a>. También puedes reclamar ante la autoridad de control.</p>

        <h2>8. Cookies</h2>
        <p>Usamos cookies esenciales para que la web funcione y analíticas opcionales. Puedes gestionar tu consentimiento en el banner de cookies.</p>

        <h2>9. Seguridad</h2>
        <p>Aplicamos medidas técnicas y organizativas razonables para proteger tus datos (cifrado TLS, control de accesos, copias de seguridad).</p>

        <h2>10. Cambios</h2>
        <p>Esta política puede actualizarse. Si el cambio es relevante, lo comunicaremos en la web.</p>
    </section>
</main>

</body>
</html>
