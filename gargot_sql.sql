-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 10-01-2026 a las 22:35:36
-- Versión del servidor: 10.4.32-MariaDB
-- Versión de PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de datos: `gargot`
--
CREATE DATABASE IF NOT EXISTS `gargot` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE `gargot`;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `categorias`
--

DROP TABLE IF EXISTS `categorias`;
CREATE TABLE `categorias` (
  `id_categoria` int(10) UNSIGNED NOT NULL,
  `nombre` varchar(50) NOT NULL,
  `descripcion` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- RELACIONES PARA LA TABLA `categorias`:
--

--
-- Volcado de datos para la tabla `categorias`
--

INSERT INTO `categorias` (`id_categoria`, `nombre`, `descripcion`) VALUES
(1, 'CLOTHING', 'Prendas de ropa (tops, bottoms, dresses, outerwear, skirts, two-piece)'),
(2, 'FOOTWEAR', 'Calzado'),
(3, 'ACCESSORIES', 'Accesorios');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `detalle_pedido`
--

DROP TABLE IF EXISTS `detalle_pedido`;
CREATE TABLE `detalle_pedido` (
  `id_detalle` int(11) NOT NULL,
  `id_pedido` int(11) NOT NULL,
  `id_producto` int(11) NOT NULL,
  `cantidad` int(11) NOT NULL DEFAULT 1,
  `precio_unitario` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- RELACIONES PARA LA TABLA `detalle_pedido`:
--

--
-- Volcado de datos para la tabla `detalle_pedido`
--

INSERT INTO `detalle_pedido` (`id_detalle`, `id_pedido`, `id_producto`, `cantidad`, `precio_unitario`) VALUES
(1, 1, 2, 3, 200.00),
(2, 2, 2, 1, 200.00);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `home_cards`
--

DROP TABLE IF EXISTS `home_cards`;
CREATE TABLE `home_cards` (
  `id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `subtitle` varchar(255) DEFAULT NULL,
  `image_path` varchar(500) NOT NULL,
  `link_url` varchar(500) DEFAULT NULL,
  `sort_order` int(11) NOT NULL DEFAULT 1,
  `active` tinyint(1) NOT NULL DEFAULT 1,
  `card_size` varchar(10) NOT NULL DEFAULT 'md'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- RELACIONES PARA LA TABLA `home_cards`:
--

--
-- Volcado de datos para la tabla `home_cards`
--

INSERT INTO `home_cards` (`id`, `title`, `subtitle`, `image_path`, `link_url`, `sort_order`, `active`, `card_size`) VALUES
(4, 'new collection', 'spring/summer 26', 'img/home/homeimage1_1768078034.jpeg', '../shop.php?categoria=1', 1, 1, 'md'),
(5, 'OUR BOOTS', '', 'img/home/homeimage3_1768078195.jpeg', '../shop.php?categoria=2', 1, 1, 'md');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `newsletter_subscribers`
--

DROP TABLE IF EXISTS `newsletter_subscribers`;
CREATE TABLE `newsletter_subscribers` (
  `id` int(10) UNSIGNED NOT NULL,
  `email` varchar(255) NOT NULL,
  `created_at` datetime NOT NULL,
  `last_update` datetime NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `first_source` varchar(100) DEFAULT NULL,
  `last_ip` varchar(45) DEFAULT NULL,
  `notes` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- RELACIONES PARA LA TABLA `newsletter_subscribers`:
--

--
-- Volcado de datos para la tabla `newsletter_subscribers`
--

INSERT INTO `newsletter_subscribers` (`id`, `email`, `created_at`, `last_update`, `is_active`, `first_source`, `last_ip`, `notes`) VALUES
(1, 'jorgepaslaguia@gmail.com', '2025-12-03 11:18:16', '2025-12-04 16:08:37', 1, 'site_footer', '::1', NULL);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `pedidos`
--

DROP TABLE IF EXISTS `pedidos`;
CREATE TABLE `pedidos` (
  `id_pedido` int(11) NOT NULL,
  `id_usuario` int(11) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `apellidos` varchar(150) NOT NULL,
  `email` varchar(150) NOT NULL,
  `telefono` varchar(30) DEFAULT NULL,
  `direccion` varchar(255) NOT NULL,
  `codigo_postal` varchar(20) NOT NULL,
  `ciudad` varchar(100) NOT NULL,
  `provincia` varchar(100) DEFAULT NULL,
  `pais` varchar(100) NOT NULL DEFAULT 'España',
  `notas` text DEFAULT NULL,
  `fecha_pedido` datetime NOT NULL DEFAULT current_timestamp(),
  `estado` varchar(20) NOT NULL DEFAULT 'pending',
  `total` decimal(10,2) NOT NULL DEFAULT 0.00,
  `metodo_pago` varchar(50) NOT NULL DEFAULT 'pending'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- RELACIONES PARA LA TABLA `pedidos`:
--

--
-- Volcado de datos para la tabla `pedidos`
--

INSERT INTO `pedidos` (`id_pedido`, `id_usuario`, `nombre`, `apellidos`, `email`, `telefono`, `direccion`, `codigo_postal`, `ciudad`, `provincia`, `pais`, `notas`, `fecha_pedido`, `estado`, `total`, `metodo_pago`) VALUES
(1, 1, '', '', '', NULL, '', '', '', NULL, 'España', NULL, '2025-12-02 10:47:41', 'shipped', 600.00, 'pending'),
(2, 1, 'berta', 'parrot', 'arxiugargot@gmail.com', '618332165', 'carrer ample', '08018', 'barcelona', 'barcelona', 'España', '', '2025-12-02 11:07:44', 'paid', 200.00, 'offline');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `productos`
--

DROP TABLE IF EXISTS `productos`;
CREATE TABLE `productos` (
  `id_producto` int(10) UNSIGNED NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `descripcion` text DEFAULT NULL,
  `precio` decimal(10,2) NOT NULL,
  `descuento` tinyint(3) UNSIGNED NOT NULL DEFAULT 0,
  `stock` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `id_categoria` int(10) UNSIGNED NOT NULL,
  `familia` enum('BOTTOMS','OUTERWEAR','DRESSES','TOPS','SKIRTS','TWO-PIECE') NOT NULL DEFAULT 'TOPS',
  `marca` varchar(100) DEFAULT NULL,
  `imagen` varchar(255) DEFAULT NULL,
  `talla` varchar(10) DEFAULT NULL,
  `color` varchar(30) DEFAULT NULL,
  `fecha_creacion` datetime NOT NULL DEFAULT current_timestamp(),
  `is_visible` tinyint(1) NOT NULL DEFAULT 1,
  `sort_order` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- RELACIONES PARA LA TABLA `productos`:
--   `id_categoria`
--       `categorias` -> `id_categoria`
--

--
-- Volcado de datos para la tabla `productos`
--

INSERT INTO `productos` (`id_producto`, `nombre`, `descripcion`, `precio`, `descuento`, `stock`, `id_categoria`, `familia`, `marca`, `imagen`, `talla`, `color`, `fecha_creacion`, `is_visible`, `sort_order`) VALUES
(3, 'Missoni Coat', 'Abrigo de lana de Missoni. Azul marino. 100% lana', 200.00, 0, 1, 1, 'OUTERWEAR', 'Missoni', 'img/products/missonifront_1768075111_3.jpeg', 'M', NULL, '2026-01-10 20:56:48', 1, 2),
(4, 'Mcqueen T-Shirt', '', 75.00, 0, 1, 1, 'TOPS', 'Alexander McQuen', 'img/products/frontmcqueen_1768077126_3.jpeg', 'S', NULL, '2026-01-10 21:32:06', 1, NULL),
(5, 'MiuMiu Denim Skirt', '', 90.00, 0, 1, 1, 'SKIRTS', 'MiuMiu', 'img/products/miumiufront_1768077219_3.jpeg', 'S', NULL, '2026-01-10 21:33:39', 1, NULL),
(6, 'Lurdes Bergada Skirt', '', 100.00, 0, 1, 1, 'SKIRTS', 'Lurdes Bergada', 'img/products/lurdesbergadafront_1768077284_4.jpeg', 'S', NULL, '2026-01-10 21:34:44', 1, NULL);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `producto_imagenes`
--

DROP TABLE IF EXISTS `producto_imagenes`;
CREATE TABLE `producto_imagenes` (
  `id` int(10) UNSIGNED NOT NULL,
  `id_producto` int(10) UNSIGNED NOT NULL,
  `image_path` varchar(255) NOT NULL,
  `orden` int(11) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- RELACIONES PARA LA TABLA `producto_imagenes`:
--   `id_producto`
--       `productos` -> `id_producto`
--

--
-- Volcado de datos para la tabla `producto_imagenes`
--

INSERT INTO `producto_imagenes` (`id`, `id_producto`, `image_path`, `orden`) VALUES
(41, 3, 'img/products/missonietiqueta_1768075111_0.jpeg', 4),
(42, 3, 'img/products/missoniatras_1768075111_1.jpeg', 3),
(43, 3, 'img/products/missonilateral_1768075111_2.jpeg', 2),
(44, 3, 'img/products/missonifront_1768075111_3.jpeg', 1),
(45, 4, 'img/products/mcqueenetiqueta_1768077126_0.jpeg', 4),
(46, 4, 'img/products/mcqueenatras_1768077126_1.jpeg', 3),
(47, 4, 'img/products/mcqueenlateral_1768077126_2.jpeg', 2),
(48, 4, 'img/products/frontmcqueen_1768077126_3.jpeg', 1),
(49, 5, 'img/products/miumiuetiqueta_1768077219_0.jpeg', 4),
(50, 5, 'img/products/miumiuatras_1768077219_1.jpeg', 3),
(51, 5, 'img/products/miumiulateral_1768077219_2.jpeg', 2),
(52, 5, 'img/products/miumiufront_1768077219_3.jpeg', 1),
(53, 6, 'img/products/lurdesbergadaetiqueta_1768077284_0.jpeg', 5),
(54, 6, 'img/products/lurdesbergadatras3_1768077284_1.jpeg', 4),
(55, 6, 'img/products/lurdesbergadatras2_1768077284_2.jpeg', 3),
(56, 6, 'img/products/lurdesbergadatras_1768077284_3.jpeg', 2),
(57, 6, 'img/products/lurdesbergadafront_1768077284_4.jpeg', 1);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `usuarios`
--

DROP TABLE IF EXISTS `usuarios`;
CREATE TABLE `usuarios` (
  `id_usuario` int(10) UNSIGNED NOT NULL,
  `nombre` varchar(50) NOT NULL,
  `apellidos` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `rol` enum('admin','cliente') NOT NULL DEFAULT 'cliente',
  `telefono` varchar(15) DEFAULT NULL,
  `fecha_alta` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- RELACIONES PARA LA TABLA `usuarios`:
--

--
-- Volcado de datos para la tabla `usuarios`
--

INSERT INTO `usuarios` (`id_usuario`, `nombre`, `apellidos`, `email`, `password`, `rol`, `telefono`, `fecha_alta`) VALUES
(1, 'Admin', 'Principal', 'admin@gargot.com', '$2y$10$zsjMD2NRQqkizGXg8TsCg.OYfJMs0zRJxj50f1U5qYFUuU6a6FVuS', 'admin', '000000000', '2025-11-18 12:17:04');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `wishlist`
--

DROP TABLE IF EXISTS `wishlist`;
CREATE TABLE `wishlist` (
  `id_wishlist` int(10) UNSIGNED NOT NULL,
  `id_usuario` int(10) UNSIGNED NOT NULL,
  `id_producto` int(10) UNSIGNED NOT NULL,
  `fecha_agregado` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- RELACIONES PARA LA TABLA `wishlist`:
--   `id_producto`
--       `productos` -> `id_producto`
--   `id_usuario`
--       `usuarios` -> `id_usuario`
--

--
-- Volcado de datos para la tabla `wishlist`
--

INSERT INTO `wishlist` (`id_wishlist`, `id_usuario`, `id_producto`, `fecha_agregado`) VALUES
(25, 1, 6, '2026-01-10 22:27:17');

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `categorias`
--
ALTER TABLE `categorias`
  ADD PRIMARY KEY (`id_categoria`);

--
-- Indices de la tabla `detalle_pedido`
--
ALTER TABLE `detalle_pedido`
  ADD PRIMARY KEY (`id_detalle`);

--
-- Indices de la tabla `home_cards`
--
ALTER TABLE `home_cards`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `newsletter_subscribers`
--
ALTER TABLE `newsletter_subscribers`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_newsletter_email` (`email`);

--
-- Indices de la tabla `pedidos`
--
ALTER TABLE `pedidos`
  ADD PRIMARY KEY (`id_pedido`);

--
-- Indices de la tabla `productos`
--
ALTER TABLE `productos`
  ADD PRIMARY KEY (`id_producto`),
  ADD KEY `fk_prod_categoria` (`id_categoria`),
  ADD KEY `idx_productos_sort_order` (`sort_order`);

--
-- Indices de la tabla `producto_imagenes`
--
ALTER TABLE `producto_imagenes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `id_producto` (`id_producto`);

--
-- Indices de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  ADD PRIMARY KEY (`id_usuario`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indices de la tabla `wishlist`
--
ALTER TABLE `wishlist`
  ADD PRIMARY KEY (`id_wishlist`),
  ADD UNIQUE KEY `uniq_usuario_producto` (`id_usuario`,`id_producto`),
  ADD KEY `fk_wishlist_producto` (`id_producto`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `categorias`
--
ALTER TABLE `categorias`
  MODIFY `id_categoria` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de la tabla `detalle_pedido`
--
ALTER TABLE `detalle_pedido`
  MODIFY `id_detalle` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de la tabla `home_cards`
--
ALTER TABLE `home_cards`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT de la tabla `newsletter_subscribers`
--
ALTER TABLE `newsletter_subscribers`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT de la tabla `pedidos`
--
ALTER TABLE `pedidos`
  MODIFY `id_pedido` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de la tabla `productos`
--
ALTER TABLE `productos`
  MODIFY `id_producto` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT de la tabla `producto_imagenes`
--
ALTER TABLE `producto_imagenes`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=58;

--
-- AUTO_INCREMENT de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  MODIFY `id_usuario` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de la tabla `wishlist`
--
ALTER TABLE `wishlist`
  MODIFY `id_wishlist` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=26;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `productos`
--
ALTER TABLE `productos`
  ADD CONSTRAINT `fk_prod_categoria` FOREIGN KEY (`id_categoria`) REFERENCES `categorias` (`id_categoria`) ON UPDATE CASCADE;

--
-- Filtros para la tabla `producto_imagenes`
--
ALTER TABLE `producto_imagenes`
  ADD CONSTRAINT `producto_imagenes_ibfk_1` FOREIGN KEY (`id_producto`) REFERENCES `productos` (`id_producto`) ON DELETE CASCADE;

--
-- Filtros para la tabla `wishlist`
--
ALTER TABLE `wishlist`
  ADD CONSTRAINT `fk_wishlist_producto` FOREIGN KEY (`id_producto`) REFERENCES `productos` (`id_producto`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_wishlist_usuario` FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id_usuario`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
