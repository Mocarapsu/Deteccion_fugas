-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 15-10-2025 a las 17:38:37
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
-- Base de datos: `deteccion_fugas`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `arduinos`
--

CREATE TABLE `arduinos` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `codigo_arduino` varchar(50) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `ubicacion` varchar(200) DEFAULT NULL,
  `fecha_registro` timestamp NOT NULL DEFAULT current_timestamp(),
  `activo` tinyint(1) DEFAULT 1,
  `ultima_lectura` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `arduinos`
--

INSERT INTO `arduinos` (`id`, `user_id`, `codigo_arduino`, `nombre`, `ubicacion`, `fecha_registro`, `activo`, `ultima_lectura`) VALUES
(1, 1, 'ARD001', 'Arduino Prueba', 'Laboratorio Principal', '2025-10-09 00:21:35', 1, NULL);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `configuracion_sensores`
--

CREATE TABLE `configuracion_sensores` (
  `id` int(11) NOT NULL,
  `arduino_id` int(11) NOT NULL,
  `sensor_temperatura` tinyint(1) DEFAULT 1,
  `sensor_humedad` tinyint(1) DEFAULT 1,
  `sensor_corriente` tinyint(1) DEFAULT 1,
  `frecuencia_lectura` int(11) DEFAULT 30 COMMENT 'Segundos entre lecturas'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `configuracion_sensores`
--

INSERT INTO `configuracion_sensores` (`id`, `arduino_id`, `sensor_temperatura`, `sensor_humedad`, `sensor_corriente`, `frecuencia_lectura`) VALUES
(1, 1, 1, 1, 1, 30);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `lecturas`
--

CREATE TABLE `lecturas` (
  `id` bigint(20) NOT NULL,
  `arduino_id` int(11) NOT NULL,
  `temperatura` decimal(5,2) DEFAULT NULL COMMENT 'Temperatura en grados Celsius',
  `humedad` decimal(5,2) DEFAULT NULL COMMENT 'Humedad en porcentaje',
  `corriente` decimal(6,3) DEFAULT NULL COMMENT 'Corriente en Amperios',
  `timestamp` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `logs_actividad`
--

CREATE TABLE `logs_actividad` (
  `id` bigint(20) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `arduino_id` int(11) DEFAULT NULL,
  `accion` varchar(100) NOT NULL,
  `descripcion` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `timestamp` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `logs_actividad`
--

INSERT INTO `logs_actividad` (`id`, `user_id`, `arduino_id`, `accion`, `descripcion`, `ip_address`, `timestamp`) VALUES
(1, 1, NULL, 'LOGIN_FALLIDO', 'Intento de login fallido', '::1', '2025-10-14 16:03:30'),
(2, 1, NULL, 'LOGIN_FALLIDO', 'Intento de login fallido', '::1', '2025-10-14 16:04:38'),
(3, 1, NULL, 'LOGIN', 'Login exitoso', '::1', '2025-10-14 16:08:36'),
(4, 1, NULL, 'LOGOUT', 'Usuario cerró sesión', '::1', '2025-10-14 16:11:15');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `usuarios`
--

CREATE TABLE `usuarios` (
  `id` int(11) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `email` varchar(150) NOT NULL,
  `password` varchar(255) NOT NULL,
  `fecha_registro` timestamp NOT NULL DEFAULT current_timestamp(),
  `activo` tinyint(1) DEFAULT 1,
  `intentos_fallidos` int(11) DEFAULT 0,
  `bloqueado_hasta` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `usuarios`
--

INSERT INTO `usuarios` (`id`, `nombre`, `email`, `password`, `fecha_registro`, `activo`, `intentos_fallidos`, `bloqueado_hasta`) VALUES
(1, 'Admin', 'admin@test.com', '$2y$12$2gPrAkl7X6fOA6Ep.dSrDedsXg7lXmCB9eWXL9.FRIxKyEe4DvEby', '2025-10-09 00:21:35', 1, 0, NULL);

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `arduinos`
--
ALTER TABLE `arduinos`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `codigo_arduino` (`codigo_arduino`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_codigo` (`codigo_arduino`),
  ADD KEY `idx_ultima_lectura` (`ultima_lectura`);

--
-- Indices de la tabla `configuracion_sensores`
--
ALTER TABLE `configuracion_sensores`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_arduino_id` (`arduino_id`);

--
-- Indices de la tabla `lecturas`
--
ALTER TABLE `lecturas`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_arduino_timestamp` (`arduino_id`,`timestamp`),
  ADD KEY `idx_timestamp` (`timestamp`);

--
-- Indices de la tabla `logs_actividad`
--
ALTER TABLE `logs_actividad`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_timestamp` (`timestamp`);

--
-- Indices de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `idx_email` (`email`),
  ADD KEY `idx_activo` (`activo`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `arduinos`
--
ALTER TABLE `arduinos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de la tabla `configuracion_sensores`
--
ALTER TABLE `configuracion_sensores`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de la tabla `lecturas`
--
ALTER TABLE `lecturas`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `logs_actividad`
--
ALTER TABLE `logs_actividad`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `arduinos`
--
ALTER TABLE `arduinos`
  ADD CONSTRAINT `arduinos_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `configuracion_sensores`
--
ALTER TABLE `configuracion_sensores`
  ADD CONSTRAINT `configuracion_sensores_ibfk_1` FOREIGN KEY (`arduino_id`) REFERENCES `arduinos` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `lecturas`
--
ALTER TABLE `lecturas`
  ADD CONSTRAINT `lecturas_ibfk_1` FOREIGN KEY (`arduino_id`) REFERENCES `arduinos` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
