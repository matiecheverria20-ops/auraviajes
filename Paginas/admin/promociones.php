<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/data.php';
require_permission('gestionar_contenido');

if (is_post()) {
    verify_csrf();
    $promotionId = post_int('promotion_id');
    $promotion = db_one('SELECT * FROM promociones WHERE ID_Promocion = :id', [':id' => $promotionId]);
    if ($promotion === null) {
        redirect_with('admin/promociones.php', 'error', 'La promoción indicada no existe.');
    }
    if (post_string('action') === 'toggle') {
        $newState = $promotion['Estado'] === 'activa' ? 'inactiva' : 'activa';
        db_exec('UPDATE promociones SET Estado = :estado WHERE ID_Promocion = :id', [':estado' => $newState, ':id' => $promotionId]);
        audit('promociones', 'UPDATE', (string) $promotionId, 'Cambio de estado a ' . $newState);
        redirect_with('admin/promociones.php', 'success', 'Estado de la promoción actualizado.');
    }
    if (post_string('action') === 'delete') {
        $pdo = db();
        $pdo->beginTransaction();
        db_exec('DELETE FROM promocion_paquete WHERE FK_ID_Promocion = :id', [':id' => $promotionId]);
        db_exec('DELETE FROM promociones WHERE ID_Promocion = :id', [':id' => $promotionId]);
        $pdo->commit();
        audit('promociones', 'DELETE', (string) $promotionId, 'Baja de promoción');
        redirect_with('admin/promociones.php', 'success', 'Promoción eliminada.');
    }
}

$promotions = db_all(
    'SELECT pr.*,
            (SELECT GROUP_CONCAT(p.Nombre ORDER BY p.Nombre SEPARATOR " · ") FROM promocion_paquete pp INNER JOIN paquetes p ON p.ID_Paquete = pp.FK_ID_Paquete WHERE pp.FK_ID_Promocion = pr.ID_Promocion) AS Paquetes
     FROM promociones pr
     ORDER BY FIELD(pr.Estado, "activa", "inactiva", "finalizada"), pr.FechaFin DESC'
);
$pageTitle = 'Promociones';
$activeSection = 'contenido';
$breadcrumbs = ['Panel' => 'admin/index.php', 'Contenido' => 'admin/contenido.php', 'Promociones' => ''];
$pageActions = '<a class="btn btn-accent" href="' . e(base_url('admin/promocion_form.php')) . '">Nueva promoción</a>';
require __DIR__ . '/../includes/admin_header.php';
?>
<div class="card">
    <div class="card-header"><h2>Promociones y descuentos</h2><a class="small" href="<?= e(base_url('admin/contenido.php')) ?>">Volver a contenido</a></div>
    <div class="table-wrap">
        <table class="data-table">
            <thead><tr><th>Promoción</th><th>Descuento</th><th>Vigencia</th><th>Paquetes</th><th>Estado</th><th></th></tr></thead>
            <tbody>
            <?php if ($promotions === []): ?>
                <tr><td colspan="6" class="table-empty">No hay promociones cargadas.</td></tr>
            <?php endif; ?>
            <?php foreach ($promotions as $promo): ?>
                <tr>
                    <td><strong><?= e($promo['Nombre']) ?></strong><br><small class="muted"><?= e(summary_text($promo['Descripcion'] ?? '', 90)) ?></small></td>
                    <td><?= $promo['TipoDescuento'] === 'porcentaje' ? (float) $promo['ValorDescuento'] . '%' : e(money($promo['ValorDescuento'])) ?></td>
                    <td><span class="small"><?= e(fecha($promo['FechaInicio'])) ?> al <?= e(fecha($promo['FechaFin'])) ?></span></td>
                    <td><span class="small"><?= e($promo['Paquetes'] ?: 'Sin paquetes') ?></span></td>
                    <td><span class="<?= e(badge_class($promo['Estado'])) ?>"><?= e(estado_label($promo['Estado'])) ?></span></td>
                    <td class="actions">
                        <a class="btn btn-ghost btn-sm" href="<?= e(base_url('admin/promocion_form.php?id=' . (int) $promo['ID_Promocion'])) ?>">Editar</a>
                        <form method="post" action="<?= e(base_url('admin/promociones.php')) ?>" style="display:inline;">
                            <?= csrf_field() ?>
                            <input type="hidden" name="action" value="toggle">
                            <input type="hidden" name="promotion_id" value="<?= (int) $promo['ID_Promocion'] ?>">
                            <button class="btn btn-ghost btn-sm" type="submit"><?= $promo['Estado'] === 'activa' ? 'Desactivar' : 'Activar' ?></button>
                        </form>
                        <form method="post" action="<?= e(base_url('admin/promociones.php')) ?>" data-confirm="¿Eliminar esta promoción?" style="display:inline;">
                            <?= csrf_field() ?>
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="promotion_id" value="<?= (int) $promo['ID_Promocion'] ?>">
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
