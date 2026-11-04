<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/data.php';
require_permission('gestionar_paquetes');

$id = get_int('id');
$isEdit = $id > 0;
$destination = $isEdit ? db_one(
    'SELECT d.*, di.Descripcion, di.Clima, di.Moneda, di.Recomendaciones
     FROM destino d LEFT JOIN destino_informacion di ON di.FK_ID_Destino = d.ID_Destino
     WHERE d.ID_Destino = :id',
    [':id' => $id]
) : null;
if ($isEdit && $destination === null) {
    redirect_with('admin/destinos.php', 'error', 'El destino solicitado no existe.');
}

$values = [
    'NombreUbicacion' => $destination['NombreUbicacion'] ?? '',
    'Link' => $destination['Link'] ?? '',
    'Descripcion' => $destination['Descripcion'] ?? '',
    'Clima' => $destination['Clima'] ?? '',
    'Moneda' => $destination['Moneda'] ?? '',
    'Recomendaciones' => $destination['Recomendaciones'] ?? '',
];
$selectedCategories = $isEdit
    ? array_map(static fn(array $row): int => (int) $row['FK_ID_CategoriaDestino'], db_all('SELECT FK_ID_CategoriaDestino FROM destino_categoria WHERE FK_ID_Destino = :id', [':id' => $id]))
    : [];
$categories = db_all('SELECT * FROM categorias_destino ORDER BY Nombre');
$errors = [];

if (is_post()) {
    verify_csrf();
    foreach (array_keys($values) as $key) {
        $values[$key] = post_string($key);
    }
    $selectedCategories = array_values(array_filter(array_map('intval', $_POST['categorias'] ?? [])));

    if ($values['NombreUbicacion'] === '') {
        $errors[] = 'El nombre del destino es obligatorio.';
    }
    $nameParams = [':name' => $values['NombreUbicacion']];
    $nameSql = 'SELECT ID_Destino FROM destino WHERE NombreUbicacion = :name';
    if ($isEdit) {
        $nameSql .= ' AND ID_Destino <> :id';
        $nameParams[':id'] = $id;
    }
    if (db_val($nameSql, $nameParams) !== null) {
        $errors[] = 'Ya existe un destino con ese nombre.';
    }
    if ($values['Link'] !== '' && !filter_var($values['Link'], FILTER_VALIDATE_URL)) {
        $errors[] = 'El enlace debe ser una URL válida.';
    }

    if ($errors === []) {
        try {
            $pdo = db();
            $pdo->beginTransaction();
            if ($isEdit) {
                db_exec(
                    'UPDATE destino SET NombreUbicacion = :name, Link = :link WHERE ID_Destino = :id',
                    [':name' => $values['NombreUbicacion'], ':link' => $values['Link'] !== '' ? $values['Link'] : null, ':id' => $id]
                );
                $destinationId = $id;
            } else {
                db_exec(
                    'INSERT INTO destino (NombreUbicacion, Link) VALUES (:name, :link)',
                    [':name' => $values['NombreUbicacion'], ':link' => $values['Link'] !== '' ? $values['Link'] : null]
                );
                $destinationId = (int) $pdo->lastInsertId();
            }

            $info = db_val('SELECT ID_InformacionDestino FROM destino_informacion WHERE FK_ID_Destino = :id', [':id' => $destinationId]);
            $infoParams = [
                ':descripcion' => $values['Descripcion'] !== '' ? $values['Descripcion'] : 'Destino turístico.',
                ':clima' => $values['Clima'] !== '' ? $values['Clima'] : null,
                ':moneda' => $values['Moneda'] !== '' ? $values['Moneda'] : null,
                ':recomendaciones' => $values['Recomendaciones'] !== '' ? $values['Recomendaciones'] : null,
                ':destino' => $destinationId,
            ];
            if ($info !== null) {
                db_exec(
                    'UPDATE destino_informacion SET Descripcion = :descripcion, Clima = :clima, Moneda = :moneda,
                            Recomendaciones = :recomendaciones WHERE FK_ID_Destino = :destino',
                    $infoParams
                );
            } else {
                db_exec(
                    'INSERT INTO destino_informacion (FK_ID_Destino, Descripcion, Clima, Moneda, Recomendaciones)
                     VALUES (:destino, :descripcion, :clima, :moneda, :recomendaciones)',
                    $infoParams
                );
            }

            db_exec('DELETE FROM destino_categoria WHERE FK_ID_Destino = :id', [':id' => $destinationId]);
            foreach ($selectedCategories as $categoryId) {
                db_exec(
                    'INSERT INTO destino_categoria (FK_ID_Destino, FK_ID_CategoriaDestino) VALUES (:destino, :categoria)',
                    [':destino' => $destinationId, ':categoria' => $categoryId]
                );
            }

            $pdo->commit();
            audit('destino', $isEdit ? 'UPDATE' : 'INSERT', (string) $destinationId, ($isEdit ? 'Actualización' : 'Alta') . ' de destino ' . $values['NombreUbicacion']);
            redirect_with('admin/destinos.php', 'success', 'Destino guardado correctamente.');
        } catch (Throwable $e) {
            if (db()->inTransaction()) {
                db()->rollBack();
            }
            error_log('[AuraViajes] Error destino_form: ' . $e->getMessage());
            $errors[] = 'No se pudo guardar el destino.';
        }
    }
}

