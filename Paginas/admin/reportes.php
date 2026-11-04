<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/data.php';
require_permission('ver_reportes');

$from = get_string('desde', date('Y-m-d', strtotime('first day of january this year')));
$to = get_string('hasta', date('Y-m-d'));
if ($from === '' || $to === '' || $from > $to) {
    $from = date('Y-m-d', strtotime('first day of january this year'));
    $to = date('Y-m-d');
}
$cancelState = reservation_status_id('Cancelada');
$baseParams = [':desde' => $from . ' 00:00:00', ':hasta' => $to . ' 23:59:59'];
$params = $baseParams + [':cancel' => $cancelState];

$summary = db_one(
    "SELECT COUNT(*) AS Reservas,
            COALESCE(SUM(CASE WHEN FK_ID_EstadoReserva <> " . (int) $cancelState . " THEN Total ELSE 0 END), 0) AS Ventas,
            COALESCE(AVG(CASE WHEN FK_ID_EstadoReserva <> " . (int) $cancelState . " THEN Total END), 0) AS Ticket
     FROM reservas WHERE FechaReserva BETWEEN :desde AND :hasta",
    $baseParams
) ?? ['Reservas' => 0, 'Ventas' => 0, 'Ticket' => 0];
$collected = (float) db_val(
    "SELECT COALESCE(SUM(Importe), 0) FROM pagos_reserva WHERE Estado = 'aprobado' AND FechaPago BETWEEN :desde AND :hasta",
    [':desde' => $params[':desde'], ':hasta' => $params[':hasta']],
    0
);
$sales = (float) $summary['Ventas'];
$pending = max(0, $sales - $collected);

$statusRows = db_all(
    'SELECT er.Nombre, COUNT(r.ID_Reserva) AS Total, COALESCE(SUM(r.Total), 0) AS Importe
     FROM estados_reserva er
     LEFT JOIN reservas r ON r.FK_ID_EstadoReserva = er.ID_EstadoReserva AND r.FechaReserva BETWEEN :desde AND :hasta
     GROUP BY er.ID_EstadoReserva, er.Nombre ORDER BY Total DESC',
    [':desde' => $params[':desde'], ':hasta' => $params[':hasta']]
);

$topPackages = db_all(
    'SELECT p.ID_Paquete, p.Nombre, p.Codigo, SUM(rd.Cantidad) AS Pasajeros, SUM(rd.Subtotal) AS Ingresos,
            COUNT(DISTINCT rd.FK_ID_Reserva) AS Reservas
     FROM reserva_detalle rd
     INNER JOIN reservas r ON r.ID_Reserva = rd.FK_ID_Reserva
     INNER JOIN salidas s ON s.ID_Salida = rd.FK_ID_Salida
     INNER JOIN paquetes p ON p.ID_Paquete = s.FK_ID_Paquete
     WHERE r.FechaReserva BETWEEN :desde AND :hasta AND r.FK_ID_EstadoReserva <> :cancel
     GROUP BY p.ID_Paquete, p.Nombre, p.Codigo
     ORDER BY Ingresos DESC LIMIT 8',
    $params
);

$topClients = db_all(
    "SELECT c.ID_Cliente, c.CodigoCliente, u.Email, p.Nombre, p.Apellido, COUNT(r.ID_Reserva) AS Reservas, SUM(r.Total) AS Ingresos
     FROM reservas r
     INNER JOIN clientes c ON c.ID_Cliente = r.FK_ID_Cliente
     LEFT JOIN pasajeroonline u ON u.ID_PasajeroOnline = c.FK_ID_PasajeroOnline
     LEFT JOIN pasajero p ON p.ID_Pasajero = (
         SELECT MIN(p2.ID_Pasajero) FROM pasajero p2 WHERE p2.FK_ID_PasajeroOnline = u.ID_PasajeroOnline
     )
     WHERE r.FechaReserva BETWEEN :desde AND :hasta AND r.FK_ID_EstadoReserva <> :cancel
     GROUP BY c.ID_Cliente, c.CodigoCliente, u.Email, p.Nombre, p.Apellido
     ORDER BY Ingresos DESC LIMIT 8",
    $params
);

