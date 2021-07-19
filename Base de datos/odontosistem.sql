-- phpMyAdmin SQL Dump
-- version 4.9.1
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 19-07-2021 a las 03:20:51
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
  `nota` varchar(255) DEFAULT NULL,
  `methos_pay_id` int(10) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `adjunto` longtext DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Volcado de datos para la tabla `abonos`
--

INSERT INTO `abonos` (`id`, `paciente_id`, `abonado`, `factura_id`, `referencia`, `nota`, `methos_pay_id`, `created_at`, `updated_at`, `adjunto`) VALUES
(1, 1, 15, 1, '561414', '', 0, '2020-11-16 15:55:52', '2020-11-16 11:55:52', NULL),
(2, 1, 10, 1, '5458', '', 0, '2020-11-16 15:55:52', '2020-11-16 11:55:52', NULL),
(3, 1, 20, 1, '5555555', '', 1, '2020-11-16 15:55:52', '2020-11-16 11:55:52', NULL),
(4, 9, 3, 7, NULL, NULL, NULL, '2021-07-17 23:45:29', '2021-07-18 06:53:19', NULL),
(5, 9, 4, 7, NULL, NULL, NULL, '2021-07-17 23:51:54', '2021-07-18 06:53:19', NULL),
(6, 9, 5, 7, NULL, NULL, NULL, '2021-07-18 00:12:47', '2021-07-18 06:53:19', NULL),
(7, 9, 5, 7, '5555555', NULL, NULL, '2021-07-18 00:24:46', '2021-07-18 07:43:23', NULL),
(8, 9, 5, 7, NULL, NULL, NULL, '2021-07-18 00:27:49', '2021-07-18 06:53:19', NULL),
(17, 9, 14, 7, NULL, NULL, NULL, '2021-07-18 01:27:19', '2021-07-18 07:40:46', NULL),
(18, 9, 1, 7, NULL, NULL, NULL, '2021-07-18 02:09:24', '2021-07-18 06:53:24', NULL),
(20, 9, 2, 7, 'LICORERÍA HERMANOS PEREZ', NULL, 2, '2021-07-18 19:15:57', '2021-07-18 15:15:57', 'firma.jpg'),
(22, 9, 45646, 7, 'rrrrrr', NULL, 3, '2021-07-18 20:51:43', '2021-07-18 16:51:43', 'facturas-adjuntos/TTAqwAGJy9pSlqccKtAPxhLV8h4p3irtX5VGyrTY.png');

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
(6, '2021-07-17', '23:00:00', 9, 0, '2021-07-17 15:19:29', '2020-08-11 00:00:00', 1),
(10, '2021-07-17', '16:04:00', 16, 1, '2020-08-09 17:00:01', '2020-08-09 17:00:01', NULL),
(11, '2021-07-17', '23:08:00', 2, 0, '2020-10-16 17:03:15', '2020-08-09 23:05:10', NULL),
(12, '2021-07-17', '14:29:00', 2, NULL, '2021-07-17 15:28:40', '2020-11-09 22:25:46', 0),
(13, '2021-07-14', '10:59:00', 20, NULL, '2021-07-18 19:55:44', '2021-07-18 19:55:44', NULL);

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
(3, 1, '2020-11-16', 'Evaluacion', 'Medicacion', 'Analissis test', 'comentario test', NULL, '2020-11-17 00:53:40', '2020-11-16 20:53:40'),
(4, 16, '2021-07-17', 'Evaluacion', 'Medicacion', 'Análisis clínico solicitados', 'Comentario (Visible para el paciente)', 'Comentario (Solo visible para el médico)', '2021-07-17 21:56:44', '2021-07-17 17:56:44'),
(5, 16, '2021-07-17', 'Evaluacion', 'Medicacion', 'Análisis clínico solicitados', 'Comentario (Visible para el paciente)', 'Comentario (Solo visible para el médico)', '2021-07-17 21:57:55', '2021-07-17 17:57:55'),
(6, 16, '2021-07-17', 'Evaluacion', 'Medicacion', 'Análisis clínico solicitados', 'Comentario (Visible para el paciente)', 'Comentario (Solo visible para el médico)', '2021-07-17 21:58:45', '2021-07-17 17:58:45'),
(7, 9, '2021-07-17', 'Evaluacion', 'Medicacion', 'Análisis clínico solicitados', NULL, NULL, '2021-07-17 22:05:24', '2021-07-17 18:05:24'),
(8, 9, '2021-07-17', 'Evaluacion', 'Medicacion', 'Análisis clínico solicitados', NULL, NULL, '2021-07-17 22:06:09', '2021-07-17 18:06:09'),
(9, 9, '2021-07-17', 'Evaluacion', 'Medicacion', 'Análisis clínico solicitados', 'Comentario (Visible para el paciente)', 'Comentario (Solo visible para el médico)', '2021-07-17 22:10:13', '2021-07-17 18:10:13'),
(10, 9, '2021-07-17', 'test', NULL, NULL, NULL, NULL, '2021-07-17 22:12:32', '2021-07-17 18:12:32'),
(11, 9, '2021-07-17', 'tes2', NULL, NULL, NULL, NULL, '2021-07-17 22:16:20', '2021-07-17 18:16:20'),
(12, 9, '2021-07-17', '2', NULL, NULL, NULL, NULL, '2021-07-17 22:17:12', '2021-07-17 18:17:12'),
(13, 9, '2021-07-17', '555', NULL, NULL, NULL, NULL, '2021-07-17 22:18:30', '2021-07-17 18:18:30'),
(14, 9, '2021-07-17', '456456456456', NULL, NULL, NULL, NULL, '2021-07-17 22:19:03', '2021-07-17 18:19:03'),
(15, 9, '2021-07-17', '3243243', NULL, NULL, NULL, NULL, '2021-07-17 22:20:20', '2021-07-17 18:20:20'),
(16, 9, '2021-07-17', 'Evaluacion', NULL, NULL, NULL, NULL, '2021-07-17 22:22:31', '2021-07-17 18:22:31');

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
(4, 6, 1, 1, '2020-11-17 00:53:40', '2020-11-16 20:53:40'),
(5, 2, 1, 6, '2021-07-17 21:58:45', '2021-07-17 17:58:45'),
(6, 20, 1, 6, '2021-07-17 21:58:45', '2021-07-17 17:58:45'),
(7, 27, 2, 6, '2021-07-17 21:58:45', '2021-07-17 17:58:45'),
(8, 3, 1, 7, '2021-07-17 22:05:24', '2021-07-17 18:05:24'),
(9, 20, 2, 7, '2021-07-17 22:05:24', '2021-07-17 18:05:24'),
(10, 3, 1, 8, '2021-07-17 22:06:09', '2021-07-17 18:06:09'),
(11, 20, 2, 8, '2021-07-17 22:06:09', '2021-07-17 18:06:09'),
(12, 18, 1, 9, '2021-07-17 22:10:13', '2021-07-17 18:10:13'),
(13, 27, 1, 9, '2021-07-17 22:10:13', '2021-07-17 18:10:13'),
(14, 18, 2, 10, '2021-07-17 22:12:32', '2021-07-17 18:12:32'),
(15, 28, 1, 10, '2021-07-17 22:12:32', '2021-07-17 18:12:32'),
(16, 4, 1, 11, '2021-07-17 22:16:20', '2021-07-17 18:16:20'),
(17, 29, 1, 11, '2021-07-17 22:16:20', '2021-07-17 18:16:20'),
(18, 27, 1, 11, '2021-07-17 22:16:20', '2021-07-17 18:16:20'),
(19, 26, 3, 12, '2021-07-17 22:17:12', '2021-07-17 18:17:12'),
(20, 26, 4, 13, '2021-07-17 22:18:30', '2021-07-17 18:18:30'),
(21, 3, 1, 14, '2021-07-17 22:19:03', '2021-07-17 18:19:03'),
(22, 26, 4, 14, '2021-07-17 22:19:03', '2021-07-17 18:19:03'),
(23, 26, 6, 15, '2021-07-17 22:20:20', '2021-07-17 18:20:20'),
(24, 28, 1213, 15, '2021-07-17 22:20:20', '2021-07-17 18:20:20'),
(25, 26, 1, 16, '2021-07-17 22:22:31', '2021-07-17 18:22:31');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `facturas`
--

