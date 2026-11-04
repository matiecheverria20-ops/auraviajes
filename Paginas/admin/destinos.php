<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/data.php';
require_permission('ver_paquetes');

$search = trim(get_string('q'));
$where = '';
$params = [];
if ($search !== '') {
    $where = ' WHERE d.NombreUbicacion LIKE :search';
    $params[':search'] = '%' . $search . '%';
}
$destinations = db_all(
    'SELECT d.*, di.Descripcion, di.Clima, di.Moneda,
            (SELECT COUNT(*) FROM paquete_destino pd WHERE pd.FK_ID_Destino = d.ID_Destino) AS Paquetes,
            (SELECT GROUP_CONCAT(cd.Nombre ORDER BY cd.Nombre SEPARATOR ", ") FROM destino_categoria dc INNER JOIN categorias_destino cd ON cd.ID_CategoriaDestino = dc.FK_ID_CategoriaDestino WHERE dc.FK_ID_Destino = d.ID_Destino) AS Categorias
     FROM destino d
     LEFT JOIN destino_informacion di ON di.FK_ID_Destino = d.ID_Destino' . $where . '
     ORDER BY d.NombreUbicacion ASC',
    $params
);
$manage = can('gestionar_paquetes');

$pageTitle = 'Destinos';
$activeSection = 'destinos';
$breadcrumbs = ['Panel' => 'admin/index.php', 'Destinos' => ''];
$pageActions = $manage ? '<a class="btn btn-accent" href="' . e(base_url('admin/destino_form.php')) . '">Nuevo destino</a>' : '';
require __DIR__ . '/../includes/admin_header.php';
?>
<div class="card">
    <div class="card-header"><h2>Destinos turísticos</h2><span class="small muted"><?= count($destinations) ?> destinos</span></div>
    <div class="card-body">
        <form class="filters" method="get" action="<?= e(base_url('admin/destinos.php')) ?>">
            <div class="field">
                <label for="q">Buscar destino</label>
                <input type="search" id="q" name="q" value="<?= e($search) ?>" placeholder="Ciudad, provincia o país">
            </div>
            <div class="filters-actions">
                <button class="btn btn-primary" type="submit">Buscar</button>
                <a class="btn btn-ghost" href="<?= e(base_url('admin/destinos.php')) ?>">Limpiar</a>
            </div>
        </form>
    </div>
</div>

<div class="destination-admin-grid">
    <?php if ($destinations === []): ?>
        <div class="card"><div class="empty-state"><h3>Sin destinos</h3><p>No se encontraron destinos con ese criterio.</p></div></div>
    <?php endif; ?>
    <?php foreach ($destinations as $dest): ?>
        <article class="destination-admin-card">
            <div class="destination-admin-cover" style="background-image:url('<?= e(destination_image($dest['NombreUbicacion'])) ?>');"></div>
            <div class="package-admin-body">
                <span class="small muted"><?= (int) $dest['Paquetes'] ?> paquetes asociados</span>
                <h3><?= e($dest['NombreUbicacion']) ?></h3>
                <p class="small muted"><?= e(summary_text($dest['Descripcion'] ?? '', 120) ?: 'Sin descripción.') ?></p>
                <?php if (!empty($dest['Categorias'])): ?>
                    <div class="small muted"><?= e($dest['Categorias']) ?></div>
                <?php endif; ?>
                <div class="flex gap-1" style="margin-top:.6rem;flex-wrap:wrap;">
                    <a class="btn btn-ghost btn-sm" target="_blank" rel="noopener" href="<?= e(base_url('destino.php?id=' . (int) $dest['ID_Destino'])) ?>">Ver en el sitio</a>
                    <?php if ($manage): ?>
                        <a class="btn btn-ghost btn-sm" href="<?= e(base_url('admin/destino_form.php?id=' . (int) $dest['ID_Destino'])) ?>">Editar</a>
                    <?php endif; ?>
                </div>
            </div>
        </article>
    <?php endforeach; ?>
</div>
<?php require __DIR__ . '/../includes/admin_footer.php'; ?>
