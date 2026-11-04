<?php
declare(strict_types=1);

function client_select_sql(): string
{
    return "SELECT c.*, u.Email, u.FechaNacimiento,
                   p.ID_Pasajero, p.DNI, p.Nombre, p.Apellido, p.Telefono, p.Nacionalidad,
                   (SELECT COUNT(*) FROM reservas r WHERE r.FK_ID_Cliente = c.ID_Cliente) AS reservas_count,
                   (SELECT COALESCE(SUM(r.Total), 0) FROM reservas r WHERE r.FK_ID_Cliente = c.ID_Cliente AND r.FK_ID_EstadoReserva <> (SELECT ID_EstadoReserva FROM estados_reserva WHERE Nombre = 'Cancelada' LIMIT 1)) AS total_facturado,
                   (COALESCE((SELECT SUM(pg.Importe) FROM pagos_reserva pg INNER JOIN reservas r ON r.ID_Reserva = pg.FK_ID_Reserva WHERE r.FK_ID_Cliente = c.ID_Cliente AND pg.Estado = 'aprobado'), 0)
                    - COALESCE((SELECT SUM(cn.ImporteDevuelto) FROM cancelaciones cn INNER JOIN reservas rcn ON rcn.ID_Reserva = cn.FK_ID_Reserva WHERE rcn.FK_ID_Cliente = c.ID_Cliente), 0)) AS total_pagado,
                   (SELECT COUNT(*) FROM formularios_contacto fc WHERE LOWER(fc.Email) = LOWER(u.Email)) AS consultas_count
            FROM clientes c
            LEFT JOIN pasajeroonline u ON u.ID_PasajeroOnline = c.FK_ID_PasajeroOnline
            LEFT JOIN pasajero p ON p.ID_Pasajero = (
                SELECT MIN(p2.ID_Pasajero) FROM pasajero p2 WHERE p2.FK_ID_PasajeroOnline = u.ID_PasajeroOnline
            )";
}

function get_client(int $id): ?array
{
    return db_one(client_select_sql() . ' WHERE c.ID_Cliente = :id', [':id' => $id]);
}

function get_client_by_email(string $email): ?array
{
    return db_one(client_select_sql() . ' WHERE LOWER(u.Email) = :email', [':email' => mb_strtolower($email)]);
}

function client_full_name(?array $client): string
{
    if ($client === null) {
        return 'Cliente';
    }
    $name = trim((string) ($client['Nombre'] ?? '') . ' ' . (string) ($client['Apellido'] ?? ''));
    return $name !== '' ? $name : (string) ($client['Email'] ?? 'Cliente');
}

function get_reservation(int $id): ?array
{
    return db_one(
        "SELECT r.*, c.CodigoCliente, u.Email,
                p.Nombre, p.Apellido, p.DNI, p.Telefono,
                e.Nombre AS EmpleadoNombre, e.Apellido AS EmpleadoApellido,
                er.Nombre AS EstadoNombre
         FROM reservas r
         INNER JOIN clientes c ON c.ID_Cliente = r.FK_ID_Cliente
         LEFT JOIN pasajeroonline u ON u.ID_PasajeroOnline = c.FK_ID_PasajeroOnline
         LEFT JOIN pasajero p ON p.ID_Pasajero = (
             SELECT MIN(p2.ID_Pasajero) FROM pasajero p2 WHERE p2.FK_ID_PasajeroOnline = u.ID_PasajeroOnline
         )
         LEFT JOIN empleados e ON e.ID_Empleado = r.FK_ID_Empleado
         LEFT JOIN estados_reserva er ON er.ID_EstadoReserva = r.FK_ID_EstadoReserva
         WHERE r.ID_Reserva = :id",
        [':id' => $id]
    );
}

function reservation_paid(int $reservationId): float
{
    return (float) db_val(
        "SELECT COALESCE(SUM(Importe), 0) FROM pagos_reserva WHERE FK_ID_Reserva = :id AND Estado = 'aprobado'",
        [':id' => $reservationId],
        0
    );
}

function reservation_balance(int $reservationId): float
{
    $reservation = db_one('SELECT Total, FK_ID_EstadoReserva FROM reservas WHERE ID_Reserva = :id', [':id' => $reservationId]);
    if ($reservation === null) {
        return 0.0;
    }
    $paid = reservation_paid($reservationId);
    return max(0.0, (float) $reservation['Total'] - $paid);
}

function reservation_status_id(string $name): int
{
    return (int) db_val('SELECT ID_EstadoReserva FROM estados_reserva WHERE Nombre = :name LIMIT 1', [':name' => $name], 0);
}

