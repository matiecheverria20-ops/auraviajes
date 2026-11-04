<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/data.php';
require_login();

$cancelState = reservation_status_id('Cancelada');
$activeClients = (int) db_val("SELECT COUNT(*) FROM clientes WHERE Estado = 'activo'", [], 0);
$monthReservations = (int) db_val('SELECT COUNT(*) FROM reservas WHERE YEAR(FechaReserva) = YEAR(CURDATE()) AND MONTH(FechaReserva) = MONTH(CURDATE())', [], 0);
$sold = (float) db_val('SELECT COALESCE(SUM(Total), 0) FROM reservas WHERE FK_ID_EstadoReserva <> :cancel', [':cancel' => $cancelState], 0);
$collected = (float) db_val("SELECT COALESCE(SUM(Importe), 0) FROM pagos_reserva WHERE Estado = 'aprobado'", [], 0);
$pending = max(0, $sold - $collected);
$pendingReservations = (int) db_val(
    "SELECT COUNT(*) FROM reservas r
     WHERE r.FK_ID_EstadoReserva <> :cancel
       AND r.Total > (SELECT COALESCE(SUM(p.Importe), 0) FROM pagos_reserva p WHERE p.FK_ID_Reserva = r.ID_Reserva AND p.Estado = 'aprobado') + 0.01",
    [':cancel' => $cancelState],
    0
);
$newMessages = (int) db_val("SELECT COUNT(*) FROM formularios_contacto WHERE Estado = 'nuevo'", [], 0);

$recentReservations = db_all(
    "SELECT r.ID_Reserva, r.CodigoReserva, r.FechaReserva, r.Total, er.Nombre AS Estado,
            c.CodigoCliente, u.Email, p.Nombre, p.Apellido,
            (SELECT COALESCE(SUM(pg.Importe), 0) FROM pagos_reserva pg WHERE pg.FK_ID_Reserva = r.ID_Reserva AND pg.Estado = 'aprobado') AS Pagado
     FROM reservas r
     INNER JOIN estados_reserva er ON er.ID_EstadoReserva = r.FK_ID_EstadoReserva
     INNER JOIN clientes c ON c.ID_Cliente = r.FK_ID_Cliente
     LEFT JOIN pasajeroonline u ON u.ID_PasajeroOnline = c.FK_ID_PasajeroOnline
     LEFT JOIN pasajero p ON p.ID_Pasajero = (
         SELECT MIN(p2.ID_Pasajero) FROM pasajero p2 WHERE p2.FK_ID_PasajeroOnline = u.ID_PasajeroOnline
     )
     ORDER BY r.FechaReserva DESC LIMIT 6"
);

$recentPayments = db_all(
    "SELECT pg.*, m.Metodo, r.CodigoReserva, c.CodigoCliente, u.Email, p.Nombre, p.Apellido
     FROM pagos_reserva pg
     INNER JOIN metodopago m ON m.ID_Metodo = pg.FK_ID_Metodo
     INNER JOIN reservas r ON r.ID_Reserva = pg.FK_ID_Reserva
     INNER JOIN clientes c ON c.ID_Cliente = r.FK_ID_Cliente
     LEFT JOIN pasajeroonline u ON u.ID_PasajeroOnline = c.FK_ID_PasajeroOnline
     LEFT JOIN pasajero p ON p.ID_Pasajero = (
         SELECT MIN(p2.ID_Pasajero) FROM pasajero p2 WHERE p2.FK_ID_PasajeroOnline = u.ID_PasajeroOnline
     )
     ORDER BY pg.FechaPago DESC, pg.ID_PagoReserva DESC LIMIT 6"
);

$upcomingDepartures = db_all(
    "SELECT s.*, paq.Nombre AS Paquete
     FROM salidas s
     INNER JOIN paquetes paq ON paq.ID_Paquete = s.FK_ID_Paquete
     WHERE s.FechaSalida >= CURDATE() AND s.Estado IN ('abierta', 'completa')
     ORDER BY s.FechaSalida ASC LIMIT 6"
);