$byMethod = db_all(
    "SELECT m.Metodo, COUNT(pg.ID_PagoReserva) AS Operaciones, COALESCE(SUM(pg.Importe), 0) AS Total
     FROM pagos_reserva pg
     INNER JOIN metodopago m ON m.ID_Metodo = pg.FK_ID_Metodo
     WHERE pg.Estado = 'aprobado' AND pg.FechaPago BETWEEN :desde AND :hasta
     GROUP BY m.ID_Metodo, m.Metodo ORDER BY Total DESC",
    [':desde' => $params[':desde'], ':hasta' => $params[':hasta']]
);

$byAgent = db_all(
    "SELECT COALESCE(CONCAT(e.Nombre, ' ', e.Apellido), 'Sin agente') AS Agente,
            COUNT(r.ID_Reserva) AS Reservas, COALESCE(SUM(r.Total), 0) AS Ventas
     FROM reservas r
     LEFT JOIN empleados e ON e.ID_Empleado = r.FK_ID_Empleado
     WHERE r.FechaReserva BETWEEN :desde AND :hasta AND r.FK_ID_EstadoReserva <> :cancel
     GROUP BY r.FK_ID_Empleado, e.Nombre, e.Apellido ORDER BY Ventas DESC",
    $params
);

$byDestination = db_all(
    'SELECT d.NombreUbicacion, COUNT(DISTINCT r.ID_Reserva) AS Reservas, COALESCE(SUM(rd.Subtotal), 0) AS Ingresos
     FROM reserva_detalle rd
     INNER JOIN reservas r ON r.ID_Reserva = rd.FK_ID_Reserva
     INNER JOIN salidas s ON s.ID_Salida = rd.FK_ID_Salida
     INNER JOIN paquete_destino pd ON pd.FK_ID_Paquete = s.FK_ID_Paquete
     INNER JOIN destino d ON d.ID_Destino = pd.FK_ID_Destino
     WHERE r.FechaReserva BETWEEN :desde AND :hasta AND r.FK_ID_EstadoReserva <> :cancel
     GROUP BY d.ID_Destino, d.NombreUbicacion ORDER BY Ingresos DESC LIMIT 8',
    $params
);

$monthly = [];
$cursor = new DateTime(date('Y-m-01', strtotime($from)));
$end = new DateTime(date('Y-m-01', strtotime($to)));
while ($cursor <= $end) {
    $monthly[$cursor->format('Y-m')] = [
        'label' => ['ene', 'feb', 'mar', 'abr', 'may', 'jun', 'jul', 'ago', 'sep', 'oct', 'nov', 'dic'][(int) $cursor->format('n') - 1] . ' ' . $cursor->format('y'),
        'reservas' => 0.0,
        'pagos' => 0.0,
    ];
    $cursor->modify('+1 month');
}
$salesMonth = db_all(
    "SELECT DATE_FORMAT(FechaReserva, '%Y-%m') AS Periodo, SUM(Total) AS Total
     FROM reservas WHERE FechaReserva BETWEEN :desde AND :hasta AND FK_ID_EstadoReserva <> :cancel
     GROUP BY Periodo",
    $params
);
foreach ($salesMonth as $row) {
    if (isset($monthly[$row['Periodo']])) {
        $monthly[$row['Periodo']]['reservas'] = (float) $row['Total'];
    }
}
$paymentsMonth = db_all(
    "SELECT DATE_FORMAT(FechaPago, '%Y-%m') AS Periodo, SUM(Importe) AS Total
     FROM pagos_reserva WHERE Estado = 'aprobado' AND FechaPago BETWEEN :desde AND :hasta GROUP BY Periodo",
    [':desde' => $params[':desde'], ':hasta' => $params[':hasta']]
);
foreach ($paymentsMonth as $row) {
    if (isset($monthly[$row['Periodo']])) {
        $monthly[$row['Periodo']]['pagos'] = (float) $row['Total'];
    }
}
$maxMonthly = max(1.0, ...array_values(array_map(static fn(array $row): float => max((float) $row['reservas'], (float) $row['pagos']), $monthly)));
$statusTotal = max(1, array_sum(array_map(static fn(array $row): int => (int) $row['Total'], $statusRows)));