function sync_cuenta_corriente(int $clientId): void
{
    $total = (float) db_val(
        "SELECT COALESCE(SUM(r.Total), 0) FROM reservas r
         WHERE r.FK_ID_Cliente = :id
           AND r.FK_ID_EstadoReserva <> (SELECT ID_EstadoReserva FROM estados_reserva WHERE Nombre = 'Cancelada' LIMIT 1)",
        [':id' => $clientId],
        0
    );
    $paid = (float) db_val(
        "SELECT COALESCE(SUM(p.Importe), 0)
         FROM pagos_reserva p
         INNER JOIN reservas r ON r.ID_Reserva = p.FK_ID_Reserva
         WHERE r.FK_ID_Cliente = :id AND p.Estado = 'aprobado'",
        [':id' => $clientId],
        0
    );
    $refunded = (float) db_val(
        'SELECT COALESCE(SUM(c.ImporteDevuelto), 0)
         FROM cancelaciones c
         INNER JOIN reservas r ON r.ID_Reserva = c.FK_ID_Reserva
         WHERE r.FK_ID_Cliente = :id',
        [':id' => $clientId],
        0
    );
    $saldo = round($total - ($paid - $refunded), 2);
    db_exec(
        'INSERT INTO cuentas_corrientes (FK_ID_Cliente, Saldo, FechaActualizacion)
         VALUES (:cliente, :saldo, NOW())
         ON DUPLICATE KEY UPDATE Saldo = VALUES(Saldo), FechaActualizacion = NOW()',
        [':cliente' => $clientId, ':saldo' => $saldo]
    );
}

function package_current_price(int $packageId): array
{
    $base = (float) db_val(
        "SELECT Importe FROM precios
         WHERE FK_ID_Paquete = :id AND FechaDesde <= CURDATE() AND (FechaHasta IS NULL OR FechaHasta >= CURDATE())
         ORDER BY FIELD(Tipo, 'oferta', 'temporada', 'base'), FechaDesde DESC LIMIT 1",
        [':id' => $packageId],
        0
    );
    if ($base <= 0) {
        $base = (float) db_val('SELECT PrecioBase FROM paquetes WHERE ID_Paquete = :id', [':id' => $packageId], 0);
    }

    $promo = db_one(
        "SELECT pr.*
         FROM promocion_paquete pp
         INNER JOIN promociones pr ON pr.ID_Promocion = pp.FK_ID_Promocion
         WHERE pp.FK_ID_Paquete = :id
           AND pr.Estado = 'activa'
           AND CURDATE() BETWEEN pr.FechaInicio AND pr.FechaFin
         ORDER BY pr.FechaFin ASC LIMIT 1",
        [':id' => $packageId]
    );

    $final = $base;
    $discountLabel = null;
    if ($promo !== null) {
        if ($promo['TipoDescuento'] === 'porcentaje') {
            $final = $base * (1 - ((float) $promo['ValorDescuento'] / 100));
            $discountLabel = rtrim(rtrim(number_format((float) $promo['ValorDescuento'], 2, ',', '.'), '0'), ',') . '% off';
        } else {
            $final = max(0, $base - (float) $promo['ValorDescuento']);
            $discountLabel = money($promo['ValorDescuento']) . ' off';
        }
        $final = round($final, 2);
    }

    return [
        'base' => $base,
        'final' => $final,
        'promo' => $promo,
        'discount_label' => $discountLabel,
        'has_discount' => $promo !== null && $final < $base,
    ];
}

function available_departures(int $packageId, bool $onlyAvailable = true): array
{
    $sql = "SELECT * FROM salidas WHERE FK_ID_Paquete = :id AND FechaSalida >= CURDATE()";
    if ($onlyAvailable) {
        $sql .= " AND Estado = 'abierta' AND CuposDisponibles > 0";
    }
    $sql .= ' ORDER BY FechaSalida ASC';
    return db_all($sql, [':id' => $packageId]);
}

function get_package(int $id): ?array
{
    return db_one(
        "SELECT p.*, (SELECT COUNT(*) FROM reserva_detalle rd WHERE rd.FK_ID_Salida IN (SELECT ID_Salida FROM salidas WHERE FK_ID_Paquete = p.ID_Paquete)) AS reservas_count
         FROM paquetes p WHERE p.ID_Paquete = :id",
        [':id' => $id]
    );
}

function package_destinations(int $packageId): array
{
    return db_all(
        'SELECT d.*, pd.Noches, pd.Orden, di.Descripcion, di.Clima, di.Moneda, di.Recomendaciones
         FROM paquete_destino pd
         INNER JOIN destino d ON d.ID_Destino = pd.FK_ID_Destino
         LEFT JOIN destino_informacion di ON di.FK_ID_Destino = d.ID_Destino
         WHERE pd.FK_ID_Paquete = :id
         ORDER BY pd.Orden ASC',
        [':id' => $packageId]
    );
}

