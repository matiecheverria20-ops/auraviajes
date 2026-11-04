<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/data.php';
require_permission('gestionar_contenido');

if (is_post()) {
    verify_csrf();
    $faqId = post_int('faq_id');
    $faq = db_one('SELECT * FROM preguntas_frecuentes WHERE ID_FAQ = :id', [':id' => $faqId]);
    if ($faq === null) {
        redirect_with('admin/faqs.php', 'error', 'La pregunta indicada no existe.');
    }
    if (post_string('action') === 'toggle') {
        db_exec('UPDATE preguntas_frecuentes SET Activo = :active WHERE ID_FAQ = :id', [':active' => $faq['Activo'] ? 0 : 1, ':id' => $faqId]);
        audit('preguntas_frecuentes', 'UPDATE', (string) $faqId, 'Cambio de visibilidad');
        redirect_with('admin/faqs.php', 'success', 'Visibilidad actualizada.');
    }
    if (post_string('action') === 'delete') {
        db_exec('DELETE FROM preguntas_frecuentes WHERE ID_FAQ = :id', [':id' => $faqId]);
        audit('preguntas_frecuentes', 'DELETE', (string) $faqId, 'Baja de pregunta frecuente');
        redirect_with('admin/faqs.php', 'success', 'Pregunta eliminada.');
    }
}

$faqs = db_all('SELECT * FROM preguntas_frecuentes ORDER BY Orden ASC, ID_FAQ ASC');
$pageTitle = 'Preguntas frecuentes';
$activeSection = 'contenido';
$breadcrumbs = ['Panel' => 'admin/index.php', 'Contenido' => 'admin/contenido.php', 'FAQ' => ''];
$pageActions = '<a class="btn btn-accent" href="' . e(base_url('admin/faq_form.php')) . '">Nueva pregunta</a>';
require __DIR__ . '/../includes/admin_header.php';
?>
<div class="card">
    <div class="card-header"><h2>Preguntas frecuentes</h2><a class="small" href="<?= e(base_url('admin/contenido.php')) ?>">Volver a contenido</a></div>
    <div class="table-wrap">
        <table class="data-table">
            <thead><tr><th>Orden</th><th>Pregunta</th><th>Respuesta</th><th>Estado</th><th></th></tr></thead>
            <tbody>
            <?php if ($faqs === []): ?>
                <tr><td colspan="5" class="table-empty">No hay preguntas cargadas.</td></tr>
            <?php endif; ?>
            <?php foreach ($faqs as $faq): ?>
                <tr>
                    <td><?= (int) $faq['Orden'] ?></td>
                    <td><strong><?= e($faq['Pregunta']) ?></strong></td>
                    <td><span class="small muted"><?= e(summary_text($faq['Respuesta'], 140)) ?></span></td>
                    <td><span class="<?= e(badge_class($faq['Activo'] ? 'activo' : 'inactivo')) ?>"><?= $faq['Activo'] ? 'Activa' : 'Inactiva' ?></span></td>
                    <td class="actions">
                        <a class="btn btn-ghost btn-sm" href="<?= e(base_url('admin/faq_form.php?id=' . (int) $faq['ID_FAQ'])) ?>">Editar</a>
                        <form method="post" action="<?= e(base_url('admin/faqs.php')) ?>" style="display:inline;">
                            <?= csrf_field() ?>
                            <input type="hidden" name="action" value="toggle">
                            <input type="hidden" name="faq_id" value="<?= (int) $faq['ID_FAQ'] ?>">
                            <button class="btn btn-ghost btn-sm" type="submit"><?= $faq['Activo'] ? 'Ocultar' : 'Mostrar' ?></button>
                        </form>
                        <form method="post" action="<?= e(base_url('admin/faqs.php')) ?>" data-confirm="¿Eliminar esta pregunta?" style="display:inline;">
                            <?= csrf_field() ?>
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="faq_id" value="<?= (int) $faq['ID_FAQ'] ?>">
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
