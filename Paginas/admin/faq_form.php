<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/data.php';
require_permission('gestionar_contenido');

$id = get_int('id');
$isEdit = $id > 0;
$faq = $isEdit ? db_one('SELECT * FROM preguntas_frecuentes WHERE ID_FAQ = :id', [':id' => $id]) : null;
if ($isEdit && $faq === null) {
    redirect_with('admin/faqs.php', 'error', 'La pregunta solicitada no existe.');
}
$values = [
    'Pregunta' => $faq['Pregunta'] ?? '',
    'Respuesta' => $faq['Respuesta'] ?? '',
    'Orden' => $faq['Orden'] ?? 1,
    'Activo' => $faq['Activo'] ?? 1,
];
$errors = [];

if (is_post()) {
    verify_csrf();
    $values = [
        'Pregunta' => post_string('Pregunta'),
        'Respuesta' => post_string('Respuesta'),
        'Orden' => post_int('Orden', 1),
        'Activo' => isset($_POST['Activo']) ? 1 : 0,
    ];
    if ($values['Pregunta'] === '') {
        $errors[] = 'La pregunta es obligatoria.';
    }
    if ($values['Respuesta'] === '') {
        $errors[] = 'La respuesta es obligatoria.';
    }
    if ($values['Orden'] < 1) {
        $values['Orden'] = 1;
    }
    if ($errors === []) {
        if ($isEdit) {
            db_exec(
                'UPDATE preguntas_frecuentes SET Pregunta = :pregunta, Respuesta = :respuesta, Orden = :orden, Activo = :activo WHERE ID_FAQ = :id',
                [':pregunta' => $values['Pregunta'], ':respuesta' => $values['Respuesta'], ':orden' => $values['Orden'], ':activo' => $values['Activo'], ':id' => $id]
            );
            audit('preguntas_frecuentes', 'UPDATE', (string) $id, 'Actualización de FAQ');
        } else {
            db_exec(
                'INSERT INTO preguntas_frecuentes (Pregunta, Respuesta, Orden, Activo) VALUES (:pregunta, :respuesta, :orden, :activo)',
                [':pregunta' => $values['Pregunta'], ':respuesta' => $values['Respuesta'], ':orden' => $values['Orden'], ':activo' => $values['Activo']]
            );
            audit('preguntas_frecuentes', 'INSERT', (string) db()->lastInsertId(), 'Alta de FAQ');
        }
        redirect_with('admin/faqs.php', 'success', 'Pregunta guardada correctamente.');
    }
}

$pageTitle = $isEdit ? 'Editar pregunta' : 'Nueva pregunta';
$activeSection = 'contenido';
$breadcrumbs = ['Panel' => 'admin/index.php', 'Contenido' => 'admin/contenido.php', 'FAQ' => 'admin/faqs.php', $pageTitle => ''];
require __DIR__ . '/../includes/admin_header.php';
?>
<div class="card">
    <div class="card-header"><h2><?= e($pageTitle) ?></h2><a class="small" href="<?= e(base_url('admin/faqs.php')) ?>">Volver</a></div>
    <div class="card-body">
        <?php foreach ($errors as $error): ?><div class="alert alert-error"><?= e($error) ?></div><?php endforeach; ?>
        <form method="post" action="<?= e(base_url('admin/faq_form.php' . ($isEdit ? '?id=' . $id : ''))) ?>" data-confirm="¿Guardar la pregunta?">
            <?= csrf_field() ?>
            <div class="form-grid">
                <div class="field span-2">
                    <label for="Pregunta">Pregunta *</label>
                    <input type="text" id="Pregunta" name="Pregunta" value="<?= e($values['Pregunta']) ?>" required maxlength="255">
                </div>
                <div class="field span-2">
                    <label for="Respuesta">Respuesta *</label>
                    <textarea id="Respuesta" name="Respuesta" rows="5" required><?= e($values['Respuesta']) ?></textarea>
                </div>
                <div class="field">
                    <label for="Orden">Orden</label>
                    <input type="number" id="Orden" name="Orden" value="<?= (int) $values['Orden'] ?>" min="1">
                </div>
                <div class="field">
                    <label class="check-item"><input type="checkbox" name="Activo" value="1" <?= (int) $values['Activo'] === 1 ? 'checked' : '' ?>> Visible en el sitio</label>
                </div>
            </div>
            <div class="form-actions">
                <button class="btn btn-primary" type="submit">Guardar pregunta</button>
                <a class="btn btn-ghost" href="<?= e(base_url('admin/faqs.php')) ?>">Cancelar</a>
            </div>
        </form>
    </div>
</div>
<?php require __DIR__ . '/../includes/admin_footer.php'; ?>
