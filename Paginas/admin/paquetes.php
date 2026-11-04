<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/data.php';
require_permission('ver_paquetes');

$search = trim(get_string('q'));
$status = get_string('estado');
$destination = get_int('destino');
$where = [];
$params = [];

if ($search !== '') {
    $where[] = '(p.Nombre LIKE :search OR p.Codigo LIKE :search OR p.Descripcion LIKE :search)';
    $params[':search'] = '%' . $search . '%';
}
if (in_array($status, ['borrador', 'publicado', 'pausado', 'finalizado'], true)) {
    $where[] = 'p.Estado = :estado';
    $params[':estado'] = $status;
}
if ($destination > 0) {
    $where[] = 'EXISTS (SELECT 1 FROM paquete_destino pd WHERE pd.FK_ID_Paquete = p.ID_Paquete AND pd.FK_ID_Destino = :destino)';
    $params[':destino'] = $destination;
}
$whereSql = $where !== [] ? ' WHERE ' . implode(' AND ', $where) : '';

$total = (int) db_val('SELECT COUNT(*) FROM paquetes p' . $whereSql, $params, 0);
$pagination = pagination_data($total, 12);
$packages = db_all(
    'SELECT p.*,
            (SELECT MIN(s.FechaSalida) FROM salidas s WHERE s.FK_ID_Paquete = p.ID_Paquete AND s.FechaSalida >= CURDATE() AND s.Estado = "abierta") AS ProximaSalida,
            (SELECT COUNT(*) FROM salidas s WHERE s.FK_ID_Paquete = p.ID_Paquete AND s.FechaSalida >= CURDATE()) AS SalidasFuturas,
            (SELECT COALESCE(SUM(s.CuposDisponibles), 0) FROM salidas s WHERE s.FK_ID_Paquete = p.ID_Paquete AND s.FechaSalida >= CURDATE() AND s.Estado = "abierta") AS CuposDisponibles,
            (SELECT GROUP_CONCAT(d.NombreUbicacion ORDER BY pd.Orden SEPARATOR " · ") FROM paquete_destino pd INNER JOIN destino d ON d.ID_Destino = pd.FK_ID_Destino WHERE pd.FK_ID_Paquete = p.ID_Paquete) AS Destinos
     FROM paquetes p' . $whereSql . '
     ORDER BY FIELD(p.Estado, "publicado", "borrador", "pausado", "finalizado"), p.FechaAlta DESC
     LIMIT ' . (int) $pagination['per_page'] . ' OFFSET ' . (int) $pagination['offset'],
    $params
);
$destinations = all_destinations();
$stateCounts = db_all('SELECT Estado, COUNT(*) AS Total FROM paquetes GROUP BY Estado');

$pageTitle = 'Paquetes';
$activeSection = 'paquetes';
$breadcrumbs = ['Panel' => 'admin/index.php', 'Paquetes' => ''];
$pageActions = can('gestionar_paquetes') ? '<a class="btn btn-accent" href="' . e(base_url('admin/paquete_form.php')) . '">Nuevo paquete</a>' : '';
require __DIR__ . '/../includes/admin_header.php';
?>
<div class="mini-stats">
    <?php foreach ($stateCounts as $row): ?>
        <span><strong><?= (int) $row['Total'] ?></strong> <?= e(estado_label($row['Estado'])) ?></span>
    <?php endforeach; ?>
    <span><strong><?= number_format($total, 0, ',', '.') ?></strong> resultados</span>
</div>

