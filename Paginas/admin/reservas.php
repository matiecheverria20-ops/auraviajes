<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/data.php';
require_any_permission(['gestionar_reservas', 'ver_reservas']);

$search = trim(get_string('q'));
$status = get_int('estado');
$clientId = get_int('cliente');
$from = get_string('desde');
$to = get_string('hasta');
$onlyPending = get_string('pendientes') === '1';

$where = [];
$params = [];
if ($search !== '') {
    $where[] = '(r.CodigoReserva LIKE :search OR c.CodigoCliente LIKE :search OR u.Email LIKE :search OR p.Nombre LIKE :search OR p.Apellido LIKE :search)';
    $params[':search'] = '%' . $search . '%';
}
if ($status > 0) {
    $where[] = 'r.FK_ID_EstadoReserva = :estado';
    $params[':estado'] = $status;
}
if ($clientId > 0) {
    $where[] = 'r.FK_ID_Cliente = :cliente';
    $params[':cliente'] = $clientId;
}
if ($from !== '') {
    $where[] = 'r.FechaReserva >= :desde';
    $params[':desde'] = $from . ' 00:00:00';
}
if ($to !== '') {
    $where[] = 'r.FechaReserva <= :hasta';
    $params[':hasta'] = $to . ' 23:59:59';
}
if ($onlyPending) {
    $where[] = "r.Total > (SELECT COALESCE(SUM(pg.Importe), 0) FROM pagos_reserva pg WHERE pg.FK_ID_Reserva = r.ID_Reserva AND pg.Estado = 'aprobado') + 0.01";
    $where[] = "r.FK_ID_EstadoReserva <> (SELECT ID_EstadoReserva FROM estados_reserva WHERE Nombre = 'Cancelada' LIMIT 1)";
}
$whereSql = $where !== [] ? ' WHERE ' . implode(' AND ', $where) : '';
$fromSql = ' FROM reservas r
             INNER JOIN estados_reserva er ON er.ID_EstadoReserva = r.FK_ID_EstadoReserva
             INNER JOIN clientes c ON c.ID_Cliente = r.FK_ID_Cliente
             LEFT JOIN pasajeroonline u ON u.ID_PasajeroOnline = c.FK_ID_PasajeroOnline
             LEFT JOIN pasajero p ON p.ID_Pasajero = (
                 SELECT MIN(p2.ID_Pasajero) FROM pasajero p2 WHERE p2.FK_ID_PasajeroOnline = u.ID_PasajeroOnline
             )';

$total = (int) db_val('SELECT COUNT(*)' . $fromSql . $whereSql, $params, 0);
$pagination = pagination_data($total, 15);
$reservations = db_all(
    "SELECT r.*, er.Nombre AS Estado, c.CodigoCliente, u.Email, p.Nombre, p.Apellido,
            (SELECT COALESCE(SUM(pg.Importe), 0) FROM pagos_reserva pg WHERE pg.FK_ID_Reserva = r.ID_Reserva AND pg.Estado = 'aprobado') AS Pagado,
            (SELECT GROUP_CONCAT(DISTINCT paq.Nombre SEPARATOR ' · ') FROM reserva_detalle rd INNER JOIN salidas s ON s.ID_Salida = rd.FK_ID_Salida INNER JOIN paquetes paq ON paq.ID_Paquete = s.FK_ID_Paquete WHERE rd.FK_ID_Reserva = r.ID_Reserva) AS Paquetes
     " . $fromSql . $whereSql . '
     ORDER BY r.FechaReserva DESC
     LIMIT ' . (int) $pagination['per_page'] . ' OFFSET ' . (int) $pagination['offset'],
    $params
);
$statuses = db_all('SELECT * FROM estados_reserva ORDER BY ID_EstadoReserva');
$totals = db_one(
    "SELECT COALESCE(SUM(r.Total), 0) AS Total,
            COALESCE(SUM(CASE WHEN r.FK_ID_EstadoReserva = (SELECT ID_EstadoReserva FROM estados_reserva WHERE Nombre = 'Cancelada' LIMIT 1) THEN 0 ELSE r.Total END), 0) AS Valido,
            COUNT(*) AS Cantidad
     " . $fromSql . $whereSql,
    $params
) ?? ['Total' => 0, 'Valido' => 0, 'Cantidad' => 0];

