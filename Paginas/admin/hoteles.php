<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/data.php';
require_permission('gestionar_proveedores');

$search = trim(get_string('q'));
$where = '';
$params = [];
if ($search !== '') {
    $where = ' WHERE (h.Nombre LIKE :search OR h.Ubicacion LIKE :search)';
    $params[':search'] = '%' . $search . '%';
}
$hotels = db_all(
    'SELECT h.*, vh.Valoracion, th.Tipo AS TipoHabitacion,
            (SELECT GROUP_CONCAT(s.Nombre SEPARATOR ", ") FROM hotel_servicios hs INNER JOIN servicios s ON s.ID_Servicio = hs.FK_ID_Servicio WHERE hs.FK_ID_Hotel = h.ID_Hotel) AS Servicios,
            (SELECT COUNT(*) FROM habitaciones ha WHERE ha.FK_ID_Hotel = h.ID_Hotel) AS Habitaciones
     FROM hoteles h
     LEFT JOIN valoracionh vh ON vh.ID_ValoracionH = h.FK_ID_ValoracionH
     LEFT JOIN tipohabitacion th ON th.ID_THabitacion = h.FK_ID_THabitacion' . $where . '
     ORDER BY h.Nombre ASC',
    $params
);
$pageTitle = 'Hoteles y servicios';
$activeSection = 'hoteles';
$breadcrumbs = ['Panel' => 'admin/index.php', 'Hoteles' => ''];
$pageActions = '<a class="btn btn-accent" href="' . e(base_url('admin/hotel_form.php')) . '">Nuevo hotel</a>';
require __DIR__ . '/../includes/admin_header.php';
?>
<div class="card">
    <div class="card-header"><h2>Alojamientos</h2><span class="small muted"><?= count($hotels) ?> hoteles</span></div>
    <div class="card-body">
        <form class="filters" method="get" action="<?= e(base_url('admin/hoteles.php')) ?>">
            <div class="field">
                <label for="q">Buscar</label>
                <input type="search" id="q" name="q" value="<?= e($search) ?>" placeholder="Nombre o ubicación">
            </div>
            <div class="filters-actions">
                <button class="btn btn-primary" type="submit">Buscar</button>
                <a class="btn btn-ghost" href="<?= e(base_url('admin/hoteles.php')) ?>">Limpiar</a>
            </div>
        </form>
    </div>
    <div class="table-wrap">
        <table class="data-table">
            <thead><tr><th>Hotel</th><th>Ubicación</th><th>Valoración</th><th>Habitaciones</th><th>Servicios</th><th></th></tr></thead>
            <tbody>
            <?php if ($hotels === []): ?>
                <tr><td colspan="6" class="table-empty">No se encontraron hoteles.</td></tr>
            <?php endif; ?>
            <?php foreach ($hotels as $hotel): ?>
                <tr>
                    <td><strong><?= e($hotel['Nombre']) ?></strong><br><small class="muted"><?= e($hotel['TipoHabitacion'] ?? '—') ?></small></td>
                    <td><?= e($hotel['Ubicacion'] ?? '—') ?></td>
                    <td><?= $hotel['Valoracion'] !== null ? '★ ' . e(number_format((float) $hotel['Valoracion'], 1, ',', '.')) : '—' ?></td>
                    <td><?= (int) $hotel['CantidadHabitaciones'] ?> <small class="muted">(<?= (int) $hotel['Habitaciones'] ?> registradas)</small></td>
                    <td><span class="small"><?= e($hotel['Servicios'] ?: '—') ?></span></td>
                    <td class="actions"><a class="btn btn-ghost btn-sm" href="<?= e(base_url('admin/hotel_form.php?id=' . (int) $hotel['ID_Hotel'])) ?>">Editar</a></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php require __DIR__ . '/../includes/admin_footer.php'; ?>