<div class="card">
    <div class="card-header"><h2>Catálogo de paquetes</h2></div>
    <div class="card-body">
        <form class="filters" method="get" action="<?= e(base_url('admin/paquetes.php')) ?>">
            <div class="field">
                <label for="q">Buscar</label>
                <input type="search" id="q" name="q" value="<?= e($search) ?>" placeholder="Nombre, código o descripción">
            </div>
            <div class="field" style="max-width:190px;">
                <label for="estado">Estado</label>
                <select id="estado" name="estado">
                    <option value="">Todos</option>
                    <?php foreach (['publicado', 'borrador', 'pausado', 'finalizado'] as $option): ?>
                        <option value="<?= e($option) ?>" <?= $status === $option ? 'selected' : '' ?>><?= e(estado_label($option)) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="field" style="max-width:230px;">
                <label for="destino">Destino</label>
                <select id="destino" name="destino">
                    <option value="">Todos</option>
                    <?php foreach ($destinations as $dest): ?>
                        <option value="<?= (int) $dest['ID_Destino'] ?>" <?= $destination === (int) $dest['ID_Destino'] ? 'selected' : '' ?>><?= e($dest['NombreUbicacion']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="filters-actions">
                <button class="btn btn-primary" type="submit">Filtrar</button>
                <a class="btn btn-ghost" href="<?= e(base_url('admin/paquetes.php')) ?>">Limpiar</a>
            </div>
        </form>
    </div>
</div>

<?php if ($packages === []): ?>
    <div class="card"><div class="empty-state"><h3>Sin paquetes</h3><p>No hay paquetes que coincidan con los filtros.</p></div></div>
<?php else: ?>
    <div class="package-admin-grid">
        <?php foreach ($packages as $package): ?>
            <?php $pricing = package_current_price((int) $package['ID_Paquete']); ?>
            <article class="package-admin-card">
                <div class="package-admin-cover" style="background-image:url('<?= e(destination_image($package['Destinos'] ?? $package['Nombre'])) ?>');">
                    <span class="<?= e(badge_class($package['Estado'])) ?>"><?= e(estado_label($package['Estado'])) ?></span>
                </div>
                <div class="package-admin-body">
                    <span class="small muted"><?= e($package['Codigo']) ?> · <?= (int) $package['DuracionDias'] ?> días</span>
                    <h3><?= e($package['Nombre']) ?></h3>
                    <p class="small muted"><?= e($package['Destinos'] ?: 'Sin destinos asignados') ?></p>
                    <div class="flex-between" style="margin-top:auto;">
                        <span class="price"><?= e(money($pricing['final'])) ?><?= $pricing['has_discount'] ? ' <small class="muted" style="text-decoration:line-through;">' . e(money($pricing['base'])) . '</small>' : '' ?></span>
                        <span class="small muted"><?= (int) $package['SalidasFuturas'] ?> salidas</span>
                    </div>
                    <div class="small muted">Próxima: <?= e(fecha($package['ProximaSalida'])) ?> · <?= (int) $package['CuposDisponibles'] ?> cupos</div>
                    <div class="flex gap-1" style="margin-top:.6rem;flex-wrap:wrap;">
                        <a class="btn btn-ghost btn-sm" href="<?= e(base_url('admin/paquete_ver.php?id=' . (int) $package['ID_Paquete'])) ?>">Ver</a>
                        <?php if (can('gestionar_paquetes')): ?>
                            <a class="btn btn-ghost btn-sm" href="<?= e(base_url('admin/paquete_form.php?id=' . (int) $package['ID_Paquete'])) ?>">Editar</a>
                            <form method="post" action="<?= e(base_url('admin/paquete_ver.php?id=' . (int) $package['ID_Paquete'])) ?>" data-confirm="¿Cambiar la publicación del paquete?">
                                <?= csrf_field() ?>
                                <input type="hidden" name="action" value="toggle_publish">
                                <button class="btn btn-ghost btn-sm" type="submit"><?= $package['Estado'] === 'publicado' ? 'Pausar' : 'Publicar' ?></button>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>
            </article>
        <?php endforeach; ?>
    </div>
    <div class="card"><div class="card-footer"><?= pagination_links($pagination) ?></div></div>
<?php endif; ?>
<?php require __DIR__ . '/../includes/admin_footer.php'; ?>
