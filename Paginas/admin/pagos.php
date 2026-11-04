<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/data.php';
require_any_permission(['gestionar_pagos', 'ver_pagos']);

$search = trim(get_string('q'));
$methodId = get_int('metodo');
$state = get_string('estado');
$from = get_string('desde');
$to = get_string('hasta');

$where = [];
$params = [];
if ($search !== '') {
    $where[] = '(r.CodigoReserva LIKE :search OR pg.NroOperacion LIKE :search OR c.CodigoCliente LIKE :search OR u.Email LIKE :search OR p.Nombre LIKE :search OR p.Apellido LIKE :search)';
    $params[':search'] = '%' . $search . '%';
}
if ($methodId > 0) {
    $where[] = 'pg.FK_ID_Metodo = :metodo';
    $params[':metodo'] = $methodId;
}
if (in_array($state, ['pendiente', 'aprobado', 'rechazado', 'anulado'], true)) {
    $where[] = 'pg.Estado = :estado';
    $params[':estado'] = $state;
}
if ($from !== '') {
    $where[] = 'pg.FechaPago >= :desde';
    $params[':desde'] = $from . ' 00:00:00';
}
if ($to !== '') {
    $where[] = 'pg.FechaPago <= :hasta';
    $params[':hasta'] = $to . ' 23:59:59';
}
$whereSql = $where !== [] ? ' WHERE ' . implode(' AND ', $where) : '';
$fromSql = ' FROM pagos_reserva pg
             INNER JOIN reservas r ON r.ID_Reserva = pg.FK_ID_Reserva
             INNER JOIN metodopago m ON m.ID_Metodo = pg.FK_ID_Metodo
             INNER JOIN monedas mo ON mo.ID_Moneda = pg.FK_ID_Moneda
             INNER JOIN clientes c ON c.ID_Cliente = r.FK_ID_Cliente
             LEFT JOIN pasajeroonline u ON u.ID_PasajeroOnline = c.FK_ID_PasajeroOnline
             LEFT JOIN pasajero p ON p.ID_Pasajero = (
                 SELECT MIN(p2.ID_Pasajero) FROM pasajero p2 WHERE p2.FK_ID_PasajeroOnline = u.ID_PasajeroOnline
             )';

$total = (int) db_val('SELECT COUNT(*)' . $fromSql . $whereSql, $params, 0);
$pagination = pagination_data($total, 20);
$payments = db_all(
    'SELECT pg.*, r.CodigoReserva, c.CodigoCliente, u.Email, p.Nombre, p.Apellido, m.Metodo, mo.Codigo
     ' . $fromSql . $whereSql . '
     ORDER BY pg.FechaPago DESC, pg.ID_PagoReserva DESC
     LIMIT ' . (int) $pagination['per_page'] . ' OFFSET ' . (int) $pagination['offset'],
    $params
);
$stats = db_one(
    "SELECT COALESCE(SUM(CASE WHEN pg.Estado = 'aprobado' THEN pg.Importe ELSE 0 END), 0) AS Cobrado,
            COALESCE(SUM(CASE WHEN pg.Estado = 'pendiente' THEN pg.Importe ELSE 0 END), 0) AS Pendiente,
            COUNT(*) AS Cantidad
     " . $fromSql . $whereSql,
    $params
) ?? ['Cobrado' => 0, 'Pendiente' => 0, 'Cantidad' => 0];
$methods = payment_methods();

$pageTitle = 'Pagos';
$activeSection = 'pagos';
$breadcrumbs = ['Panel' => 'admin/index.php', 'Pagos' => ''];
$pageActions = can('gestionar_pagos') ? '<a class="btn btn-accent" href="' . e(base_url('admin/reservas.php?pendientes=1')) . '">Cobrar reserva</a>' : '';
require __DIR__ . '/../includes/admin_header.php';
?>
<div class="mini-stats">
    <span><strong><?= number_format($total, 0, ',', '.') ?></strong> movimientos</span>
    <span><strong><?= e(money($stats['Cobrado'])) ?></strong> cobrado</span>
    <span><strong><?= e(money($stats['Pendiente'])) ?></strong> pendiente</span>
</div>

<div class="card">
    <div class="card-header"><h2>Movimientos de pago</h2></div>
    <div class="card-body">
        <form class="filters" method="get" action="<?= e(base_url('admin/pagos.php')) ?>">
            <div class="field">
                <label for="q">Buscar</label>
                <input type="search" id="q" name="q" value="<?= e($search) ?>" placeholder="Reserva, cliente u operación">
            </div>
            <div class="field" style="max-width:210px;">
                <label for="metodo">Método</label>
                <select id="metodo" name="metodo">
                    <option value="">Todos</option>
                    <?php foreach ($methods as $method): ?>
                        <option value="<?= (int) $method['ID_Metodo'] ?>" <?= $methodId === (int) $method['ID_Metodo'] ? 'selected' : '' ?>><?= e($method['Metodo']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="field" style="max-width:170px;">
                <label for="estado">Estado</label>
                <select id="estado" name="estado">
                    <option value="">Todos</option>
                    <?php foreach (['aprobado', 'pendiente', 'rechazado', 'anulado'] as $option): ?>
                        <option value="<?= e($option) ?>" <?= $state === $option ? 'selected' : '' ?>><?= e(estado_label($option)) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="field"><label for="desde">Desde</label><input type="date" id="desde" name="desde" value="<?= e($from) ?>"></div>
            <div class="field"><label for="hasta">Hasta</label><input type="date" id="hasta" name="hasta" value="<?= e($to) ?>"></div>
            <div class="filters-actions">
                <button class="btn btn-primary" type="submit">Filtrar</button>
                <a class="btn btn-ghost" href="<?= e(base_url('admin/pagos.php')) ?>">Limpiar</a>
            </div>
        </form>
    </div>
    <div class="table-wrap">
        <table class="data-table">
            <thead><tr><th>Fecha</th><th>Reserva</th><th>Cliente</th><th>Método</th><th>Operación</th><th class="num">Importe</th><th>Estado</th><th></th></tr></thead>
            <tbody>
            <?php if ($payments === []): ?>
                <tr><td colspan="8" class="table-empty">No hay pagos con los filtros aplicados.</td></tr>
            <?php endif; ?>
            <?php foreach ($payments as $pay): ?>
                <?php $name = trim((string) ($pay['Nombre'] ?? '') . ' ' . (string) ($pay['Apellido'] ?? '')) ?: ($pay['Email'] ?? $pay['CodigoCliente']); ?>
                <tr>
                    <td><?= e(fecha($pay['FechaPago'], true)) ?></td>
                    <td><a href="<?= e(base_url('admin/reserva_ver.php?id=' . (int) $pay['FK_ID_Reserva'])) ?>"><?= e($pay['CodigoReserva']) ?></a></td>
                    <td><?= e($name) ?></td>
                    <td><?= e($pay['Metodo']) ?></td>
                    <td><?= e($pay['NroOperacion'] ?? '—') ?></td>
                    <td class="num"><?= e(money($pay['Importe'])) ?> <small class="muted"><?= e($pay['Codigo']) ?></small></td>
                    <td><span class="<?= e(badge_class($pay['Estado'])) ?>"><?= e(estado_label($pay['Estado'])) ?></span></td>
                    <td class="actions"><a class="btn btn-ghost btn-sm" href="<?= e(base_url('admin/reserva_ver.php?id=' . (int) $pay['FK_ID_Reserva'])) ?>">Ver reserva</a></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <div class="card-footer"><?= pagination_links($pagination) ?></div>
</div>
<?php require __DIR__ . '/../includes/admin_footer.php'; ?>
