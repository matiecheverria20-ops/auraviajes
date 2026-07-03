-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 05-06-2026 a las 06:57:30
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
-- Base de datos: `sistema_viajes`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `actividades`
--

CREATE TABLE `actividades` (
  `ID_Actividad` int(11) NOT NULL,
  `Nombre` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `clases`
--

CREATE TABLE `clases` (
  `ID_Clase` int(11) NOT NULL,
  `Clase` varchar(60) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `destino`
--

CREATE TABLE `destino` (
  `ID_Destino` int(11) NOT NULL,
  `NombreUbicacion` varchar(100) NOT NULL,
  `Link` varchar(200) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `detalle_evento`
--

CREATE TABLE `detalle_evento` (
  `ID_DetalleEvento` int(11) NOT NULL,
  `FK_ID_Viaje` int(11) NOT NULL,
  `FK_ID_Evento` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `evento_actividad`
--

CREATE TABLE `evento_actividad` (
  `ID_EventoActividad` int(11) NOT NULL,
  `FK_ID_Evento` int(11) NOT NULL,
  `FK_ID_Actividad` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `hotel_servicios`
--

CREATE TABLE `hotel_servicios` (
  `ID_HotelServicio` int(11) NOT NULL,
  `FK_ID_Hotel` int(11) NOT NULL,
  `FK_ID_Servicio` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `mediotransporte`
--

CREATE TABLE `mediotransporte` (
  `ID_MedioTrans` int(11) NOT NULL,
  `Nombre` varchar(60) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `metodopago`
--

CREATE TABLE `metodopago` (
  `ID_Metodo` int(11) NOT NULL,
  `Metodo` varchar(60) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `pension`
--

CREATE TABLE `pension` (
  `ID_Pension` int(11) NOT NULL,
  `Opcion` varchar(60) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `proveedores`
--

CREATE TABLE `proveedores` (
  `ID_Proveedor` int(11) NOT NULL,
  `Nombre` varchar(100) NOT NULL,
  `Contacto` varchar(200) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `restricciones`
--

CREATE TABLE `restricciones` (
  `ID_Restriccion` int(11) NOT NULL,
  `FK_ID_Evento` int(11) NOT NULL,
  `Descripcion` varchar(200) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `servicios`
--

CREATE TABLE `servicios` (
  `ID_Servicio` int(11) NOT NULL,
  `Nombre` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `tipohabitacion`
--

CREATE TABLE `tipohabitacion` (
  `ID_THabitacion` int(11) NOT NULL,
  `Tipo` varchar(60) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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
-- Indices de la tabla `destino`
--
ALTER TABLE `destino`
  ADD PRIMARY KEY (`ID_Destino`);

--
-- Indices de la tabla `detalle_evento`
--
ALTER TABLE `detalle_evento`
  ADD PRIMARY KEY (`ID_DetalleEvento`),
  ADD KEY `fk_de_viaje_idx` (`FK_ID_Viaje`),
  ADD KEY `fk_de_evento_idx` (`FK_ID_Evento`);

--
-- Indices de la tabla `establecimiento`
--
ALTER TABLE `establecimiento`
  ADD PRIMARY KEY (`ID_Establecimiento`);

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
-- Indices de la tabla `pago`
--
ALTER TABLE `pago`
  ADD PRIMARY KEY (`ID_Pago`),
  ADD KEY `fk_pago_pasaje_idx` (`FK_ID_Pasaje`),
  ADD KEY `fk_pago_metodo1_idx` (`FK_ID_Metodo1`),
  ADD KEY `fk_pago_metodo2_idx` (`FK_ID_Metodo2`),
  ADD KEY `fk_pago_establecimiento_idx` (`FK_ID_Establecimiento`);

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
-- Indices de la tabla `proveedores`
--
ALTER TABLE `proveedores`
  ADD PRIMARY KEY (`ID_Proveedor`);

--
-- Indices de la tabla `restricciones`
--
ALTER TABLE `restricciones`
  ADD PRIMARY KEY (`ID_Restriccion`),
  ADD KEY `fk_restriccion_evento_idx` (`FK_ID_Evento`);

--
-- Indices de la tabla `rutas`
--
ALTER TABLE `rutas`
  ADD PRIMARY KEY (`ID_Ruta`),
  ADD KEY `fk_ruta_inicio_idx` (`ID_Inicio`),
  ADD KEY `fk_ruta_destino_idx` (`ID_Destino`);

--
-- Indices de la tabla `servicios`
--
ALTER TABLE `servicios`
  ADD PRIMARY KEY (`ID_Servicio`);

--
-- Indices de la tabla `tipohabitacion`
--
ALTER TABLE `tipohabitacion`
  ADD PRIMARY KEY (`ID_THabitacion`);

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
  MODIFY `ID_Actividad` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `asiento`
--
ALTER TABLE `asiento`
  MODIFY `ID_Asiento` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `choferes`
--
ALTER TABLE `choferes`
  MODIFY `ID_Chofer` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `clases`
--
ALTER TABLE `clases`
  MODIFY `ID_Clase` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `destino`
--
ALTER TABLE `destino`
  MODIFY `ID_Destino` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `detalle_evento`
--
ALTER TABLE `detalle_evento`
  MODIFY `ID_DetalleEvento` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `establecimiento`
--
ALTER TABLE `establecimiento`
  MODIFY `ID_Establecimiento` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `eventos`
--
ALTER TABLE `eventos`
  MODIFY `ID_Evento` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `evento_actividad`
--
ALTER TABLE `evento_actividad`
  MODIFY `ID_EventoActividad` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `hoteles`
--
ALTER TABLE `hoteles`
  MODIFY `ID_Hotel` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `hotel_servicios`
--
ALTER TABLE `hotel_servicios`
  MODIFY `ID_HotelServicio` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `h_viajes`
--
ALTER TABLE `h_viajes`
  MODIFY `ID_HViaje` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `mediotransporte`
--
ALTER TABLE `mediotransporte`
  MODIFY `ID_MedioTrans` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `metodopago`
--
ALTER TABLE `metodopago`
  MODIFY `ID_Metodo` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `pago`
--
ALTER TABLE `pago`
  MODIFY `ID_Pago` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `paradas`
--
ALTER TABLE `paradas`
  MODIFY `ID_Parada` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `pasaje`
--
ALTER TABLE `pasaje`
  MODIFY `ID_Pasaje` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `pasajero`
--
ALTER TABLE `pasajero`
  MODIFY `ID_Pasajero` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `pasajeroonline`
--
ALTER TABLE `pasajeroonline`
  MODIFY `ID_PasajeroOnline` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `pension`
--
ALTER TABLE `pension`
  MODIFY `ID_Pension` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `proveedores`
--
ALTER TABLE `proveedores`
  MODIFY `ID_Proveedor` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `restricciones`
--
ALTER TABLE `restricciones`
  MODIFY `ID_Restriccion` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `rutas`
--
ALTER TABLE `rutas`
  MODIFY `ID_Ruta` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `servicios`
--
ALTER TABLE `servicios`
  MODIFY `ID_Servicio` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `tipohabitacion`
--
ALTER TABLE `tipohabitacion`
  MODIFY `ID_THabitacion` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `valoracion`
--
ALTER TABLE `valoracion`
  MODIFY `ID_Valoracion` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `valoracionh`
--
ALTER TABLE `valoracionh`
  MODIFY `ID_ValoracionH` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `vehiculo`
--
ALTER TABLE `vehiculo`
  MODIFY `ID_Vehiculo` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `viajes`
--
ALTER TABLE `viajes`
  MODIFY `ID_Viaje` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `viaje_chofer`
--
ALTER TABLE `viaje_chofer`
  MODIFY `ID_ViajeChofer` int(11) NOT NULL AUTO_INCREMENT;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `asiento`
--
ALTER TABLE `asiento`
  ADD CONSTRAINT `fk_asiento_vehiculo` FOREIGN KEY (`FK_ID_Vehiculo`) REFERENCES `vehiculo` (`ID_Vehiculo`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Filtros para la tabla `detalle_evento`
--
ALTER TABLE `detalle_evento`
  ADD CONSTRAINT `fk_de_evento` FOREIGN KEY (`FK_ID_Evento`) REFERENCES `eventos` (`ID_Evento`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_de_viaje` FOREIGN KEY (`FK_ID_Viaje`) REFERENCES `viajes` (`ID_Viaje`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Filtros para la tabla `evento_actividad`
--
ALTER TABLE `evento_actividad`
  ADD CONSTRAINT `fk_ea_actividad` FOREIGN KEY (`FK_ID_Actividad`) REFERENCES `actividades` (`ID_Actividad`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_ea_evento` FOREIGN KEY (`FK_ID_Evento`) REFERENCES `eventos` (`ID_Evento`) ON DELETE CASCADE ON UPDATE CASCADE;

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
-- Filtros para la tabla `pago`
--
ALTER TABLE `pago`
  ADD CONSTRAINT `fk_pago_establecimiento` FOREIGN KEY (`FK_ID_Establecimiento`) REFERENCES `establecimiento` (`ID_Establecimiento`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_pago_metodo1` FOREIGN KEY (`FK_ID_Metodo1`) REFERENCES `metodopago` (`ID_Metodo`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_pago_metodo2` FOREIGN KEY (`FK_ID_Metodo2`) REFERENCES `metodopago` (`ID_Metodo`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_pago_pasaje` FOREIGN KEY (`FK_ID_Pasaje`) REFERENCES `pasaje` (`ID_Pasaje`) ON UPDATE CASCADE;

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
-- Filtros para la tabla `restricciones`
--
ALTER TABLE `restricciones`
  ADD CONSTRAINT `fk_restriccion_evento` FOREIGN KEY (`FK_ID_Evento`) REFERENCES `eventos` (`ID_Evento`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Filtros para la tabla `rutas`
--
ALTER TABLE `rutas`
  ADD CONSTRAINT `fk_ruta_destino` FOREIGN KEY (`ID_Destino`) REFERENCES `destino` (`ID_Destino`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_ruta_inicio` FOREIGN KEY (`ID_Inicio`) REFERENCES `destino` (`ID_Destino`) ON UPDATE CASCADE;

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
