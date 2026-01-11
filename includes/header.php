<?php
// Este archivo asume que YA se ha hecho session_start() en la pagina que lo incluye

// Detectar si estamos en un subdirectorio para ajustar rutas (normalizando en Windows)
$path = $_SERVER['PHP_SELF'] ?? '';
$normalizedPath = str_replace('\\', '/', $path);
$base = (preg_match('#(^|/)(admin|user|renting)/#', $normalizedPath)) ? '..' : '.';

// CSRF para newsletter (publico y admin)
if (empty($_SESSION['newsletter_csrf'])) {
    $_SESSION['newsletter_csrf'] = bin2hex(random_bytes(32));
}
$newsletterToken = $_SESSION['newsletter_csrf'];

/* =========================
   CONTADORES CARRITO / WISHLIST
   ========================= */
$itemsCarrito = 0;
if (!empty($_SESSION['carrito'])) {
    foreach ($_SESSION['carrito'] as $item) {
        $itemsCarrito += (int)$item['cantidad'];
    }
}

$wishlistCount = 0;
if (!empty($_SESSION['wishlist'])) {
    $wishlistCount = count($_SESSION['wishlist']);
}
?>

<!-- =========================
     HEADER NUEVO GLOBAL
     ========================= -->
<header class="site-header">
    <div class="header-inner">
        <!-- BOTON MENU IZQUIERDA -->
        <button class="header-menu-btn" id="openMenu" aria-label="Open menu" type="button">
            <img src="<?php echo $base; ?>/img/menu.png" alt="Menu" class="header-icon">
            <span class="header-menu-label">MENU</span>
        </button>

        <!-- LOGO CENTRADO -->
        <div class="header-logo-center">
            <a href="<?php echo $base; ?>/index.php">
                <img src="<?php echo $base; ?>/img/logo.png" alt="Gargot logo" class="logo-img header-logo-img">
            </a>
        </div>

        <!-- ICONOS DERECHA -->
        <div class="header-right">
            <a href="<?php echo $base; ?>/user/wishlist.php" class="header-icon-link" aria-label="Wishlist">
                <span class="header-icon-wrapper">
                    <img src="<?php echo $base; ?>/img/heart.png" alt="Wishlist" class="header-icon">
                    <?php if ($wishlistCount > 0): ?>
                        <span class="header-badge"><?php echo $wishlistCount; ?></span>
                    <?php endif; ?>
                </span>
            </a>

            <a href="<?php echo $base; ?>/cart.php" class="header-icon-link" aria-label="Cart">
                <span class="header-icon-wrapper">
                    <img src="<?php echo $base; ?>/img/cart.png" alt="Cart" class="header-icon">
                    <?php if ($itemsCarrito > 0): ?>
                        <span class="header-badge"><?php echo $itemsCarrito; ?></span>
                    <?php endif; ?>
                </span>
            </a>
        </div>
    </div>
</header>

<!-- MENU DESPLEGABLE A PANTALLA COMPLETA -->
<div class="mobile-menu-overlay" id="mobileMenu" aria-hidden="true">
    <div class="mobile-menu-inner">
        <div class="mobile-menu-header">
            <span class="mobile-menu-title">MENU</span>
            <button class="header-close-btn" id="closeMenu" aria-label="Close menu" type="button">
                <img src="<?php echo $base; ?>/img/close.png" alt="Close" class="header-icon">
            </button>
        </div>

        <!-- BARRA DE BUSQUEDA -->
        <form class="menu-search" action="<?php echo $base; ?>/shop.php" method="get">
            <input type="text" name="q" placeholder="SEARCH PRODUCTS">
        </form>

        <nav class="mobile-menu-nav">
            <a href="<?php echo $base; ?>/new_in.php" class="menu-link">
                <img src="<?php echo $base; ?>/img/menu.png" alt="" class="menu-bullet">
                <span>NEW IN</span>
            </a>

            <a href="<?php echo $base; ?>/shop.php" class="menu-link">
                <img src="<?php echo $base; ?>/img/menu.png" alt="" class="menu-bullet">
                <span>SHOP</span>
            </a>

            <a href="<?php echo $base; ?>/renting/how_it_works.php" class="menu-link">
                <img src="<?php echo $base; ?>/img/menu.png" alt="" class="menu-bullet">
                <span>RENTING</span>
            </a>

            <a href="<?php echo $base; ?>/shipping_returns.php" class="menu-link">
                <img src="<?php echo $base; ?>/img/menu.png" alt="" class="menu-bullet">
                <span>SHIPPING & RETURNS</span>
            </a>

            <a href="<?php echo $base; ?>/contact.php" class="menu-link">
                <img src="<?php echo $base; ?>/img/menu.png" alt="" class="menu-bullet">
                <span>CONTACT</span>
            </a>

            <?php if (isset($_SESSION['id_usuario'])): ?>
                <a href="<?php echo $base; ?>/<?php echo ($_SESSION['rol'] === 'admin') ? 'admin/index.php' : 'user/index.php'; ?>" class="menu-link">
                    <img src="<?php echo $base; ?>/img/menu.png" alt="" class="menu-bullet">
                    <span>MY ACCOUNT</span>
                </a>
                <a href="<?php echo $base; ?>/logout.php" class="menu-link">
                    <img src="<?php echo $base; ?>/img/menu.png" alt="" class="menu-bullet">
                    <span>LOG OUT</span>
                </a>
            <?php else: ?>
                <a href="<?php echo $base; ?>/login.php" class="menu-link">
                    <img src="<?php echo $base; ?>/img/menu.png" alt="" class="menu-bullet">
                    <span>LOG IN</span>
                </a>
            <?php endif; ?>
        </nav>
    </div>
</div>

