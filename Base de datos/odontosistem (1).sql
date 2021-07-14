-- phpMyAdmin SQL Dump
-- version 4.9.1
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 04-12-2020 a las 06:28:10
-- Versión del servidor: 10.4.8-MariaDB
-- Versión de PHP: 7.2.24

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de datos: `odontosistem`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `abonos`
--

CREATE TABLE `abonos` (
  `id` int(11) NOT NULL,
  `paciente_id` bigint(20) UNSIGNED NOT NULL,
  `abonado` float NOT NULL,
  `factura_id` int(11) NOT NULL,
  `referencia` varchar(255) DEFAULT NULL,
  `nota` varchar(255) NOT NULL,
  `methos_pay_id` int(10) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Volcado de datos para la tabla `abonos`
--

INSERT INTO `abonos` (`id`, `paciente_id`, `abonado`, `factura_id`, `referencia`, `nota`, `methos_pay_id`, `created_at`, `updated_at`) VALUES
(1, 1, 15, 1, '561414', '', 0, '2020-11-16 15:55:52', '2020-11-16 11:55:52'),
(2, 1, 10, 1, '5458', '', 0, '2020-11-16 15:55:52', '2020-11-16 11:55:52'),
(3, 1, 20, 1, '5555555', '', 1, '2020-11-16 15:55:52', '2020-11-16 11:55:52');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `citas`
--

CREATE TABLE `citas` (
  `id` int(11) NOT NULL,
  `fecha` date NOT NULL,
  `hora` time DEFAULT NULL,
  `paciente_id` bigint(20) UNSIGNED NOT NULL,
  `atendido` tinyint(1) DEFAULT NULL,
  `updated_at` datetime NOT NULL,
  `created_at` datetime NOT NULL,
  `asistencia_confirmada` tinyint(1) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Volcado de datos para la tabla `citas`
--

INSERT INTO `citas` (`id`, `fecha`, `hora`, `paciente_id`, `atendido`, `updated_at`, `created_at`, `asistencia_confirmada`) VALUES
(6, '2020-12-04', '23:00:00', 9, 0, '2020-08-24 00:00:00', '2020-08-11 00:00:00', 0),
(10, '2020-12-04', '16:04:00', 16, 1, '2020-08-09 17:00:01', '2020-08-09 17:00:01', 1),
(11, '2020-12-04', '23:08:00', 2, 0, '2020-10-16 17:03:15', '2020-08-09 23:05:10', 0),
(12, '2020-12-03', '14:29:00', 2, NULL, '2020-11-09 22:25:46', '2020-11-09 22:25:46', NULL);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `citas_medicas`
--

CREATE TABLE `citas_medicas` (
  `id` int(11) NOT NULL,
  `pacientes_id` bigint(20) UNSIGNED NOT NULL,
  `date` date NOT NULL DEFAULT current_timestamp(),
  `evaluacion` varchar(255) DEFAULT NULL,
  `medicacion` varchar(255) DEFAULT NULL,
  `analisis_solicitados` varchar(255) DEFAULT NULL,
  `comentario_paciente` varchar(255) DEFAULT NULL,
  `comentario_doctor` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Volcado de datos para la tabla `citas_medicas`
--

INSERT INTO `citas_medicas` (`id`, `pacientes_id`, `date`, `evaluacion`, `medicacion`, `analisis_solicitados`, `comentario_paciente`, `comentario_doctor`, `created_at`, `updated_at`) VALUES
(1, 1, '0000-00-00', 'evaluacion', 'medicacion', 'analisis', 'comentario paciente', 'comentario doctor', '2020-11-16 15:56:40', '2020-11-16 11:56:40'),
(2, 1, '0000-00-00', 'evaluacion2', 'medicacion2', 'analisis2', 'comentario paciente2', 'comentario doctor', '2020-11-16 15:56:40', '2020-11-16 11:56:40'),
(3, 1, '2020-11-16', 'Evaluacion', 'Medicacion', 'Analissis test', 'comentario test', NULL, '2020-11-17 00:53:40', '2020-11-16 20:53:40');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `citas_medicas_procedure`
--

CREATE TABLE `citas_medicas_procedure` (
  `id` int(11) NOT NULL,
  `procedure_id` int(11) UNSIGNED NOT NULL DEFAULT 0,
  `cantidad` int(11) NOT NULL,
  `citas_medicas_id` int(11) UNSIGNED NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Volcado de datos para la tabla `citas_medicas_procedure`
--

INSERT INTO `citas_medicas_procedure` (`id`, `procedure_id`, `cantidad`, `citas_medicas_id`, `created_at`, `updated_at`) VALUES
(1, 1, 1, 1, '2020-11-16 16:07:12', '2020-11-16 12:07:12'),
(2, 2, 3, 1, '2020-11-16 16:07:12', '2020-11-16 12:07:12'),
(3, 4, 1, 1, '2020-11-17 00:53:40', '2020-11-16 20:53:40'),
(4, 6, 1, 1, '2020-11-17 00:53:40', '2020-11-16 20:53:40');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `facturas`
--

CREATE TABLE `facturas` (
  `id` int(11) NOT NULL,
  `paciente_id` bigint(20) UNSIGNED NOT NULL,
  `total_neto` float NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Volcado de datos para la tabla `facturas`
--

INSERT INTO `facturas` (`id`, `paciente_id`, `total_neto`, `created_at`, `updated_at`) VALUES
(1, 1, 150, '2020-11-16 15:55:05', '2020-11-16 11:55:05');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `failed_jobs`
--

CREATE TABLE `failed_jobs` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `connection` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `queue` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `payload` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `exception` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `failed_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `methos_pay`
--

CREATE TABLE `methos_pay` (
  `id` int(11) NOT NULL,
  `name` varchar(50) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Volcado de datos para la tabla `methos_pay`
--

INSERT INTO `methos_pay` (`id`, `name`, `created_at`, `updated_at`) VALUES
(1, 'Efectivo', '2020-11-16 15:53:48', '2020-11-16 11:53:48'),
(2, 'Transferencia', '2020-11-16 15:53:48', '2020-11-16 11:53:48'),
(3, 'Pago movil', '2020-11-16 15:53:48', '2020-11-16 11:53:48');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `migrations`
--

CREATE TABLE `migrations` (
  `id` int(10) UNSIGNED NOT NULL,
  `migration` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `batch` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `pacientes`
--

CREATE TABLE `pacientes` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `second_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `last_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `otros` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `second_last_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `dni` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `birth_date` date NOT NULL,
  `address` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `phone` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `sex` tinyint(1) DEFAULT NULL,
  `height` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `weight` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `antecedentes` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL,
  `medicamentos` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `habitos` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL,
  `alergias` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL,
  `medical_history` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL,
  `procedures` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL
) ;

--
-- Volcado de datos para la tabla `pacientes`
--

INSERT INTO `pacientes` (`id`, `name`, `second_name`, `last_name`, `otros`, `second_last_name`, `dni`, `email`, `birth_date`, `address`, `phone`, `sex`, `height`, `weight`, `antecedentes`, `medicamentos`, `habitos`, `alergias`, `medical_history`, `procedures`, `user_id`, `registered_by`, `created_at`, `updated_at`, `motivoConsulta`, `coagulacion`, `embarazo`, `anestesicos`) VALUES
(1, 'angelo', 'Rafael', 'Amaro', NULL, 'Trujillo', '252525', NULL, '2020-06-04', 'marhuanta', '11111111111111', 1, NULL, NULL, 'null', '', 'null', 'null', NULL, NULL, NULL, 1, NULL, '2020-06-07 20:54:56', '', 0, 0, 0),
(2, 'Samuel', 'emmanuel', 'Trias', NULL, 'Santamaria', '24186725', NULL, '1994-12-10', 'San José', '04163891799', 1, NULL, NULL, 'null', '', 'null', 'null', NULL, NULL, 1, 1, '2020-06-05 12:05:45', '2020-08-10 03:35:35', '', 0, 0, 0),
(8, 'Saul', NULL, 'Yanave', NULL, 'Guilarte', '25914064', NULL, '1997-10-31', 'Marhuanta', '04163891799', 1, NULL, NULL, 'null', '', 'null', 'null', NULL, NULL, NULL, 1, '2020-06-05 23:49:54', '2020-06-26 13:16:31', '', 0, 0, 0),
(9, 'ivan', NULL, 'ascanio', NULL, NULL, '25252525', 'ivan@test.com', '1995-07-01', 'Su casa', '21', 1, NULL, NULL, 'null', '', 'null', 'null', NULL, NULL, NULL, 1, '2020-07-27 04:30:00', '2020-08-09 15:02:29', '', 0, 0, 0),
(16, 'Loidy', 'Andreina', 'Torrealba', 'otro', NULL, '25914065', 'test@test.com', '2020-08-11', 'Desconocida', '+58564785', 0, '1.65', '80', 'antecedentes medicos', 'medicamentos', 'habitos', 'alergias', 'medical_history', NULL, 5, 1, '2020-08-09 21:29:49', '2020-11-09 03:56:23', 'primer motivo', 0, 0, 0),
(17, 'Edian', NULL, 'Godoy', NULL, NULL, '0000000', 'radicador@stork.com', '2000-12-15', 'San José', '04163891799', 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1, '2020-12-03 15:35:38', '2020-12-03 15:42:04', NULL, 0, 0, 0);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `password_resets`
--

CREATE TABLE `password_resets` (
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `token` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `password_resets`
--

INSERT INTO `password_resets` (`email`, `token`, `created_at`) VALUES
('admin@admin.com', '$2y$10$3b1MP9U0iVKHu74HmWD1Y.myLbJQT6aJy.O44tvxnRwSh0zdn9vO6', '2020-07-03 19:40:59');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `permissions`
--

CREATE TABLE `permissions` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `slug` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `permissions`
--

INSERT INTO `permissions` (`id`, `name`, `slug`, `description`, `created_at`, `updated_at`) VALUES
(1, 'List role', 'role.index', 'A user can list role', '2020-06-05 02:33:22', '2020-06-05 02:33:22'),
(2, 'Show role', 'role.show', 'A user can see role', '2020-06-05 02:33:22', '2020-06-05 02:33:22'),
(3, 'Create role', 'role.create', 'A user can create role', '2020-06-05 02:33:22', '2020-06-05 02:33:22'),
(4, 'Edit role', 'role.edit', 'A user can edit role', '2020-06-05 02:33:22', '2020-06-05 02:33:22'),
(5, 'Destroy role', 'role.destroy', 'A user can destroy role', '2020-06-05 02:33:22', '2020-06-05 02:33:22'),
(6, 'List user', 'user.index', 'A user can list user', '2020-06-05 02:33:22', '2020-06-05 02:33:22'),
(7, 'Show user', 'user.show', 'A user can see user', '2020-06-05 02:33:22', '2020-06-05 02:33:22'),
(8, 'Edit user', 'user.edit', 'A user can edit user', '2020-06-05 02:33:22', '2020-06-05 02:33:22'),
(9, 'Destroy user', 'user.destroy', 'A user can destroy user', '2020-06-05 02:33:22', '2020-06-05 02:33:22');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `permission_role`
--

CREATE TABLE `permission_role` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `role_id` bigint(20) UNSIGNED NOT NULL,
  `permission_id` bigint(20) UNSIGNED NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `procedures`
--

CREATE TABLE `procedures` (
  `id` int(255) NOT NULL,
  `key_p` varchar(255) DEFAULT NULL,
  `title` varchar(255) NOT NULL,
  `code` varchar(100) NOT NULL,
  `price` double NOT NULL,
  `className` varchar(255) DEFAULT NULL,
  `type` varchar(255) DEFAULT NULL,
  `apply` varchar(255) DEFAULT NULL,
  `ClearBefore` varchar(255) DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `created_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Volcado de datos para la tabla `procedures`
--

INSERT INTO `procedures` (`id`, `key_p`, `title`, `code`, `price`, `className`, `type`, `apply`, `ClearBefore`, `updated_at`, `created_at`) VALUES
(1, '49', 'Movilidad grado I', 'code', 50, NULL, NULL, NULL, NULL, '2020-11-09 01:16:59', '0000-00-00 00:00:00'),
(2, '50', 'Movilidad grado II', '', 14.36, 'pro_mobility_ii', 'Pendiente', 'tooth', 'false', '2020-12-04 00:30:49', '0000-00-00 00:00:00'),
(3, '51', 'Movilidad grado III', '', 0, 'pro_mobility_iii', 'Pendiente', 'tooth', 'false', '2020-11-01 17:06:17', '0000-00-00 00:00:00'),
(4, '97', 'Angulo distal o mesial pendiente', '', 0, 'pro_angle', 'Pendiente', 'side', 'true', '2020-11-01 17:06:17', '0000-00-00 00:00:00'),
(5, '65', 'Angulo distal o mesial en buen estado', '', 0, 'pro_angle_done', 'Completado', 'side', 'true', '2020-11-01 17:06:17', '0000-00-00 00:00:00'),
(6, '98', 'Sellante en mal estado', '', 0, 'pro_sealing', 'Pendiente', 'side', 'true', '2020-11-01 17:06:17', '0000-00-00 00:00:00'),
(7, '66', 'Sellante Bueno', '', 0, 'pro_sealing_done', 'Completado', 'side', 'true', '2020-11-01 17:06:17', '0000-00-00 00:00:00'),
(8, '99', 'Superficie Cariada', '', 0, 'pro_caries', 'Pendiente', 'side', 'true', '2020-11-01 17:06:17', '0000-00-00 00:00:00'),
(9, '67', 'Tratamiento realizado', '', 0, 'pro_caries_done', 'Completado', 'side', 'true', '2020-11-01 17:06:17', '0000-00-00 00:00:00'),
(10, '100', 'Endodoncia por realizar', '', 0, 'pro_endodontics', 'Pendiente', 'tooth', 'true', '2020-11-01 17:06:17', '0000-00-00 00:00:00'),
(11, '68', 'Endodoncia Realizada', '', 0, 'pro_endodontics_done', 'Completado', 'tooth', 'true', '2020-11-01 17:06:17', '0000-00-00 00:00:00'),
(12, '101', 'Exodoncia', '', 0, 'pro_exodontics', 'Pendiente', 'tooth', 'true', '2020-11-01 17:06:17', '0000-00-00 00:00:00'),
(13, '69', 'Exodoncia realizada', '', 0, 'pro_exodontics_done', 'Completado', 'tooth', 'true', '2020-11-01 17:06:17', '0000-00-00 00:00:00'),
(14, '102', 'Línea de fractura', '', 0, 'pro_fracture', 'Pendiente', 'side', 'true', '2020-11-01 17:06:17', '0000-00-00 00:00:00'),
(15, '77', 'Diente sin erupcionar', '', 0, 'pro_unbroken', 'Completado', 'tooth', 'true', '2020-11-01 17:06:17', '0000-00-00 00:00:00'),
(16, '111', 'Corona', '', 0, 'pro_crown', 'Pendiente', 'tooth', 'false', '2020-11-01 17:06:17', '0000-00-00 00:00:00'),
(17, '79', 'Corona Buena', '', 0, 'pro_crown_done', 'Completado', 'tooth', 'false', '2020-11-01 17:06:17', '0000-00-00 00:00:00'),
(18, '112', 'Pulido pendiente', '', 0, 'pro_polish', 'Pendiente', 'tooth', 'true', '2020-11-01 17:06:17', '0000-00-00 00:00:00'),
(19, '80', 'Pulido realizado', '', 0, 'pro_polish_done', 'Completado', 'tooth', 'true', '2020-11-01 17:06:17', '0000-00-00 00:00:00'),
(20, '113', 'Exodoncia quirúrgica', '', 0, 'pro_exodontics_surgical', 'Pendiente', 'tooth', 'true', '2020-11-01 17:06:17', '0000-00-00 00:00:00'),
(21, '114', 'Resina preventiva en mal estado', '', 0, 'pro_resin', 'Pendiente', 'tooth', 'false', '2020-11-01 17:06:17', '0000-00-00 00:00:00'),
(22, '82', 'Resina preventiva buena', '', 0, 'pro_resin_done', 'Completado', 'tooth', 'false', '2020-11-01 17:06:17', '0000-00-00 00:00:00'),
(23, '115', 'Sellante en mal estado', '', 0, 'pro_filling', 'Pendiente', 'tooth', 'false', '2020-11-01 17:06:17', '0000-00-00 00:00:00'),
(24, '83', 'Sellante Bueno', '', 0, 'pro_filling_done', 'Completado', 'tooth', 'false', '2020-11-01 17:06:17', '0000-00-00 00:00:00'),
(25, '118', 'Restauración cervical en mal estado', '', 0, 'pro_restoration', 'Pendiente', 'side', 'true', '2020-11-01 17:06:17', '0000-00-00 00:00:00'),
(26, '86', 'Restauración cervical buena', '', 0, 'pro_restoration_done', 'Completado', 'side', 'true', '2020-11-01 17:06:17', '0000-00-00 00:00:00'),
(27, '120', 'Radiografía pendiente', '', 0, 'pro_rx', 'Pendiente', 'tooth', 'true', '2020-11-01 17:06:17', '0000-00-00 00:00:00'),
(28, '88', 'Radiografía realizada', '', 0, 'pro_rx_done', 'Completado', 'tooth', 'true', '2020-11-01 17:06:17', '0000-00-00 00:00:00'),
(29, '90', 'Diente extraído', '', 0, 'pro_missing', 'Pendiente', 'tooth', 'true', '2020-11-01 17:06:17', '0000-00-00 00:00:00');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `roles`
--

CREATE TABLE `roles` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `slug` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `full-access` enum('yes','no') COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `roles`
--

INSERT INTO `roles` (`id`, `name`, `slug`, `description`, `full-access`, `created_at`, `updated_at`) VALUES
(1, 'Admin', 'admin', 'Administrator', 'yes', '2020-06-05 02:33:21', '2020-06-05 02:33:21'),
(2, 'Invitado', 'guest', 'sin ningún tipo de control ni acceso', 'no', '2020-06-05 18:18:00', '2020-07-13 19:56:11');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `role_user`
--

CREATE TABLE `role_user` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `role_id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `role_user`
--

INSERT INTO `role_user` (`id`, `role_id`, `user_id`, `created_at`, `updated_at`) VALUES
(1, 1, 1, '2020-06-05 02:33:21', '2020-06-05 02:33:21'),
(2, 2, 3, '2020-08-08 17:20:00', '2020-08-08 17:20:00');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `users`
--

CREATE TABLE `users` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `password` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `remember_token` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `users`
--

INSERT INTO `users` (`id`, `name`, `email`, `email_verified_at`, `password`, `remember_token`, `created_at`, `updated_at`) VALUES
(1, 'admin', 'admin@admin.com', NULL, '$2y$10$gVcSaZpcdcnVhGGbU3M0rukwY8OD.E9z45m73CQ3wK2Vn9nq/jTBO', 'Z26bR7qVGJbwIEPKQuBYa6xRrHB3xz029dxSRlvjESuWAzrCOql1jn90KtrR', '2020-06-05 02:33:21', '2020-06-05 02:33:21'),
(3, 'lisbeth santmaria', 'lisbethsantamaria2009@hotmail.com', NULL, '$2y$10$lZeb6zWrDWBU/P1AtVfEaOK0ugg2Wf4YO3u8J4xrVM/tqM07ydLzK', NULL, '2020-08-08 17:16:36', '2020-08-08 17:16:36'),
(5, 'Loidy Torrealba', 'test@test.com', NULL, '$2y$10$MKr1A1RHcpZiO5oh8Fek8eu//qvJCxMQu9dtmamiTkPQp1ifmAnZK', NULL, '2020-08-08 21:32:36', '2020-08-09 02:54:42');

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `abonos`
--
ALTER TABLE `abonos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `factura_id` (`factura_id`),
  ADD KEY `paciente_id` (`paciente_id`),
  ADD KEY `methos_pay_id` (`methos_pay_id`);

--
-- Indices de la tabla `citas`
--
ALTER TABLE `citas`
  ADD PRIMARY KEY (`id`),
  ADD KEY `paciente_id` (`paciente_id`);

--
-- Indices de la tabla `citas_medicas`
--
ALTER TABLE `citas_medicas`
  ADD PRIMARY KEY (`id`),
  ADD KEY `paciente_id` (`pacientes_id`);

--
-- Indices de la tabla `citas_medicas_procedure`
--
ALTER TABLE `citas_medicas_procedure`
  ADD PRIMARY KEY (`id`),
  ADD KEY `cita_medica_id` (`citas_medicas_id`) USING BTREE,
  ADD KEY `procedures_id` (`procedure_id`) USING BTREE;

--
-- Indices de la tabla `facturas`
--
ALTER TABLE `facturas`
  ADD PRIMARY KEY (`id`),
  ADD KEY `paciente_id` (`paciente_id`);

--
-- Indices de la tabla `failed_jobs`
--
ALTER TABLE `failed_jobs`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `methos_pay`
--
ALTER TABLE `methos_pay`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `migrations`
--
ALTER TABLE `migrations`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `password_resets`
--
ALTER TABLE `password_resets`
  ADD KEY `password_resets_email_index` (`email`);

--
-- Indices de la tabla `permissions`
--
ALTER TABLE `permissions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `permissions_name_unique` (`name`),
  ADD UNIQUE KEY `permissions_slug_unique` (`slug`);

--
-- Indices de la tabla `permission_role`
--
ALTER TABLE `permission_role`
  ADD PRIMARY KEY (`id`),
  ADD KEY `permission_role_role_id_foreign` (`role_id`),
  ADD KEY `permission_role_permission_id_foreign` (`permission_id`);

--
-- Indices de la tabla `procedures`
--
ALTER TABLE `procedures`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `roles`
--
ALTER TABLE `roles`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `roles_name_unique` (`name`),
  ADD UNIQUE KEY `roles_slug_unique` (`slug`);

--
-- Indices de la tabla `role_user`
--
ALTER TABLE `role_user`
  ADD PRIMARY KEY (`id`),
  ADD KEY `role_user_role_id_foreign` (`role_id`),
  ADD KEY `role_user_user_id_foreign` (`user_id`);

--
-- Indices de la tabla `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `users_email_unique` (`email`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `abonos`
--
ALTER TABLE `abonos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de la tabla `citas`
--
ALTER TABLE `citas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT de la tabla `citas_medicas`
--
ALTER TABLE `citas_medicas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT de la tabla `citas_medicas_procedure`
--
ALTER TABLE `citas_medicas_procedure`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=34;

--
-- AUTO_INCREMENT de la tabla `failed_jobs`
--
ALTER TABLE `failed_jobs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `methos_pay`
--
ALTER TABLE `methos_pay`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de la tabla `migrations`
--
ALTER TABLE `migrations`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `pacientes`
--
ALTER TABLE `pacientes`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `permissions`
--
ALTER TABLE `permissions`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT de la tabla `permission_role`
--
ALTER TABLE `permission_role`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `procedures`
--
ALTER TABLE `procedures`
  MODIFY `id` int(255) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=32;

--
-- AUTO_INCREMENT de la tabla `roles`
--
ALTER TABLE `roles`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de la tabla `role_user`
--
ALTER TABLE `role_user`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de la tabla `users`
--
ALTER TABLE `users`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `abonos`
--
ALTER TABLE `abonos`
  ADD CONSTRAINT `abonos_ibfk_1` FOREIGN KEY (`factura_id`) REFERENCES `facturas` (`id`),
  ADD CONSTRAINT `abonos_ibfk_2` FOREIGN KEY (`paciente_id`) REFERENCES `pacientes` (`id`);

--
-- Filtros para la tabla `citas`
--
ALTER TABLE `citas`
  ADD CONSTRAINT `FK_citas_pacientes` FOREIGN KEY (`paciente_id`) REFERENCES `pacientes` (`id`);

--
-- Filtros para la tabla `citas_medicas`
--
ALTER TABLE `citas_medicas`
  ADD CONSTRAINT `citas_medicas_ibfk_1` FOREIGN KEY (`pacientes_id`) REFERENCES `pacientes` (`id`) ON UPDATE CASCADE;

--
-- Filtros para la tabla `facturas`
--
ALTER TABLE `facturas`
  ADD CONSTRAINT `facturas_ibfk_1` FOREIGN KEY (`paciente_id`) REFERENCES `pacientes` (`id`);

--
-- Filtros para la tabla `permission_role`
--
ALTER TABLE `permission_role`
  ADD CONSTRAINT `permission_role_permission_id_foreign` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `permission_role_role_id_foreign` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `role_user`
--
ALTER TABLE `role_user`
  ADD CONSTRAINT `role_user_role_id_foreign` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `role_user_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;