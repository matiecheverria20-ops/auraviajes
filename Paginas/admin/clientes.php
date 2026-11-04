<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/data.php';
require_any_permission(['gestionar_clientes', 'ver_clientes']);

$search = trim(get_string('q'));
$status = get_string('estado');
$where = [];
$params = [];

if ($search !== '') {
    $where[] = '(c.CodigoCliente LIKE :search OR u.Email LIKE :search OR p.Nombre LIKE :search OR p.Apellido LIKE :search OR p.DNI LIKE :search OR CONCAT(p.Nombre, " ", p.Apellido) LIKE :search)';
    $params[':search'] = '%' . $search . '%';
}
if (in_array($status, ['activo', 'inactivo'], true)) {
    $where[] = 'c.Estado = :estado';
    $params[':estado'] = $status;
}
$whereSql = $where !== [] ? ' WHERE ' . implode(' AND ', $where) : '';
$fromSql = ' FROM clientes c
             LEFT JOIN pasajeroonline u ON u.ID_PasajeroOnline = c.FK_ID_PasajeroOnline
             LEFT JOIN pasajero p ON p.ID_Pasajero = (
                 SELECT MIN(p2.ID_Pasajero) FROM pasajero p2 WHERE p2.FK_ID_PasajeroOnline = u.ID_PasajeroOnline
             )';

if (get_string('export') === '1') {
    $rows = db_all(client_select_sql() . $whereSql . ' ORDER BY c.FechaAlta DESC', $params);
    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="clientes-' . date('Y-m-d') . '.csv"');
    echo "\xEF\xBB\xBF";
    $out = fopen('php://output', 'w');
    fputcsv($out, ['Código', 'Nombre', 'Apellido', 'DNI', 'Email', 'Teléfono', 'Estado', 'Fecha alta', 'Reservas', 'Facturado', 'Cobrado'], ';');
    foreach ($rows as $row) {
        fputcsv($out, [
            $row['CodigoCliente'],
            $row['Nombre'] ?? '',
            $row['Apellido'] ?? '',
            $row['DNI'] ?? '',
            $row['Email'] ?? '',
            $row['Telefono'] ?? '',
            $row['Estado'],
            fecha_input($row['FechaAlta']),
            $row['reservas_count'],
            number_format((float) $row['total_facturado'], 2, '.', ''),
            number_format((float) $row['total_pagado'], 2, '.', ''),
        ], ';');
    }
    fclose($out);
    audit('clientes', 'EXPORT', null, 'Exportación de listado de clientes');
    exit;
}

$total = (int) db_val('SELECT COUNT(*)' . $fromSql . $whereSql, $params, 0);
$pagination = pagination_data($total, 15);
$clients = db_all(
    client_select_sql() . $whereSql . ' ORDER BY c.FechaAlta DESC LIMIT ' . (int) $pagination['per_page'] . ' OFFSET ' . (int) $pagination['offset'],
    $params
);
$stats = db_one(
    "SELECT SUM(Estado = 'activo') AS activos, SUM(Estado = 'inactivo') AS inactivos FROM clientes"
) ?? [];

$pageTitle = 'Clientes';
$activeSection = 'clientes';
$breadcrumbs = ['Panel' => 'admin/index.php', 'Clientes' => ''];
$pageActions = can('gestionar_clientes') ? '<a class="btn btn-accent" href="' . e(base_url('admin/cliente_form.php')) . '">Nuevo cliente</a>' : '';
require __DIR__ . '/../includes/admin_header.php';
?>
<div class="mini-stats">
    <span><strong><?= number_format($total, 0, ',', '.') ?></strong> resultados</span>
    <span><strong><?= (int) ($stats['activos'] ?? 0) ?></strong> activos</span>
    <span><strong><?= (int) ($stats['inactivos'] ?? 0) ?></strong> inactivos</span>
</div>

<div class="card">
    <div class="card-header">
        <h2>Listado de clientes</h2>
        <a class="small" href="<?= e(base_url('admin/clientes.php?' . http_build_query(array_filter(['q' => $search, 'estado' => $status, 'export' => '1'])))) ?>">Exportar CSV</a>
    </div>
    <div class="card-body">
        <form class="filters" method="get" action="<?= e(base_url('admin/clientes.php')) ?>">
            <div class="field">
                <label for="q">Buscar</label>
                <input type="search" id="q" name="q" value="<?= e($search) ?>" placeholder="Nombre, DNI, email o código">
            </div>
            <div class="field" style="max-width:200px;">
                <label for="estado">Estado</label>
                <select id="estado" name="estado">
                    <option value="">Todos</option>
                    <option value="activo" <?= $status === 'activo' ? 'selected' : '' ?>>Activos</option>
                    <option value="inactivo" <?= $status === 'inactivo' ? 'selected' : '' ?>>Inactivos</option>
                </select>
            </div>
            <div class="filters-actions">
                <button class="btn btn-primary" type="submit">Filtrar</button>
                <a class="btn btn-ghost" href="<?= e(base_url('admin/clientes.php')) ?>">Limpiar</a>
            </div>
        </form>
    </div>
    <div class="table-wrap">
        <table class="data-table">
            <thead>
            <tr>
                <th>Cliente</th>
                <th>DNI</th>
                <th>Contacto</th>
                <th class="num">Reservas</th>
                <th class="num">Saldo</th>
                <th>Estado</th>
                <th>Alta</th>
                <th></th>
            </tr>
            </thead>
            <tbody>
            <?php if ($clients === []): ?>
                <tr><td colspan="8" class="table-empty">No se encontraron clientes con los filtros aplicados.</td></tr>
            <?php endif; ?>
            <?php foreach ($clients as $client): ?>
                <?php $name = client_full_name($client); $saldo = (float) $client['total_facturado'] - (float) $client['total_pagado']; ?>
                <tr>
                    <td>
                        <div class="table-user">
                            <span class="avatar"><?= e(initials($name)) ?></span>
                            <span><strong><?= e($name) ?></strong><small><?= e($client['CodigoCliente']) ?></small></span>
                        </div>
                    </td>
                    <td><?= e($client['DNI'] ?? '—') ?></td>
                    <td><span class="small"><?= e($client['Email'] ?? '—') ?></span><br><small class="muted"><?= e($client['Telefono'] ?? '—') ?></small></td>
                    <td class="num"><?= (int) $client['reservas_count'] ?></td>
                    <td class="num <?= $saldo > 0.01 ? 'text-danger' : '' ?>"><?= e(money($saldo)) ?></td>
                    <td><span class="<?= e(badge_class($client['Estado'])) ?>"><?= e(estado_label($client['Estado'])) ?></span></td>
                    <td><?= e(fecha($client['FechaAlta'])) ?></td>
                    <td class="actions">
                        <a class="btn btn-ghost btn-sm" href="<?= e(base_url('admin/cliente_ver.php?id=' . (int) $client['ID_Cliente'])) ?>">Ver</a>
                        <?php if (can('gestionar_clientes')): ?>
                            <a class="btn btn-ghost btn-sm" href="<?= e(base_url('admin/cliente_form.php?id=' . (int) $client['ID_Cliente'])) ?>">Editar</a>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <div class="card-footer"><?= pagination_links($pagination) ?></div>
</div>
<?php require __DIR__ . '/../includes/admin_footer.php'; ?>
