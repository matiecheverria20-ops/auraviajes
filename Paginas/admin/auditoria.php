<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/data.php';
require_permission('ver_auditoria');

$search = trim(get_string('q'));
$userId = get_int('usuario');
$operation = get_string('operacion');
$table = get_string('tabla');
$from = get_string('desde');
$to = get_string('hasta');

$where = [];
$params = [];
if ($search !== '') {
    $where[] = '(a.Detalle LIKE :search OR a.IDRegistro LIKE :search OR a.TablaAfectada LIKE :search OR u.Email LIKE :search)';
    $params[':search'] = '%' . $search . '%';
}
if ($userId > 0) {
    $where[] = 'a.FK_ID_PasajeroOnline = :usuario';
    $params[':usuario'] = $userId;
}
if (in_array($operation, ['INSERT', 'UPDATE', 'DELETE', 'LOGIN', 'LOGOUT'], true)) {
    $where[] = 'a.Operacion = :operacion';
    $params[':operacion'] = $operation;
}
if ($table !== '') {
    $where[] = 'a.TablaAfectada = :tabla';
    $params[':tabla'] = $table;
}
if ($from !== '') {
    $where[] = 'a.Fecha >= :desde';
    $params[':desde'] = $from . ' 00:00:00';
}
if ($to !== '') {
    $where[] = 'a.Fecha <= :hasta';
    $params[':hasta'] = $to . ' 23:59:59';
}
$whereSql = $where !== [] ? ' WHERE ' . implode(' AND ', $where) : '';
$fromSql = ' FROM auditoria a LEFT JOIN pasajeroonline u ON u.ID_PasajeroOnline = a.FK_ID_PasajeroOnline';

if (get_string('export') === '1') {
    $rows = db_all('SELECT a.*, u.Email ' . $fromSql . $whereSql . ' ORDER BY a.Fecha DESC LIMIT 5000', $params);
    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="auditoria-' . date('Y-m-d') . '.csv"');
    echo "\xEF\xBB\xBF";
    $out = fopen('php://output', 'w');
    fputcsv($out, ['Fecha', 'Usuario', 'Tabla', 'Operación', 'ID registro', 'Detalle'], ';');
    foreach ($rows as $row) {
        fputcsv($out, [fecha($row['Fecha'], true), $row['Email'] ?? 'Sistema', $row['TablaAfectada'], $row['Operacion'], $row['IDRegistro'] ?? '', $row['Detalle'] ?? ''], ';');
    }
    fclose($out);
    exit;
}

$total = (int) db_val('SELECT COUNT(*)' . $fromSql . $whereSql, $params, 0);
$pagination = pagination_data($total, 25);
$logs = db_all(
    'SELECT a.*, u.Email ' . $fromSql . $whereSql . ' ORDER BY a.Fecha DESC, a.ID_Auditoria DESC LIMIT ' . (int) $pagination['per_page'] . ' OFFSET ' . (int) $pagination['offset'],
    $params
);
$users = db_all('SELECT DISTINCT u.ID_PasajeroOnline, u.Email FROM auditoria a INNER JOIN pasajeroonline u ON u.ID_PasajeroOnline = a.FK_ID_PasajeroOnline ORDER BY u.Email');
$tables = db_all('SELECT DISTINCT TablaAfectada FROM auditoria ORDER BY TablaAfectada');
$counts = db_one(
    "SELECT SUM(Operacion = 'LOGIN') AS Logins, SUM(Operacion = 'INSERT') AS Inserts, SUM(Operacion = 'UPDATE') AS Updates, SUM(Operacion = 'DELETE') AS Deletes FROM auditoria"
) ?? [];

$pageTitle = 'Auditoría';
$activeSection = 'auditoria';
$breadcrumbs = ['Panel' => 'admin/index.php', 'Auditoría' => ''];
require __DIR__ . '/../includes/admin_header.php';
?>
<div class="mini-stats">
    <span><strong><?= number_format($total, 0, ',', '.') ?></strong> resultados</span>
    <span><strong><?= (int) ($counts['Logins'] ?? 0) ?></strong> ingresos</span>
    <span><strong><?= (int) ($counts['Updates'] ?? 0) ?></strong> modificaciones</span>
    <span><strong><?= (int) ($counts['Deletes'] ?? 0) ?></strong> bajas</span>
