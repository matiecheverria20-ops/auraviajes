<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/data.php';
require_permission('gestionar_contenido');

$id = get_int('id');
$isEdit = $id > 0;
$banner = $isEdit ? db_one('SELECT * FROM banners WHERE ID_Banner = :id', [':id' => $id]) : null;
if ($isEdit && $banner === null) {
    redirect_with('admin/banners.php', 'error', 'El banner solicitado no existe.');
}
$values = [
    'Titulo' => $banner['Titulo'] ?? '',
    'Texto' => $banner['Texto'] ?? '',
    'Link' => $banner['Link'] ?? '',
    'Orden' => $banner['Orden'] ?? 1,
    'Activo' => $banner['Activo'] ?? 1,
];
$errors = [];

if (is_post()) {
    verify_csrf();
    $values = [
        'Titulo' => post_string('Titulo'),
        'Texto' => post_string('Texto'),
        'Link' => post_string('Link'),
        'Orden' => post_int('Orden', 1),
        'Activo' => isset($_POST['Activo']) ? 1 : 0,
    ];
    if ($values['Titulo'] === '') {
        $errors[] = 'El título es obligatorio.';
    }
    if ($values['Orden'] < 1) {
        $values['Orden'] = 1;
    }
    $link = $values['Link'];
    if ($link !== '' && !str_starts_with($link, '/') && !filter_var($link, FILTER_VALIDATE_URL)) {
        $errors[] = 'El enlace debe ser una URL válida o una ruta interna que empiece con /.';
    }

    $newImage = null;
    try {
        $newImage = save_upload($_FILES['imagen'] ?? [], 'banners');
    } catch (Throwable $e) {
        $errors[] = $e->getMessage();
    }
    $removeImage = isset($_POST['remove_image']);

    if ($errors === []) {
        $image = $banner['Imagen'] ?? null;
        if ($newImage !== null) {
            remove_upload($image);
            $image = $newImage;
        } elseif ($removeImage) {
            remove_upload($image);
            $image = null;
        }
        if ($isEdit) {
            db_exec(
                'UPDATE banners SET Titulo = :titulo, Texto = :texto, Imagen = :imagen, Link = :link, Orden = :orden, Activo = :activo WHERE ID_Banner = :id',
                [
                    ':titulo' => $values['Titulo'],
                    ':texto' => $values['Texto'] !== '' ? $values['Texto'] : null,
                    ':imagen' => $image,
                    ':link' => $link !== '' ? $link : null,
                    ':orden' => $values['Orden'],
                    ':activo' => $values['Activo'],
                    ':id' => $id,
                ]
            );
            audit('banners', 'UPDATE', (string) $id, 'Actualización de banner');
        } else {
            db_exec(
                'INSERT INTO banners (Titulo, Texto, Imagen, Link, Orden, Activo) VALUES (:titulo, :texto, :imagen, :link, :orden, :activo)',
                [
                    ':titulo' => $values['Titulo'],
                    ':texto' => $values['Texto'] !== '' ? $values['Texto'] : null,
                    ':imagen' => $image,
                    ':link' => $link !== '' ? $link : null,
                    ':orden' => $values['Orden'],
                    ':activo' => $values['Activo'],
                ]
            );
            $id = (int) db()->lastInsertId();
            audit('banners', 'INSERT', (string) $id, 'Alta de banner');
        }
        redirect_with('admin/banners.php', 'success', 'Banner guardado correctamente.');
    }
}

$pageTitle = $isEdit ? 'Editar banner' : 'Nuevo banner';
$activeSection = 'contenido';
$breadcrumbs = ['Panel' => 'admin/index.php', 'Contenido' => 'admin/contenido.php', 'Banners' => 'admin/banners.php', $pageTitle => ''];
require __DIR__ . '/../includes/admin_header.php';
?>
<div class="card">
    <div class="card-header"><h2><?= e($pageTitle) ?></h2><a class="small" href="<?= e(base_url('admin/banners.php')) ?>">Volver</a></div>
    <div class="card-body">
        <?php foreach ($errors as $error): ?><div class="alert alert-error"><?= e($error) ?></div><?php endforeach; ?>
        <form method="post" action="<?= e(base_url('admin/banner_form.php' . ($isEdit ? '?id=' . $id : ''))) ?>" enctype="multipart/form-data" data-confirm="¿Guardar el banner?">
            <?= csrf_field() ?>
            <div class="form-grid">
                <div class="field">
                    <label for="Titulo">Título *</label>
                    <input type="text" id="Titulo" name="Titulo" value="<?= e($values['Titulo']) ?>" required maxlength="150">
                </div>
                <div class="field">
                    <label for="Orden">Orden</label>
                    <input type="number" id="Orden" name="Orden" value="<?= (int) $values['Orden'] ?>" min="1">
                </div>
                <div class="field span-2">
                    <label for="Texto">Texto</label>
                    <textarea id="Texto" name="Texto" rows="3"><?= e($values['Texto']) ?></textarea>
                </div>
                <div class="field span-2">
                    <label for="Link">Enlace</label>
                    <input type="text" id="Link" name="Link" value="<?= e($values['Link']) ?>" maxlength="255" placeholder="/paquetes.php o https://...">
                </div>
                <div class="field">
                    <label for="imagen">Imagen</label>
                    <input type="file" id="imagen" name="imagen" accept="image/jpeg,image/png,image/webp,image/gif">
                    <small>JPG, PNG, WEBP o GIF. Máximo 4 MB.</small>
                </div>
                <div class="field">
                    <label>Vista previa</label>
                    <?php $current = $banner['Imagen'] ?? null; ?>
                    <?php if ($current): ?>
                        <?php $currentPath = str_starts_with($current, 'uploads/') ? $current : 'assets/img/' . $current; ?>
                        <div style="display:flex;flex-direction:column;gap:.4rem;">
                            <span class="table-thumb" style="width:180px;height:90px;background-image:url('<?= e(asset($currentPath)) ?>');"></span>
                            <label class="check-item"><input type="checkbox" name="remove_image" value="1"> Quitar imagen actual</label>
                        </div>
                    <?php else: ?>
                        <span class="muted small">Sin imagen cargada.</span>
                    <?php endif; ?>
                </div>
                <div class="field">
                    <label class="check-item"><input type="checkbox" name="Activo" value="1" <?= (int) $values['Activo'] === 1 ? 'checked' : '' ?>> Banner activo</label>
                </div>
            </div>
            <div class="form-actions">
                <button class="btn btn-primary" type="submit">Guardar banner</button>
                <a class="btn btn-ghost" href="<?= e(base_url('admin/banners.php')) ?>">Cancelar</a>
            </div>
        </form>
    </div>
</div>
<?php require __DIR__ . '/../includes/admin_footer.php'; ?>