function package_itinerary(int $packageId): array
{
    return db_all('SELECT * FROM paquete_itinerario WHERE FK_ID_Paquete = :id ORDER BY Dia ASC', [':id' => $packageId]);
}

function package_services(int $packageId): array
{
    return db_all(
        'SELECT s.*, ps.Incluido
         FROM paquete_servicio ps
         INNER JOIN servicios s ON s.ID_Servicio = ps.FK_ID_Servicio
         WHERE ps.FK_ID_Paquete = :id
         ORDER BY s.Nombre ASC',
        [':id' => $packageId]
    );
}

function package_categories(int $packageId): array
{
    return db_all(
        'SELECT cp.* FROM paquete_categoria pc
         INNER JOIN categorias_paquete cp ON cp.ID_CategoriaPaquete = pc.FK_ID_CategoriaPaquete
         WHERE pc.FK_ID_Paquete = :id ORDER BY cp.Nombre',
        [':id' => $packageId]
    );
}

function active_promotions(int $limit = 10): array
{
    return db_all(
        "SELECT * FROM promociones
         WHERE Estado = 'activa' AND CURDATE() BETWEEN FechaInicio AND FechaFin
         ORDER BY FechaFin ASC LIMIT " . max(1, $limit)
    );
}

function payment_methods(): array
{
    return db_all('SELECT * FROM metodopago ORDER BY Metodo ASC');
}

function currencies(): array
{
    return db_all('SELECT * FROM monedas ORDER BY Codigo ASC');
}

function get_roles(): array
{
    return db_all('SELECT * FROM roles ORDER BY ID_Rol ASC');
}

function get_permissions(): array
{
    return db_all('SELECT * FROM permisos ORDER BY ID_Permiso ASC');
}

function reservation_details(int $reservationId): array
{
    return db_all(
        'SELECT rd.*, s.FechaSalida, s.FechaRegreso, p.Nombre AS PaqueteNombre, p.Codigo AS PaqueteCodigo,
                v.FechaSalida AS ViajeFechaSalida
         FROM reserva_detalle rd
         LEFT JOIN salidas s ON s.ID_Salida = rd.FK_ID_Salida
         LEFT JOIN paquetes p ON p.ID_Paquete = s.FK_ID_Paquete
         LEFT JOIN viajes v ON v.ID_Viaje = rd.FK_ID_Viaje
         WHERE rd.FK_ID_Reserva = :id
         ORDER BY rd.ID_ReservaDetalle ASC',
        [':id' => $reservationId]
    );
}

function reservation_passengers(int $reservationId): array
{
    return db_all(
        'SELECT rp.*, p.DNI, p.Nombre, p.Apellido, p.Telefono, p.Nacionalidad
         FROM reserva_pasajero rp
         INNER JOIN pasajero p ON p.ID_Pasajero = rp.FK_ID_Pasajero
         WHERE rp.FK_ID_Reserva = :id
         ORDER BY rp.Titular DESC, p.Apellido ASC, p.Nombre ASC',
        [':id' => $reservationId]
    );
}

function reservation_payments(int $reservationId): array
{
    return db_all(
        'SELECT p.*, m.Metodo, mo.Codigo, mo.Nombre AS MonedaNombre
         FROM pagos_reserva p
         INNER JOIN metodopago m ON m.ID_Metodo = p.FK_ID_Metodo
         INNER JOIN monedas mo ON mo.ID_Moneda = p.FK_ID_Moneda
         WHERE p.FK_ID_Reserva = :id
         ORDER BY p.FechaPago DESC, p.ID_PagoReserva DESC',
        [':id' => $reservationId]
    );
}

function check_availability(int $salidaId, int $quantity): bool
{
    $available = (int) db_val('SELECT CuposDisponibles FROM salidas WHERE ID_Salida = :id', [':id' => $salidaId], 0);
    return $available >= $quantity;
}

function passenger_options(): array
{
    return db_all(
        'SELECT p.*, u.Email FROM pasajero p
         LEFT JOIN pasajeroonline u ON u.ID_PasajeroOnline = p.FK_ID_PasajeroOnline
         ORDER BY p.Apellido ASC, p.Nombre ASC'
    );
}

function all_packages(bool $onlyPublished = false): array
{
    $sql = 'SELECT * FROM paquetes';
    if ($onlyPublished) {
        $sql .= " WHERE Estado = 'publicado'";
    }
    $sql .= ' ORDER BY Nombre ASC';
    return db_all($sql);
}

function all_destinations(): array
{
    return db_all('SELECT * FROM destino ORDER BY NombreUbicacion ASC');
}
