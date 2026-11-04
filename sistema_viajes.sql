-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 01-11-2026 a las 22:07:39
-- Versión del servidor: 10.4.32-MariaDB
-- Versión de PHP: 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de datos: `sistema_viajes3`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `actividades`
--

CREATE TABLE `actividades` (
  `ID_Actividad` int(11) NOT NULL,
  `Nombre` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `actividades`
--

INSERT INTO `actividades` (`ID_Actividad`, `Nombre`) VALUES
(1, 'Excursión'),
(2, 'Senderismo'),
(3, 'Visita guiada'),
(4, 'Paseo en barco'),
(5, 'Turismo gastronómico');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `asiento`
--

CREATE TABLE `asiento` (
  `ID_Asiento` int(11) NOT NULL,
  `FK_ID_Vehiculo` int(11) NOT NULL,
  `NroAsiento` varchar(10) NOT NULL,
  `Disponible` tinyint(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `asiento`
--

INSERT INTO `asiento` (`ID_Asiento`, `FK_ID_Vehiculo`, `NroAsiento`, `Disponible`) VALUES
(1, 1, '01', 0),
(2, 2, '01', 0),
(3, 3, '01', 0),
(4, 4, '01', 0),
(5, 5, '01', 0);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `auditoria`
--

CREATE TABLE `auditoria` (
  `ID_Auditoria` bigint(20) NOT NULL,
  `FK_ID_PasajeroOnline` int(11) DEFAULT NULL,
  `TablaAfectada` varchar(100) NOT NULL,
  `Operacion` enum('INSERT','UPDATE','DELETE','LOGIN','LOGOUT') NOT NULL,
  `IDRegistro` varchar(50) DEFAULT NULL,
  `Fecha` datetime NOT NULL DEFAULT current_timestamp(),
  `Detalle` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `auditoria`
--

INSERT INTO `auditoria` (`ID_Auditoria`, `FK_ID_PasajeroOnline`, `TablaAfectada`, `Operacion`, `IDRegistro`, `Fecha`, `Detalle`) VALUES
(1, 6, 'pasajeroonline', 'LOGIN', '6', '2026-10-30 09:00:00', 'Inicio de sesion'),
(2, 7, 'reservas', 'INSERT', '1', '2026-10-30 10:00:00', 'Creacion de reserva'),
(3, 8, 'clientes', 'UPDATE', '3', '2026-10-31 12:00:00', 'Actualizacion de datos'),
(4, 6, 'pagos_reserva', 'INSERT', '1', '2026-11-01 13:00:00', 'Registro de pago'),
(5, 6, 'reservas', 'UPDATE', '1', '2026-11-01 14:00:00', 'Cambio de estado');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `banners`
--

CREATE TABLE `banners` (
  `ID_Banner` int(11) NOT NULL,
  `Titulo` varchar(150) NOT NULL,
  `Texto` text DEFAULT NULL,
  `Imagen` varchar(255) DEFAULT NULL,
  `Link` varchar(255) DEFAULT NULL,
  `Orden` int(11) NOT NULL DEFAULT 1,
  `Activo` tinyint(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `banners`
--

INSERT INTO `banners` (`ID_Banner`, `Titulo`, `Texto`, `Imagen`, `Link`, `Orden`, `Activo`) VALUES
(1, 'Descubrí las Cataratas', 'Viví una experiencia inolvidable.', 'banner_cataratas.jpg', '/paquetes.php?id=1', 1, 1),
(2, 'Bariloche', 'Nieve, montaña y aventura.', 'banner_bariloche.jpg', '/paquetes.php?id=2', 2, 1),
(3, 'Salta', 'Cultura y gastronomía.', 'banner_salta.jpg', '/paquetes.php?id=3', 3, 1),
(4, 'Mendoza', 'Experiencias premium.', 'banner_mendoza.jpg', '/paquetes.php?id=4', 4, 1),
(5, 'Promociones', 'Ofertas por tiempo limitado.', 'banner_ofertas.jpg', '/ofertas.php', 5, 1);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `cancelaciones`
--

CREATE TABLE `cancelaciones` (
  `ID_Cancelacion` int(11) NOT NULL,
  `FK_ID_Reserva` int(11) NOT NULL,
  `FK_ID_MotivoCancelacion` int(11) NOT NULL,
  `Fecha` datetime NOT NULL DEFAULT current_timestamp(),
  `ImporteDevuelto` decimal(12,2) NOT NULL DEFAULT 0.00,
  `Observaciones` varchar(300) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `cancelaciones`
--

INSERT INTO `cancelaciones` (`ID_Cancelacion`, `FK_ID_Reserva`, `FK_ID_MotivoCancelacion`, `Fecha`, `ImporteDevuelto`, `Observaciones`) VALUES
(1, 5, 2, '2026-10-10 15:00:00', 0.00, 'Cancelacion solicitada por el cliente');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `categorias_destino`
--

CREATE TABLE `categorias_destino` (
  `ID_CategoriaDestino` int(11) NOT NULL,
  `Nombre` varchar(80) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `categorias_destino`
--

INSERT INTO `categorias_destino` (`ID_CategoriaDestino`, `Nombre`) VALUES
(3, 'Aventura'),
(2, 'Cultura'),
(5, 'Gastronomía'),
(1, 'Naturaleza'),
(4, 'Playa');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `categorias_paquete`
--

CREATE TABLE `categorias_paquete` (
  `ID_CategoriaPaquete` int(11) NOT NULL,
  `Nombre` varchar(80) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `categorias_paquete`
--

INSERT INTO `categorias_paquete` (`ID_CategoriaPaquete`, `Nombre`) VALUES
(2, 'Aventura'),
(4, 'Cultural'),
(1, 'Escapada'),
(3, 'Familiar'),
(5, 'Premium');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `choferes`
--

CREATE TABLE `choferes` (
  `ID_Chofer` int(11) NOT NULL,
  `Nombre` varchar(60) NOT NULL,
  `Apellido` varchar(60) NOT NULL,
  `DNI` varchar(20) NOT NULL,
  `Telefono` varchar(20) DEFAULT NULL,
  `Email` varchar(100) DEFAULT NULL,
  `FechaNacimiento` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `choferes`
--

INSERT INTO `choferes` (`ID_Chofer`, `Nombre`, `Apellido`, `DNI`, `Telefono`, `Email`, `FechaNacimiento`) VALUES
(1, 'Carlos', 'Gómez', '30111222', '3704123456', 'carlos.gomez@email.com', '1980-05-12'),
(2, 'Miguel', 'Fernández', '28444555', '3704234567', 'miguel.fernandez@email.com', '1978-08-20'),
(3, 'Jorge', 'Ramírez', '32666777', '3704345678', 'jorge.ramirez@email.com', '1985-03-15'),
(4, 'Pablo', 'Martínez', '35777888', '3704456789', 'pablo.martinez@email.com', '1988-11-02'),
(5, 'Roberto', 'Benítez', '26333999', '3704567890', 'roberto.benitez@email.com', '1975-07-25');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `clases`
--

CREATE TABLE `clases` (
  `ID_Clase` int(11) NOT NULL,
  `Clase` varchar(60) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `clases`
--

INSERT INTO `clases` (`ID_Clase`, `Clase`) VALUES
(1, 'Económica'),
(2, 'Ejecutiva'),
(3, 'Turista'),
(4, 'Premium'),
(5, 'Primera clase');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `clientes`
--

CREATE TABLE `clientes` (
  `ID_Cliente` int(11) NOT NULL,
  `FK_ID_PasajeroOnline` int(11) DEFAULT NULL,
  `CodigoCliente` varchar(30) NOT NULL,
  `FechaAlta` datetime NOT NULL DEFAULT current_timestamp(),
  `Estado` enum('activo','inactivo') NOT NULL DEFAULT 'activo',
  `Observaciones` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `clientes`
--

INSERT INTO `clientes` (`ID_Cliente`, `FK_ID_PasajeroOnline`, `CodigoCliente`, `FechaAlta`, `Estado`, `Observaciones`) VALUES
(1, 1, 'CLI-0001', '2026-07-01 10:30:00', 'activo', 'Cliente frecuente'),
(2, 2, 'CLI-0002', '2026-07-03 14:20:00', 'activo', 'Prefiere viajes familiares'),
(3, 3, 'CLI-0003', '2026-07-05 09:10:00', 'activo', 'Interesado en turismo aventura'),
(4, 4, 'CLI-0004', '2026-07-08 16:45:00', 'activo', 'Solicita asistencia especial'),
(5, 5, 'CLI-0005', '2026-07-10 11:00:00', 'inactivo', 'Cuenta inactiva');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `cliente_direccion`
--

CREATE TABLE `cliente_direccion` (
  `ID_ClienteDireccion` int(11) NOT NULL,
  `FK_ID_Cliente` int(11) NOT NULL,
  `FK_ID_Direccion` int(11) NOT NULL,
  `Tipo` enum('domicilio','trabajo','otro') NOT NULL DEFAULT 'domicilio',
  `Principal` tinyint(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `cliente_direccion`
--

INSERT INTO `cliente_direccion` (`ID_ClienteDireccion`, `FK_ID_Cliente`, `FK_ID_Direccion`, `Tipo`, `Principal`) VALUES
(1, 1, 1, 'domicilio', 1),
(2, 2, 2, 'domicilio', 1),
(3, 3, 3, 'domicilio', 1),
(4, 4, 4, 'domicilio', 1),
(5, 5, 5, 'trabajo', 1);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `codigos_verificacion`
--

CREATE TABLE `codigos_verificacion` (
  `ID_Codigo` int(11) NOT NULL,
  `FK_ID_PasajeroOnline` int(11) NOT NULL,
  `Codigo` varchar(6) NOT NULL,
  `FechaCreacion` datetime NOT NULL DEFAULT current_timestamp(),
  `FechaExpiracion` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `contactos_emergencia`
--

CREATE TABLE `contactos_emergencia` (
  `ID_ContactoEmergencia` int(11) NOT NULL,
  `FK_ID_Cliente` int(11) NOT NULL,
  `Nombre` varchar(100) NOT NULL,
  `Telefono` varchar(30) NOT NULL,
  `Relacion` varchar(60) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `contactos_emergencia`
--

INSERT INTO `contactos_emergencia` (`ID_ContactoEmergencia`, `FK_ID_Cliente`, `Nombre`, `Telefono`, `Relacion`) VALUES
(1, 1, 'Ana Pérez', '3704110001', 'Madre'),
(2, 2, 'Carlos García', '3704220002', 'Padre'),
(3, 3, 'Laura López', '3704330003', 'Hermana'),
(4, 4, 'Marcos Romero', '3704440004', 'Pareja'),
(5, 5, 'Sergio Díaz', '3704550005', 'Hermano');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `cuentas_corrientes`
--

CREATE TABLE `cuentas_corrientes` (
  `ID_CuentaCorriente` int(11) NOT NULL,
  `FK_ID_Cliente` int(11) NOT NULL,
  `Saldo` decimal(12,2) NOT NULL DEFAULT 0.00,
  `FechaActualizacion` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `cuentas_corrientes`
--

INSERT INTO `cuentas_corrientes` (`ID_CuentaCorriente`, `FK_ID_Cliente`, `Saldo`, `FechaActualizacion`) VALUES
(1, 1, 0.00, '2026-10-02 11:00:00'),
(2, 2, 320000.00, '2026-10-02 12:00:00'),
(3, 3, 390000.00, '2026-10-03 12:00:00'),
(4, 4, 0.00, '2026-10-04 14:00:00'),
(5, 5, 160000.00, '2026-10-05 15:00:00');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `cupones`
--

CREATE TABLE `cupones` (
  `ID_Cupon` int(11) NOT NULL,
  `FK_ID_Promocion` int(11) NOT NULL,
  `Codigo` varchar(40) NOT NULL,
  `UsosMaximos` int(11) DEFAULT NULL,
  `UsosActuales` int(11) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `cupones`
--

INSERT INTO `cupones` (`ID_Cupon`, `FK_ID_Promocion`, `Codigo`, `UsosMaximos`, `UsosActuales`) VALUES
(1, 1, 'CATARATAS10', 100, 12),
(2, 2, 'BLACKTRAVEL50', 50, 8),
(3, 3, 'FAMILIA15', 100, 25),
(4, 4, 'WINE10', 40, 5),
(5, 5, 'FINDEANO8', 100, 0);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `cupon_uso`
--

CREATE TABLE `cupon_uso` (
  `ID_CuponUso` int(11) NOT NULL,
  `FK_ID_Cupon` int(11) NOT NULL,
  `FK_ID_Cliente` int(11) NOT NULL,
  `FK_ID_Reserva` int(11) NOT NULL,
  `FechaUso` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `cupon_uso`
--

INSERT INTO `cupon_uso` (`ID_CuponUso`, `FK_ID_Cupon`, `FK_ID_Cliente`, `FK_ID_Reserva`, `FechaUso`) VALUES
(1, 1, 1, 1, '2026-10-01 10:00:00'),
(2, 2, 2, 2, '2026-10-02 11:00:00'),
(3, 3, 3, 3, '2026-10-03 12:00:00'),
(4, 4, 4, 4, '2026-10-04 13:00:00'),
(5, 5, 5, 5, '2026-10-05 14:00:00');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `cupos_salida`
--

CREATE TABLE `cupos_salida` (
  `ID_Cupo` int(11) NOT NULL,
  `FK_ID_Salida` int(11) NOT NULL,
  `TipoCupo` varchar(60) NOT NULL,
  `Cantidad` int(11) NOT NULL,
  `PrecioUnitario` decimal(12,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `cupos_salida`
--

INSERT INTO `cupos_salida` (`ID_Cupo`, `FK_ID_Salida`, `TipoCupo`, `Cantidad`, `PrecioUnitario`) VALUES
(1, 1, 'Adulto', 15, 285000.00),
(2, 1, 'Menor', 5, 210000.00),
(3, 2, 'Adulto', 18, 520000.00),
(4, 3, 'Adulto', 15, 390000.00),
(5, 4, 'Adulto', 12, 610000.00);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `destino`
--

CREATE TABLE `destino` (
  `ID_Destino` int(11) NOT NULL,
  `NombreUbicacion` varchar(100) NOT NULL,
  `Link` varchar(200) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `destino`
--

INSERT INTO `destino` (`ID_Destino`, `NombreUbicacion`, `Link`) VALUES
(1, 'Formosa, Formosa', 'https://maps.google.com/?q=Formosa+Argentina'),
(2, 'Puerto Iguazú, Misiones', 'https://maps.google.com/?q=Puerto+Iguazu'),
(3, 'Bariloche, Río Negro', 'https://maps.google.com/?q=Bariloche'),
(4, 'Salta, Salta', 'https://maps.google.com/?q=Salta+Argentina'),
(5, 'Mendoza, Mendoza', 'https://maps.google.com/?q=Mendoza+Argentina');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `destino_categoria`
--

CREATE TABLE `destino_categoria` (
  `ID_DestinoCategoria` int(11) NOT NULL,
  `FK_ID_Destino` int(11) NOT NULL,
  `FK_ID_CategoriaDestino` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `destino_categoria`
--

INSERT INTO `destino_categoria` (`ID_DestinoCategoria`, `FK_ID_Destino`, `FK_ID_CategoriaDestino`) VALUES
(1, 1, 2),
(2, 2, 1),
(3, 3, 1),
(4, 4, 2),
(5, 5, 5);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `destino_informacion`
--

CREATE TABLE `destino_informacion` (
  `ID_InformacionDestino` int(11) NOT NULL,
  `FK_ID_Destino` int(11) NOT NULL,
  `Descripcion` text NOT NULL,
  `Clima` varchar(120) DEFAULT NULL,
  `Moneda` varchar(80) DEFAULT NULL,
  `Recomendaciones` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `destino_informacion`
--

INSERT INTO `destino_informacion` (`ID_InformacionDestino`, `FK_ID_Destino`, `Descripcion`, `Clima`, `Moneda`, `Recomendaciones`) VALUES
(1, 1, 'Capital provincial y puerta de entrada al litoral.', 'Cálido', 'Peso argentino', 'Llevar protector solar'),
(2, 2, 'Destino de las Cataratas del Iguazú.', 'Cálido y húmedo', 'Peso argentino', 'Usar calzado cómodo'),
(3, 3, 'Ciudad turística de la Patagonia.', 'Frío', 'Peso argentino', 'Llevar abrigo'),
(4, 4, 'Destino cultural y gastronómico del norte argentino.', 'Seco y templado', 'Peso argentino', 'Visitar el casco histórico'),
(5, 5, 'Destino de bodegas y montaña.', 'Seco', 'Peso argentino', 'Reservar excursiones con anticipación');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `detalle_evento`
--

CREATE TABLE `detalle_evento` (
  `ID_DetalleEvento` int(11) NOT NULL,
  `FK_ID_Viaje` int(11) NOT NULL,
  `FK_ID_Evento` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `detalle_evento`
--

INSERT INTO `detalle_evento` (`ID_DetalleEvento`, `FK_ID_Viaje`, `FK_ID_Evento`) VALUES
(1, 1, 1),
(2, 2, 4),
(3, 3, 2),
(4, 4, 5),
(5, 5, 3);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `direcciones`
--

CREATE TABLE `direcciones` (
  `ID_Direccion` int(11) NOT NULL,
  `Calle` varchar(120) DEFAULT NULL,
  `Numero` varchar(20) DEFAULT NULL,
  `Localidad` varchar(100) DEFAULT NULL,
  `Provincia` varchar(100) DEFAULT NULL,
  `Pais` varchar(100) DEFAULT 'Argentina',
  `CodigoPostal` varchar(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `direcciones`
--

INSERT INTO `direcciones` (`ID_Direccion`, `Calle`, `Numero`, `Localidad`, `Provincia`, `Pais`, `CodigoPostal`) VALUES
(1, 'Av. 25 de Mayo', '100', 'Formosa', 'Formosa', 'Argentina', '3600'),
(2, 'Av. Italia', '450', 'Formosa', 'Formosa', 'Argentina', '3600'),
(3, 'Av. Sarmiento', '800', 'Resistencia', 'Chaco', 'Argentina', '3500'),
(4, 'Calle San Martin', '120', 'Posadas', 'Misiones', 'Argentina', '3300'),
(5, 'Av. Belgrano', '900', 'Salta', 'Salta', 'Argentina', '4400');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `dispositivos`
--

CREATE TABLE `dispositivos` (
  `ID_Dispositivo` int(11) NOT NULL,
  `FK_ID_PasajeroOnline` int(11) NOT NULL,
  `Plataforma` enum('Android','iOS','Web') NOT NULL,
  `Modelo` varchar(100) DEFAULT NULL,
  `VersionApp` varchar(30) DEFAULT NULL,
  `UltimoAcceso` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `dispositivos`
--

INSERT INTO `dispositivos` (`ID_Dispositivo`, `FK_ID_PasajeroOnline`, `Plataforma`, `Modelo`, `VersionApp`, `UltimoAcceso`) VALUES
(1, 1, 'Android', 'Samsung A54', '1.0.0', '2026-10-30 10:00:00'),
(2, 2, 'iOS', 'iPhone 14', '1.0.0', '2026-10-30 11:00:00'),
(3, 3, 'Android', 'Motorola G84', '1.0.0', '2026-10-31 12:00:00'),
(4, 4, 'iOS', 'iPhone 13', '1.0.1', '2026-11-01 13:00:00'),
(5, 5, 'Web', 'Chrome Desktop', '1.0.1', '2026-10-29 14:00:00');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `documentos_cliente`
--

CREATE TABLE `documentos_cliente` (
  `ID_DocumentoCliente` int(11) NOT NULL,
  `FK_ID_Cliente` int(11) NOT NULL,
  `FK_ID_TipoDocumento` int(11) NOT NULL,
  `Numero` varchar(50) NOT NULL,
  `FechaEmision` date DEFAULT NULL,
  `FechaVencimiento` date DEFAULT NULL,
  `PaisEmision` varchar(80) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `documentos_cliente`
--

INSERT INTO `documentos_cliente` (`ID_DocumentoCliente`, `FK_ID_Cliente`, `FK_ID_TipoDocumento`, `Numero`, `FechaEmision`, `FechaVencimiento`, `PaisEmision`) VALUES
(1, 1, 1, '40111222', '2015-03-10', '2030-03-10', 'Argentina'),
(2, 2, 1, '41222333', '2016-04-20', '2031-04-20', 'Argentina'),
(3, 3, 1, '39333444', '2014-01-15', '2029-01-15', 'Argentina'),
(4, 4, 1, '42555666', '2017-12-08', '2032-12-08', 'Argentina'),
(5, 5, 2, 'AA123456', '2022-06-30', '2032-06-30', 'Argentina');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `empleados`
--

CREATE TABLE `empleados` (
  `ID_Empleado` int(11) NOT NULL,
  `FK_ID_PasajeroOnline` int(11) DEFAULT NULL,
  `Nombre` varchar(60) NOT NULL,
  `Apellido` varchar(60) NOT NULL,
  `DNI` varchar(20) NOT NULL,
  `Email` varchar(100) DEFAULT NULL,
  `Telefono` varchar(30) DEFAULT NULL,
  `Cargo` varchar(80) NOT NULL,
  `FechaIngreso` date DEFAULT NULL,
  `Estado` enum('activo','inactivo') NOT NULL DEFAULT 'activo'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `empleados`
--

INSERT INTO `empleados` (`ID_Empleado`, `FK_ID_PasajeroOnline`, `Nombre`, `Apellido`, `DNI`, `Email`, `Telefono`, `Cargo`, `FechaIngreso`, `Estado`) VALUES
(1, 6, 'Matias', 'Baez', '30100111', 'admin@agencia.com', '3704000001', 'Administrador', '2025-03-01', 'activo'),
(2, 7, 'Lucia', 'Gomez', '30200222', 'lucia@agencia.com', '3704000002', 'Agente', '2025-04-01', 'activo'),
(3, 8, 'Martin', 'Perez', '30300333', 'martin@agencia.com', '3704000003', 'Gerente', '2024-02-10', 'activo'),
(4, NULL, 'Carla', 'Sosa', '30400444', 'carla@agencia.com', '3704000004', 'Agente', '2025-06-15', 'activo'),
(5, NULL, 'Diego', 'Ramos', '30500555', 'diego@agencia.com', '3704000005', 'Contador', '2024-08-20', 'activo');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `empleado_sucursal`
--

CREATE TABLE `empleado_sucursal` (
  `ID_EmpleadoSucursal` int(11) NOT NULL,
  `FK_ID_Empleado` int(11) NOT NULL,
  `FK_ID_Establecimiento` int(11) NOT NULL,
  `FechaDesde` date NOT NULL,
  `Principal` tinyint(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `empleado_sucursal`
--

INSERT INTO `empleado_sucursal` (`ID_EmpleadoSucursal`, `FK_ID_Empleado`, `FK_ID_Establecimiento`, `FechaDesde`, `Principal`) VALUES
(1, 1, 1, '2025-03-01', 1),
(2, 2, 1, '2025-04-01', 1),
(3, 3, 1, '2024-02-10', 1),
(4, 4, 2, '2025-06-15', 1),
(5, 5, 1, '2024-08-20', 1);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `establecimiento`
--

CREATE TABLE `establecimiento` (
  `ID_Establecimiento` int(11) NOT NULL,
  `Nombre` varchar(100) NOT NULL,
  `Localidad` varchar(100) DEFAULT NULL,
  `Ubicacion` varchar(200) DEFAULT NULL,
  `TitularACargo` varchar(120) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `establecimiento`
--

INSERT INTO `establecimiento` (`ID_Establecimiento`, `Nombre`, `Localidad`, `Ubicacion`, `TitularACargo`) VALUES
(1, 'Sucursal Formosa Centro', 'Formosa', 'Av. 25 de Mayo 850', 'Laura Fernández'),
(2, 'Sucursal Resistencia', 'Chaco', 'Av. Sarmiento 1250', 'Carlos Martínez'),
(3, 'Sucursal Posadas', 'Misiones', 'Calle Bolívar 540', 'María López'),
(4, 'Sucursal Salta', 'Salta', 'Av. Belgrano 920', 'Jorge Gómez'),
(5, 'Sucursal Mendoza', 'Mendoza', 'Av. San Martín 650', 'Sofía Ramírez');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `estados_reserva`
--

CREATE TABLE `estados_reserva` (
  `ID_EstadoReserva` int(11) NOT NULL,
  `Nombre` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `estados_reserva`
--

INSERT INTO `estados_reserva` (`ID_EstadoReserva`, `Nombre`) VALUES
(4, 'Cancelada'),
(2, 'Confirmada'),
(5, 'Finalizada'),
(3, 'Pagada'),
(1, 'Pendiente');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `eventos`
--

CREATE TABLE `eventos` (
  `ID_Evento` int(11) NOT NULL,
  `Nombre` varchar(100) NOT NULL,
  `Fecha_Inicio` date DEFAULT NULL,
  `Fecha_Final` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `eventos`
--

INSERT INTO `eventos` (`ID_Evento`, `Nombre`, `Fecha_Inicio`, `Fecha_Final`) VALUES
(1, 'Festival de la Selva', '2026-09-10', '2026-09-12'),
(2, 'Fiesta de la Nieve', '2026-08-15', '2026-08-20'),
(3, 'Fiesta Nacional de la Vendimia', '2027-03-01', '2027-03-10'),
(4, 'Festival de Música de Salta', '2026-10-05', '2026-10-07'),
(5, 'Encuentro Cultural del Litoral', '2026-11-12', '2026-11-15');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `evento_actividad`
--

CREATE TABLE `evento_actividad` (
  `ID_EventoActividad` int(11) NOT NULL,
  `FK_ID_Evento` int(11) NOT NULL,
  `FK_ID_Actividad` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `evento_actividad`
--

INSERT INTO `evento_actividad` (`ID_EventoActividad`, `FK_ID_Evento`, `FK_ID_Actividad`) VALUES
(1, 1, 1),
(2, 2, 2),
(3, 3, 5),
(4, 4, 3),
(5, 5, 4);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `favoritos`
--

CREATE TABLE `favoritos` (
  `ID_Favorito` int(11) NOT NULL,
  `FK_ID_Cliente` int(11) NOT NULL,
  `FK_ID_Paquete` int(11) DEFAULT NULL,
  `FK_ID_Destino` int(11) DEFAULT NULL,
  `Fecha` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `favoritos`
--

INSERT INTO `favoritos` (`ID_Favorito`, `FK_ID_Cliente`, `FK_ID_Paquete`, `FK_ID_Destino`, `Fecha`) VALUES
(1, 1, 1, NULL, '2026-10-01 10:00:00'),
(2, 2, 2, NULL, '2026-10-02 10:00:00'),
(3, 3, NULL, 4, '2026-10-03 10:00:00'),
(4, 4, 4, NULL, '2026-10-04 10:00:00'),
(5, 5, NULL, 3, '2026-10-05 10:00:00');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `formularios_contacto`
--

CREATE TABLE `formularios_contacto` (
  `ID_Formulario` int(11) NOT NULL,
  `Nombre` varchar(100) NOT NULL,
  `Email` varchar(120) NOT NULL,
  `Telefono` varchar(30) DEFAULT NULL,
  `Asunto` varchar(150) DEFAULT NULL,
  `Mensaje` text NOT NULL,
  `Fecha` datetime NOT NULL DEFAULT current_timestamp(),
  `Estado` enum('nuevo','contactado','cerrado') NOT NULL DEFAULT 'nuevo'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `formularios_contacto`
--

INSERT INTO `formularios_contacto` (`ID_Formulario`, `Nombre`, `Email`, `Telefono`, `Asunto`, `Mensaje`, `Fecha`, `Estado`) VALUES
(1, 'Juan Perez', 'juan@mail.com', '3704112233', 'Cotización', 'Quiero viajar a Cataratas.', '2026-10-20 09:00:00', 'nuevo'),
(2, 'Maria Garcia', 'maria@mail.com', '3704223344', 'Bariloche', 'Solicito opciones familiares.', '2026-10-21 10:00:00', 'contactado'),
(3, 'Lucas Lopez', 'lucas@mail.com', '3704334455', 'Salta', '¿Qué actividades incluye?', '2026-10-22 11:00:00', 'contactado'),
(4, 'Sofia Romero', 'sofia@mail.com', '3704445566', 'Mendoza', 'Necesito asistencia especial.', '2026-10-23 12:00:00', 'nuevo'),
(5, 'Agustin Diaz', 'agustin@mail.com', '3704556677', 'Otro', 'Consulta general.', '2026-10-24 13:00:00', 'cerrado');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `habitaciones`
--

CREATE TABLE `habitaciones` (
  `ID_Habitacion` int(11) NOT NULL,
  `FK_ID_Hotel` int(11) NOT NULL,
  `FK_ID_THabitacion` int(11) NOT NULL,
  `Numero` varchar(20) NOT NULL,
  `Piso` int(11) DEFAULT NULL,
  `Capacidad` int(11) NOT NULL,
  `PrecioNoche` decimal(12,2) NOT NULL,
  `Estado` enum('disponible','ocupada','mantenimiento') NOT NULL DEFAULT 'disponible'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `habitaciones`
--

INSERT INTO `habitaciones` (`ID_Habitacion`, `FK_ID_Hotel`, `FK_ID_THabitacion`, `Numero`, `Piso`, `Capacidad`, `PrecioNoche`, `Estado`) VALUES
(1, 1, 2, '101', 1, 2, 65000.00, 'disponible'),
(2, 2, 4, '201', 2, 4, 95000.00, 'disponible'),
(3, 3, 3, '301', 3, 3, 72000.00, 'ocupada'),
(4, 4, 4, '401', 4, 4, 120000.00, 'disponible'),
(5, 5, 2, '102', 1, 2, 58000.00, 'mantenimiento');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `historial_estados`
--

CREATE TABLE `historial_estados` (
  `ID_Historial` bigint(20) NOT NULL,
  `FK_ID_Reserva` int(11) NOT NULL,
  `FK_ID_EstadoReserva` int(11) NOT NULL,
  `FK_ID_Empleado` int(11) DEFAULT NULL,
  `Fecha` datetime NOT NULL DEFAULT current_timestamp(),
  `Observacion` varchar(250) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `historial_estados`
--

INSERT INTO `historial_estados` (`ID_Historial`, `FK_ID_Reserva`, `FK_ID_EstadoReserva`, `FK_ID_Empleado`, `Fecha`, `Observacion`) VALUES
(1, 1, 1, 2, '2026-10-01 10:00:00', 'Reserva creada'),
(2, 1, 3, 2, '2026-10-02 11:00:00', 'Pago total recibido'),
(3, 2, 1, 2, '2026-10-02 11:00:00', 'Reserva creada'),
(4, 2, 2, 2, '2026-10-03 09:00:00', 'Reserva confirmada'),
(5, 5, 4, 2, '2026-10-10 15:00:00', 'Cancelada por cliente');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `hoteles`
--

CREATE TABLE `hoteles` (
  `ID_Hotel` int(11) NOT NULL,
  `Nombre` varchar(100) NOT NULL,
  `FK_ID_ValoracionH` int(11) DEFAULT NULL,
  `Ubicacion` varchar(200) DEFAULT NULL,
  `CantidadHabitaciones` int(11) DEFAULT NULL,
  `FK_ID_THabitacion` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `hoteles`
--

INSERT INTO `hoteles` (`ID_Hotel`, `Nombre`, `FK_ID_ValoracionH`, `Ubicacion`, `CantidadHabitaciones`, `FK_ID_THabitacion`) VALUES
(1, 'Hotel Cataratas', 1, 'Puerto Iguazú, Misiones', 80, 2),
(2, 'Hotel Patagonia', 2, 'Bariloche, Río Negro', 120, 4),
(3, 'Hotel Salta Plaza', 3, 'Salta Capital', 70, 3),
(4, 'Hotel Mendoza Sol', 4, 'Ciudad de Mendoza', 100, 4),
(5, 'Hotel Formosa Central', 5, 'Formosa Capital', 60, 2);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `hotel_servicios`
--

CREATE TABLE `hotel_servicios` (
  `ID_HotelServicio` int(11) NOT NULL,
  `FK_ID_Hotel` int(11) NOT NULL,
  `FK_ID_Servicio` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `hotel_servicios`
--

INSERT INTO `hotel_servicios` (`ID_HotelServicio`, `FK_ID_Hotel`, `FK_ID_Servicio`) VALUES
(1, 1, 1),
(2, 1, 2),
(3, 2, 3),
(4, 3, 5),
(5, 4, 4);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `h_viajes`
--

CREATE TABLE `h_viajes` (
  `ID_HViaje` int(11) NOT NULL,
  `FK_ID_Viaje` int(11) NOT NULL,
  `ValoracionPromedio` decimal(3,1) DEFAULT NULL,
  `Observaciones` varchar(300) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `h_viajes`
--

INSERT INTO `h_viajes` (`ID_HViaje`, `FK_ID_Viaje`, `ValoracionPromedio`, `Observaciones`) VALUES
(1, 1, 4.5, 'Viaje realizado sin inconvenientes'),
(2, 2, 4.0, 'Buen servicio general'),
(3, 3, 4.8, 'Excelente experiencia'),
(4, 4, 3.9, 'Servicio correcto'),
(5, 5, 4.6, 'Muy buena atención');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `impuestos`
--

CREATE TABLE `impuestos` (
  `ID_Impuesto` int(11) NOT NULL,
  `Nombre` varchar(80) NOT NULL,
  `Porcentaje` decimal(5,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `impuestos`
--

INSERT INTO `impuestos` (`ID_Impuesto`, `Nombre`, `Porcentaje`) VALUES
(1, 'IVA 21%', 21.00),
(2, 'IVA 10.5%', 10.50),
(3, 'Impuesto país', 30.00),
(4, 'Tasa turística', 2.00),
(5, 'Percepción', 5.00);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `infoviajes`
--

CREATE TABLE `infoviajes` (
  `ID_Info` int(11) NOT NULL,
  `FK_ID_Viaje` int(11) NOT NULL,
  `Informacion` text NOT NULL,
  `Clima` varchar(100) DEFAULT '24°C - Templado',
  `Moneda` varchar(50) DEFAULT 'Peso Argentino (ARS)',
  `Recomendaciones` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `infoviajes`
--

INSERT INTO `infoviajes` (`ID_Info`, `FK_ID_Viaje`, `Informacion`, `Clima`, `Moneda`, `Recomendaciones`) VALUES
(1, 1, 'Puerto Iguazú alberga una de las 7 Maravillas Naturales del Mundo. Recuerda conservar tu voucher para el ingreso al Parque Nacional.', '28°C - Caluroso y Húmedo', 'Peso Argentino (ARS)', '67');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `localidades`
--

CREATE TABLE `localidades` (
  `ID_Localidad` int(11) NOT NULL,
  `FK_ID_Provincia` int(11) NOT NULL,
  `Nombre` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `localidades`
--

INSERT INTO `localidades` (`ID_Localidad`, `FK_ID_Provincia`, `Nombre`) VALUES
(1, 1, 'Formosa'),
(2, 2, 'Puerto Iguazú'),
(3, 3, 'San Carlos de Bariloche'),
(4, 4, 'Salta'),
(5, 5, 'Mendoza');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `mediotransporte`
--

CREATE TABLE `mediotransporte` (
  `ID_MedioTrans` int(11) NOT NULL,
  `Nombre` varchar(60) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `mediotransporte`
--

INSERT INTO `mediotransporte` (`ID_MedioTrans`, `Nombre`) VALUES
(1, 'Ómnibus'),
(2, 'Avión'),
(3, 'Minibús'),
(4, 'Van'),
(5, 'Tren');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `metodopago`
--

CREATE TABLE `metodopago` (
  `ID_Metodo` int(11) NOT NULL,
  `Metodo` varchar(60) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `metodopago`
--

INSERT INTO `metodopago` (`ID_Metodo`, `Metodo`) VALUES
(1, 'Efectivo'),
(2, 'Tarjeta de débito'),
(3, 'Tarjeta de crédito'),
(4, 'Transferencia bancaria'),
(5, 'Mercado Pago');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `monedas`
--

CREATE TABLE `monedas` (
  `ID_Moneda` int(11) NOT NULL,
  `Codigo` char(3) NOT NULL,
  `Nombre` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `monedas`
--

INSERT INTO `monedas` (`ID_Moneda`, `Codigo`, `Nombre`) VALUES
(1, 'ARS', 'Peso Argentino'),
(2, 'USD', 'Dólar estadounidense'),
(3, 'BRL', 'Real brasileño'),
(4, 'PYG', 'Guaraní paraguayo'),
(5, 'EUR', 'Euro');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `motivos_cancelacion`
--

CREATE TABLE `motivos_cancelacion` (
  `ID_MotivoCancelacion` int(11) NOT NULL,
  `Nombre` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `motivos_cancelacion`
--

INSERT INTO `motivos_cancelacion` (`ID_MotivoCancelacion`, `Nombre`) VALUES
(1, 'Cambio de fecha'),
(3, 'Falta de documentación'),
(5, 'Otro'),
(4, 'Problema del proveedor'),
(2, 'Problemas personales');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `nacionalidades`
--

CREATE TABLE `nacionalidades` (
  `ID_Nacionalidad` int(11) NOT NULL,
  `Nombre` varchar(80) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `nacionalidades`
--

INSERT INTO `nacionalidades` (`ID_Nacionalidad`, `Nombre`) VALUES
(1, 'Argentina'),
(3, 'Brasileña'),
(5, 'Chilena'),
(2, 'Paraguaya'),
(4, 'Uruguaya');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `notificaciones`
--

CREATE TABLE `notificaciones` (
  `ID_Notificacion` bigint(20) NOT NULL,
  `FK_ID_PasajeroOnline` int(11) NOT NULL,
  `Titulo` varchar(150) NOT NULL,
  `Mensaje` text NOT NULL,
  `Tipo` varchar(50) DEFAULT NULL,
  `Fecha` datetime NOT NULL DEFAULT current_timestamp(),
  `Leida` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `notificaciones`
--

INSERT INTO `notificaciones` (`ID_Notificacion`, `FK_ID_PasajeroOnline`, `Titulo`, `Mensaje`, `Tipo`, `Fecha`, `Leida`) VALUES
(1, 1, 'Reserva confirmada', 'Tu reserva RES-0001 fue confirmada.', 'reserva', '2026-10-01 11:00:00', 1),
(2, 2, 'Pago recibido', 'Registramos tu anticipo.', 'pago', '2026-10-02 12:00:00', 1),
(3, 3, 'Documentación pendiente', 'Revisá la documentación de tu viaje.', 'documentacion', '2026-10-03 13:00:00', 0),
(4, 4, 'Itinerario disponible', 'Tu itinerario está disponible.', 'itinerario', '2026-10-04 14:00:00', 0),
(5, 5, 'Reserva cancelada', 'Tu reserva fue cancelada.', 'reserva', '2026-10-05 15:00:00', 1);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `pago`
--

CREATE TABLE `pago` (
  `ID_Pago` int(11) NOT NULL,
  `FK_ID_Pasaje` int(11) NOT NULL,
  `FK_ID_Metodo1` int(11) NOT NULL,
  `FK_ID_Metodo2` int(11) DEFAULT NULL,
  `Monto1` decimal(10,2) NOT NULL,
  `Monto2` decimal(10,2) DEFAULT NULL,
  `Fecha` datetime NOT NULL DEFAULT current_timestamp(),
  `FK_ID_Establecimiento` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `pago`
--

INSERT INTO `pago` (`ID_Pago`, `FK_ID_Pasaje`, `FK_ID_Metodo1`, `FK_ID_Metodo2`, `Monto1`, `Monto2`, `Fecha`, `FK_ID_Establecimiento`) VALUES
(1, 1, 5, NULL, 85000.00, NULL, '2026-08-01 10:35:00', 1),
(2, 2, 3, NULL, 120000.00, NULL, '2026-08-02 14:25:00', 1),
(3, 3, 4, NULL, 95000.00, NULL, '2026-08-03 09:20:00', 1),
(4, 4, 1, 2, 50000.00, 30000.00, '2026-08-04 16:45:00', 2),
(5, 5, 5, NULL, 110000.00, NULL, '2026-08-05 11:15:00', 1);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `pagos_reserva`
--

CREATE TABLE `pagos_reserva` (
  `ID_PagoReserva` int(11) NOT NULL,
  `FK_ID_Reserva` int(11) NOT NULL,
  `FK_ID_Metodo` int(11) NOT NULL,
  `FK_ID_Moneda` int(11) NOT NULL,
  `Importe` decimal(12,2) NOT NULL,
  `FechaPago` datetime NOT NULL DEFAULT current_timestamp(),
  `NroOperacion` varchar(80) DEFAULT NULL,
  `Estado` enum('pendiente','aprobado','rechazado','anulado') NOT NULL DEFAULT 'aprobado'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `pagos_reserva`
--

INSERT INTO `pagos_reserva` (`ID_PagoReserva`, `FK_ID_Reserva`, `FK_ID_Metodo`, `FK_ID_Moneda`, `Importe`, `FechaPago`, `NroOperacion`, `Estado`) VALUES
(1, 1, 5, 1, 150000.00, '2026-10-01 10:30:00', 'MP-0001', 'aprobado'),
(2, 1, 3, 1, 135000.00, '2026-10-02 11:00:00', 'TC-0002', 'aprobado'),
(3, 2, 4, 1, 200000.00, '2026-10-02 12:00:00', 'TR-0003', 'aprobado'),
(4, 4, 3, 1, 610000.00, '2026-10-04 14:00:00', 'TC-0004', 'aprobado'),
(5, 5, 1, 1, 100000.00, '2026-10-05 15:00:00', 'EF-0005', 'anulado');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `paises`
--

CREATE TABLE `paises` (
  `ID_Pais` int(11) NOT NULL,
  `Nombre` varchar(80) NOT NULL,
  `CodigoISO` char(2) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `paises`
--

INSERT INTO `paises` (`ID_Pais`, `Nombre`, `CodigoISO`) VALUES
(1, 'Argentina', 'AR'),
(2, 'Brasil', 'BR'),
(3, 'Paraguay', 'PY'),
(4, 'Chile', 'CL'),
(5, 'Uruguay', 'UY');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `paquetes`
--

CREATE TABLE `paquetes` (
  `ID_Paquete` int(11) NOT NULL,
  `Codigo` varchar(30) NOT NULL,
  `Nombre` varchar(120) NOT NULL,
  `Descripcion` text DEFAULT NULL,
  `DuracionDias` int(11) NOT NULL,
  `PrecioBase` decimal(12,2) NOT NULL,
  `Estado` enum('borrador','publicado','pausado','finalizado') NOT NULL DEFAULT 'borrador',
  `FechaAlta` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `paquetes`
--

INSERT INTO `paquetes` (`ID_Paquete`, `Codigo`, `Nombre`, `Descripcion`, `DuracionDias`, `PrecioBase`, `Estado`, `FechaAlta`) VALUES
(1, 'PKG-001', 'Cataratas Express', 'Visita a Puerto Iguazú y Cataratas.', 3, 285000.00, 'publicado', '2026-08-01 10:00:00'),
(2, 'PKG-002', 'Bariloche Aventura', 'Patagonia, nieve y excursiones.', 5, 520000.00, 'publicado', '2026-08-02 10:00:00'),
(3, 'PKG-003', 'Salta Cultural', 'Circuito histórico y gastronómico.', 4, 390000.00, 'publicado', '2026-08-03 10:00:00'),
(4, 'PKG-004', 'Mendoza Premium', 'Bodegas y experiencias premium.', 4, 610000.00, 'publicado', '2026-08-04 10:00:00'),
(5, 'PKG-005', 'Litoral Familiar', 'Recorrido familiar por el litoral.', 3, 260000.00, 'pausado', '2026-08-05 10:00:00');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `paquete_categoria`
--

CREATE TABLE `paquete_categoria` (
  `ID_PaqueteCategoria` int(11) NOT NULL,
  `FK_ID_Paquete` int(11) NOT NULL,
  `FK_ID_CategoriaPaquete` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `paquete_categoria`
--

INSERT INTO `paquete_categoria` (`ID_PaqueteCategoria`, `FK_ID_Paquete`, `FK_ID_CategoriaPaquete`) VALUES
(1, 1, 1),
(2, 2, 2),
(3, 3, 4),
(4, 4, 5),
(5, 5, 3);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `paquete_destino`
--

CREATE TABLE `paquete_destino` (
  `ID_PaqueteDestino` int(11) NOT NULL,
  `FK_ID_Paquete` int(11) NOT NULL,
  `FK_ID_Destino` int(11) NOT NULL,
  `Orden` int(11) NOT NULL,
  `Noches` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `paquete_destino`
--

INSERT INTO `paquete_destino` (`ID_PaqueteDestino`, `FK_ID_Paquete`, `FK_ID_Destino`, `Orden`, `Noches`) VALUES
(1, 1, 2, 1, 2),
(2, 2, 3, 1, 4),
(3, 3, 4, 1, 3),
(4, 4, 5, 1, 3),
(5, 5, 1, 1, 2);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `paquete_itinerario`
--

CREATE TABLE `paquete_itinerario` (
  `ID_Itinerario` int(11) NOT NULL,
  `FK_ID_Paquete` int(11) NOT NULL,
  `Dia` int(11) NOT NULL,
  `Titulo` varchar(120) NOT NULL,
  `Descripcion` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `paquete_itinerario`
--

INSERT INTO `paquete_itinerario` (`ID_Itinerario`, `FK_ID_Paquete`, `Dia`, `Titulo`, `Descripcion`) VALUES
(1, 1, 1, 'Llegada', 'Traslado al hotel y check-in'),
(2, 1, 2, 'Cataratas', 'Excursión de día completo'),
(3, 2, 1, 'Llegada a Bariloche', 'Recepción y alojamiento'),
(4, 3, 1, 'Ciudad de Salta', 'City tour'),
(5, 4, 2, 'Ruta del vino', 'Visita a bodegas');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `paquete_servicio`
--

CREATE TABLE `paquete_servicio` (
  `ID_PaqueteServicio` int(11) NOT NULL,
  `FK_ID_Paquete` int(11) NOT NULL,
  `FK_ID_Servicio` int(11) NOT NULL,
  `Incluido` tinyint(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `paquete_servicio`
--

INSERT INTO `paquete_servicio` (`ID_PaqueteServicio`, `FK_ID_Paquete`, `FK_ID_Servicio`, `Incluido`) VALUES
(1, 1, 1, 1),
(2, 1, 2, 1),
(3, 2, 1, 1),
(4, 2, 3, 1),
(5, 4, 4, 1);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `paradas`
--

CREATE TABLE `paradas` (
  `ID_Parada` int(11) NOT NULL,
  `FK_ID_Ruta` int(11) NOT NULL,
  `Nombre` varchar(100) NOT NULL,
  `Distancia` int(11) DEFAULT NULL,
  `TiempoEstimado` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `paradas`
--

INSERT INTO `paradas` (`ID_Parada`, `FK_ID_Ruta`, `Nombre`, `Distancia`, `TiempoEstimado`) VALUES
(1, 1, 'Clorinda', 120, 120),
(2, 2, 'Resistencia', 500, 420),
(3, 3, 'Corrientes', 600, 480),
(4, 4, 'San Juan', 550, 450),
(5, 5, 'Neuquén', 800, 600);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `pasaje`
--

CREATE TABLE `pasaje` (
  `ID_Pasaje` int(11) NOT NULL,
  `FK_ID_Pasajero` int(11) NOT NULL,
  `FK_ID_Viaje` int(11) NOT NULL,
  `FK_ID_Asiento` int(11) NOT NULL,
  `NroTicket` varchar(30) NOT NULL,
  `FechaEmision` datetime NOT NULL DEFAULT current_timestamp(),
  `Estado` enum('reservado','pagado','cancelado') NOT NULL DEFAULT 'reservado'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `pasaje`
--

INSERT INTO `pasaje` (`ID_Pasaje`, `FK_ID_Pasajero`, `FK_ID_Viaje`, `FK_ID_Asiento`, `NroTicket`, `FechaEmision`, `Estado`) VALUES
(1, 1, 1, 1, 'TKT-AR-000001', '2026-08-01 10:30:00', 'pagado'),
(2, 2, 2, 2, 'TKT-AR-000002', '2026-08-02 14:20:00', 'pagado'),
(3, 3, 3, 3, 'TKT-AR-000003', '2026-08-03 09:15:00', 'reservado'),
(4, 4, 4, 4, 'TKT-AR-000004', '2026-08-04 16:40:00', 'pagado'),
(5, 5, 5, 5, 'TKT-AR-000005', '2026-08-05 11:10:00', 'reservado');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `pasajero`
--

CREATE TABLE `pasajero` (
  `ID_Pasajero` int(11) NOT NULL,
  `FK_ID_PasajeroOnline` int(11) DEFAULT NULL,
  `DNI` varchar(20) NOT NULL,
  `Nombre` varchar(60) NOT NULL,
  `Apellido` varchar(60) NOT NULL,
  `Telefono` varchar(20) DEFAULT NULL,
  `Nacionalidad` varchar(60) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `pasajero`
--

INSERT INTO `pasajero` (`ID_Pasajero`, `FK_ID_PasajeroOnline`, `DNI`, `Nombre`, `Apellido`, `Telefono`, `Nacionalidad`) VALUES
(1, 1, '40111222', 'Juan', 'Pérez', '3704112233', 'Argentina'),
(2, 2, '41222333', 'María', 'García', '3704223344', 'Argentina'),
(3, 3, '39333444', 'Lucas', 'López', '3704334455', 'Argentina'),
(4, 4, '42555666', 'Sofía', 'Romero', '3704445566', 'Argentina'),
(5, 5, '40666777', 'Agustín', 'Díaz', '3704556677', 'Argentina');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `pasajeroonline`
--

CREATE TABLE `pasajeroonline` (
  `ID_PasajeroOnline` int(11) NOT NULL,
  `Email` varchar(100) NOT NULL,
  `Contrasena_Hash` varchar(255) NOT NULL,
  `FechaNacimiento` date DEFAULT NULL,
  `FechaRegistro` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `pasajeroonline`
--

INSERT INTO `pasajeroonline` (`ID_PasajeroOnline`, `Email`, `Contrasena_Hash`, `FechaNacimiento`, `FechaRegistro`) VALUES
(1, 'juan.perez@email.com', '$2y$10$hashdemo001', '1995-04-10', '2026-07-01 10:30:00'),
(2, 'maria.garcia@email.com', '$2y$10$hashdemo002', '1998-09-22', '2026-07-03 14:20:00'),
(3, 'lucas.lopez@email.com', '$2y$10$hashdemo003', '1992-01-15', '2026-07-05 09:10:00'),
(4, 'sofia.romero@email.com', '$2y$10$hashdemo004', '2000-12-08', '2026-07-08 16:45:00'),
(5, 'agustin.diaz@email.com', '$2y$10$hashdemo005', '1996-06-30', '2026-07-10 11:00:00'),
(6, 'baezmatias781@gmail.com', '$2y$10$8c4UXM1TDpWv3lNENT5Eu.xzTv2csh0fkHk6/xPwF.cFg5Lsmb80C', NULL, '2026-09-02 23:28:02'),
(7, 'baezmatias78@gmail.com', 'holacomoestas', NULL, '2026-09-13 17:55:16'),
(8, 'baezmatias6767@gmail.com', 'holacomoestas', NULL, '2026-09-13 17:55:35');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `pension`
--

CREATE TABLE `pension` (
  `ID_Pension` int(11) NOT NULL,
  `Opcion` varchar(60) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `pension`
--

INSERT INTO `pension` (`ID_Pension`, `Opcion`) VALUES
(1, 'Solo alojamiento'),
(2, 'Desayuno incluido'),
(3, 'Media pensión'),
(4, 'Pensión completa'),
(5, 'Todo incluido');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `permisos`
--

CREATE TABLE `permisos` (
  `ID_Permiso` int(11) NOT NULL,
  `Nombre` varchar(80) NOT NULL,
  `Descripcion` varchar(200) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `permisos`
--

INSERT INTO `permisos` (`ID_Permiso`, `Nombre`, `Descripcion`) VALUES
(1, 'gestionar_clientes', 'Alta, baja y modificacion de clientes'),
(2, 'gestionar_cotizaciones', 'Crear y modificar cotizaciones'),
(3, 'gestionar_reservas', 'Gestionar reservas y expedientes'),
(4, 'gestionar_pagos', 'Registrar y consultar pagos'),
(5, 'consultar_reportes', 'Acceso a reportes');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `precios`
--

CREATE TABLE `precios` (
  `ID_Precio` int(11) NOT NULL,
  `FK_ID_Paquete` int(11) NOT NULL,
  `FK_ID_Moneda` int(11) NOT NULL,
  `Importe` decimal(12,2) NOT NULL,
  `FechaDesde` date NOT NULL,
  `FechaHasta` date DEFAULT NULL,
  `Tipo` enum('base','oferta','temporada') NOT NULL DEFAULT 'base'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `precios`
--

INSERT INTO `precios` (`ID_Precio`, `FK_ID_Paquete`, `FK_ID_Moneda`, `Importe`, `FechaDesde`, `FechaHasta`, `Tipo`) VALUES
(1, 1, 1, 285000.00, '2026-08-01', NULL, 'base'),
(2, 2, 1, 520000.00, '2026-08-01', NULL, 'base'),
(3, 3, 1, 390000.00, '2026-08-01', NULL, 'base'),
(4, 4, 1, 610000.00, '2026-08-01', NULL, 'base'),
(5, 5, 1, 260000.00, '2026-08-01', NULL, 'base');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `precio_impuesto`
--

CREATE TABLE `precio_impuesto` (
  `ID_PrecioImpuesto` int(11) NOT NULL,
  `FK_ID_Precio` int(11) NOT NULL,
  `FK_ID_Impuesto` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `precio_impuesto`
--

INSERT INTO `precio_impuesto` (`ID_PrecioImpuesto`, `FK_ID_Precio`, `FK_ID_Impuesto`) VALUES
(1, 1, 1),
(2, 2, 1),
(3, 3, 1),
(4, 4, 1),
(5, 5, 1);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `preferencias_cliente`
--

CREATE TABLE `preferencias_cliente` (
  `ID_Preferencia` int(11) NOT NULL,
  `FK_ID_Cliente` int(11) NOT NULL,
  `Tipo` varchar(60) NOT NULL,
  `Valor` varchar(150) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `preferencias_cliente`
--

INSERT INTO `preferencias_cliente` (`ID_Preferencia`, `FK_ID_Cliente`, `Tipo`, `Valor`) VALUES
(1, 1, 'Destino', 'Patagonia'),
(2, 2, 'Habitacion', 'Familiar'),
(3, 3, 'Actividad', 'Senderismo'),
(4, 4, 'Asistencia', 'Movilidad reducida'),
(5, 5, 'Transporte', 'Avion');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `preferencias_notificacion`
--

CREATE TABLE `preferencias_notificacion` (
  `ID_PreferenciaNotificacion` int(11) NOT NULL,
  `FK_ID_PasajeroOnline` int(11) NOT NULL,
  `Email` tinyint(1) NOT NULL DEFAULT 1,
  `Push` tinyint(1) NOT NULL DEFAULT 1,
  `Promociones` tinyint(1) NOT NULL DEFAULT 1,
  `Reservas` tinyint(1) NOT NULL DEFAULT 1,
  `Pagos` tinyint(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `preferencias_notificacion`
--

INSERT INTO `preferencias_notificacion` (`ID_PreferenciaNotificacion`, `FK_ID_PasajeroOnline`, `Email`, `Push`, `Promociones`, `Reservas`, `Pagos`) VALUES
(1, 1, 1, 1, 1, 1, 1),
(2, 2, 1, 1, 0, 1, 1),
(3, 3, 1, 0, 1, 1, 1),
(4, 4, 1, 1, 1, 1, 0),
(5, 5, 0, 0, 0, 1, 1);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `preguntas_frecuentes`
--

CREATE TABLE `preguntas_frecuentes` (
  `ID_FAQ` int(11) NOT NULL,
  `Pregunta` varchar(255) NOT NULL,
  `Respuesta` text NOT NULL,
  `Orden` int(11) NOT NULL DEFAULT 1,
  `Activo` tinyint(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `preguntas_frecuentes`
--

INSERT INTO `preguntas_frecuentes` (`ID_FAQ`, `Pregunta`, `Respuesta`, `Orden`, `Activo`) VALUES
(1, '¿Cómo solicito una cotización?', 'Desde el formulario de cotización o contactando a un agente.', 1, 1),
(2, '¿Puedo pagar en cuotas?', 'Sí, según el medio y las condiciones de la reserva.', 2, 1),
(3, '¿Puedo cancelar una reserva?', 'Sí, según las condiciones contratadas.', 3, 1),
(4, '¿La agencia ofrece seguros?', 'Sí, mediante proveedores asociados.', 4, 1),
(5, '¿Recibo mi itinerario?', 'Sí, puede enviarse por web, PDF o aplicación.', 5, 1);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `promociones`
--

CREATE TABLE `promociones` (
  `ID_Promocion` int(11) NOT NULL,
  `Nombre` varchar(120) NOT NULL,
  `Descripcion` text DEFAULT NULL,
  `TipoDescuento` enum('porcentaje','importe') NOT NULL,
  `ValorDescuento` decimal(12,2) NOT NULL,
  `FechaInicio` date NOT NULL,
  `FechaFin` date NOT NULL,
  `Estado` enum('activa','inactiva','finalizada') NOT NULL DEFAULT 'activa'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `promociones`
--

INSERT INTO `promociones` (`ID_Promocion`, `Nombre`, `Descripcion`, `TipoDescuento`, `ValorDescuento`, `FechaInicio`, `FechaFin`, `Estado`) VALUES
(1, 'Promo Primavera', '10% en Cataratas', 'porcentaje', 10.00, '2026-09-01', '2026-11-30', 'activa'),
(2, 'Black Travel', '50000 de descuento', 'importe', 50000.00, '2026-11-01', '2026-11-15', 'activa'),
(3, 'Familias', '15% para familias', 'porcentaje', 15.00, '2026-10-01', '2026-12-20', 'activa'),
(4, 'Mendoza Wine', '10% en paquete premium', 'porcentaje', 10.00, '2026-10-15', '2027-01-15', 'activa'),
(5, 'Fin de año', '8% en salidas seleccionadas', 'porcentaje', 8.00, '2026-12-01', '2026-12-31', 'inactiva');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `promocion_paquete`
--

CREATE TABLE `promocion_paquete` (
  `ID_PromocionPaquete` int(11) NOT NULL,
  `FK_ID_Promocion` int(11) NOT NULL,
  `FK_ID_Paquete` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `promocion_paquete`
--

INSERT INTO `promocion_paquete` (`ID_PromocionPaquete`, `FK_ID_Promocion`, `FK_ID_Paquete`) VALUES
(1, 1, 1),
(2, 2, 2),
(3, 3, 5),
(4, 4, 4),
(5, 5, 3);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `proveedores`
--

CREATE TABLE `proveedores` (
  `ID_Proveedor` int(11) NOT NULL,
  `Nombre` varchar(100) NOT NULL,
  `Contacto` varchar(200) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `proveedores`
--

INSERT INTO `proveedores` (`ID_Proveedor`, `Nombre`, `Contacto`) VALUES
(1, 'Andes Turismo S.A.', 'contacto@andesturismo.com.ar'),
(2, 'Patagonia Travel', 'info@patagoniatravel.com.ar'),
(3, 'Aerolíneas Argentinas', '0810-222-86527'),
(4, 'Norte Bus S.R.L.', 'contacto@nortebus.com.ar'),
(5, 'Viajes del Litoral', 'info@viajeslitoral.com.ar');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `proveedor_contacto`
--

CREATE TABLE `proveedor_contacto` (
  `ID_ProveedorContacto` int(11) NOT NULL,
  `FK_ID_Proveedor` int(11) NOT NULL,
  `Nombre` varchar(100) NOT NULL,
  `Cargo` varchar(80) DEFAULT NULL,
  `Telefono` varchar(30) DEFAULT NULL,
  `Email` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `proveedor_contacto`
--

INSERT INTO `proveedor_contacto` (`ID_ProveedorContacto`, `FK_ID_Proveedor`, `Nombre`, `Cargo`, `Telefono`, `Email`) VALUES
(1, 1, 'Ana Torres', 'Ejecutiva', '3704100001', 'ana@andesturismo.com.ar'),
(2, 2, 'Pedro Ruiz', 'Ventas', '3704100002', 'pedro@patagoniatravel.com.ar'),
(3, 3, 'María Silva', 'Corporativo', '0114100003', 'maria@aerolinea.com'),
(4, 4, 'Juan Benitez', 'Operaciones', '3704100004', 'juan@nortebus.com.ar'),
(5, 5, 'Laura Vera', 'Ventas', '3704100005', 'laura@viajeslitoral.com.ar');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `proveedor_servicio`
--

CREATE TABLE `proveedor_servicio` (
  `ID_ProveedorServicio` int(11) NOT NULL,
  `FK_ID_Proveedor` int(11) NOT NULL,
  `FK_ID_Servicio` int(11) NOT NULL,
  `PrecioReferencia` decimal(12,2) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `proveedor_servicio`
--

INSERT INTO `proveedor_servicio` (`ID_ProveedorServicio`, `FK_ID_Proveedor`, `FK_ID_Servicio`, `PrecioReferencia`) VALUES
(1, 1, 1, 50000.00),
(2, 1, 2, 35000.00),
(3, 2, 3, 60000.00),
(4, 4, 4, 120000.00),
(5, 5, 5, 45000.00);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `proveedor_tipo`
--

CREATE TABLE `proveedor_tipo` (
  `ID_ProveedorTipo` int(11) NOT NULL,
  `FK_ID_Proveedor` int(11) NOT NULL,
  `FK_ID_TipoProveedor` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `proveedor_tipo`
--

INSERT INTO `proveedor_tipo` (`ID_ProveedorTipo`, `FK_ID_Proveedor`, `FK_ID_TipoProveedor`) VALUES
(1, 1, 3),
(2, 2, 3),
(3, 3, 2),
(4, 4, 4),
(5, 5, 3);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `provincias`
--

CREATE TABLE `provincias` (
  `ID_Provincia` int(11) NOT NULL,
  `FK_ID_Pais` int(11) NOT NULL,
  `Nombre` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `provincias`
--

INSERT INTO `provincias` (`ID_Provincia`, `FK_ID_Pais`, `Nombre`) VALUES
(1, 1, 'Formosa'),
(5, 1, 'Mendoza'),
(2, 1, 'Misiones'),
(3, 1, 'Río Negro'),
(4, 1, 'Salta');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `recuperacion_cuenta`
--

CREATE TABLE `recuperacion_cuenta` (
  `ID_Recuperacion` int(11) NOT NULL,
  `FK_ID_PasajeroOnline` int(11) NOT NULL,
  `Token` varchar(255) NOT NULL,
  `FechaCreacion` datetime NOT NULL DEFAULT current_timestamp(),
  `FechaExpiracion` datetime NOT NULL,
  `Usado` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `recuperacion_cuenta`
--

INSERT INTO `recuperacion_cuenta` (`ID_Recuperacion`, `FK_ID_PasajeroOnline`, `Token`, `FechaCreacion`, `FechaExpiracion`, `Usado`) VALUES
(1, 6, 'rec_demo_001', '2026-10-30 09:00:00', '2026-10-31 09:00:00', 1),
(2, 7, 'rec_demo_002', '2026-10-30 10:00:00', '2026-11-01 10:00:00', 0),
(3, 8, 'rec_demo_003', '2026-10-31 12:00:00', '2026-11-02 12:00:00', 0),
(4, 1, 'rec_demo_004', '2026-10-20 10:00:00', '2026-10-21 10:00:00', 1),
(5, 2, 'rec_demo_005', '2026-10-21 10:00:00', '2026-10-22 10:00:00', 1);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `reservas`
--

CREATE TABLE `reservas` (
  `ID_Reserva` int(11) NOT NULL,
  `CodigoReserva` varchar(30) NOT NULL,
  `FK_ID_Cliente` int(11) NOT NULL,
  `FK_ID_Empleado` int(11) DEFAULT NULL,
  `FK_ID_EstadoReserva` int(11) NOT NULL,
  `FechaReserva` datetime NOT NULL DEFAULT current_timestamp(),
  `Total` decimal(12,2) NOT NULL DEFAULT 0.00,
  `Observaciones` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `reservas`
--

INSERT INTO `reservas` (`ID_Reserva`, `CodigoReserva`, `FK_ID_Cliente`, `FK_ID_Empleado`, `FK_ID_EstadoReserva`, `FechaReserva`, `Total`, `Observaciones`) VALUES
(1, 'RES-0001', 1, 2, 3, '2026-10-01 10:00:00', 285000.00, 'Reserva online'),
(2, 'RES-0002', 2, 2, 2, '2026-10-02 11:00:00', 520000.00, 'Viaje familiar'),
(3, 'RES-0003', 3, 4, 1, '2026-10-03 12:00:00', 390000.00, 'Pendiente de anticipo'),
(4, 'RES-0004', 4, 4, 3, '2026-10-04 13:00:00', 610000.00, 'Cliente solicita asistencia'),
(5, 'RES-0005', 5, 2, 4, '2026-10-05 14:00:00', 260000.00, 'Cancelada por cliente');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `reserva_detalle`
--

CREATE TABLE `reserva_detalle` (
  `ID_ReservaDetalle` int(11) NOT NULL,
  `FK_ID_Reserva` int(11) NOT NULL,
  `FK_ID_Salida` int(11) DEFAULT NULL,
  `FK_ID_Viaje` int(11) DEFAULT NULL,
  `Descripcion` varchar(200) NOT NULL,
  `Cantidad` int(11) NOT NULL DEFAULT 1,
  `PrecioUnitario` decimal(12,2) NOT NULL,
  `Subtotal` decimal(12,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `reserva_detalle`
--

INSERT INTO `reserva_detalle` (`ID_ReservaDetalle`, `FK_ID_Reserva`, `FK_ID_Salida`, `FK_ID_Viaje`, `Descripcion`, `Cantidad`, `PrecioUnitario`, `Subtotal`) VALUES
(1, 1, 1, NULL, 'Paquete Cataratas Express', 1, 285000.00, 285000.00),
(2, 2, 2, NULL, 'Paquete Bariloche Aventura', 1, 520000.00, 520000.00),
(3, 3, 3, NULL, 'Paquete Salta Cultural', 1, 390000.00, 390000.00),
(4, 4, 4, NULL, 'Paquete Mendoza Premium', 1, 610000.00, 610000.00),
(5, 5, 5, NULL, 'Paquete Litoral Familiar', 1, 260000.00, 260000.00);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `reserva_pasajero`
--

CREATE TABLE `reserva_pasajero` (
  `ID_ReservaPasajero` int(11) NOT NULL,
  `FK_ID_Reserva` int(11) NOT NULL,
  `FK_ID_Pasajero` int(11) NOT NULL,
  `Titular` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `reserva_pasajero`
--

INSERT INTO `reserva_pasajero` (`ID_ReservaPasajero`, `FK_ID_Reserva`, `FK_ID_Pasajero`, `Titular`) VALUES
(1, 1, 1, 1),
(2, 2, 2, 1),
(3, 3, 3, 1),
(4, 4, 4, 1),
(5, 5, 5, 1);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `reserva_seguro`
--

CREATE TABLE `reserva_seguro` (
  `ID_ReservaSeguro` int(11) NOT NULL,
  `FK_ID_Reserva` int(11) NOT NULL,
  `FK_ID_Seguro` int(11) NOT NULL,
  `Precio` decimal(12,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `reserva_seguro`
--

INSERT INTO `reserva_seguro` (`ID_ReservaSeguro`, `FK_ID_Reserva`, `FK_ID_Seguro`, `Precio`) VALUES
(1, 1, 1, 25000.00),
(2, 2, 3, 60000.00),
(3, 3, 2, 45000.00),
(4, 4, 4, 90000.00),
(5, 5, 5, 30000.00);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `restricciones`
--

CREATE TABLE `restricciones` (
  `ID_Restriccion` int(11) NOT NULL,
  `FK_ID_Evento` int(11) NOT NULL,
  `Descripcion` varchar(200) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `restricciones`
--

INSERT INTO `restricciones` (`ID_Restriccion`, `FK_ID_Evento`, `Descripcion`) VALUES
(1, 1, 'Cupos limitados para excursiones'),
(2, 2, 'Se recomienda llevar abrigo'),
(3, 3, 'Reserva previa obligatoria'),
(4, 4, 'Ingreso permitido desde los 12 años'),
(5, 5, 'Actividad sujeta a condiciones climáticas');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `roles`
--

CREATE TABLE `roles` (
  `ID_Rol` int(11) NOT NULL,
  `Nombre` varchar(60) NOT NULL,
  `Descripcion` varchar(200) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `roles`
--

INSERT INTO `roles` (`ID_Rol`, `Nombre`, `Descripcion`) VALUES
(1, 'Administrador', 'Acceso total al sistema'),
(2, 'Agente', 'Gestiona clientes, cotizaciones y expedientes'),
(3, 'Gerente', 'Consulta y controla reportes'),
(4, 'Cliente', 'Consulta y gestiona sus viajes'),
(5, 'Contador', 'Gestiona facturacion y pagos');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `rol_permiso`
--

CREATE TABLE `rol_permiso` (
  `ID_RolPermiso` int(11) NOT NULL,
  `FK_ID_Rol` int(11) NOT NULL,
  `FK_ID_Permiso` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `rol_permiso`
--

INSERT INTO `rol_permiso` (`ID_RolPermiso`, `FK_ID_Rol`, `FK_ID_Permiso`) VALUES
(1, 1, 1),
(2, 1, 2),
(3, 1, 3),
(4, 1, 4),
(5, 1, 5),
(6, 2, 1),
(7, 2, 2),
(8, 2, 3),
(9, 3, 5),
(10, 5, 4);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `rutas`
--

CREATE TABLE `rutas` (
  `ID_Ruta` int(11) NOT NULL,
  `ID_Inicio` int(11) NOT NULL,
  `ID_Destino` int(11) NOT NULL,
  `DistanciaTotal` int(11) DEFAULT NULL,
  `TiempoEstimado` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `rutas`
--

INSERT INTO `rutas` (`ID_Ruta`, `ID_Inicio`, `ID_Destino`, `DistanciaTotal`, `TiempoEstimado`) VALUES
(1, 1, 2, 1050, 900),
(2, 1, 4, 1250, 1080),
(3, 2, 3, 2500, 1800),
(4, 4, 5, 1050, 900),
(5, 3, 5, 1350, 1200);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `salidas`
--

CREATE TABLE `salidas` (
  `ID_Salida` int(11) NOT NULL,
  `FK_ID_Paquete` int(11) NOT NULL,
  `FechaSalida` date NOT NULL,
  `FechaRegreso` date NOT NULL,
  `CuposTotales` int(11) NOT NULL,
  `CuposDisponibles` int(11) NOT NULL,
  `Estado` enum('abierta','completa','cancelada','finalizada') NOT NULL DEFAULT 'abierta'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `salidas`
--

INSERT INTO `salidas` (`ID_Salida`, `FK_ID_Paquete`, `FechaSalida`, `FechaRegreso`, `CuposTotales`, `CuposDisponibles`, `Estado`) VALUES
(1, 1, '2026-11-15', '2026-11-17', 20, 15, 'abierta'),
(2, 2, '2026-12-05', '2026-12-09', 18, 10, 'abierta'),
(3, 3, '2026-11-20', '2026-11-23', 15, 15, 'abierta'),
(4, 4, '2027-01-10', '2027-01-13', 12, 8, 'abierta'),
(5, 5, '2026-12-18', '2026-12-20', 25, 25, 'abierta');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `secciones_landing`
--

CREATE TABLE `secciones_landing` (
  `ID_Seccion` int(11) NOT NULL,
  `Nombre` varchar(100) NOT NULL,
  `Titulo` varchar(150) DEFAULT NULL,
  `Contenido` text DEFAULT NULL,
  `Orden` int(11) NOT NULL DEFAULT 1,
  `Activo` tinyint(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `secciones_landing`
--

INSERT INTO `secciones_landing` (`ID_Seccion`, `Nombre`, `Titulo`, `Contenido`, `Orden`, `Activo`) VALUES
(1, 'hero', 'Viaja con nosotros', 'Paquetes personalizados y atención profesional.', 1, 1),
(2, 'destinos', 'Destinos destacados', 'Conocé nuestras principales propuestas.', 2, 1),
(3, 'beneficios', '¿Por qué elegirnos?', 'Atención, seguridad y financiación.', 3, 1),
(4, 'testimonios', 'Opiniones', 'Experiencias de nuestros clientes.', 4, 1),
(5, 'contacto', 'Contactanos', 'Solicitá tu cotización.', 5, 1);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `seguros`
--

CREATE TABLE `seguros` (
  `ID_Seguro` int(11) NOT NULL,
  `FK_ID_Proveedor` int(11) NOT NULL,
  `Nombre` varchar(120) NOT NULL,
  `Cobertura` text DEFAULT NULL,
  `Precio` decimal(12,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `seguros`
--

INSERT INTO `seguros` (`ID_Seguro`, `FK_ID_Proveedor`, `Nombre`, `Cobertura`, `Precio`) VALUES
(1, 5, 'Seguro Básico', 'Asistencia médica y equipaje', 25000.00),
(2, 5, 'Seguro Plus', 'Asistencia médica ampliada', 45000.00),
(3, 5, 'Seguro Familiar', 'Cobertura familiar', 60000.00),
(4, 5, 'Seguro Premium', 'Cobertura integral', 90000.00),
(5, 5, 'Seguro Regional', 'Cobertura países limítrofes', 30000.00);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `servicios`
--

CREATE TABLE `servicios` (
  `ID_Servicio` int(11) NOT NULL,
  `Nombre` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `servicios`
--

INSERT INTO `servicios` (`ID_Servicio`, `Nombre`) VALUES
(1, 'WiFi'),
(2, 'Desayuno'),
(3, 'Piscina'),
(4, 'Estacionamiento'),
(5, 'Restaurante');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `sesiones`
--

CREATE TABLE `sesiones` (
  `ID_Sesion` bigint(20) NOT NULL,
  `FK_ID_PasajeroOnline` int(11) NOT NULL,
  `Token` varchar(255) NOT NULL,
  `Inicio` datetime NOT NULL DEFAULT current_timestamp(),
  `UltimoAcceso` datetime DEFAULT NULL,
  `FechaExpiracion` datetime DEFAULT NULL,
  `Activa` tinyint(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `sesiones`
--

INSERT INTO `sesiones` (`ID_Sesion`, `FK_ID_PasajeroOnline`, `Token`, `Inicio`, `UltimoAcceso`, `FechaExpiracion`, `Activa`) VALUES
(1, 6, 'tok_demo_001', '2026-10-30 09:00:00', '2026-11-01 16:30:00', '2026-11-02 16:30:00', 1),
(2, 7, 'tok_demo_002', '2026-10-30 10:00:00', '2026-10-31 18:00:00', '2026-11-01 18:00:00', 0),
(3, 8, 'tok_demo_003', '2026-10-31 12:00:00', '2026-11-01 15:00:00', '2026-11-02 15:00:00', 1),
(4, 1, 'tok_demo_004', '2026-10-29 08:00:00', '2026-10-30 08:00:00', '2026-10-31 08:00:00', 0),
(5, 2, 'tok_demo_005', '2026-10-28 11:00:00', '2026-10-29 11:00:00', '2026-10-30 11:00:00', 0);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `tipohabitacion`
--

CREATE TABLE `tipohabitacion` (
  `ID_THabitacion` int(11) NOT NULL,
  `Tipo` varchar(60) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `tipohabitacion`
--

INSERT INTO `tipohabitacion` (`ID_THabitacion`, `Tipo`) VALUES
(1, 'Individual'),
(2, 'Doble'),
(3, 'Triple'),
(4, 'Suite'),
(5, 'Familiar');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `tipos_documento`
--

CREATE TABLE `tipos_documento` (
  `ID_TipoDocumento` int(11) NOT NULL,
  `Nombre` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `tipos_documento`
--

INSERT INTO `tipos_documento` (`ID_TipoDocumento`, `Nombre`) VALUES
(3, 'Cedula'),
(4, 'CUIT'),
(1, 'DNI'),
(5, 'Licencia de conducir'),
(2, 'Pasaporte');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `tipos_proveedor`
--

CREATE TABLE `tipos_proveedor` (
  `ID_TipoProveedor` int(11) NOT NULL,
  `Nombre` varchar(80) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `tipos_proveedor`
--

INSERT INTO `tipos_proveedor` (`ID_TipoProveedor`, `Nombre`) VALUES
(2, 'Aerolínea'),
(5, 'Aseguradora'),
(1, 'Hotel'),
(4, 'Transporte terrestre'),
(3, 'Turoperador');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `tokens_push`
--

CREATE TABLE `tokens_push` (
  `ID_TokenPush` int(11) NOT NULL,
  `FK_ID_Dispositivo` int(11) NOT NULL,
  `Token` varchar(255) NOT NULL,
  `Activo` tinyint(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `tokens_push`
--

INSERT INTO `tokens_push` (`ID_TokenPush`, `FK_ID_Dispositivo`, `Token`, `Activo`) VALUES
(1, 1, 'push_demo_001', 1),
(2, 2, 'push_demo_002', 1),
(3, 3, 'push_demo_003', 0),
(4, 4, 'push_demo_004', 1),
(5, 5, 'push_demo_005', 1);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `traslados`
--

CREATE TABLE `traslados` (
  `ID_Traslado` int(11) NOT NULL,
  `FK_ID_Proveedor` int(11) NOT NULL,
  `FK_ID_Destino` int(11) NOT NULL,
  `Origen` varchar(150) NOT NULL,
  `Destino` varchar(150) NOT NULL,
  `FechaHora` datetime NOT NULL,
  `Precio` decimal(12,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `traslados`
--

INSERT INTO `traslados` (`ID_Traslado`, `FK_ID_Proveedor`, `FK_ID_Destino`, `Origen`, `Destino`, `FechaHora`, `Precio`) VALUES
(1, 4, 2, 'Aeropuerto Iguazú', 'Hotel Cataratas', '2026-11-15 12:00:00', 25000.00),
(2, 4, 3, 'Aeropuerto Bariloche', 'Hotel Patagonia', '2026-12-05 12:00:00', 35000.00),
(3, 4, 4, 'Aeropuerto Salta', 'Hotel Salta Plaza', '2026-11-20 13:00:00', 22000.00),
(4, 4, 5, 'Aeropuerto Mendoza', 'Hotel Mendoza Sol', '2027-01-10 14:00:00', 30000.00),
(5, 4, 1, 'Terminal Formosa', 'Hotel Formosa Central', '2026-12-18 12:00:00', 18000.00);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `usuario_rol`
--

CREATE TABLE `usuario_rol` (
  `ID_UsuarioRol` int(11) NOT NULL,
  `FK_ID_PasajeroOnline` int(11) NOT NULL,
  `FK_ID_Rol` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `usuario_rol`
--

INSERT INTO `usuario_rol` (`ID_UsuarioRol`, `FK_ID_PasajeroOnline`, `FK_ID_Rol`) VALUES
(1, 1, 4),
(2, 2, 4),
(3, 3, 4),
(4, 4, 4),
(5, 5, 4),
(6, 6, 1),
(7, 7, 2),
(8, 8, 3);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `valoracion`
--

CREATE TABLE `valoracion` (
  `ID_Valoracion` int(11) NOT NULL,
  `FK_ID_Viaje` int(11) NOT NULL,
  `FK_ID_PasajeroOnline` int(11) NOT NULL,
  `Valoracion` tinyint(4) NOT NULL,
  `Comentario` varchar(500) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `valoracionh`
--

CREATE TABLE `valoracionh` (
  `ID_ValoracionH` int(11) NOT NULL,
  `Valoracion` decimal(3,1) NOT NULL DEFAULT 0.0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `valoracionh`
--

INSERT INTO `valoracionh` (`ID_ValoracionH`, `Valoracion`) VALUES
(1, 4.0),
(2, 4.5),
(3, 3.5),
(4, 5.0),
(5, 4.2);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `vehiculo`
--

CREATE TABLE `vehiculo` (
  `ID_Vehiculo` int(11) NOT NULL,
  `FK_ID_MedioTrans` int(11) NOT NULL,
  `FK_ID_Clase` int(11) NOT NULL,
  `FK_ID_Proveedor` int(11) NOT NULL,
  `Patente` varchar(20) DEFAULT NULL,
  `Capacidad` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `vehiculo`
--

INSERT INTO `vehiculo` (`ID_Vehiculo`, `FK_ID_MedioTrans`, `FK_ID_Clase`, `FK_ID_Proveedor`, `Patente`, `Capacidad`) VALUES
(1, 1, 1, 4, 'AB123CD', 45),
(2, 1, 3, 1, 'AC456EF', 50),
(3, 3, 2, 2, 'AD789GH', 20),
(4, 4, 4, 5, 'AE321JK', 15),
(5, 1, 2, 4, 'AF654LM', 40);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `viajes`
--

CREATE TABLE `viajes` (
  `ID_Viaje` int(11) NOT NULL,
  `FK_ID_Proveedor` int(11) NOT NULL,
  `FK_ID_Ruta` int(11) NOT NULL,
  `FK_ID_Vehiculo` int(11) NOT NULL,
  `FK_ID_Pension` int(11) DEFAULT NULL,
  `FK_ID_Hotel` int(11) DEFAULT NULL,
  `FechaSalida` datetime NOT NULL,
  `FechaLlegada` datetime NOT NULL,
  `Estado` enum('programado','en_curso','completado','cancelado') NOT NULL DEFAULT 'programado'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `viajes`
--

INSERT INTO `viajes` (`ID_Viaje`, `FK_ID_Proveedor`, `FK_ID_Ruta`, `FK_ID_Vehiculo`, `FK_ID_Pension`, `FK_ID_Hotel`, `FechaSalida`, `FechaLlegada`, `Estado`) VALUES
(1, 4, 1, 1, 2, 1, '2026-09-20 08:00:00', '2026-09-22 10:00:00', 'programado'),
(2, 1, 2, 2, 3, 3, '2026-10-05 07:00:00', '2026-10-06 13:00:00', 'programado'),
(3, 2, 3, 3, 4, 2, '2026-08-20 09:00:00', '2026-08-22 15:00:00', 'programado'),
(4, 5, 4, 4, 2, 4, '2026-11-01 06:30:00', '2026-11-02 20:00:00', 'programado'),
(5, 4, 5, 5, 5, 5, '2026-12-10 08:00:00', '2026-12-11 18:00:00', 'programado');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `viaje_chofer`
--

CREATE TABLE `viaje_chofer` (
  `ID_ViajeChofer` int(11) NOT NULL,
  `FK_ID_Viaje` int(11) NOT NULL,
  `FK_ID_Chofer` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `viaje_chofer`
--

INSERT INTO `viaje_chofer` (`ID_ViajeChofer`, `FK_ID_Viaje`, `FK_ID_Chofer`) VALUES
(1, 1, 1),
(2, 2, 2),
(3, 3, 3),
(4, 4, 4),
(5, 5, 5);

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `actividades`
--
ALTER TABLE `actividades`
  ADD PRIMARY KEY (`ID_Actividad`);

--
-- Indices de la tabla `asiento`
--
ALTER TABLE `asiento`
  ADD PRIMARY KEY (`ID_Asiento`),
  ADD KEY `fk_asiento_vehiculo_idx` (`FK_ID_Vehiculo`);

--
-- Indices de la tabla `auditoria`
--
ALTER TABLE `auditoria`
  ADD PRIMARY KEY (`ID_Auditoria`),
  ADD KEY `fk_aud_usuario` (`FK_ID_PasajeroOnline`);

--
-- Indices de la tabla `banners`
--
ALTER TABLE `banners`
  ADD PRIMARY KEY (`ID_Banner`);

--
-- Indices de la tabla `cancelaciones`
--
ALTER TABLE `cancelaciones`
  ADD PRIMARY KEY (`ID_Cancelacion`),
  ADD UNIQUE KEY `uq_cancelacion_reserva` (`FK_ID_Reserva`),
  ADD KEY `fk_can_motivo` (`FK_ID_MotivoCancelacion`);

--
-- Indices de la tabla `categorias_destino`
--
ALTER TABLE `categorias_destino`
  ADD PRIMARY KEY (`ID_CategoriaDestino`),
  ADD UNIQUE KEY `uq_cd_nombre` (`Nombre`);

--
-- Indices de la tabla `categorias_paquete`
--
ALTER TABLE `categorias_paquete`
  ADD PRIMARY KEY (`ID_CategoriaPaquete`),
  ADD UNIQUE KEY `uq_cp_nombre` (`Nombre`);

--
-- Indices de la tabla `choferes`
--
ALTER TABLE `choferes`
  ADD PRIMARY KEY (`ID_Chofer`),
  ADD UNIQUE KEY `uq_chofer_dni` (`DNI`);

--
-- Indices de la tabla `clases`
--
ALTER TABLE `clases`
  ADD PRIMARY KEY (`ID_Clase`);

--
-- Indices de la tabla `clientes`
--
ALTER TABLE `clientes`
  ADD PRIMARY KEY (`ID_Cliente`),
  ADD UNIQUE KEY `uq_cliente_codigo` (`CodigoCliente`),
  ADD UNIQUE KEY `uq_cliente_usuario` (`FK_ID_PasajeroOnline`);

--
-- Indices de la tabla `cliente_direccion`
--
ALTER TABLE `cliente_direccion`
  ADD PRIMARY KEY (`ID_ClienteDireccion`),
  ADD UNIQUE KEY `uq_cd` (`FK_ID_Cliente`,`FK_ID_Direccion`),
  ADD KEY `fk_cd_direccion` (`FK_ID_Direccion`);

--
-- Indices de la tabla `codigos_verificacion`
--
ALTER TABLE `codigos_verificacion`
  ADD PRIMARY KEY (`ID_Codigo`),
  ADD UNIQUE KEY `uq_codigo_pasajeroonline` (`FK_ID_PasajeroOnline`);

--
-- Indices de la tabla `contactos_emergencia`
--
ALTER TABLE `contactos_emergencia`
  ADD PRIMARY KEY (`ID_ContactoEmergencia`),
  ADD KEY `fk_ce_cliente` (`FK_ID_Cliente`);

--
-- Indices de la tabla `cuentas_corrientes`
--
ALTER TABLE `cuentas_corrientes`
  ADD PRIMARY KEY (`ID_CuentaCorriente`),
  ADD UNIQUE KEY `uq_cc_cliente` (`FK_ID_Cliente`);

--
-- Indices de la tabla `cupones`
--
ALTER TABLE `cupones`
  ADD PRIMARY KEY (`ID_Cupon`),
  ADD UNIQUE KEY `uq_cupon_codigo` (`Codigo`),
  ADD KEY `fk_cupon_promocion` (`FK_ID_Promocion`);

--
-- Indices de la tabla `cupon_uso`
--
ALTER TABLE `cupon_uso`
  ADD PRIMARY KEY (`ID_CuponUso`),
  ADD UNIQUE KEY `uq_cupon_reserva` (`FK_ID_Cupon`,`FK_ID_Reserva`),
  ADD KEY `fk_cu_cliente` (`FK_ID_Cliente`),
  ADD KEY `fk_cu_reserva` (`FK_ID_Reserva`);

--
-- Indices de la tabla `cupos_salida`
--
ALTER TABLE `cupos_salida`
  ADD PRIMARY KEY (`ID_Cupo`),
  ADD KEY `fk_cupo_salida` (`FK_ID_Salida`);

--
-- Indices de la tabla `destino`
--
ALTER TABLE `destino`
  ADD PRIMARY KEY (`ID_Destino`);

--
-- Indices de la tabla `destino_categoria`
--
ALTER TABLE `destino_categoria`
  ADD PRIMARY KEY (`ID_DestinoCategoria`),
  ADD UNIQUE KEY `uq_dest_cat` (`FK_ID_Destino`,`FK_ID_CategoriaDestino`),
  ADD KEY `fk_dc_categoria` (`FK_ID_CategoriaDestino`);

--
-- Indices de la tabla `destino_informacion`
--
ALTER TABLE `destino_informacion`
  ADD PRIMARY KEY (`ID_InformacionDestino`),
  ADD UNIQUE KEY `uq_info_destino` (`FK_ID_Destino`);

--
-- Indices de la tabla `detalle_evento`
--
ALTER TABLE `detalle_evento`
  ADD PRIMARY KEY (`ID_DetalleEvento`),
  ADD KEY `fk_de_viaje_idx` (`FK_ID_Viaje`),
  ADD KEY `fk_de_evento_idx` (`FK_ID_Evento`);

--
-- Indices de la tabla `direcciones`
--
ALTER TABLE `direcciones`
  ADD PRIMARY KEY (`ID_Direccion`);

--
-- Indices de la tabla `dispositivos`
--
ALTER TABLE `dispositivos`
  ADD PRIMARY KEY (`ID_Dispositivo`),
  ADD KEY `fk_disp_usuario` (`FK_ID_PasajeroOnline`);

--
-- Indices de la tabla `documentos_cliente`
--
ALTER TABLE `documentos_cliente`
  ADD PRIMARY KEY (`ID_DocumentoCliente`),
  ADD UNIQUE KEY `uq_cliente_doc` (`FK_ID_Cliente`,`FK_ID_TipoDocumento`,`Numero`),
  ADD KEY `fk_doc_tipo` (`FK_ID_TipoDocumento`);

--
-- Indices de la tabla `empleados`
--
ALTER TABLE `empleados`
  ADD PRIMARY KEY (`ID_Empleado`),
  ADD UNIQUE KEY `uq_empleado_dni` (`DNI`),
  ADD UNIQUE KEY `uq_empleado_usuario` (`FK_ID_PasajeroOnline`);

--
-- Indices de la tabla `empleado_sucursal`
--
ALTER TABLE `empleado_sucursal`
  ADD PRIMARY KEY (`ID_EmpleadoSucursal`),
  ADD UNIQUE KEY `uq_es` (`FK_ID_Empleado`,`FK_ID_Establecimiento`),
  ADD KEY `fk_es_sucursal` (`FK_ID_Establecimiento`);

--
-- Indices de la tabla `establecimiento`
--
ALTER TABLE `establecimiento`
  ADD PRIMARY KEY (`ID_Establecimiento`);

--
-- Indices de la tabla `estados_reserva`
--
ALTER TABLE `estados_reserva`
  ADD PRIMARY KEY (`ID_EstadoReserva`),
  ADD UNIQUE KEY `uq_er_nombre` (`Nombre`);

--
-- Indices de la tabla `eventos`
--
ALTER TABLE `eventos`
  ADD PRIMARY KEY (`ID_Evento`);

--
-- Indices de la tabla `evento_actividad`
--
ALTER TABLE `evento_actividad`
  ADD PRIMARY KEY (`ID_EventoActividad`),
  ADD UNIQUE KEY `uq_evento_actividad` (`FK_ID_Evento`,`FK_ID_Actividad`),
  ADD KEY `fk_ea_actividad_idx` (`FK_ID_Actividad`);

--
-- Indices de la tabla `favoritos`
--
ALTER TABLE `favoritos`
  ADD PRIMARY KEY (`ID_Favorito`),
  ADD KEY `fk_fav_cliente` (`FK_ID_Cliente`),
  ADD KEY `fk_fav_paquete` (`FK_ID_Paquete`),
  ADD KEY `fk_fav_destino` (`FK_ID_Destino`);

--
-- Indices de la tabla `formularios_contacto`
--
ALTER TABLE `formularios_contacto`
  ADD PRIMARY KEY (`ID_Formulario`);

--
-- Indices de la tabla `habitaciones`
--
ALTER TABLE `habitaciones`
  ADD PRIMARY KEY (`ID_Habitacion`),
  ADD UNIQUE KEY `uq_habitacion_numero` (`FK_ID_Hotel`,`Numero`),
  ADD KEY `fk_hab_tipo` (`FK_ID_THabitacion`);

--
-- Indices de la tabla `historial_estados`
--
ALTER TABLE `historial_estados`
  ADD PRIMARY KEY (`ID_Historial`),
  ADD KEY `fk_he_reserva` (`FK_ID_Reserva`),
  ADD KEY `fk_he_estado` (`FK_ID_EstadoReserva`),
  ADD KEY `fk_he_empleado` (`FK_ID_Empleado`);

--
-- Indices de la tabla `hoteles`
--
ALTER TABLE `hoteles`
  ADD PRIMARY KEY (`ID_Hotel`),
  ADD KEY `fk_hotel_valoracion_idx` (`FK_ID_ValoracionH`),
  ADD KEY `fk_hotel_habitacion_idx` (`FK_ID_THabitacion`);

--
-- Indices de la tabla `hotel_servicios`
--
ALTER TABLE `hotel_servicios`
  ADD PRIMARY KEY (`ID_HotelServicio`),
  ADD UNIQUE KEY `uq_hotel_servicio` (`FK_ID_Hotel`,`FK_ID_Servicio`),
  ADD KEY `fk_hs_servicio_idx` (`FK_ID_Servicio`);

--
-- Indices de la tabla `h_viajes`
--
ALTER TABLE `h_viajes`
  ADD PRIMARY KEY (`ID_HViaje`),
  ADD KEY `fk_hviaje_viaje_idx` (`FK_ID_Viaje`);

--
-- Indices de la tabla `impuestos`
--
ALTER TABLE `impuestos`
  ADD PRIMARY KEY (`ID_Impuesto`),
  ADD UNIQUE KEY `uq_imp_nombre` (`Nombre`);

--
-- Indices de la tabla `infoviajes`
--
ALTER TABLE `infoviajes`
  ADD PRIMARY KEY (`ID_Info`),
  ADD KEY `FK_ID_Viaje` (`FK_ID_Viaje`);

--
-- Indices de la tabla `localidades`
--
ALTER TABLE `localidades`
  ADD PRIMARY KEY (`ID_Localidad`),
  ADD UNIQUE KEY `uq_localidad` (`FK_ID_Provincia`,`Nombre`);

--
-- Indices de la tabla `mediotransporte`
--
ALTER TABLE `mediotransporte`
  ADD PRIMARY KEY (`ID_MedioTrans`);

--
-- Indices de la tabla `metodopago`
--
ALTER TABLE `metodopago`
  ADD PRIMARY KEY (`ID_Metodo`);

--
-- Indices de la tabla `monedas`
--
ALTER TABLE `monedas`
  ADD PRIMARY KEY (`ID_Moneda`),
  ADD UNIQUE KEY `uq_moneda_codigo` (`Codigo`);

--
-- Indices de la tabla `motivos_cancelacion`
--
ALTER TABLE `motivos_cancelacion`
  ADD PRIMARY KEY (`ID_MotivoCancelacion`),
  ADD UNIQUE KEY `uq_mc_nombre` (`Nombre`);

--
-- Indices de la tabla `nacionalidades`
--
ALTER TABLE `nacionalidades`
  ADD PRIMARY KEY (`ID_Nacionalidad`),
  ADD UNIQUE KEY `uq_nacionalidad` (`Nombre`);

--
-- Indices de la tabla `notificaciones`
--
ALTER TABLE `notificaciones`
  ADD PRIMARY KEY (`ID_Notificacion`),
  ADD KEY `fk_not_usuario` (`FK_ID_PasajeroOnline`);

--
-- Indices de la tabla `pago`
--
ALTER TABLE `pago`
  ADD PRIMARY KEY (`ID_Pago`),
  ADD KEY `fk_pago_pasaje_idx` (`FK_ID_Pasaje`),
  ADD KEY `fk_pago_metodo1_idx` (`FK_ID_Metodo1`),
  ADD KEY `fk_pago_metodo2_idx` (`FK_ID_Metodo2`),
  ADD KEY `fk_pago_establecimiento_idx` (`FK_ID_Establecimiento`);

--
-- Indices de la tabla `pagos_reserva`
--
ALTER TABLE `pagos_reserva`
  ADD PRIMARY KEY (`ID_PagoReserva`),
  ADD UNIQUE KEY `uq_pago_operacion` (`NroOperacion`),
  ADD KEY `fk_pr_reserva` (`FK_ID_Reserva`),
  ADD KEY `fk_pr_metodo` (`FK_ID_Metodo`),
  ADD KEY `fk_pr_moneda` (`FK_ID_Moneda`);

--
-- Indices de la tabla `paises`
--
ALTER TABLE `paises`
  ADD PRIMARY KEY (`ID_Pais`),
  ADD UNIQUE KEY `uq_pais_nombre` (`Nombre`),
  ADD UNIQUE KEY `uq_pais_iso` (`CodigoISO`);

--
-- Indices de la tabla `paquetes`
--
ALTER TABLE `paquetes`
  ADD PRIMARY KEY (`ID_Paquete`),
  ADD UNIQUE KEY `uq_paquete_codigo` (`Codigo`);

--
-- Indices de la tabla `paquete_categoria`
--
ALTER TABLE `paquete_categoria`
  ADD PRIMARY KEY (`ID_PaqueteCategoria`),
  ADD UNIQUE KEY `uq_pc` (`FK_ID_Paquete`,`FK_ID_CategoriaPaquete`),
  ADD KEY `fk_pc_categoria` (`FK_ID_CategoriaPaquete`);

--
-- Indices de la tabla `paquete_destino`
--
ALTER TABLE `paquete_destino`
  ADD PRIMARY KEY (`ID_PaqueteDestino`),
  ADD UNIQUE KEY `uq_pd` (`FK_ID_Paquete`,`FK_ID_Destino`),
  ADD KEY `fk_pd_destino` (`FK_ID_Destino`);

--
-- Indices de la tabla `paquete_itinerario`
--
ALTER TABLE `paquete_itinerario`
  ADD PRIMARY KEY (`ID_Itinerario`),
  ADD UNIQUE KEY `uq_pi_dia` (`FK_ID_Paquete`,`Dia`);

--
-- Indices de la tabla `paquete_servicio`
--
ALTER TABLE `paquete_servicio`
  ADD PRIMARY KEY (`ID_PaqueteServicio`),
  ADD UNIQUE KEY `uq_ps` (`FK_ID_Paquete`,`FK_ID_Servicio`),
  ADD KEY `fk_ps_servicio` (`FK_ID_Servicio`);

--
-- Indices de la tabla `paradas`
--
ALTER TABLE `paradas`
  ADD PRIMARY KEY (`ID_Parada`),
  ADD KEY `fk_parada_ruta_idx` (`FK_ID_Ruta`);

--
-- Indices de la tabla `pasaje`
--
ALTER TABLE `pasaje`
  ADD PRIMARY KEY (`ID_Pasaje`),
  ADD UNIQUE KEY `uq_ticket` (`NroTicket`),
  ADD UNIQUE KEY `uq_asiento_viaje` (`FK_ID_Viaje`,`FK_ID_Asiento`),
  ADD KEY `fk_pasaje_pasajero_idx` (`FK_ID_Pasajero`),
  ADD KEY `fk_pasaje_asiento_idx` (`FK_ID_Asiento`);

--
-- Indices de la tabla `pasajero`
--
ALTER TABLE `pasajero`
  ADD PRIMARY KEY (`ID_Pasajero`),
  ADD UNIQUE KEY `uq_dni` (`DNI`),
  ADD KEY `fk_pasajero_online_idx` (`FK_ID_PasajeroOnline`);

--
-- Indices de la tabla `pasajeroonline`
--
ALTER TABLE `pasajeroonline`
  ADD PRIMARY KEY (`ID_PasajeroOnline`),
  ADD UNIQUE KEY `uq_email` (`Email`);

--
-- Indices de la tabla `pension`
--
ALTER TABLE `pension`
  ADD PRIMARY KEY (`ID_Pension`);

--
-- Indices de la tabla `permisos`
--
ALTER TABLE `permisos`
  ADD PRIMARY KEY (`ID_Permiso`),
  ADD UNIQUE KEY `uq_permisos_nombre` (`Nombre`);

--
-- Indices de la tabla `precios`
--
ALTER TABLE `precios`
  ADD PRIMARY KEY (`ID_Precio`),
  ADD KEY `fk_precio_paquete` (`FK_ID_Paquete`),
  ADD KEY `fk_precio_moneda` (`FK_ID_Moneda`);

--
-- Indices de la tabla `precio_impuesto`
--
ALTER TABLE `precio_impuesto`
  ADD PRIMARY KEY (`ID_PrecioImpuesto`),
  ADD UNIQUE KEY `uq_precio_imp` (`FK_ID_Precio`,`FK_ID_Impuesto`),
  ADD KEY `fk_pi_impuesto` (`FK_ID_Impuesto`);

--
-- Indices de la tabla `preferencias_cliente`
--
ALTER TABLE `preferencias_cliente`
  ADD PRIMARY KEY (`ID_Preferencia`),
  ADD KEY `fk_pref_cliente` (`FK_ID_Cliente`);

--
-- Indices de la tabla `preferencias_notificacion`
--
ALTER TABLE `preferencias_notificacion`
  ADD PRIMARY KEY (`ID_PreferenciaNotificacion`),
  ADD UNIQUE KEY `uq_pn_usuario` (`FK_ID_PasajeroOnline`);

--
-- Indices de la tabla `preguntas_frecuentes`
--
ALTER TABLE `preguntas_frecuentes`
  ADD PRIMARY KEY (`ID_FAQ`);

--
-- Indices de la tabla `promociones`
--
ALTER TABLE `promociones`
  ADD PRIMARY KEY (`ID_Promocion`);

--
-- Indices de la tabla `promocion_paquete`
--
ALTER TABLE `promocion_paquete`
  ADD PRIMARY KEY (`ID_PromocionPaquete`),
  ADD UNIQUE KEY `uq_pp` (`FK_ID_Promocion`,`FK_ID_Paquete`),
  ADD KEY `fk_pp_paquete` (`FK_ID_Paquete`);

--
-- Indices de la tabla `proveedores`
--
ALTER TABLE `proveedores`
  ADD PRIMARY KEY (`ID_Proveedor`);

--
-- Indices de la tabla `proveedor_contacto`
--
ALTER TABLE `proveedor_contacto`
  ADD PRIMARY KEY (`ID_ProveedorContacto`),
  ADD KEY `fk_pcontacto_proveedor` (`FK_ID_Proveedor`);

--
-- Indices de la tabla `proveedor_servicio`
--
ALTER TABLE `proveedor_servicio`
  ADD PRIMARY KEY (`ID_ProveedorServicio`),
  ADD UNIQUE KEY `uq_prov_serv` (`FK_ID_Proveedor`,`FK_ID_Servicio`),
  ADD KEY `fk_pserv_servicio` (`FK_ID_Servicio`);

--
-- Indices de la tabla `proveedor_tipo`
--
ALTER TABLE `proveedor_tipo`
  ADD PRIMARY KEY (`ID_ProveedorTipo`),
  ADD UNIQUE KEY `uq_pt` (`FK_ID_Proveedor`,`FK_ID_TipoProveedor`),
  ADD KEY `fk_pt_tipo` (`FK_ID_TipoProveedor`);

--
-- Indices de la tabla `provincias`
--
ALTER TABLE `provincias`
  ADD PRIMARY KEY (`ID_Provincia`),
  ADD UNIQUE KEY `uq_provincia` (`FK_ID_Pais`,`Nombre`);

--
-- Indices de la tabla `recuperacion_cuenta`
--
ALTER TABLE `recuperacion_cuenta`
  ADD PRIMARY KEY (`ID_Recuperacion`),
  ADD UNIQUE KEY `uq_rec_token` (`Token`),
  ADD KEY `fk_rec_usuario` (`FK_ID_PasajeroOnline`);

--
-- Indices de la tabla `reservas`
--
ALTER TABLE `reservas`
  ADD PRIMARY KEY (`ID_Reserva`),
  ADD UNIQUE KEY `uq_reserva_codigo` (`CodigoReserva`),
  ADD KEY `fk_res_cliente` (`FK_ID_Cliente`),
  ADD KEY `fk_res_empleado` (`FK_ID_Empleado`),
  ADD KEY `fk_res_estado` (`FK_ID_EstadoReserva`);

--
-- Indices de la tabla `reserva_detalle`
--
ALTER TABLE `reserva_detalle`
  ADD PRIMARY KEY (`ID_ReservaDetalle`),
  ADD KEY `fk_rd_reserva` (`FK_ID_Reserva`),
  ADD KEY `fk_rd_salida` (`FK_ID_Salida`),
  ADD KEY `fk_rd_viaje` (`FK_ID_Viaje`);

--
-- Indices de la tabla `reserva_pasajero`
--
ALTER TABLE `reserva_pasajero`
  ADD PRIMARY KEY (`ID_ReservaPasajero`),
  ADD UNIQUE KEY `uq_res_pasajero` (`FK_ID_Reserva`,`FK_ID_Pasajero`),
  ADD KEY `fk_rp_pasajero` (`FK_ID_Pasajero`);

--
-- Indices de la tabla `reserva_seguro`
--
ALTER TABLE `reserva_seguro`
  ADD PRIMARY KEY (`ID_ReservaSeguro`),
  ADD UNIQUE KEY `uq_rs` (`FK_ID_Reserva`,`FK_ID_Seguro`),
  ADD KEY `fk_rs_seguro` (`FK_ID_Seguro`);

--
-- Indices de la tabla `restricciones`
--
ALTER TABLE `restricciones`
  ADD PRIMARY KEY (`ID_Restriccion`),
  ADD KEY `fk_restriccion_evento_idx` (`FK_ID_Evento`);

--
-- Indices de la tabla `roles`
--
ALTER TABLE `roles`
  ADD PRIMARY KEY (`ID_Rol`),
  ADD UNIQUE KEY `uq_roles_nombre` (`Nombre`);

--
-- Indices de la tabla `rol_permiso`
--
ALTER TABLE `rol_permiso`
  ADD PRIMARY KEY (`ID_RolPermiso`),
  ADD UNIQUE KEY `uq_rol_permiso` (`FK_ID_Rol`,`FK_ID_Permiso`),
  ADD KEY `idx_rp_permiso` (`FK_ID_Permiso`);

--
-- Indices de la tabla `rutas`
--
ALTER TABLE `rutas`
  ADD PRIMARY KEY (`ID_Ruta`),
  ADD KEY `fk_ruta_inicio_idx` (`ID_Inicio`),
  ADD KEY `fk_ruta_destino_idx` (`ID_Destino`);

--
-- Indices de la tabla `salidas`
--
ALTER TABLE `salidas`
  ADD PRIMARY KEY (`ID_Salida`),
  ADD KEY `fk_salidas_paquete` (`FK_ID_Paquete`);

--
-- Indices de la tabla `secciones_landing`
--
ALTER TABLE `secciones_landing`
  ADD PRIMARY KEY (`ID_Seccion`);

--
-- Indices de la tabla `seguros`
--
ALTER TABLE `seguros`
  ADD PRIMARY KEY (`ID_Seguro`),
  ADD KEY `fk_seg_proveedor` (`FK_ID_Proveedor`);

--
-- Indices de la tabla `servicios`
--
ALTER TABLE `servicios`
  ADD PRIMARY KEY (`ID_Servicio`);

--
-- Indices de la tabla `sesiones`
--
ALTER TABLE `sesiones`
  ADD PRIMARY KEY (`ID_Sesion`),
  ADD UNIQUE KEY `uq_sesion_token` (`Token`),
  ADD KEY `fk_sesion_usuario` (`FK_ID_PasajeroOnline`);

--
-- Indices de la tabla `tipohabitacion`
--
ALTER TABLE `tipohabitacion`
  ADD PRIMARY KEY (`ID_THabitacion`);

--
-- Indices de la tabla `tipos_documento`
--
ALTER TABLE `tipos_documento`
  ADD PRIMARY KEY (`ID_TipoDocumento`),
  ADD UNIQUE KEY `uq_td_nombre` (`Nombre`);

--
-- Indices de la tabla `tipos_proveedor`
--
ALTER TABLE `tipos_proveedor`
  ADD PRIMARY KEY (`ID_TipoProveedor`),
  ADD UNIQUE KEY `uq_tp_nombre` (`Nombre`);

--
-- Indices de la tabla `tokens_push`
--
ALTER TABLE `tokens_push`
  ADD PRIMARY KEY (`ID_TokenPush`),
  ADD UNIQUE KEY `uq_push_token` (`Token`),
  ADD KEY `fk_push_dispositivo` (`FK_ID_Dispositivo`);

--
-- Indices de la tabla `traslados`
--
ALTER TABLE `traslados`
  ADD PRIMARY KEY (`ID_Traslado`),
  ADD KEY `fk_tras_proveedor` (`FK_ID_Proveedor`),
  ADD KEY `fk_tras_destino` (`FK_ID_Destino`);

--
-- Indices de la tabla `usuario_rol`
--
ALTER TABLE `usuario_rol`
  ADD PRIMARY KEY (`ID_UsuarioRol`),
  ADD UNIQUE KEY `uq_usuario_rol` (`FK_ID_PasajeroOnline`,`FK_ID_Rol`),
  ADD KEY `idx_ur_rol` (`FK_ID_Rol`);

--
-- Indices de la tabla `valoracion`
--
ALTER TABLE `valoracion`
  ADD PRIMARY KEY (`ID_Valoracion`),
  ADD KEY `fk_val_viaje_idx` (`FK_ID_Viaje`),
  ADD KEY `fk_val_pasajero_idx` (`FK_ID_PasajeroOnline`);

--
-- Indices de la tabla `valoracionh`
--
ALTER TABLE `valoracionh`
  ADD PRIMARY KEY (`ID_ValoracionH`);

--
-- Indices de la tabla `vehiculo`
--
ALTER TABLE `vehiculo`
  ADD PRIMARY KEY (`ID_Vehiculo`),
  ADD KEY `fk_vehiculo_medio_idx` (`FK_ID_MedioTrans`),
  ADD KEY `fk_vehiculo_clase_idx` (`FK_ID_Clase`),
  ADD KEY `fk_vehiculo_proveedor_idx` (`FK_ID_Proveedor`);

--
-- Indices de la tabla `viajes`
--
ALTER TABLE `viajes`
  ADD PRIMARY KEY (`ID_Viaje`),
  ADD KEY `fk_viaje_proveedor_idx` (`FK_ID_Proveedor`),
  ADD KEY `fk_viaje_ruta_idx` (`FK_ID_Ruta`),
  ADD KEY `fk_viaje_vehiculo_idx` (`FK_ID_Vehiculo`),
  ADD KEY `fk_viaje_pension_idx` (`FK_ID_Pension`);

--
-- Indices de la tabla `viaje_chofer`
--
ALTER TABLE `viaje_chofer`
  ADD PRIMARY KEY (`ID_ViajeChofer`),
  ADD UNIQUE KEY `uq_viaje_chofer` (`FK_ID_Viaje`,`FK_ID_Chofer`),
  ADD KEY `fk_vc_chofer_idx` (`FK_ID_Chofer`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `actividades`
--
ALTER TABLE `actividades`
  MODIFY `ID_Actividad` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT de la tabla `asiento`
--
ALTER TABLE `asiento`
  MODIFY `ID_Asiento` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT de la tabla `auditoria`
--
ALTER TABLE `auditoria`
  MODIFY `ID_Auditoria` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT de la tabla `banners`
--
ALTER TABLE `banners`
  MODIFY `ID_Banner` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT de la tabla `cancelaciones`
--
ALTER TABLE `cancelaciones`
  MODIFY `ID_Cancelacion` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de la tabla `categorias_destino`
--
ALTER TABLE `categorias_destino`
  MODIFY `ID_CategoriaDestino` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT de la tabla `categorias_paquete`
--
ALTER TABLE `categorias_paquete`
  MODIFY `ID_CategoriaPaquete` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT de la tabla `choferes`
--
ALTER TABLE `choferes`
  MODIFY `ID_Chofer` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT de la tabla `clases`
--
ALTER TABLE `clases`
  MODIFY `ID_Clase` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT de la tabla `clientes`
--
ALTER TABLE `clientes`
  MODIFY `ID_Cliente` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT de la tabla `cliente_direccion`
--
ALTER TABLE `cliente_direccion`
  MODIFY `ID_ClienteDireccion` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT de la tabla `codigos_verificacion`
--
ALTER TABLE `codigos_verificacion`
  MODIFY `ID_Codigo` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de la tabla `contactos_emergencia`
--
ALTER TABLE `contactos_emergencia`
  MODIFY `ID_ContactoEmergencia` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT de la tabla `cuentas_corrientes`
--
ALTER TABLE `cuentas_corrientes`
  MODIFY `ID_CuentaCorriente` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT de la tabla `cupones`
--
ALTER TABLE `cupones`
  MODIFY `ID_Cupon` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT de la tabla `cupon_uso`
--
ALTER TABLE `cupon_uso`
  MODIFY `ID_CuponUso` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT de la tabla `cupos_salida`
--
ALTER TABLE `cupos_salida`
  MODIFY `ID_Cupo` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT de la tabla `destino`
--
ALTER TABLE `destino`
  MODIFY `ID_Destino` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT de la tabla `destino_categoria`
--
ALTER TABLE `destino_categoria`
  MODIFY `ID_DestinoCategoria` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT de la tabla `destino_informacion`
--
ALTER TABLE `destino_informacion`
  MODIFY `ID_InformacionDestino` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT de la tabla `detalle_evento`
--
ALTER TABLE `detalle_evento`
  MODIFY `ID_DetalleEvento` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT de la tabla `direcciones`
--
ALTER TABLE `direcciones`
  MODIFY `ID_Direccion` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT de la tabla `dispositivos`
--
ALTER TABLE `dispositivos`
  MODIFY `ID_Dispositivo` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT de la tabla `documentos_cliente`
--
ALTER TABLE `documentos_cliente`
  MODIFY `ID_DocumentoCliente` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT de la tabla `empleados`
--
ALTER TABLE `empleados`
  MODIFY `ID_Empleado` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT de la tabla `empleado_sucursal`
--
ALTER TABLE `empleado_sucursal`
  MODIFY `ID_EmpleadoSucursal` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT de la tabla `establecimiento`
--
ALTER TABLE `establecimiento`
  MODIFY `ID_Establecimiento` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT de la tabla `estados_reserva`
--
ALTER TABLE `estados_reserva`
  MODIFY `ID_EstadoReserva` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT de la tabla `eventos`
--
ALTER TABLE `eventos`
  MODIFY `ID_Evento` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT de la tabla `evento_actividad`
--
ALTER TABLE `evento_actividad`
  MODIFY `ID_EventoActividad` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT de la tabla `favoritos`
--
ALTER TABLE `favoritos`
  MODIFY `ID_Favorito` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT de la tabla `formularios_contacto`
--
ALTER TABLE `formularios_contacto`
  MODIFY `ID_Formulario` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT de la tabla `habitaciones`
--
ALTER TABLE `habitaciones`
  MODIFY `ID_Habitacion` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT de la tabla `historial_estados`
--
ALTER TABLE `historial_estados`
  MODIFY `ID_Historial` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT de la tabla `hoteles`
--
ALTER TABLE `hoteles`
  MODIFY `ID_Hotel` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT de la tabla `hotel_servicios`
--
ALTER TABLE `hotel_servicios`
  MODIFY `ID_HotelServicio` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT de la tabla `h_viajes`
--
ALTER TABLE `h_viajes`
  MODIFY `ID_HViaje` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT de la tabla `impuestos`
--
ALTER TABLE `impuestos`
  MODIFY `ID_Impuesto` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT de la tabla `infoviajes`
--
ALTER TABLE `infoviajes`
  MODIFY `ID_Info` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de la tabla `localidades`
--
ALTER TABLE `localidades`
  MODIFY `ID_Localidad` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT de la tabla `mediotransporte`
--
ALTER TABLE `mediotransporte`
  MODIFY `ID_MedioTrans` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT de la tabla `metodopago`
--
ALTER TABLE `metodopago`
  MODIFY `ID_Metodo` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT de la tabla `monedas`
--
ALTER TABLE `monedas`
  MODIFY `ID_Moneda` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT de la tabla `motivos_cancelacion`
--
ALTER TABLE `motivos_cancelacion`
  MODIFY `ID_MotivoCancelacion` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT de la tabla `nacionalidades`
--
ALTER TABLE `nacionalidades`
  MODIFY `ID_Nacionalidad` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT de la tabla `notificaciones`
--
ALTER TABLE `notificaciones`
  MODIFY `ID_Notificacion` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT de la tabla `pago`
--
ALTER TABLE `pago`
  MODIFY `ID_Pago` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT de la tabla `pagos_reserva`
--
ALTER TABLE `pagos_reserva`
  MODIFY `ID_PagoReserva` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT de la tabla `paises`
--
ALTER TABLE `paises`
  MODIFY `ID_Pais` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT de la tabla `paquetes`
--
ALTER TABLE `paquetes`
  MODIFY `ID_Paquete` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT de la tabla `paquete_categoria`
--
ALTER TABLE `paquete_categoria`
  MODIFY `ID_PaqueteCategoria` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT de la tabla `paquete_destino`
--
ALTER TABLE `paquete_destino`
  MODIFY `ID_PaqueteDestino` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT de la tabla `paquete_itinerario`
--
ALTER TABLE `paquete_itinerario`
  MODIFY `ID_Itinerario` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT de la tabla `paquete_servicio`
--
ALTER TABLE `paquete_servicio`
  MODIFY `ID_PaqueteServicio` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT de la tabla `paradas`
--
ALTER TABLE `paradas`
  MODIFY `ID_Parada` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT de la tabla `pasaje`
--
ALTER TABLE `pasaje`
  MODIFY `ID_Pasaje` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT de la tabla `pasajero`
--
ALTER TABLE `pasajero`
  MODIFY `ID_Pasajero` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT de la tabla `pasajeroonline`
--
ALTER TABLE `pasajeroonline`
  MODIFY `ID_PasajeroOnline` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT de la tabla `pension`
--
ALTER TABLE `pension`
  MODIFY `ID_Pension` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT de la tabla `permisos`
--
ALTER TABLE `permisos`
  MODIFY `ID_Permiso` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT de la tabla `precios`
--
ALTER TABLE `precios`
  MODIFY `ID_Precio` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT de la tabla `precio_impuesto`
--
ALTER TABLE `precio_impuesto`
  MODIFY `ID_PrecioImpuesto` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT de la tabla `preferencias_cliente`
--
ALTER TABLE `preferencias_cliente`
  MODIFY `ID_Preferencia` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT de la tabla `preferencias_notificacion`
--
ALTER TABLE `preferencias_notificacion`
  MODIFY `ID_PreferenciaNotificacion` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT de la tabla `preguntas_frecuentes`
--
ALTER TABLE `preguntas_frecuentes`
  MODIFY `ID_FAQ` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT de la tabla `promociones`
--
ALTER TABLE `promociones`
  MODIFY `ID_Promocion` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT de la tabla `promocion_paquete`
--
ALTER TABLE `promocion_paquete`
  MODIFY `ID_PromocionPaquete` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT de la tabla `proveedores`
--
ALTER TABLE `proveedores`
  MODIFY `ID_Proveedor` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT de la tabla `proveedor_contacto`
--
ALTER TABLE `proveedor_contacto`
  MODIFY `ID_ProveedorContacto` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT de la tabla `proveedor_servicio`
--
ALTER TABLE `proveedor_servicio`
  MODIFY `ID_ProveedorServicio` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT de la tabla `proveedor_tipo`
--
ALTER TABLE `proveedor_tipo`
  MODIFY `ID_ProveedorTipo` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT de la tabla `provincias`
--
ALTER TABLE `provincias`
  MODIFY `ID_Provincia` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT de la tabla `recuperacion_cuenta`
--
ALTER TABLE `recuperacion_cuenta`
  MODIFY `ID_Recuperacion` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT de la tabla `reservas`
--
ALTER TABLE `reservas`
  MODIFY `ID_Reserva` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT de la tabla `reserva_detalle`
--
ALTER TABLE `reserva_detalle`
  MODIFY `ID_ReservaDetalle` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT de la tabla `reserva_pasajero`
--
ALTER TABLE `reserva_pasajero`
  MODIFY `ID_ReservaPasajero` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT de la tabla `reserva_seguro`
--
ALTER TABLE `reserva_seguro`
  MODIFY `ID_ReservaSeguro` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT de la tabla `restricciones`
--
ALTER TABLE `restricciones`
  MODIFY `ID_Restriccion` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT de la tabla `roles`
--
ALTER TABLE `roles`
  MODIFY `ID_Rol` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT de la tabla `rol_permiso`
--
ALTER TABLE `rol_permiso`
  MODIFY `ID_RolPermiso` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT de la tabla `rutas`
--
ALTER TABLE `rutas`
  MODIFY `ID_Ruta` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT de la tabla `salidas`
--
ALTER TABLE `salidas`
  MODIFY `ID_Salida` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT de la tabla `secciones_landing`
--
ALTER TABLE `secciones_landing`
  MODIFY `ID_Seccion` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT de la tabla `seguros`
--
ALTER TABLE `seguros`
  MODIFY `ID_Seguro` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT de la tabla `servicios`
--
ALTER TABLE `servicios`
  MODIFY `ID_Servicio` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT de la tabla `sesiones`
--
ALTER TABLE `sesiones`
  MODIFY `ID_Sesion` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT de la tabla `tipohabitacion`
--
ALTER TABLE `tipohabitacion`
  MODIFY `ID_THabitacion` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT de la tabla `tipos_documento`
--
ALTER TABLE `tipos_documento`
  MODIFY `ID_TipoDocumento` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT de la tabla `tipos_proveedor`
--
ALTER TABLE `tipos_proveedor`
  MODIFY `ID_TipoProveedor` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT de la tabla `tokens_push`
--
ALTER TABLE `tokens_push`
  MODIFY `ID_TokenPush` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT de la tabla `traslados`
--
ALTER TABLE `traslados`
  MODIFY `ID_Traslado` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT de la tabla `usuario_rol`
--
ALTER TABLE `usuario_rol`
  MODIFY `ID_UsuarioRol` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT de la tabla `valoracion`
--
ALTER TABLE `valoracion`
  MODIFY `ID_Valoracion` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `valoracionh`
--
ALTER TABLE `valoracionh`
  MODIFY `ID_ValoracionH` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT de la tabla `vehiculo`
--
ALTER TABLE `vehiculo`
  MODIFY `ID_Vehiculo` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT de la tabla `viajes`
--
ALTER TABLE `viajes`
  MODIFY `ID_Viaje` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT de la tabla `viaje_chofer`
--
ALTER TABLE `viaje_chofer`
  MODIFY `ID_ViajeChofer` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `asiento`
--
ALTER TABLE `asiento`
  ADD CONSTRAINT `fk_asiento_vehiculo` FOREIGN KEY (`FK_ID_Vehiculo`) REFERENCES `vehiculo` (`ID_Vehiculo`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Filtros para la tabla `auditoria`
--
ALTER TABLE `auditoria`
  ADD CONSTRAINT `fk_aud_usuario` FOREIGN KEY (`FK_ID_PasajeroOnline`) REFERENCES `pasajeroonline` (`ID_PasajeroOnline`) ON DELETE SET NULL;

--
-- Filtros para la tabla `cancelaciones`
--
ALTER TABLE `cancelaciones`
  ADD CONSTRAINT `fk_can_motivo` FOREIGN KEY (`FK_ID_MotivoCancelacion`) REFERENCES `motivos_cancelacion` (`ID_MotivoCancelacion`),
  ADD CONSTRAINT `fk_can_reserva` FOREIGN KEY (`FK_ID_Reserva`) REFERENCES `reservas` (`ID_Reserva`) ON DELETE CASCADE;

--
-- Filtros para la tabla `clientes`
--
ALTER TABLE `clientes`
  ADD CONSTRAINT `fk_cliente_usuario` FOREIGN KEY (`FK_ID_PasajeroOnline`) REFERENCES `pasajeroonline` (`ID_PasajeroOnline`) ON DELETE SET NULL;

--
-- Filtros para la tabla `cliente_direccion`
--
ALTER TABLE `cliente_direccion`
  ADD CONSTRAINT `fk_cd_cliente` FOREIGN KEY (`FK_ID_Cliente`) REFERENCES `clientes` (`ID_Cliente`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_cd_direccion` FOREIGN KEY (`FK_ID_Direccion`) REFERENCES `direcciones` (`ID_Direccion`) ON DELETE CASCADE;

--
-- Filtros para la tabla `codigos_verificacion`
--
ALTER TABLE `codigos_verificacion`
  ADD CONSTRAINT `fk_codigo_pasajeroonline` FOREIGN KEY (`FK_ID_PasajeroOnline`) REFERENCES `pasajeroonline` (`ID_PasajeroOnline`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Filtros para la tabla `contactos_emergencia`
--
ALTER TABLE `contactos_emergencia`
  ADD CONSTRAINT `fk_ce_cliente` FOREIGN KEY (`FK_ID_Cliente`) REFERENCES `clientes` (`ID_Cliente`) ON DELETE CASCADE;

--
-- Filtros para la tabla `cuentas_corrientes`
--
ALTER TABLE `cuentas_corrientes`
  ADD CONSTRAINT `fk_cc_cliente` FOREIGN KEY (`FK_ID_Cliente`) REFERENCES `clientes` (`ID_Cliente`) ON DELETE CASCADE;

--
-- Filtros para la tabla `cupones`
--
ALTER TABLE `cupones`
  ADD CONSTRAINT `fk_cupon_promocion` FOREIGN KEY (`FK_ID_Promocion`) REFERENCES `promociones` (`ID_Promocion`) ON DELETE CASCADE;

--
-- Filtros para la tabla `cupon_uso`
--
ALTER TABLE `cupon_uso`
  ADD CONSTRAINT `fk_cu_cliente` FOREIGN KEY (`FK_ID_Cliente`) REFERENCES `clientes` (`ID_Cliente`),
  ADD CONSTRAINT `fk_cu_cupon` FOREIGN KEY (`FK_ID_Cupon`) REFERENCES `cupones` (`ID_Cupon`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_cu_reserva` FOREIGN KEY (`FK_ID_Reserva`) REFERENCES `reservas` (`ID_Reserva`) ON DELETE CASCADE;

--
-- Filtros para la tabla `cupos_salida`
--
ALTER TABLE `cupos_salida`
  ADD CONSTRAINT `fk_cupo_salida` FOREIGN KEY (`FK_ID_Salida`) REFERENCES `salidas` (`ID_Salida`) ON DELETE CASCADE;

--
-- Filtros para la tabla `destino_categoria`
--
ALTER TABLE `destino_categoria`
  ADD CONSTRAINT `fk_dc_categoria` FOREIGN KEY (`FK_ID_CategoriaDestino`) REFERENCES `categorias_destino` (`ID_CategoriaDestino`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_dc_destino` FOREIGN KEY (`FK_ID_Destino`) REFERENCES `destino` (`ID_Destino`) ON DELETE CASCADE;

--
-- Filtros para la tabla `destino_informacion`
--
ALTER TABLE `destino_informacion`
  ADD CONSTRAINT `fk_di_destino` FOREIGN KEY (`FK_ID_Destino`) REFERENCES `destino` (`ID_Destino`) ON DELETE CASCADE;

--
-- Filtros para la tabla `detalle_evento`
--
ALTER TABLE `detalle_evento`
  ADD CONSTRAINT `fk_de_evento` FOREIGN KEY (`FK_ID_Evento`) REFERENCES `eventos` (`ID_Evento`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_de_viaje` FOREIGN KEY (`FK_ID_Viaje`) REFERENCES `viajes` (`ID_Viaje`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Filtros para la tabla `dispositivos`
--
ALTER TABLE `dispositivos`
  ADD CONSTRAINT `fk_disp_usuario` FOREIGN KEY (`FK_ID_PasajeroOnline`) REFERENCES `pasajeroonline` (`ID_PasajeroOnline`) ON DELETE CASCADE;

--
-- Filtros para la tabla `documentos_cliente`
--
ALTER TABLE `documentos_cliente`
  ADD CONSTRAINT `fk_doc_cliente` FOREIGN KEY (`FK_ID_Cliente`) REFERENCES `clientes` (`ID_Cliente`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_doc_tipo` FOREIGN KEY (`FK_ID_TipoDocumento`) REFERENCES `tipos_documento` (`ID_TipoDocumento`);

--
-- Filtros para la tabla `empleados`
--
ALTER TABLE `empleados`
  ADD CONSTRAINT `fk_empleado_usuario` FOREIGN KEY (`FK_ID_PasajeroOnline`) REFERENCES `pasajeroonline` (`ID_PasajeroOnline`) ON DELETE SET NULL;

--
-- Filtros para la tabla `empleado_sucursal`
--
ALTER TABLE `empleado_sucursal`
  ADD CONSTRAINT `fk_es_empleado` FOREIGN KEY (`FK_ID_Empleado`) REFERENCES `empleados` (`ID_Empleado`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_es_sucursal` FOREIGN KEY (`FK_ID_Establecimiento`) REFERENCES `establecimiento` (`ID_Establecimiento`) ON DELETE CASCADE;

--
-- Filtros para la tabla `evento_actividad`
--
ALTER TABLE `evento_actividad`
  ADD CONSTRAINT `fk_ea_actividad` FOREIGN KEY (`FK_ID_Actividad`) REFERENCES `actividades` (`ID_Actividad`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_ea_evento` FOREIGN KEY (`FK_ID_Evento`) REFERENCES `eventos` (`ID_Evento`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Filtros para la tabla `favoritos`
--
ALTER TABLE `favoritos`
  ADD CONSTRAINT `fk_fav_cliente` FOREIGN KEY (`FK_ID_Cliente`) REFERENCES `clientes` (`ID_Cliente`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_fav_destino` FOREIGN KEY (`FK_ID_Destino`) REFERENCES `destino` (`ID_Destino`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_fav_paquete` FOREIGN KEY (`FK_ID_Paquete`) REFERENCES `paquetes` (`ID_Paquete`) ON DELETE CASCADE;

--
-- Filtros para la tabla `habitaciones`
--
ALTER TABLE `habitaciones`
  ADD CONSTRAINT `fk_hab_hotel` FOREIGN KEY (`FK_ID_Hotel`) REFERENCES `hoteles` (`ID_Hotel`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_hab_tipo` FOREIGN KEY (`FK_ID_THabitacion`) REFERENCES `tipohabitacion` (`ID_THabitacion`);

--
-- Filtros para la tabla `historial_estados`
--
ALTER TABLE `historial_estados`
  ADD CONSTRAINT `fk_he_empleado` FOREIGN KEY (`FK_ID_Empleado`) REFERENCES `empleados` (`ID_Empleado`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_he_estado` FOREIGN KEY (`FK_ID_EstadoReserva`) REFERENCES `estados_reserva` (`ID_EstadoReserva`),
  ADD CONSTRAINT `fk_he_reserva` FOREIGN KEY (`FK_ID_Reserva`) REFERENCES `reservas` (`ID_Reserva`) ON DELETE CASCADE;

--
-- Filtros para la tabla `hoteles`
--
ALTER TABLE `hoteles`
  ADD CONSTRAINT `fk_hotel_habitacion` FOREIGN KEY (`FK_ID_THabitacion`) REFERENCES `tipohabitacion` (`ID_THabitacion`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_hotel_valoracion` FOREIGN KEY (`FK_ID_ValoracionH`) REFERENCES `valoracionh` (`ID_ValoracionH`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Filtros para la tabla `hotel_servicios`
--
ALTER TABLE `hotel_servicios`
  ADD CONSTRAINT `fk_hs_hotel` FOREIGN KEY (`FK_ID_Hotel`) REFERENCES `hoteles` (`ID_Hotel`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_hs_servicio` FOREIGN KEY (`FK_ID_Servicio`) REFERENCES `servicios` (`ID_Servicio`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Filtros para la tabla `h_viajes`
--
ALTER TABLE `h_viajes`
  ADD CONSTRAINT `fk_hviaje_viaje` FOREIGN KEY (`FK_ID_Viaje`) REFERENCES `viajes` (`ID_Viaje`) ON UPDATE CASCADE;

--
-- Filtros para la tabla `localidades`
--
ALTER TABLE `localidades`
  ADD CONSTRAINT `fk_loc_prov` FOREIGN KEY (`FK_ID_Provincia`) REFERENCES `provincias` (`ID_Provincia`);

--
-- Filtros para la tabla `notificaciones`
--
ALTER TABLE `notificaciones`
  ADD CONSTRAINT `fk_not_usuario` FOREIGN KEY (`FK_ID_PasajeroOnline`) REFERENCES `pasajeroonline` (`ID_PasajeroOnline`) ON DELETE CASCADE;

--
-- Filtros para la tabla `pago`
--
ALTER TABLE `pago`
  ADD CONSTRAINT `fk_pago_establecimiento` FOREIGN KEY (`FK_ID_Establecimiento`) REFERENCES `establecimiento` (`ID_Establecimiento`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_pago_metodo1` FOREIGN KEY (`FK_ID_Metodo1`) REFERENCES `metodopago` (`ID_Metodo`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_pago_metodo2` FOREIGN KEY (`FK_ID_Metodo2`) REFERENCES `metodopago` (`ID_Metodo`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_pago_pasaje` FOREIGN KEY (`FK_ID_Pasaje`) REFERENCES `pasaje` (`ID_Pasaje`) ON UPDATE CASCADE;

--
-- Filtros para la tabla `pagos_reserva`
--
ALTER TABLE `pagos_reserva`
  ADD CONSTRAINT `fk_pr_metodo` FOREIGN KEY (`FK_ID_Metodo`) REFERENCES `metodopago` (`ID_Metodo`),
  ADD CONSTRAINT `fk_pr_moneda` FOREIGN KEY (`FK_ID_Moneda`) REFERENCES `monedas` (`ID_Moneda`),
  ADD CONSTRAINT `fk_pr_reserva` FOREIGN KEY (`FK_ID_Reserva`) REFERENCES `reservas` (`ID_Reserva`) ON DELETE CASCADE;

--
-- Filtros para la tabla `paquete_categoria`
--
ALTER TABLE `paquete_categoria`
  ADD CONSTRAINT `fk_pc_categoria` FOREIGN KEY (`FK_ID_CategoriaPaquete`) REFERENCES `categorias_paquete` (`ID_CategoriaPaquete`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_pc_paquete` FOREIGN KEY (`FK_ID_Paquete`) REFERENCES `paquetes` (`ID_Paquete`) ON DELETE CASCADE;

--
-- Filtros para la tabla `paquete_destino`
--
ALTER TABLE `paquete_destino`
  ADD CONSTRAINT `fk_pd_destino` FOREIGN KEY (`FK_ID_Destino`) REFERENCES `destino` (`ID_Destino`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_pd_paquete` FOREIGN KEY (`FK_ID_Paquete`) REFERENCES `paquetes` (`ID_Paquete`) ON DELETE CASCADE;

--
-- Filtros para la tabla `paquete_itinerario`
--
ALTER TABLE `paquete_itinerario`
  ADD CONSTRAINT `fk_pi_paquete` FOREIGN KEY (`FK_ID_Paquete`) REFERENCES `paquetes` (`ID_Paquete`) ON DELETE CASCADE;

--
-- Filtros para la tabla `paquete_servicio`
--
ALTER TABLE `paquete_servicio`
  ADD CONSTRAINT `fk_ps_paquete` FOREIGN KEY (`FK_ID_Paquete`) REFERENCES `paquetes` (`ID_Paquete`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_ps_servicio` FOREIGN KEY (`FK_ID_Servicio`) REFERENCES `servicios` (`ID_Servicio`) ON DELETE CASCADE;

--
-- Filtros para la tabla `paradas`
--
ALTER TABLE `paradas`
  ADD CONSTRAINT `fk_parada_ruta` FOREIGN KEY (`FK_ID_Ruta`) REFERENCES `rutas` (`ID_Ruta`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Filtros para la tabla `pasaje`
--
ALTER TABLE `pasaje`
  ADD CONSTRAINT `fk_pasaje_asiento` FOREIGN KEY (`FK_ID_Asiento`) REFERENCES `asiento` (`ID_Asiento`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_pasaje_pasajero` FOREIGN KEY (`FK_ID_Pasajero`) REFERENCES `pasajero` (`ID_Pasajero`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_pasaje_viaje` FOREIGN KEY (`FK_ID_Viaje`) REFERENCES `viajes` (`ID_Viaje`) ON UPDATE CASCADE;

--
-- Filtros para la tabla `pasajero`
--
ALTER TABLE `pasajero`
  ADD CONSTRAINT `fk_pasajero_online` FOREIGN KEY (`FK_ID_PasajeroOnline`) REFERENCES `pasajeroonline` (`ID_PasajeroOnline`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Filtros para la tabla `precios`
--
ALTER TABLE `precios`
  ADD CONSTRAINT `fk_precio_moneda` FOREIGN KEY (`FK_ID_Moneda`) REFERENCES `monedas` (`ID_Moneda`),
  ADD CONSTRAINT `fk_precio_paquete` FOREIGN KEY (`FK_ID_Paquete`) REFERENCES `paquetes` (`ID_Paquete`) ON DELETE CASCADE;

--
-- Filtros para la tabla `precio_impuesto`
--
ALTER TABLE `precio_impuesto`
  ADD CONSTRAINT `fk_pi_impuesto` FOREIGN KEY (`FK_ID_Impuesto`) REFERENCES `impuestos` (`ID_Impuesto`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_pi_precio` FOREIGN KEY (`FK_ID_Precio`) REFERENCES `precios` (`ID_Precio`) ON DELETE CASCADE;

--
-- Filtros para la tabla `preferencias_cliente`
--
ALTER TABLE `preferencias_cliente`
  ADD CONSTRAINT `fk_pref_cliente` FOREIGN KEY (`FK_ID_Cliente`) REFERENCES `clientes` (`ID_Cliente`) ON DELETE CASCADE;

--
-- Filtros para la tabla `preferencias_notificacion`
--
ALTER TABLE `preferencias_notificacion`
  ADD CONSTRAINT `fk_pn_usuario` FOREIGN KEY (`FK_ID_PasajeroOnline`) REFERENCES `pasajeroonline` (`ID_PasajeroOnline`) ON DELETE CASCADE;

--
-- Filtros para la tabla `promocion_paquete`
--
ALTER TABLE `promocion_paquete`
  ADD CONSTRAINT `fk_pp_paquete` FOREIGN KEY (`FK_ID_Paquete`) REFERENCES `paquetes` (`ID_Paquete`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_pp_promocion` FOREIGN KEY (`FK_ID_Promocion`) REFERENCES `promociones` (`ID_Promocion`) ON DELETE CASCADE;

--
-- Filtros para la tabla `proveedor_contacto`
--
ALTER TABLE `proveedor_contacto`
  ADD CONSTRAINT `fk_pcontacto_proveedor` FOREIGN KEY (`FK_ID_Proveedor`) REFERENCES `proveedores` (`ID_Proveedor`) ON DELETE CASCADE;

--
-- Filtros para la tabla `proveedor_servicio`
--
ALTER TABLE `proveedor_servicio`
  ADD CONSTRAINT `fk_pserv_proveedor` FOREIGN KEY (`FK_ID_Proveedor`) REFERENCES `proveedores` (`ID_Proveedor`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_pserv_servicio` FOREIGN KEY (`FK_ID_Servicio`) REFERENCES `servicios` (`ID_Servicio`) ON DELETE CASCADE;

--
-- Filtros para la tabla `proveedor_tipo`
--
ALTER TABLE `proveedor_tipo`
  ADD CONSTRAINT `fk_pt_proveedor` FOREIGN KEY (`FK_ID_Proveedor`) REFERENCES `proveedores` (`ID_Proveedor`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_pt_tipo` FOREIGN KEY (`FK_ID_TipoProveedor`) REFERENCES `tipos_proveedor` (`ID_TipoProveedor`) ON DELETE CASCADE;

--
-- Filtros para la tabla `provincias`
--
ALTER TABLE `provincias`
  ADD CONSTRAINT `fk_prov_pais` FOREIGN KEY (`FK_ID_Pais`) REFERENCES `paises` (`ID_Pais`);

--
-- Filtros para la tabla `recuperacion_cuenta`
--
ALTER TABLE `recuperacion_cuenta`
  ADD CONSTRAINT `fk_rec_usuario` FOREIGN KEY (`FK_ID_PasajeroOnline`) REFERENCES `pasajeroonline` (`ID_PasajeroOnline`) ON DELETE CASCADE;

--
-- Filtros para la tabla `reservas`
--
ALTER TABLE `reservas`
  ADD CONSTRAINT `fk_res_cliente` FOREIGN KEY (`FK_ID_Cliente`) REFERENCES `clientes` (`ID_Cliente`),
  ADD CONSTRAINT `fk_res_empleado` FOREIGN KEY (`FK_ID_Empleado`) REFERENCES `empleados` (`ID_Empleado`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_res_estado` FOREIGN KEY (`FK_ID_EstadoReserva`) REFERENCES `estados_reserva` (`ID_EstadoReserva`);

--
-- Filtros para la tabla `reserva_detalle`
--
ALTER TABLE `reserva_detalle`
  ADD CONSTRAINT `fk_rd_reserva` FOREIGN KEY (`FK_ID_Reserva`) REFERENCES `reservas` (`ID_Reserva`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_rd_salida` FOREIGN KEY (`FK_ID_Salida`) REFERENCES `salidas` (`ID_Salida`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_rd_viaje` FOREIGN KEY (`FK_ID_Viaje`) REFERENCES `viajes` (`ID_Viaje`) ON DELETE SET NULL;

--
-- Filtros para la tabla `reserva_pasajero`
--
ALTER TABLE `reserva_pasajero`
  ADD CONSTRAINT `fk_rp_pasajero` FOREIGN KEY (`FK_ID_Pasajero`) REFERENCES `pasajero` (`ID_Pasajero`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_rp_reserva` FOREIGN KEY (`FK_ID_Reserva`) REFERENCES `reservas` (`ID_Reserva`) ON DELETE CASCADE;

--
-- Filtros para la tabla `reserva_seguro`
--
ALTER TABLE `reserva_seguro`
  ADD CONSTRAINT `fk_rs_reserva` FOREIGN KEY (`FK_ID_Reserva`) REFERENCES `reservas` (`ID_Reserva`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_rs_seguro` FOREIGN KEY (`FK_ID_Seguro`) REFERENCES `seguros` (`ID_Seguro`);

--
-- Filtros para la tabla `restricciones`
--
ALTER TABLE `restricciones`
  ADD CONSTRAINT `fk_restriccion_evento` FOREIGN KEY (`FK_ID_Evento`) REFERENCES `eventos` (`ID_Evento`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Filtros para la tabla `rol_permiso`
--
ALTER TABLE `rol_permiso`
  ADD CONSTRAINT `fk_rp_permiso` FOREIGN KEY (`FK_ID_Permiso`) REFERENCES `permisos` (`ID_Permiso`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_rp_rol` FOREIGN KEY (`FK_ID_Rol`) REFERENCES `roles` (`ID_Rol`) ON DELETE CASCADE;

--
-- Filtros para la tabla `rutas`
--
ALTER TABLE `rutas`
  ADD CONSTRAINT `fk_ruta_destino` FOREIGN KEY (`ID_Destino`) REFERENCES `destino` (`ID_Destino`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_ruta_inicio` FOREIGN KEY (`ID_Inicio`) REFERENCES `destino` (`ID_Destino`) ON UPDATE CASCADE;

--
-- Filtros para la tabla `salidas`
--
ALTER TABLE `salidas`
  ADD CONSTRAINT `fk_salidas_paquete` FOREIGN KEY (`FK_ID_Paquete`) REFERENCES `paquetes` (`ID_Paquete`) ON DELETE CASCADE;

--
-- Filtros para la tabla `seguros`
--
ALTER TABLE `seguros`
  ADD CONSTRAINT `fk_seg_proveedor` FOREIGN KEY (`FK_ID_Proveedor`) REFERENCES `proveedores` (`ID_Proveedor`);

--
-- Filtros para la tabla `sesiones`
--
ALTER TABLE `sesiones`
  ADD CONSTRAINT `fk_sesion_usuario` FOREIGN KEY (`FK_ID_PasajeroOnline`) REFERENCES `pasajeroonline` (`ID_PasajeroOnline`) ON DELETE CASCADE;

--
-- Filtros para la tabla `tokens_push`
--
ALTER TABLE `tokens_push`
  ADD CONSTRAINT `fk_push_dispositivo` FOREIGN KEY (`FK_ID_Dispositivo`) REFERENCES `dispositivos` (`ID_Dispositivo`) ON DELETE CASCADE;

--
-- Filtros para la tabla `traslados`
--
ALTER TABLE `traslados`
  ADD CONSTRAINT `fk_tras_destino` FOREIGN KEY (`FK_ID_Destino`) REFERENCES `destino` (`ID_Destino`),
  ADD CONSTRAINT `fk_tras_proveedor` FOREIGN KEY (`FK_ID_Proveedor`) REFERENCES `proveedores` (`ID_Proveedor`);

--
-- Filtros para la tabla `usuario_rol`
--
ALTER TABLE `usuario_rol`
  ADD CONSTRAINT `fk_ur_rol` FOREIGN KEY (`FK_ID_Rol`) REFERENCES `roles` (`ID_Rol`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_ur_usuario` FOREIGN KEY (`FK_ID_PasajeroOnline`) REFERENCES `pasajeroonline` (`ID_PasajeroOnline`) ON DELETE CASCADE;

--
-- Filtros para la tabla `valoracion`
--
ALTER TABLE `valoracion`
  ADD CONSTRAINT `fk_val_pasajero` FOREIGN KEY (`FK_ID_PasajeroOnline`) REFERENCES `pasajeroonline` (`ID_PasajeroOnline`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_val_viaje` FOREIGN KEY (`FK_ID_Viaje`) REFERENCES `viajes` (`ID_Viaje`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Filtros para la tabla `vehiculo`
--
ALTER TABLE `vehiculo`
  ADD CONSTRAINT `fk_vehiculo_clase` FOREIGN KEY (`FK_ID_Clase`) REFERENCES `clases` (`ID_Clase`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_vehiculo_medio` FOREIGN KEY (`FK_ID_MedioTrans`) REFERENCES `mediotransporte` (`ID_MedioTrans`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_vehiculo_proveedor` FOREIGN KEY (`FK_ID_Proveedor`) REFERENCES `proveedores` (`ID_Proveedor`) ON UPDATE CASCADE;

--
-- Filtros para la tabla `viajes`
--
ALTER TABLE `viajes`
  ADD CONSTRAINT `fk_viaje_pension` FOREIGN KEY (`FK_ID_Pension`) REFERENCES `pension` (`ID_Pension`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_viaje_proveedor` FOREIGN KEY (`FK_ID_Proveedor`) REFERENCES `proveedores` (`ID_Proveedor`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_viaje_ruta` FOREIGN KEY (`FK_ID_Ruta`) REFERENCES `rutas` (`ID_Ruta`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_viaje_vehiculo` FOREIGN KEY (`FK_ID_Vehiculo`) REFERENCES `vehiculo` (`ID_Vehiculo`) ON UPDATE CASCADE;

--
-- Filtros para la tabla `viaje_chofer`
--
ALTER TABLE `viaje_chofer`
  ADD CONSTRAINT `fk_vc_chofer` FOREIGN KEY (`FK_ID_Chofer`) REFERENCES `choferes` (`ID_Chofer`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_vc_viaje` FOREIGN KEY (`FK_ID_Viaje`) REFERENCES `viajes` (`ID_Viaje`) ON DELETE CASCADE ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