$pageTitle = $isEdit ? 'Editar destino' : 'Nuevo destino';
$activeSection = 'destinos';
$breadcrumbs = ['Panel' => 'admin/index.php', 'Destinos' => 'admin/destinos.php', $pageTitle => ''];
require __DIR__ . '/../includes/admin_header.php';
?>
<form method="post" action="<?= e(base_url('admin/destino_form.php' . ($isEdit ? '?id=' . $id : ''))) ?>" data-confirm="¿Guardar el destino?">
    <?= csrf_field() ?>
    <div class="detail-grid">
        <div class="stack">
            <div class="card">
                <div class="card-header"><h2>Datos del destino</h2></div>
                <div class="card-body">
                    <?php foreach ($errors as $error): ?>
                        <div class="alert alert-error"><?= e($error) ?></div>
                    <?php endforeach; ?>
                    <div class="form-grid">
                        <div class="field span-2">
                            <label for="NombreUbicacion">Nombre y ubicación *</label>
                            <input type="text" id="NombreUbicacion" name="NombreUbicacion" value="<?= e($values['NombreUbicacion']) ?>" required maxlength="100" placeholder="Ej: Puerto Iguazú, Misiones">
                        </div>
                        <div class="field span-2">
                            <label for="Link">Enlace de mapa</label>
                            <input type="url" id="Link" name="Link" value="<?= e($values['Link']) ?>" maxlength="200" placeholder="https://maps.google.com/...">
                        </div>
                        <div class="field span-2">
                            <label for="Descripcion">Descripción *</label>
                            <textarea id="Descripcion" name="Descripcion" rows="7" required><?= e($values['Descripcion']) ?></textarea>
                        </div>
                        <div class="field">
                            <label for="Clima">Clima</label>
                            <input type="text" id="Clima" name="Clima" value="<?= e($values['Clima']) ?>" maxlength="120" placeholder="Ej: Cálido y húmedo">
                        </div>
                        <div class="field">
                            <label for="Moneda">Moneda</label>
                            <input type="text" id="Moneda" name="Moneda" value="<?= e($values['Moneda']) ?>" maxlength="80" placeholder="Ej: Real brasileño">
                        </div>
                        <div class="field span-2">
                            <label for="Recomendaciones">Recomendaciones</label>
                            <textarea id="Recomendaciones" name="Recomendaciones" rows="4"><?= e($values['Recomendaciones']) ?></textarea>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="stack">
            <div class="card">
                <div class="card-header"><h2>Categorías</h2></div>
                <div class="card-body">
                    <div class="check-grid">
                        <?php foreach ($categories as $category): ?>
                            <label class="check-item">
                                <input type="checkbox" name="categorias[]" value="<?= (int) $category['ID_CategoriaDestino'] ?>" <?= in_array((int) $category['ID_CategoriaDestino'], $selectedCategories, true) ? 'checked' : '' ?>>
                                <span><?= e($category['Nombre']) ?></span>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            <div class="card">
                <div class="card-body">
                    <button class="btn btn-primary btn-block" type="submit"><?= $isEdit ? 'Guardar cambios' : 'Crear destino' ?></button>
                    <a class="btn btn-ghost btn-block" href="<?= e(base_url('admin/destinos.php')) ?>" style="margin-top:.5rem;">Cancelar</a>
                </div>
            </div>
        </div>
    </div>
</form>
<?php require __DIR__ . '/../includes/admin_footer.php'; ?>