$pageTitle = 'Reportes';
$activeSection = 'reportes';
$breadcrumbs = ['Panel' => 'admin/index.php', 'Reportes' => ''];
$pageActions = '<button class="btn btn-ghost" type="button" onclick="window.print()">Imprimir reporte</button>';
require __DIR__ . '/../includes/admin_header.php';
?>
<style>
    @media print {
        .admin-sidebar, .admin-topbar, .filters, .page-actions, .btn, .filters-actions { display: none !important; }
        .admin-body { background: #fff; }
        .admin-shell, .admin-frame, .admin-main, .admin-content { display: block !important; margin: 0 !important; padding: 0 !important; }
        .card { box-shadow: none; border: 1px solid #d7e0e5; break-inside: avoid; }
    }
</style>

<div class="card">
    <div class="card-body">
        <form class="filters" method="get" action="<?= e(base_url('admin/reportes.php')) ?>">
            <div class="field"><label for="desde">Desde</label><input type="date" id="desde" name="desde" value="<?= e($from) ?>"></div>
            <div class="field"><label for="hasta">Hasta</label><input type="date" id="hasta" name="hasta" value="<?= e($to) ?>"></div>
            <div class="filters-actions">
                <button class="btn btn-primary" type="submit">Generar reporte</button>
                <a class="btn btn-ghost" href="<?= e(base_url('admin/reportes.php')) ?>">Restablecer</a>
            </div>
        </form>
    </div>
</div>

<section class="stat-grid">
    <div class="stat-card accent"><span class="stat-label">Ventas del período</span><span class="stat-value"><?= e(money($sales)) ?></span><span class="stat-hint"><?= (int) $summary['Reservas'] ?> reservas</span></div>
    <div class="stat-card"><span class="stat-label">Cobrado</span><span class="stat-value"><?= e(money($collected)) ?></span><span class="stat-hint">Pagos aprobados</span></div>
    <div class="stat-card coral"><span class="stat-label">Saldo estimado</span><span class="stat-value"><?= e(money($pending)) ?></span><span class="stat-hint">Ventas menos cobros del período</span></div>
    <div class="stat-card"><span class="stat-label">Ticket promedio</span><span class="stat-value"><?= e(money($summary['Ticket'])) ?></span><span class="stat-hint">Por reserva válida</span></div>
</section>

<div class="detail-grid">
    <div class="stack">
        <div class="card">
            <div class="card-header"><h2>Evolución mensual</h2></div>
            <div class="card-body">
                <?php if ($monthly === []): ?>
                    <p class="muted small mb-0">Sin datos en el período.</p>
                <?php else: ?>
                    <div style="display:flex;align-items:flex-end;gap:.7rem;height:210px;overflow-x:auto;padding-top:.5rem;">
                        <?php foreach ($monthly as $month): ?>
                            <?php $h1 = max(2, (int) round(((float) $month['reservas'] / $maxMonthly) * 100)); $h2 = max(2, (int) round(((float) $month['pagos'] / $maxMonthly) * 100)); ?>
                            <div style="flex:1;min-width:46px;display:flex;flex-direction:column;align-items:center;gap:.3rem;height:100%;justify-content:flex-end;">
                                <div style="display:flex;align-items:flex-end;gap:2px;height:100%;width:100%;justify-content:center;">
                                    <div title="Ventas <?= e(money($month['reservas'])) ?>" style="width:45%;max-width:24px;height:<?= $h1 ?>%;background:#005778;border-radius:5px 5px 2px 2px;"></div>
                                    <div title="Cobros <?= e(money($month['pagos'])) ?>" style="width:45%;max-width:24px;height:<?= $h2 ?>%;background:#ec6b4f;border-radius:5px 5px 2px 2px;"></div>
                                </div>
                                <span class="small muted nowrap"><?= e($month['label']) ?></span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <div class="flex gap-2 small muted" style="margin-top:.8rem;">
                        <span><span style="display:inline-block;width:10px;height:10px;background:#005778;border-radius:2px;"></span> Ventas</span>
                        <span><span style="display:inline-block;width:10px;height:10px;background:#ec6b4f;border-radius:2px;"></span> Cobros</span>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="card">
            <div class="card-header"><h2>Paquetes más vendidos</h2></div>
            <div class="table-wrap">
                <table class="data-table">
                    <thead><tr><th>Paquete</th><th class="num">Reservas</th><th class="num">Pasajeros</th><th class="num">Ingresos</th></tr></thead>
                    <tbody>
                    <?php if ($topPackages === []): ?><tr><td colspan="4" class="table-empty">Sin ventas en el período.</td></tr><?php endif; ?>
                    <?php foreach ($topPackages as $row): ?>
                        <tr>
                            <td><strong><?= e($row['Nombre']) ?></strong><br><small class="muted"><?= e($row['Codigo']) ?></small></td>
                            <td class="num"><?= (int) $row['Reservas'] ?></td>
                            <td class="num"><?= (int) $row['Pasajeros'] ?></td>
                            <td class="num"><?= e(money($row['Ingresos'])) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="card">
            <div class="card-header"><h2>Mejores clientes</h2></div>
            <div class="table-wrap">
                <table class="data-table">
                    <thead><tr><th>Cliente</th><th class="num">Reservas</th><th class="num">Ingresos</th></tr></thead>
                    <tbody>
                    <?php if ($topClients === []): ?><tr><td colspan="3" class="table-empty">Sin ventas en el período.</td></tr><?php endif; ?>
                    <?php foreach ($topClients as $row): ?>
                        <?php $clientName = trim((string) ($row['Nombre'] ?? '') . ' ' . (string) ($row['Apellido'] ?? '')) ?: ($row['Email'] ?? $row['CodigoCliente']); ?>
                        <tr>
                            <td><a href="<?= e(base_url('admin/cliente_ver.php?id=' . (int) $row['ID_Cliente'])) ?>"><?= e($clientName) ?></a><br><small class="muted"><?= e($row['CodigoCliente']) ?></small></td>
                            <td class="num"><?= (int) $row['Reservas'] ?></td>
                            <td class="num"><?= e(money($row['Ingresos'])) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="card">
            <div class="card-header"><h2>Ventas por agente</h2></div>
            <div class="table-wrap">
                <table class="data-table">
                    <thead><tr><th>Agente</th><th class="num">Reservas</th><th class="num">Ventas</th></tr></thead>
                    <tbody>
                    <?php if ($byAgent === []): ?><tr><td colspan="3" class="table-empty">Sin datos.</td></tr><?php endif; ?>
                    <?php foreach ($byAgent as $row): ?>
                        <tr><td><?= e($row['Agente']) ?></td><td class="num"><?= (int) $row['Reservas'] ?></td><td class="num"><?= e(money($row['Ventas'])) ?></td></tr>
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
                <?php foreach ($statusRows as $row): ?>
                    <?php $pct = round(((int) $row['Total'] / $statusTotal) * 100); ?>
                    <div style="margin-bottom:.8rem;">
                        <div class="flex-between" style="margin-bottom:.25rem;"><span class="small"><?= e($row['Nombre']) ?></span><span class="small muted"><?= (int) $row['Total'] ?> · <?= e(money($row['Importe'])) ?></span></div>
                        <div class="progress <?= $row['Nombre'] === 'Cancelada' ? 'coral' : '' ?>"><span style="width:<?= $pct ?>%"></span></div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="card">
            <div class="card-header"><h2>Medios de pago</h2></div>
            <div class="card-body">
                <?php if ($byMethod === []): ?><p class="muted small mb-0">Sin cobros en el período.</p><?php endif; ?>
                <?php foreach ($byMethod as $row): ?>
                    <div class="flex-between" style="padding:.5rem 0;border-bottom:1px dashed var(--line);">
                        <div><strong class="small"><?= e($row['Metodo']) ?></strong><div class="small muted"><?= (int) $row['Operaciones'] ?> operaciones</div></div>
                        <strong class="small"><?= e(money($row['Total'])) ?></strong>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="card">
            <div class="card-header"><h2>Destinos con más ingresos</h2></div>
            <div class="card-body">
                <?php if ($byDestination === []): ?><p class="muted small mb-0">Sin datos.</p><?php endif; ?>
                <?php foreach ($byDestination as $row): ?>
                    <div class="flex-between" style="padding:.5rem 0;border-bottom:1px dashed var(--line);">
                        <div><strong class="small"><?= e($row['NombreUbicacion']) ?></strong><div class="small muted"><?= (int) $row['Reservas'] ?> reservas</div></div>
                        <strong class="small"><?= e(money($row['Ingresos'])) ?></strong>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>
<?php require __DIR__ . '/../includes/admin_footer.php'; ?>
