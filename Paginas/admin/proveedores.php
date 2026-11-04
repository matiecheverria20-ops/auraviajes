<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/data.php';
require_permission('gestionar_proveedores');

$search = trim(get_string('q'));
$typeId = get_int('tipo');
$where = [];
$params = [];
if ($search !== '') {
    $where[] = '(pr.Nombre LIKE :search OR pr.Contacto LIKE :search OR pc.Nombre LIKE :search OR pc.Email LIKE :search)';
    $params[':search'] = '%' . $search . '%';
}
if ($typeId > 0) {
    $where[] = 'EXISTS (SELECT 1 FROM proveedor_tipo pt WHERE pt.FK_ID_Proveedor = pr.ID_Proveedor AND pt.FK_ID_TipoProveedor = :tipo)';
    $params[':tipo'] = $typeId;
}
$whereSql = $where !== [] ? ' WHERE ' . implode(' AND ', $where) : '';
$providers = db_all(
    'SELECT pr.*,
            (SELECT GROUP_CONCAT(tp.Nombre SEPARATOR ", ") FROM proveedor_tipo pt INNER JOIN tipos_proveedor tp ON tp.ID_TipoProveedor = pt.FK_ID_TipoProveedor WHERE pt.FK_ID_Proveedor = pr.ID_Proveedor) AS Tipos,
            (SELECT GROUP_CONCAT(CONCAT(pc.Nombre, IF(pc.Email IS NULL, "", CONCAT(" · ", pc.Email))) SEPARATOR " | ") FROM proveedor_contacto pc WHERE pc.FK_ID_Proveedor = pr.ID_Proveedor) AS Contactos,
            (SELECT COUNT(*) FROM proveedor_servicio ps WHERE ps.FK_ID_Proveedor = pr.ID_Proveedor) AS Servicios
     FROM proveedores pr
     LEFT JOIN proveedor_contacto pc ON pc.FK_ID_Proveedor = pr.ID_Proveedor' . $whereSql . '
     GROUP BY pr.ID_Proveedor, pr.Nombre, pr.Contacto
     ORDER BY pr.Nombre ASC',
    $params
);
$types = db_all('SELECT * FROM tipos_proveedor ORDER BY Nombre');

$pageTitle = 'Proveedores';
$activeSection = 'proveedores';
$breadcrumbs = ['Panel' => 'admin/index.php', 'Proveedores' => ''];
$pageActions = '<a class="btn btn-accent" href="' . e(base_url('admin/proveedor_form.php')) . '">Nuevo proveedor</a>';
require __DIR__ . '/../includes/admin_header.php';
?>
<div class="card">
    <div class="card-header"><h2>Directorio de proveedores</h2><span class="small muted"><?= count($providers) ?> proveedores</span></div>
    <div class="card-body">
        <form class="filters" method="get" action="<?= e(base_url('admin/proveedores.php')) ?>">
            <div class="field">
                <label for="q">Buscar</label>
                <input type="search" id="q" name="q" value="<?= e($search) ?>" placeholder="Nombre, contacto o email">
            </div>
            <div class="field" style="max-width:220px;">
                <label for="tipo">Tipo</label>
                <select id="tipo" name="tipo">
                    <option value="">Todos</option>
                    <?php foreach ($types as $type): ?>
                        <option value="<?= (int) $type['ID_TipoProveedor'] ?>" <?= $typeId === (int) $type['ID_TipoProveedor'] ? 'selected' : '' ?>><?= e($type['Nombre']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="filters-actions">
                <button class="btn btn-primary" type="submit">Filtrar</button>
                <a class="btn btn-ghost" href="<?= e(base_url('admin/proveedores.php')) ?>">Limpiar</a>
            </div>
        </form>
    </div>
    <div class="table-wrap">
        <table class="data-table">
            <thead><tr><th>Proveedor</th><th>Tipo</th><th>Contacto</th><th>Servicios</th><th></th></tr></thead>
            <tbody>
            <?php if ($providers === []): ?>
                <tr><td colspan="5" class="table-empty">No se encontraron proveedores.</td></tr>
            <?php endif; ?>
            <?php foreach ($providers as $provider): ?>
                <tr>
                    <td><strong><?= e($provider['Nombre']) ?></strong><br><small class="muted"><?= e($provider['Contacto'] ?? '—') ?></small></td>
                    <td><span class="small"><?= e($provider['Tipos'] ?: '—') ?></span></td>
                    <td><span class="small"><?= e(summary_text($provider['Contactos'] ?? '', 90) ?: '—') ?></span></td>
                    <td><?= (int) $provider['Servicios'] ?></td>
                    <td class="actions"><a class="btn btn-ghost btn-sm" href="<?= e(base_url('admin/proveedor_form.php?id=' . (int) $provider['ID_Proveedor'])) ?>">Editar</a></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php require __DIR__ . '/../includes/admin_footer.php'; ?>
