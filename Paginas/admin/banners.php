<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/data.php';
require_permission('gestionar_contenido');

if (is_post()) {
    verify_csrf();
    $action = post_string('action');
    $bannerId = post_int('banner_id');
    $banner = db_one('SELECT * FROM banners WHERE ID_Banner = :id', [':id' => $bannerId]);
    if ($banner === null) {
        redirect_with('admin/banners.php', 'error', 'El banner indicado no existe.');
    }
    if ($action === 'toggle') {
        $active = (int) $banner['Activo'] === 1 ? 0 : 1;
        db_exec('UPDATE banners SET Activo = :active WHERE ID_Banner = :id', [':active' => $active, ':id' => $bannerId]);
        audit('banners', 'UPDATE', (string) $bannerId, 'Cambio de visibilidad');
        redirect_with('admin/banners.php', 'success', 'Visibilidad del banner actualizada.');
    }
    if ($action === 'delete') {
        remove_upload($banner['Imagen']);
        db_exec('DELETE FROM banners WHERE ID_Banner = :id', [':id' => $bannerId]);
        audit('banners', 'DELETE', (string) $bannerId, 'Baja de banner');
        redirect_with('admin/banners.php', 'success', 'Banner eliminado.');
    }
}

$banners = db_all('SELECT * FROM banners ORDER BY Orden ASC, ID_Banner ASC');
$pageTitle = 'Banners';
$activeSection = 'contenido';
$breadcrumbs = ['Panel' => 'admin/index.php', 'Contenido' => 'admin/contenido.php', 'Banners' => ''];
$pageActions = '<a class="btn btn-accent" href="' . e(base_url('admin/banner_form.php')) . '">Nuevo banner</a>';
require __DIR__ . '/../includes/admin_header.php';
?>
<div class="card">
    <div class="card-header"><h2>Banners principales</h2><a class="small" href="<?= e(base_url('admin/contenido.php')) ?>">Volver a contenido</a></div>
    <div class="table-wrap">
        <table class="data-table">
            <thead><tr><th>Orden</th><th>Vista previa</th><th>Título</th><th>Enlace</th><th>Estado</th><th></th></tr></thead>
            <tbody>
            <?php if ($banners === []): ?>
                <tr><td colspan="6" class="table-empty">No hay banners cargados.</td></tr>
            <?php endif; ?>
            <?php foreach ($banners as $banner): ?>
                <?php $imagePath = $banner['Imagen'] ? (str_starts_with($banner['Imagen'], 'uploads/') ? $banner['Imagen'] : 'assets/img/' . $banner['Imagen']) : null; ?>
                <tr>
                    <td><?= (int) $banner['Orden'] ?></td>
                    <td>
                        <?php if ($imagePath): ?>
                            <span class="table-thumb" style="background-image:url('<?= e(asset($imagePath)) ?>');"></span>
                        <?php else: ?>
                            <span class="muted small">Sin imagen</span>
                        <?php endif; ?>
                    </td>
                    <td><strong><?= e($banner['Titulo']) ?></strong><br><small class="muted"><?= e(summary_text($banner['Texto'] ?? '', 80)) ?></small></td>
                    <td><span class="small"><?= e($banner['Link'] ?: '—') ?></span></td>
                    <td><span class="<?= e(badge_class($banner['Activo'] ? 'activo' : 'inactivo')) ?>"><?= $banner['Activo'] ? 'Activo' : 'Inactivo' ?></span></td>
                    <td class="actions">
                        <a class="btn btn-ghost btn-sm" href="<?= e(base_url('admin/banner_form.php?id=' . (int) $banner['ID_Banner'])) ?>">Editar</a>
                        <form method="post" action="<?= e(base_url('admin/banners.php')) ?>" style="display:inline;">
                            <?= csrf_field() ?>
                            <input type="hidden" name="action" value="toggle">
                            <input type="hidden" name="banner_id" value="<?= (int) $banner['ID_Banner'] ?>">
                            <button class="btn btn-ghost btn-sm" type="submit"><?= $banner['Activo'] ? 'Ocultar' : 'Mostrar' ?></button>
                        </form>
                        <form method="post" action="<?= e(base_url('admin/banners.php')) ?>" data-confirm="¿Eliminar este banner?" style="display:inline;">
                            <?= csrf_field() ?>
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="banner_id" value="<?= (int) $banner['ID_Banner'] ?>">
                            <button class="btn btn-ghost btn-sm text-danger" type="submit">Eliminar</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php require __DIR__ . '/../includes/admin_footer.php'; ?>