<!-- Banner de cookies / privacidad -->
<div class="cookie-banner" id="cookieBanner" aria-live="polite">
    <div class="cookie-banner__title">cookies & privacidad</div>
    <p class="cookie-banner__text">
        Usamos cookies esenciales para que la tienda funcione y cookies analiticas opcionales para mejorar la experiencia.
        Puedes aceptarlas todas o quedarte solo con las esenciales.
        Consulta la <a href="<?php echo $base; ?>/privacy.php">politica de privacidad</a>.
    </p>
    <div class="cookie-banner__actions">
        <button class="cookie-btn" id="cookieAccept">aceptar todas</button>
        <button class="cookie-btn secondary" id="cookieEssential">solo esenciales</button>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const openBtn  = document.getElementById('openMenu');
    const closeBtn = document.getElementById('closeMenu');
    const menu     = document.getElementById('mobileMenu');
    const body     = document.body;

    if (openBtn && closeBtn && menu) {
        const openMenuFn = () => {
            menu.classList.add('is-open');
            menu.setAttribute('aria-hidden', 'false');
            body.classList.add('menu-open');
        };
        const closeMenuFn = () => {
            menu.classList.remove('is-open');
            menu.setAttribute('aria-hidden', 'true');
            body.classList.remove('menu-open');
        };
        openBtn.addEventListener('click', openMenuFn);
        closeBtn.addEventListener('click', closeMenuFn);
    }

    // Claves ligadas a la sesion PHP
    const sessionId     = '<?php echo session_id(); ?>';
    const consentKey    = `cookieConsentSession_${sessionId}`;
    const newsletterKey = `newsletterSeenSession_${sessionId}`;

    // Cookies (una vez por sesion)
    const cookieBanner    = document.getElementById('cookieBanner');
    const cookieAccept    = document.getElementById('cookieAccept');
    const cookieEssential = document.getElementById('cookieEssential');

    if (cookieBanner) {
        const stored = sessionStorage.getItem(consentKey) || document.cookie.includes(`${consentKey}=1`);
        if (!stored) {
            cookieBanner.classList.add('show');
        }
        const setConsent = () => {
            sessionStorage.setItem(consentKey, '1');
            document.cookie = `${consentKey}=1; path=/; SameSite=Lax`;
            cookieBanner.classList.remove('show');
        };
        cookieAccept && cookieAccept.addEventListener('click', setConsent);
        cookieEssential && cookieEssential.addEventListener('click', setConsent);
    }

    // Newsletter (una vez por sesion)
    const newsletterBar   = document.getElementById('newsletterBar');
    const newsletterForm  = document.getElementById('newsletterForm');
    const newsletterMsg   = document.getElementById('newsletterMessage');
    const newsletterClose = document.getElementById('newsletterClose');
    const subscribeUrl    = '<?php echo $base; ?>/newsletter_subscribe.php';
    const closeUrl        = '<?php echo $base; ?>/newsletter_close.php';
    const csrfToken       = '<?php echo $newsletterToken; ?>';

    if (newsletterBar) {
        const seen = sessionStorage.getItem(newsletterKey) || document.cookie.includes(`${newsletterKey}=1`);
        if (!seen) {
            setTimeout(function() {
                newsletterBar.classList.add('visible');
            }, 500);
        }
    }

    if (newsletterForm && newsletterBar) {
        newsletterForm.addEventListener('submit', function (e) {
            e.preventDefault();
            const formData = new FormData(newsletterForm);
            formData.append('csrf_token', csrfToken);

            fetch(subscribeUrl, {
                method: 'POST',
                body: formData
            })
            .then(function (res) { return res.json(); })
            .then(function (data) {
                if (newsletterMsg) {
                    newsletterMsg.textContent = data.message || '';
                }
                if (data && data.success) {
                    newsletterBar.classList.add('newsletter-bar--success');
                    sessionStorage.setItem(newsletterKey, '1');
                    document.cookie = `${newsletterKey}=1; path=/; SameSite=Lax`;
                    setTimeout(function () {
                        newsletterBar.classList.remove('visible');
                        setTimeout(function () {
                            newsletterBar.style.display = 'none';
                        }, 400);
                    }, 1800);
                }
            })
            .catch(function () {
                if (newsletterMsg) {
                    newsletterMsg.textContent = 'There was a problem. Please try again.';
                }
            });
        });
    }

    if (newsletterClose && newsletterBar) {
        newsletterClose.addEventListener('click', function () {
            const fd = new FormData();
            fd.append('csrf_token', csrfToken);
            fetch(closeUrl, { method: 'POST', body: fd }).finally(function () {
                newsletterBar.classList.remove('visible');
                setTimeout(function () {
                    newsletterBar.style.display = 'none';
                    sessionStorage.setItem(newsletterKey, '1');
                    document.cookie = `${newsletterKey}=1; path=/; SameSite=Lax`;
                }, 400);
            });
        });
    }
});
</script>

<?php if (empty($_SESSION['newsletter_closed'])): ?>
<div class="newsletter-bar" id="newsletterBar">
    <div class="newsletter-inner">
        <button type="button" class="newsletter-close" id="newsletterClose" aria-label="Close newsletter">x</button>
        <p class="newsletter-text">
            SUBSCRIBE TO OUR NEWSLETTER BELOW FOR PRIORITY UPDATES.<br>
            EACH DROP IS LIMITED AND TIME-SENSITIVE.<br>
            SUBSCRIBE SO YOU NEVER MISS A RELEASE.
        </p>
        <form class="newsletter-form" id="newsletterForm">
            <input type="email" name="email" placeholder="YOUR EMAIL" required>
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($newsletterToken); ?>">
            <button type="submit" class="btn-product">subscribe</button>
        </form>
        <p class="newsletter-message" id="newsletterMessage"></p>
    </div>
</div>
<?php endif; ?>