</div>

<div class="card">
    <div class="card-header">
        <h2>Registro de actividad</h2>
        <a class="small" href="<?= e(base_url('admin/auditoria.php?' . http_build_query(array_filter(['q' => $search, 'usuario' => $userId ?: '', 'operacion' => $operation, 'tabla' => $table, 'desde' => $from, 'hasta' => $to, 'export' => '1'])))) ?>">Exportar CSV</a>
    </div>
    <div class="card-body">
        <form class="filters" method="get" action="<?= e(base_url('admin/auditoria.php')) ?>">
            <div class="field"><label for="q">Buscar</label><input type="search" id="q" name="q" value="<?= e($search) ?>" placeholder="Detalle, ID o email"></div>
            <div class="field" style="max-width:220px;">
                <label for="usuario">Usuario</label>
                <select id="usuario" name="usuario">
                    <option value="">Todos</option>
                    <?php foreach ($users as $user): ?>
                        <option value="<?= (int) $user['ID_PasajeroOnline'] ?>" <?= $userId === (int) $user['ID_PasajeroOnline'] ? 'selected' : '' ?>><?= e($user['Email']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="field" style="max-width:160px;">
                <label for="operacion">Operación</label>
                <select id="operacion" name="operacion">
                    <option value="">Todas</option>
                    <?php foreach (['LOGIN', 'LOGOUT', 'INSERT', 'UPDATE', 'DELETE'] as $option): ?>
                        <option value="<?= e($option) ?>" <?= $operation === $option ? 'selected' : '' ?>><?= e($option) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="field" style="max-width:200px;">
                <label for="tabla">Tabla</label>
                <select id="tabla" name="tabla">
                    <option value="">Todas</option>
                    <?php foreach ($tables as $tableRow): ?>
                        <option value="<?= e($tableRow['TablaAfectada']) ?>" <?= $table === $tableRow['TablaAfectada'] ? 'selected' : '' ?>><?= e($tableRow['TablaAfectada']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="field"><label for="desde">Desde</label><input type="date" id="desde" name="desde" value="<?= e($from) ?>"></div>
            <div class="field"><label for="hasta">Hasta</label><input type="date" id="hasta" name="hasta" value="<?= e($to) ?>"></div>
            <div class="filters-actions">
                <button class="btn btn-primary" type="submit">Filtrar</button>
                <a class="btn btn-ghost" href="<?= e(base_url('admin/auditoria.php')) ?>">Limpiar</a>
            </div>
        </form>
    </div>
    <div class="table-wrap">
        <table class="data-table">
            <thead><tr><th>Fecha</th><th>Usuario</th><th>Operación</th><th>Tabla</th><th>Registro</th><th>Detalle</th></tr></thead>
            <tbody>
            <?php if ($logs === []): ?>
                <tr><td colspan="6" class="table-empty">No hay registros con los filtros aplicados.</td></tr>
            <?php endif; ?>
            <?php foreach ($logs as $log): ?>
                <tr>
                    <td><span class="small nowrap"><?= e(fecha($log['Fecha'], true)) ?></span></td>
                    <td><span class="small"><?= e($log['Email'] ?? 'Sistema') ?></span></td>
                    <td><span class="<?= e(in_array($log['Operacion'], ['DELETE'], true) ? 'badge badge-danger' : (in_array($log['Operacion'], ['LOGIN', 'INSERT'], true) ? 'badge badge-success' : 'badge badge-warning')) ?>"><?= e($log['Operacion']) ?></span></td>
                    <td><span class="small"><?= e($log['TablaAfectada']) ?></span></td>
                    <td><span class="small muted"><?= e($log['IDRegistro'] ?? '—') ?></span></td>
                    <td><span class="small"><?= e(summary_text($log['Detalle'] ?? '', 130)) ?></span></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <div class="card-footer"><?= pagination_links($pagination) ?></div>
</div>
<?php require __DIR__ . '/../includes/admin_footer.php'; ?>