CREATE TABLE `facturas` (
  `id` int(11) NOT NULL,
  `cita_medica_id` bigint(20) UNSIGNED NOT NULL,
  `abono_creacion` float NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Volcado de datos para la tabla `facturas`
--

INSERT INTO `facturas` (`id`, `cita_medica_id`, `abono_creacion`, `created_at`, `updated_at`) VALUES
(1, 1, 15002300, '2020-11-16 15:55:05', '2021-07-17 20:18:36'),
(13, 7, 34532, '2021-07-18 02:09:24', '2021-07-17 22:09:24');

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
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `second_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `last_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `otros` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `second_last_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `dni` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `birth_date` date NOT NULL,
  `address` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `phone` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `sex` tinyint(1) DEFAULT NULL,
  `height` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `weight` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `antecedentes` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL,
  `medicamentos` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `habitos` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL,
  `alergias` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL,
  `user_id` bigint(20) DEFAULT NULL,
  `registered_by` bigint(20) NOT NULL,
  `medical_history` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL,
  `procedures` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL,
  `coagulacion` varchar(255) DEFAULT NULL,
  `embarazo` tinyint(1) DEFAULT NULL,
  `anestesicos` tinyint(1) DEFAULT NULL,
  `motivoConsulta` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `odontogramaComentario` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Volcado de datos para la tabla `pacientes`
--

INSERT INTO `pacientes` (`id`, `name`, `second_name`, `last_name`, `otros`, `second_last_name`, `dni`, `email`, `birth_date`, `address`, `phone`, `sex`, `height`, `weight`, `antecedentes`, `medicamentos`, `habitos`, `alergias`, `user_id`, `registered_by`, `medical_history`, `procedures`, `coagulacion`, `embarazo`, `anestesicos`, `motivoConsulta`, `created_at`, `updated_at`, `odontogramaComentario`) VALUES
(1, 'angelo', 'Rafael', 'Amaro', NULL, 'Trujillo', '252525', NULL, '2020-06-04', 'marhuanta', '11111111111111', 1, NULL, NULL, 'null', '', 'null', 'null', NULL, 1, NULL, NULL, '0', 0, 0, '', '2021-01-25 01:44:23', '2020-06-07 20:54:56', ''),
(2, 'Samuel', 'emmanuel', 'Trias', NULL, 'Santamaria', '24186725', NULL, '1994-12-10', 'San José', '04163891799', 1, NULL, NULL, 'null', '', 'null', 'null', 1, 1, NULL, NULL, '0', 0, 0, '', '2020-06-05 16:35:45', '2020-08-10 03:35:35', ''),
(8, 'Saul', NULL, 'Yanave', NULL, 'Guilarte', '25914064', NULL, '1997-10-31', 'Marhuanta', '04163891799', 1, NULL, NULL, 'null', '', 'null', 'null', NULL, 1, NULL, '[{\"tooth\":41,\"pro\":66,\"title\":\"Sellante Bueno\",\"side\":\"center\"},{\"tooth\":31,\"pro\":80,\"title\":\"Pulido realizado\",\"side\":false}]', '0', 0, 0, '', '2020-06-06 04:19:54', '2021-01-24 21:48:25', NULL),
(9, 'ivan', NULL, 'ascanio', NULL, NULL, '25252525', 'ivan@test.com', '1995-07-01', 'Su casa', '21', 1, NULL, NULL, 'null', '', 'null', 'null', NULL, 1, NULL, NULL, '0', 0, 0, '', '2020-07-27 09:00:00', '2020-08-09 15:02:29', ''),
(16, 'Loidy', 'Andreina', 'Torrealba', 'otro', NULL, '25914065', 'test@test.com', '2020-08-11', 'Desconocida', '+58564785', 0, '1.65', '80', 'antecedentes medicos', 'medicamentos', 'habitos', 'alergias', 5, 1, 'medical_history', NULL, '0', 0, 0, 'primer motivo', '2020-08-10 01:59:49', '2020-11-09 03:56:23', ''),
(17, 'Edian', NULL, 'Godoy', NULL, NULL, '0000000', 'radicador@stork.com', '2000-12-15', 'San José', '04163891799', 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1, NULL, NULL, '0', 0, 0, '', '2020-12-03 20:05:38', '2020-12-03 15:42:04', ''),
(18, 'angela', 'kariana', 'rivero', NULL, NULL, '123123123123', 'samueltrias16@gmail.com', '2018-01-16', 'Dirección', '04163891799', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1, NULL, '[{\"tooth\":43,\"pro\":114,\"title\":\"Resina preventiva en mal estado\",\"side\":false},{\"tooth\":72,\"pro\":118,\"title\":\"Restauración cervical en mal estado\",\"side\":\"top\"},{\"tooth\":71,\"pro\":66,\"title\":\"Sellante Bueno\",\"side\":\"center\"},{\"tooth\":44,\"pro\":66,\"title\":\"Sellante Bueno\",\"side\":\"top\"}]', NULL, NULL, NULL, NULL, '2021-07-14 01:55:08', '2021-07-14 00:29:39', NULL),
(20, 'Angela', NULL, 'Rivero', NULL, NULL, '5461564', NULL, '2021-07-30', 'San José', '+584163891799', 0, NULL, NULL, NULL, NULL, NULL, NULL, 4, 4, NULL, NULL, NULL, NULL, NULL, NULL, '2021-07-18 23:54:47', '2021-07-18 19:54:47', NULL);

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
(3, '51', 'Movilidad grado III', '', 20, 'pro_mobility_iii', 'Pendiente', 'tooth', 'false', '2021-07-18 05:41:13', '0000-00-00 00:00:00'),
(4, '97', 'Angulo distal o mesial pendiente', '', 20, 'pro_angle', 'Pendiente', 'side', 'true', '2021-07-18 05:41:13', '0000-00-00 00:00:00'),
(5, '65', 'Angulo distal o mesial en buen estado', '', 20, 'pro_angle_done', 'Completado', 'side', 'true', '2021-07-18 05:41:13', '0000-00-00 00:00:00'),
(6, '98', 'Sellante en mal estado', '', 20, 'pro_sealing', 'Pendiente', 'side', 'true', '2021-07-18 05:41:13', '0000-00-00 00:00:00'),
(7, '66', 'Sellante Bueno', '', 20, 'pro_sealing_done', 'Completado', 'side', 'true', '2021-07-18 05:41:13', '0000-00-00 00:00:00'),
(8, '99', 'Superficie Cariada', '', 20, 'pro_caries', 'Pendiente', 'side', 'true', '2021-07-18 05:41:13', '0000-00-00 00:00:00'),
(9, '67', 'Tratamiento realizado', '', 20, 'pro_caries_done', 'Completado', 'side', 'true', '2021-07-18 05:41:13', '0000-00-00 00:00:00'),
(10, '100', 'Endodoncia por realizar', '', 20, 'pro_endodontics', 'Pendiente', 'tooth', 'true', '2021-07-18 05:41:13', '0000-00-00 00:00:00'),
(11, '68', 'Endodoncia Realizada', '', 20, 'pro_endodontics_done', 'Completado', 'tooth', 'true', '2021-07-18 05:41:13', '0000-00-00 00:00:00'),
(12, '101', 'Exodoncia', '', 20, 'pro_exodontics', 'Pendiente', 'tooth', 'true', '2021-07-18 05:41:13', '0000-00-00 00:00:00'),
(13, '69', 'Exodoncia realizada', '', 20, 'pro_exodontics_done', 'Completado', 'tooth', 'true', '2021-07-18 05:41:13', '0000-00-00 00:00:00'),
(14, '102', 'Línea de fractura', '', 20, 'pro_fracture', 'Pendiente', 'side', 'true', '2021-07-18 05:41:13', '0000-00-00 00:00:00'),
(15, '77', 'Diente sin erupcionar', '', 20, 'pro_unbroken', 'Completado', 'tooth', 'true', '2021-07-18 05:41:13', '0000-00-00 00:00:00'),
(16, '111', 'Corona', '', 20, 'pro_crown', 'Pendiente', 'tooth', 'false', '2021-07-18 05:41:13', '0000-00-00 00:00:00'),
(17, '79', 'Corona Buena', '', 20, 'pro_crown_done', 'Completado', 'tooth', 'false', '2021-07-18 05:41:13', '0000-00-00 00:00:00'),
(18, '112', 'Pulido pendiente', '', 20, 'pro_polish', 'Pendiente', 'tooth', 'true', '2021-07-18 05:41:13', '0000-00-00 00:00:00'),
(19, '80', 'Pulido realizado', '', 20, 'pro_polish_done', 'Completado', 'tooth', 'true', '2021-07-18 05:41:13', '0000-00-00 00:00:00'),
(20, '113', 'Exodoncia quirúrgica', '', 20, 'pro_exodontics_surgical', 'Pendiente', 'tooth', 'true', '2021-07-18 05:41:13', '0000-00-00 00:00:00'),
(21, '114', 'Resina preventiva en mal estado', '', 20, 'pro_resin', 'Pendiente', 'tooth', 'false', '2021-07-18 05:41:13', '0000-00-00 00:00:00'),
(22, '82', 'Resina preventiva buena', '', 20, 'pro_resin_done', 'Completado', 'tooth', 'false', '2021-07-18 05:41:13', '0000-00-00 00:00:00'),
(23, '115', 'Sellante en mal estado', '', 20, 'pro_filling', 'Pendiente', 'tooth', 'false', '2021-07-18 05:41:13', '0000-00-00 00:00:00'),
(24, '83', 'Sellante Bueno', '', 20, 'pro_filling_done', 'Completado', 'tooth', 'false', '2021-07-18 05:41:13', '0000-00-00 00:00:00'),
(25, '118', 'Restauración cervical en mal estado', '', 20, 'pro_restoration', 'Pendiente', 'side', 'true', '2021-07-18 05:41:13', '0000-00-00 00:00:00'),
(26, '86', 'Restauración cervical buena', '', 20, 'pro_restoration_done', 'Completado', 'side', 'true', '2021-07-18 05:41:13', '0000-00-00 00:00:00'),
(27, '120', 'Radiografía pendiente', '', 20, 'pro_rx', 'Pendiente', 'tooth', 'true', '2021-07-18 05:41:13', '0000-00-00 00:00:00'),
(28, '88', 'Radiografía realizada', '', 20, 'pro_rx_done', 'Completado', 'tooth', 'true', '2021-07-18 05:41:13', '0000-00-00 00:00:00'),
(29, '90', 'Diente extraído', '', 20, 'pro_missing', 'Pendiente', 'tooth', 'true', '2021-07-18 05:41:13', '0000-00-00 00:00:00');

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
(1, 'admin', 'admin@admin.com', NULL, '$2y$10$gVcSaZpcdcnVhGGbU3M0rukwY8OD.E9z45m73CQ3wK2Vn9nq/jTBO', '7Qs6x48mkXpVuzR8IMDatOgpWnndn65y24YFjtTem8x0JXrzEFTKmn5B5X0C', '2020-06-05 02:33:21', '2020-06-05 02:33:21'),
(3, 'lisbeth santmaria', 'lisbethsantamaria2009@hotmail.com', NULL, '$2y$10$lZeb6zWrDWBU/P1AtVfEaOK0ugg2Wf4YO3u8J4xrVM/tqM07ydLzK', NULL, '2020-08-08 17:16:36', '2020-08-08 17:16:36'),
(4, 'angela', 'kariana_linda2014@outolook.com', NULL, '$2y$10$DIJdrIpNiAo.s2Xee5Wli.EALU.Kg.xo3WCXk0ASEIOQivstnxivm', NULL, '2021-07-18 23:27:30', '2021-07-18 23:27:30');

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
  ADD KEY `paciente_id` (`cita_medica_id`);

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
-- Indices de la tabla `pacientes`
--
ALTER TABLE `pacientes`
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
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `abonos`
--
ALTER TABLE `abonos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23;

--
-- AUTO_INCREMENT de la tabla `citas`
--
ALTER TABLE `citas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT de la tabla `citas_medicas`
--
ALTER TABLE `citas_medicas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT de la tabla `citas_medicas_procedure`
--
ALTER TABLE `citas_medicas_procedure`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=26;

--
-- AUTO_INCREMENT de la tabla `facturas`
--
ALTER TABLE `facturas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT de la tabla `pacientes`
--
ALTER TABLE `pacientes`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT de la tabla `users`
--
ALTER TABLE `users`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