$pageTitle = 'Reservas';
$activeSection = 'reservas';
$breadcrumbs = ['Panel' => 'admin/index.php', 'Reservas' => ''];
$pageActions = can('gestionar_reservas') ? '<a class="btn btn-accent" href="' . e(base_url('admin/reserva_form.php')) . '">Nueva reserva</a>' : '';
require __DIR__ . '/../includes/admin_header.php';
?>
<div class="mini-stats">
    <span><strong><?= number_format($total, 0, ',', '.') ?></strong> resultados</span>
    <span><strong><?= e(money($totals['Valido'])) ?></strong> facturado</span>
    <span><strong><?= e(money($totals['Total'])) ?></strong> total incl. canceladas</span>
</div>

<div class="card">
    <div class="card-header"><h2>Gestión de reservas</h2></div>
    <div class="card-body">
        <form class="filters" method="get" action="<?= e(base_url('admin/reservas.php')) ?>">
            <div class="field">
                <label for="q">Buscar</label>
                <input type="search" id="q" name="q" value="<?= e($search) ?>" placeholder="Código, cliente, DNI o email">
            </div>
            <div class="field" style="max-width:190px;">
                <label for="estado">Estado</label>
                <select id="estado" name="estado">
                    <option value="">Todos</option>
                    <?php foreach ($statuses as $row): ?>
                        <option value="<?= (int) $row['ID_EstadoReserva'] ?>" <?= $status === (int) $row['ID_EstadoReserva'] ? 'selected' : '' ?>><?= e($row['Nombre']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="field">
                <label for="desde">Desde</label>
                <input type="date" id="desde" name="desde" value="<?= e($from) ?>">
            </div>
            <div class="field">
                <label for="hasta">Hasta</label>
                <input type="date" id="hasta" name="hasta" value="<?= e($to) ?>">
            </div>
            <div class="field" style="max-width:170px;">
                <label for="pendientes">Saldo</label>
                <select id="pendientes" name="pendientes">
                    <option value="">Todas</option>
                    <option value="1" <?= $onlyPending ? 'selected' : '' ?>>Con saldo pendiente</option>
                </select>
            </div>
            <div class="filters-actions">
                <button class="btn btn-primary" type="submit">Filtrar</button>
                <a class="btn btn-ghost" href="<?= e(base_url('admin/reservas.php')) ?>">Limpiar</a>
            </div>
            <?php if ($clientId > 0): ?><input type="hidden" name="cliente" value="<?= $clientId ?>"><?php endif; ?>
        </form>
    </div>
    <div class="table-wrap">
        <table class="data-table">
            <thead>
            <tr><th>Reserva</th><th>Cliente</th><th>Paquete</th><th>Fecha</th><th class="num">Total</th><th class="num">Saldo</th><th>Estado</th><th></th></tr>
            </thead>
            <tbody>
            <?php if ($reservations === []): ?>
                <tr><td colspan="8" class="table-empty">No hay reservas con los filtros aplicados.</td></tr>
            <?php endif; ?>
            <?php foreach ($reservations as $row): ?>
                <?php $name = trim((string) ($row['Nombre'] ?? '') . ' ' . (string) ($row['Apellido'] ?? '')) ?: ($row['Email'] ?? $row['CodigoCliente']); $saldo = max(0, (float) $row['Total'] - (float) $row['Pagado']); ?>
                <tr>
                    <td><strong><?= e($row['CodigoReserva']) ?></strong><br><small class="muted"><?= e(fecha($row['FechaReserva'])) ?></small></td>
                    <td>
                        <div class="table-user">
                            <span class="avatar"><?= e(initials($name)) ?></span>
                            <span><strong><?= e($name) ?></strong><small><?= e($row['CodigoCliente']) ?></small></span>
                        </div>
                    </td>
                    <td><span class="small"><?= e($row['Paquetes'] ?: 'Sin ítems') ?></span></td>
                    <td><?= e(fecha($row['FechaReserva'])) ?></td>
                    <td class="num"><?= e(money($row['Total'])) ?></td>
                    <td class="num <?= $saldo > 0.01 ? 'text-danger' : '' ?>"><?= e(money($saldo)) ?></td>
                    <td><span class="<?= e(badge_class($row['Estado'])) ?>"><?= e($row['Estado']) ?></span></td>
                    <td class="actions"><a class="btn btn-ghost btn-sm" href="<?= e(base_url('admin/reserva_ver.php?id=' . (int) $row['ID_Reserva'])) ?>">Ver</a></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <div class="card-footer"><?= pagination_links($pagination) ?></div>
</div>
<?php require __DIR__ . '/../includes/admin_footer.php'; ?>
