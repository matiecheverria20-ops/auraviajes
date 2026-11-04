<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/data.php';
require_permission('gestionar_contenido');

if (is_post()) {
    verify_csrf();
    $action = post_string('action');
    if ($action === 'update') {
        $sectionId = post_int('seccion_id');
        $section = db_one('SELECT * FROM secciones_landing WHERE ID_Seccion = :id', [':id' => $sectionId]);
        if ($section === null) {
            redirect_with('admin/secciones.php', 'error', 'La sección indicada no existe.');
        }
        $name = post_string('Nombre');
        $title = post_string('Titulo');
        if ($name === '') {
            redirect_with('admin/secciones.php', 'error', 'El nombre interno de la sección es obligatorio.');
        }
        db_exec(
            'UPDATE secciones_landing SET Nombre = :nombre, Titulo = :titulo, Contenido = :contenido, Orden = :orden, Activo = :activo WHERE ID_Seccion = :id',
            [
                ':nombre' => $name,
                ':titulo' => $title !== '' ? $title : null,
                ':contenido' => post_string('Contenido') !== '' ? post_string('Contenido') : null,
                ':orden' => max(1, post_int('Orden', 1)),
                ':activo' => isset($_POST['Activo']) ? 1 : 0,
                ':id' => $sectionId,
            ]
        );
        audit('secciones_landing', 'UPDATE', (string) $sectionId, 'Actualización de sección ' . $name);
        redirect_with('admin/secciones.php', 'success', 'Sección actualizada.');
    }
    if ($action === 'create') {
        $name = post_string('Nombre');
        if ($name === '') {
            redirect_with('admin/secciones.php', 'error', 'El nombre interno es obligatorio.');
        }
        db_exec(
            'INSERT INTO secciones_landing (Nombre, Titulo, Contenido, Orden, Activo) VALUES (:nombre, :titulo, :contenido, :orden, :activo)',
            [
                ':nombre' => $name,
                ':titulo' => post_string('Titulo') !== '' ? post_string('Titulo') : null,
                ':contenido' => post_string('Contenido') !== '' ? post_string('Contenido') : null,
                ':orden' => max(1, post_int('Orden', 1)),
                ':activo' => isset($_POST['Activo']) ? 1 : 0,
            ]
        );
        audit('secciones_landing', 'INSERT', (string) db()->lastInsertId(), 'Alta de sección ' . $name);
        redirect_with('admin/secciones.php', 'success', 'Sección creada.');
    }
}

$sections = db_all('SELECT * FROM secciones_landing ORDER BY Orden ASC, ID_Seccion ASC');
$pageTitle = 'Secciones de la landing';
$activeSection = 'contenido';
$breadcrumbs = ['Panel' => 'admin/index.php', 'Contenido' => 'admin/contenido.php', 'Secciones' => ''];
require __DIR__ . '/../includes/admin_header.php';
?>
<div class="stack">
    <?php foreach ($sections as $section): ?>
        <div class="card">
            <div class="card-header">
                <h2><?= e($section['Nombre']) ?></h2>
                <span class="<?= e(badge_class($section['Activo'] ? 'activo' : 'inactivo')) ?>"><?= $section['Activo'] ? 'Visible' : 'Oculta' ?></span>
            </div>
            <div class="card-body">
                <form method="post" action="<?= e(base_url('admin/secciones.php')) ?>" data-confirm="¿Guardar los cambios de esta sección?">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="update">
                    <input type="hidden" name="seccion_id" value="<?= (int) $section['ID_Seccion'] ?>">
                    <div class="form-grid">
                        <div class="field">
                            <label>Nombre interno</label>
                            <input type="text" name="Nombre" value="<?= e($section['Nombre']) ?>" required maxlength="100">
                        </div>
                        <div class="field">
                            <label>Título visible</label>
                            <input type="text" name="Titulo" value="<?= e($section['Titulo'] ?? '') ?>" maxlength="150">
                        </div>
                        <div class="field span-2">
                            <label>Contenido</label>
                            <textarea name="Contenido" rows="3"><?= e($section['Contenido'] ?? '') ?></textarea>
                        </div>
                        <div class="field">
                            <label>Orden</label>
                            <input type="number" name="Orden" value="<?= (int) $section['Orden'] ?>" min="1">
                        </div>
                        <div class="field">
                            <label class="check-item"><input type="checkbox" name="Activo" value="1" <?= $section['Activo'] ? 'checked' : '' ?>> Visible en el sitio</label>
                        </div>
                    </div>
                    <div class="form-actions">
                        <button class="btn btn-primary btn-sm" type="submit">Guardar sección</button>
                    </div>
                </form>
            </div>
        </div>
    <?php endforeach; ?>

    <div class="card">
        <div class="card-header"><h2>Agregar sección</h2></div>
        <div class="card-body">
            <form method="post" action="<?= e(base_url('admin/secciones.php')) ?>">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="create">
                <div class="form-grid">
                    <div class="field"><label>Nombre interno *</label><input type="text" name="Nombre" required maxlength="100"></div>
                    <div class="field"><label>Título visible</label><input type="text" name="Titulo" maxlength="150"></div>
                    <div class="field span-2"><label>Contenido</label><textarea name="Contenido" rows="2"></textarea></div>
                    <div class="field"><label>Orden</label><input type="number" name="Orden" value="<?= count($sections) + 1 ?>" min="1"></div>
                    <div class="field"><label class="check-item"><input type="checkbox" name="Activo" value="1" checked> Visible en el sitio</label></div>
                </div>
                <div class="form-actions"><button class="btn btn-accent" type="submit">Crear sección</button></div>
            </form>
        </div>
    </div>
</div>
<?php require __DIR__ . '/../includes/admin_footer.php'; ?>
