<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/data.php';
require_permission('gestionar_proveedores');

$search = trim(get_string('q'));
$state = get_string('estado');
$where = [];
$params = [];
if ($search !== '') {
    $where[] = '(d1.NombreUbicacion LIKE :search OR d2.NombreUbicacion LIKE :search OR pr.Nombre LIKE :search OR vh.Patente LIKE :search)';
    $params[':search'] = '%' . $search . '%';
}
if (in_array($state, ['programado', 'en_curso', 'completado', 'cancelado'], true)) {
    $where[] = 'v.Estado = :estado';
    $params[':estado'] = $state;
}
$whereSql = $where !== [] ? ' WHERE ' . implode(' AND ', $where) : '';
$trips = db_all(
    "SELECT v.*, d1.NombreUbicacion AS Origen, d2.NombreUbicacion AS Destino, pr.Nombre AS Proveedor,
            vh.Patente, mt.Nombre AS Medio, cl.Clase, pe.Opcion AS Pension, h.Nombre AS Hotel,
            (SELECT GROUP_CONCAT(CONCAT(ch.Nombre, ' ', ch.Apellido) SEPARATOR ', ') FROM viaje_chofer vc INNER JOIN choferes ch ON ch.ID_Chofer = vc.FK_ID_Chofer WHERE vc.FK_ID_Viaje = v.ID_Viaje) AS Choferes,
            (SELECT COUNT(*) FROM reserva_detalle rd WHERE rd.FK_ID_Viaje = v.ID_Viaje) AS Reservas
     FROM viajes v
     INNER JOIN rutas r ON r.ID_Ruta = v.FK_ID_Ruta
     INNER JOIN destino d1 ON d1.ID_Destino = r.ID_Inicio
     INNER JOIN destino d2 ON d2.ID_Destino = r.ID_Destino
     INNER JOIN proveedores pr ON pr.ID_Proveedor = v.FK_ID_Proveedor
     INNER JOIN vehiculo vh ON vh.ID_Vehiculo = v.FK_ID_Vehiculo
     LEFT JOIN mediotransporte mt ON mt.ID_MedioTrans = vh.FK_ID_MedioTrans
     LEFT JOIN clases cl ON cl.ID_Clase = vh.FK_ID_Clase
     LEFT JOIN pension pe ON pe.ID_Pension = v.FK_ID_Pension
     LEFT JOIN hoteles h ON h.ID_Hotel = v.FK_ID_Hotel" . $whereSql . '
     ORDER BY v.FechaSalida DESC',
    $params
);
$pageTitle = 'Viajes y transporte';
$activeSection = 'viajes';
$breadcrumbs = ['Panel' => 'admin/index.php', 'Viajes' => ''];
$pageActions = '<a class="btn btn-accent" href="' . e(base_url('admin/viaje_form.php')) . '">Nuevo viaje</a>';
require __DIR__ . '/../includes/admin_header.php';
?>
<div class="card">
    <div class="card-header"><h2>Operaciones de transporte</h2><span class="small muted"><?= count($trips) ?> viajes</span></div>
    <div class="card-body">
        <form class="filters" method="get" action="<?= e(base_url('admin/viajes.php')) ?>">
            <div class="field">
                <label for="q">Buscar</label>
                <input type="search" id="q" name="q" value="<?= e($search) ?>" placeholder="Origen, destino, proveedor o patente">
            </div>
            <div class="field" style="max-width:190px;">
                <label for="estado">Estado</label>
                <select id="estado" name="estado">
                    <option value="">Todos</option>
                    <?php foreach (['programado', 'en_curso', 'completado', 'cancelado'] as $option): ?>
                        <option value="<?= e($option) ?>" <?= $state === $option ? 'selected' : '' ?>><?= e(estado_label($option)) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="filters-actions">
                <button class="btn btn-primary" type="submit">Filtrar</button>
                <a class="btn btn-ghost" href="<?= e(base_url('admin/viajes.php')) ?>">Limpiar</a>
            </div>
        </form>
    </div>
    <div class="table-wrap">
        <table class="data-table">
            <thead><tr><th>Recorrido</th><th>Salida</th><th>Proveedor / vehículo</th><th>Chofer</th><th class="num">Reservas</th><th>Estado</th><th></th></tr></thead>
            <tbody>
            <?php if ($trips === []): ?>
                <tr><td colspan="7" class="table-empty">No hay viajes con los filtros aplicados.</td></tr>
            <?php endif; ?>
            <?php foreach ($trips as $trip): ?>
                <tr>
                    <td><strong><?= e($trip['Origen']) ?> → <?= e($trip['Destino']) ?></strong><br><small class="muted"><?= e($trip['Medio'] ?? '') ?><?= $trip['Clase'] ? ' · ' . e($trip['Clase']) : '' ?></small></td>
                    <td><span class="small"><?= e(fecha($trip['FechaSalida'], true)) ?></span><br><small class="muted">Llegada: <?= e(fecha($trip['FechaLlegada'], true)) ?></small></td>
                    <td><?= e($trip['Proveedor']) ?><br><small class="muted"><?= e($trip['Patente'] ?? 'Sin patente') ?></small></td>
                    <td><span class="small"><?= e($trip['Choferes'] ?: '—') ?></span></td>
                    <td class="num"><?= (int) $trip['Reservas'] ?></td>
                    <td><span class="<?= e(badge_class($trip['Estado'])) ?>"><?= e(estado_label($trip['Estado'])) ?></span></td>
                    <td class="actions"><a class="btn btn-ghost btn-sm" href="<?= e(base_url('admin/viaje_form.php?id=' . (int) $trip['ID_Viaje'])) ?>">Editar</a></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php require __DIR__ . '/../includes/admin_footer.php'; ?>