$upcomingTrips = db_all(
    "SELECT v.ID_Viaje, v.FechaSalida, v.Estado, r.DistanciaTotal,
            d1.NombreUbicacion AS Origen, d2.NombreUbicacion AS Destino, pr.Nombre AS Proveedor
     FROM viajes v
     INNER JOIN rutas r ON r.ID_Ruta = v.FK_ID_Ruta
     INNER JOIN destino d1 ON d1.ID_Destino = r.ID_Inicio
     INNER JOIN destino d2 ON d2.ID_Destino = r.ID_Destino
     INNER JOIN proveedores pr ON pr.ID_Proveedor = v.FK_ID_Proveedor
     WHERE v.FechaSalida >= NOW() AND v.Estado IN ('programado', 'en_curso')
     ORDER BY v.FechaSalida ASC LIMIT 5"
);

$docAlerts = db_all(
    "SELECT dc.*, td.Nombre AS Tipo, c.ID_Cliente, u.Email, p.Nombre, p.Apellido
     FROM documentos_cliente dc
     INNER JOIN tipos_documento td ON td.ID_TipoDocumento = dc.FK_ID_TipoDocumento
     INNER JOIN clientes c ON c.ID_Cliente = dc.FK_ID_Cliente
     LEFT JOIN pasajeroonline u ON u.ID_PasajeroOnline = c.FK_ID_PasajeroOnline
     LEFT JOIN pasajero p ON p.ID_Pasajero = (
         SELECT MIN(p2.ID_Pasajero) FROM pasajero p2 WHERE p2.FK_ID_PasajeroOnline = u.ID_PasajeroOnline
     )
     WHERE dc.FechaVencimiento IS NOT NULL
       AND dc.FechaVencimiento BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 90 DAY)
     ORDER BY dc.FechaVencimiento ASC LIMIT 6"
);

$statusCounts = db_all(
    "SELECT er.Nombre, COUNT(r.ID_Reserva) AS Total
     FROM estados_reserva er
     LEFT JOIN reservas r ON r.FK_ID_EstadoReserva = er.ID_EstadoReserva
     GROUP BY er.ID_EstadoReserva, er.Nombre
     ORDER BY Total DESC"
);
$statusTotal = array_sum(array_map(static fn(array $row): int => (int) $row['Total'], $statusCounts));

$monthly = [];
for ($i = 5; $i >= 0; $i--) {
    $date = new DateTime('first day of this month');
    $date->modify("-{$i} months");
    $monthly[$date->format('Y-m')] = [
        'label' => ['ene', 'feb', 'mar', 'abr', 'may', 'jun', 'jul', 'ago', 'sep', 'oct', 'nov', 'dic'][(int) $date->format('n') - 1] . ' ' . $date->format('y'),
        'value' => 0.0,
    ];
}
$salesRows = db_all(
    "SELECT DATE_FORMAT(FechaPago, '%Y-%m') AS Periodo, COALESCE(SUM(Importe), 0) AS Total
     FROM pagos_reserva
     WHERE Estado = 'aprobado' AND FechaPago >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
     GROUP BY Periodo ORDER BY Periodo"
);
foreach ($salesRows as $row) {
    if (isset($monthly[$row['Periodo']])) {
        $monthly[$row['Periodo']]['value'] = (float) $row['Total'];
    }
}
$maxMonthly = max(1.0, ...array_values(array_map(static fn(array $row): float => (float) $row['value'], $monthly)));

$recentActivity = db_all(
    "SELECT a.*, u.Email
     FROM auditoria a
     LEFT JOIN pasajeroonline u ON u.ID_PasajeroOnline = a.FK_ID_PasajeroOnline
     ORDER BY a.Fecha DESC, a.ID_Auditoria DESC LIMIT 6"
);

$pageTitle = 'Panel de control';
$activeSection = 'panel';
$breadcrumbs = ['Panel' => ''];
$pageActions = '<a class="btn btn-accent" href="' . e(base_url('admin/reserva_form.php')) . '">Nueva reserva</a>';
require __DIR__ . '/../includes/admin_header.php';
?>
<section class="stat-grid">
    <div class="stat-card accent">
        <span class="stat-label">Ventas registradas</span>
        <span class="stat-value"><?= e(money($sold)) ?></span>
        <span class="stat-hint"><?= e((string) $monthReservations) ?> reservas este mes</span>
    </div>
    <div class="stat-card">
        <span class="stat-label">Cobrado</span>
        <span class="stat-value"><?= e(money($collected)) ?></span>
        <span class="stat-hint">Pagos aprobados acumulados</span>
    </div>
    <div class="stat-card coral">
        <span class="stat-label">Saldo por cobrar</span>
        <span class="stat-value"><?= e(money($pending)) ?></span>
        <span class="stat-hint"><?= e((string) $pendingReservations) ?> reservas con saldo pendiente</span>
    </div>
    <div class="stat-card">
        <span class="stat-label">Clientes activos</span>
        <span class="stat-value"><?= number_format($activeClients, 0, ',', '.') ?></span>
        <span class="stat-hint"><?= e((string) $newMessages) ?> consultas nuevas</span>
    </div>
