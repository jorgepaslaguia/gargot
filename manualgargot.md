Manual de uso — Gargot 
1) Accesos rápidos 
• Web (inicio): http://localhost/gargot/ 
• Tienda / catálogo: http://localhost/gargot/shop.php 
• Login: http://localhost/gargot/login.php 
• Registro: http://localhost/gargot/register.php 
• Panel Admin: http://localhost/gargot/admin/ 
Instalación (resumen): importar gargot_sql.sql en phpMyAdmin y colocar el 
proyecto en htdocs/gargot. 
2) Tipos de usuario (3 niveles) 
1. Visitante (sin iniciar sesión) 
Puede navegar por la tienda, ver productos, añadir al carrito y ver el 
contenido del carrito. 
2. Cliente (usuario registrado) 
Además de lo anterior: puede iniciar sesión, mantener su sesión y usar 
funcionalidades asociadas al usuario (zona user, wishlist, etc.). 
3. Administrador 
Accede al panel /admin/ para gestionar productos, homecards, 
pedidos/envíos, estadísticas y auditoría de contraseñas. 
3) Credenciales de administrador (para el profesor) 
• Email: admin@gargot.com 
• Contraseña: 1234 
• Acceso: http://localhost/gargot/admin/ 
4) Navegación de la web (qué puede probar) 
Header / navegación general 
• Acceso a Home y Shop. 
• Iconos de carrito y wishlist (con contador si hay elementos). 
• Acceso a login / registro si no hay sesión. 
• Si hay sesión iniciada: 
o El menú dirige al área correspondiente (admin o usuario). 
Tienda (Shop) 
• Listado de productos con info principal (nombre, marca, precio, etc.). 
• Filtros/ordenación (según parámetros de la tienda). 
• Acceso al detalle del producto. 
Detalle de producto 
• Información completa del producto. 
• Galería de imágenes (miniaturas). 
• Añadir al carrito. 
• Añadir a wishlist. 
Carrito y checkout 
• Ver productos añadidos, cantidades y total. 
• Eliminar productos del carrito. 
• Continuar a checkout / pedido (según implementación actual del 
proyecto). 
Wishlist 
• Página: http://localhost/gargot/user/wishlist.php 
• Añadir y eliminar productos de favoritos. 
5) Panel de Administración (funcionalidades) 
Página principal: http://localhost/gargot/admin/ 
En el panel encontrarás estas secciones: 
A) Products (Gestión de productos) 
Ruta: /admin/products.php 
Aquí el admin puede: 
• Ver listado completo de productos (ID, imagen, nombre, marca, 
familia/talla, precio, stock). 
• Crear producto nuevo (/admin/product_new.php). 
• Editar producto (/admin/product_edit.php): 
o Cambiar nombre, marca, descripción, categoría, familia, talla 
o Ajustar precio, descuento y stock 
o Marcar el producto como visible / no visible 
o Subir nuevas imágenes 
o Eliminar imágenes 
o Ordenar imágenes (campo de orden por imagen) 
• Eliminar producto (/admin/product_delete.php) 
• Cambiar visibilidad rápidamente (/admin/product_visibility.php) 
B) Home Cards (Escaparate / cards de la home) 
Ruta: /admin/home_cards.php 
Sirve para gestionar las tarjetas visuales de la home: 
• Crear nueva card (/admin/home_card_new.php): 
o Título 
o Subtítulo 
o Imagen 
o Enlace (link_url) a la página deseada 
o Orden (sort_order) → controla el orden en el escaparate 
o Activo / inactivo (si se muestra o no) 
• Editar card (/admin/home_card_edit.php): 
o Cambiar texto, imagen, enlace, orden y estado activo 
C) Shipments / Orders (Gestión de pedidos y estados) 
Ruta: /admin/shipments.php 
Permite: 
• Ver pedidos con: 
o ID, fecha, usuario, email, dirección/envío, nº de items, total, pago, 
estado 
• Filtrar por estado (permitidos): 
o pending, paid, shipped, cancelled 
• Actualizar el estado del pedido (gestión básica del flujo de pedidos) 
D) Stats (Estadísticas) 
Ruta: /admin/stats.php 
Panel orientado a analítica de ventas: 
• Marcas más fuertes (últimos 30 días) 
• Precio medio (30 días) 
• Heatmap semanal de pedidos (30 días) 
• Tendencia mensual (últimos 12 meses) 
• Top categorías (30 días) 
• Top productos (30 días) 
• Tallas vendidas (30 días) 
• Stock vs demanda (30 días) 
• Top clientes 
E) Password Audit (Auditoría de contraseñas) 
Ruta: /admin/password_audit.php 
Herramienta de mantenimiento: 
• Revisa contraseñas y permite forzar hash seguro si detecta contraseñas 
en formato inseguro/antiguo. 
• Orientado a seguridad y buenas prácticas. 
6) Flujo recomendado de prueba  
Si alguien quiere comprobarlo rápido, lo ideal es: 
1. Entrar como admin (admin@gargot.com / 1234) 
2. Ir a Products → crear un producto y subirle imágenes 
3. Ir a Home Cards → crear una card y darle orden + link 
4. Volver a la Home y comprobar que aparece el contenido 
5. Entrar a la tienda → añadir al carrito y wishlist 
6. Revisar Shipments / Stats para ver que el panel tiene vida