</section>

<div class="dashboard-grid">
    <div class="stack">
        <div class="card">
            <div class="card-header">
                <h2>Últimas reservas</h2>
                <a class="small" href="<?= e(base_url('admin/reservas.php')) ?>">Ver todas</a>
            </div>
            <div class="table-wrap">
                <table class="data-table">
                    <thead>
                    <tr>
                        <th>Reserva</th>
                        <th>Cliente</th>
                        <th>Fecha</th>
                        <th class="num">Total</th>
                        <th>Estado</th>
                        <th></th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php if ($recentReservations === []): ?>
                        <tr><td colspan="6" class="table-empty">Todavía no hay reservas registradas.</td></tr>
                    <?php endif; ?>
                    <?php foreach ($recentReservations as $row): ?>
                        <?php $clientName = trim((string) ($row['Nombre'] ?? '') . ' ' . (string) ($row['Apellido'] ?? '')) ?: ($row['Email'] ?? $row['CodigoCliente']); ?>
                        <tr>
                            <td><strong><?= e($row['CodigoReserva']) ?></strong></td>
                            <td>
                                <div class="table-user">
                                    <span class="avatar"><?= e(initials($clientName)) ?></span>
                                    <span><strong><?= e($clientName) ?></strong><small><?= e($row['CodigoCliente']) ?></small></span>
                                </div>
                            </td>
                            <td><?= e(fecha($row['FechaReserva'])) ?></td>
                            <td class="num"><?= e(money($row['Total'])) ?></td>
                            <td><span class="<?= e(badge_class($row['Estado'])) ?>"><?= e($row['Estado']) ?></span></td>
                            <td class="actions"><a class="btn btn-ghost btn-sm" href="<?= e(base_url('admin/reserva_ver.php?id=' . (int) $row['ID_Reserva'])) ?>">Ver</a></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h2>Cobros de los últimos 6 meses</h2>
                <span class="small muted">Pagos aprobados</span>
            </div>
            <div class="card-body">
                <div style="display:flex;align-items:flex-end;gap:1rem;height:180px;padding-top:1rem;">
                    <?php foreach ($monthly as $month): ?>
                        <?php $height = max(4, (int) round(((float) $month['value'] / $maxMonthly) * 100)); ?>
                        <div style="flex:1;display:flex;flex-direction:column;align-items:center;gap:.4rem;height:100%;justify-content:flex-end;">
                            <span class="small muted nowrap"><?= e(money($month['value'], false)) ?></span>
                            <div style="width:100%;max-width:52px;height:<?= $height ?>%;background:linear-gradient(180deg,#2a93b5,#005778);border-radius:8px 8px 4px 4px;" title="<?= e($month['label'] . ': ' . money($month['value'])) ?>"></div>
                            <span class="small muted"><?= e($month['label']) ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h2>Últimos pagos</h2>
                <a class="small" href="<?= e(base_url('admin/pagos.php')) ?>">Ver todos</a>
            </div>
            <div class="table-wrap">
                <table class="data-table">
                    <thead><tr><th>Reserva</th><th>Cliente</th><th>Método</th><th class="num">Importe</th><th>Estado</th></tr></thead>
                    <tbody>
                    <?php if ($recentPayments === []): ?>
                        <tr><td colspan="5" class="table-empty">Sin pagos registrados.</td></tr>
                    <?php endif; ?>
                    <?php foreach ($recentPayments as $pay): ?>
                        <?php $payClient = trim((string) ($pay['Nombre'] ?? '') . ' ' . (string) ($pay['Apellido'] ?? '')) ?: $pay['Email']; ?>
                        <tr>
                            <td><a href="<?= e(base_url('admin/reserva_ver.php?id=' . (int) $pay['FK_ID_Reserva'])) ?>"><?= e($pay['CodigoReserva']) ?></a></td>
                            <td><?= e($payClient) ?></td>
                            <td><?= e($pay['Metodo']) ?></td>
                            <td class="num"><?= e(money($pay['Importe'])) ?></td>
                            <td><span class="<?= e(badge_class($pay['Estado'])) ?>"><?= e($pay['Estado']) ?></span></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="stack">
        <div class="card">
            <div class="card-header"><h2>Reservas por estado</h2></div>
            <div class="card-body">
                <?php if ($statusTotal === 0): ?>
                    <p class="muted small mb-0">Sin datos para mostrar.</p>
                <?php else: ?>
                    <?php foreach ($statusCounts as $status): ?>
                        <?php $pct = $statusTotal > 0 ? round(((int) $status['Total'] / $statusTotal) * 100) : 0; ?>
                        <div style="margin-bottom:.8rem;">
                            <div class="flex-between" style="margin-bottom:.25rem;">
                                <span class="small"><?= e($status['Nombre']) ?></span>
                                <span class="small muted"><?= e((string) $status['Total']) ?> · <?= $pct ?>%</span>
                            </div>
                            <div class="progress <?= $status['Nombre'] === 'Cancelada' ? 'coral' : '' ?>"><span style="width:<?= $pct ?>%"></span></div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h2>Próximas salidas</h2>
                <a class="small" href="<?= e(base_url('admin/paquetes.php')) ?>">Paquetes</a>
            </div>
            <div class="card-body">
                <?php if ($upcomingDepartures === []): ?>
                    <p class="muted small mb-0">No hay salidas programadas.</p>
                <?php endif; ?>
                <?php foreach ($upcomingDepartures as $dep): ?>
                    <div class="flex-between" style="padding:.55rem 0;border-bottom:1px dashed var(--line);">
                        <div>
                            <strong class="small"><?= e($dep['Paquete']) ?></strong>
                            <div class="small muted"><?= e(fecha($dep['FechaSalida'])) ?> al <?= e(fecha($dep['FechaRegreso'])) ?></div>
                        </div>
                        <span class="badge <?= $dep['CuposDisponibles'] > 0 ? 'badge-success' : 'badge-danger' ?>"><?= (int) $dep['CuposDisponibles'] ?> cupos</span>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="card">
            <div class="card-header"><h2>Próximos viajes</h2></div>
            <div class="card-body">
                <?php if ($upcomingTrips === []): ?>
                    <p class="muted small mb-0">No hay viajes programados.</p>
                <?php endif; ?>
                <?php foreach ($upcomingTrips as $trip): ?>
                    <div style="padding:.55rem 0;border-bottom:1px dashed var(--line);">
                        <strong class="small"><?= e($trip['Origen']) ?> → <?= e($trip['Destino']) ?></strong>
                        <div class="small muted"><?= e(fecha($trip['FechaSalida'], true)) ?> · <?= e($trip['Proveedor']) ?></div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="card">
            <div class="card-header"><h2>Alertas de documentación</h2></div>
            <div class="card-body">
                <?php if ($docAlerts === []): ?>
                    <p class="muted small mb-0">Sin vencimientos en los próximos 90 días.</p>
                <?php endif; ?>
                <?php foreach ($docAlerts as $doc): ?>
                    <?php $docName = trim((string) ($doc['Nombre'] ?? '') . ' ' . (string) ($doc['Apellido'] ?? '')) ?: $doc['Email']; ?>
                    <div style="padding:.55rem 0;border-bottom:1px dashed var(--line);">
                        <strong class="small"><?= e($docName) ?></strong>
                        <div class="small muted"><?= e($doc['Tipo']) ?> vence el <?= e(fecha($doc['FechaVencimiento'])) ?></div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="card">
            <div class="card-header"><h2>Actividad reciente</h2></div>
            <div class="card-body">
                <?php if ($recentActivity === []): ?>
                    <p class="muted small mb-0">Sin actividad registrada.</p>
                <?php endif; ?>
                <?php foreach ($recentActivity as $log): ?>
                    <div style="padding:.45rem 0;border-bottom:1px dashed var(--line);">
                        <strong class="small"><?= e($log['Operacion']) ?> · <?= e($log['TablaAfectada']) ?></strong>
                        <div class="small muted"><?= e($log['Email'] ?? 'Sistema') ?> · <?= e(fecha($log['Fecha'], true)) ?></div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>
<?php require __DIR__ . '/../includes/admin_footer.php'; ?>
