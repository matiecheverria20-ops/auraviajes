<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/data.php';
require_permission('gestionar_paquetes');

$id = get_int('id');
$isEdit = $id > 0;
$package = $isEdit ? get_package($id) : null;
if ($isEdit && $package === null) {
    redirect_with('admin/paquetes.php', 'error', 'El paquete solicitado no existe.');
}

$values = [
    'Codigo' => $package['Codigo'] ?? '',
    'Nombre' => $package['Nombre'] ?? '',
    'Descripcion' => $package['Descripcion'] ?? '',
    'DuracionDias' => $package['DuracionDias'] ?? 7,
    'PrecioBase' => $package['PrecioBase'] ?? '',
    'Estado' => $package['Estado'] ?? 'borrador',
];
$selectedDestinations = $isEdit ? array_map(static fn(array $row): int => (int) $row['ID_Destino'], package_destinations($id)) : [];
$selectedCategories = $isEdit ? array_map(static fn(array $row): int => (int) $row['ID_CategoriaPaquete'], package_categories($id)) : [];
$selectedServices = $isEdit ? array_map(static fn(array $row): int => (int) $row['ID_Servicio'], array_filter(package_services($id), static fn(array $row): bool => (bool) $row['Incluido'])) : [];

$destinations = all_destinations();
$categories = db_all('SELECT * FROM categorias_paquete ORDER BY Nombre');
$services = db_all('SELECT * FROM servicios ORDER BY Nombre');
$errors = [];

if (is_post()) {
    verify_csrf();
    $values = [
        'Codigo' => post_string('Codigo'),
        'Nombre' => post_string('Nombre'),
        'Descripcion' => post_string('Descripcion'),
        'DuracionDias' => post_int('DuracionDias'),
        'PrecioBase' => post_string('PrecioBase'),
        'Estado' => post_string('Estado'),
    ];
    $selectedDestinations = array_values(array_filter(array_map('intval', $_POST['destinos'] ?? [])));
    $selectedCategories = array_values(array_filter(array_map('intval', $_POST['categorias'] ?? [])));
    $selectedServices = array_values(array_filter(array_map('intval', $_POST['servicios'] ?? [])));

    if ($values['Nombre'] === '') {
        $errors[] = 'El nombre del paquete es obligatorio.';
    }
    if ($values['DuracionDias'] < 1) {
        $errors[] = 'La duración debe ser de al menos 1 día.';
    }
    if (!is_numeric($values['PrecioBase']) || (float) $values['PrecioBase'] < 0) {
        $errors[] = 'El precio base debe ser un importe válido.';
    }
    if (!in_array($values['Estado'], ['borrador', 'publicado', 'pausado', 'finalizado'], true)) {
        $values['Estado'] = 'borrador';
    }
    if ($selectedDestinations === []) {
        $errors[] = 'Seleccioná al menos un destino.';
    }
    if ($values['Codigo'] !== '') {
        $codeParams = [':code' => $values['Codigo']];
        $codeSql = 'SELECT ID_Paquete FROM paquetes WHERE Codigo = :code';
        if ($isEdit) {
            $codeSql .= ' AND ID_Paquete <> :id';
            $codeParams[':id'] = $id;
        }
        if (db_val($codeSql, $codeParams) !== null) {
            $errors[] = 'Ese código de paquete ya está en uso.';
        }
    }

    if ($errors === []) {
        try {
            $pdo = db();
            $pdo->beginTransaction();
            if ($values['Codigo'] === '') {
                $values['Codigo'] = generate_code('paquetes', 'Codigo', 'PKG');
            }
            $price = round((float) $values['PrecioBase'], 2);
            if ($isEdit) {
                db_exec(
                    'UPDATE paquetes SET Codigo = :codigo, Nombre = :nombre, Descripcion = :descripcion, DuracionDias = :duracion,
                            PrecioBase = :precio, Estado = :estado WHERE ID_Paquete = :id',
                    [
                        ':codigo' => $values['Codigo'],
                        ':nombre' => $values['Nombre'],
                        ':descripcion' => $values['Descripcion'] !== '' ? $values['Descripcion'] : null,
                        ':duracion' => $values['DuracionDias'],
                        ':precio' => $price,
                        ':estado' => $values['Estado'],
                        ':id' => $id,
                    ]
                );
                $packageId = $id;
            } else {
                db_exec(
                    'INSERT INTO paquetes (Codigo, Nombre, Descripcion, DuracionDias, PrecioBase, Estado)
                     VALUES (:codigo, :nombre, :descripcion, :duracion, :precio, :estado)',
                    [
                        ':codigo' => $values['Codigo'],
                        ':nombre' => $values['Nombre'],
                        ':descripcion' => $values['Descripcion'] !== '' ? $values['Descripcion'] : null,
                        ':duracion' => $values['DuracionDias'],
                        ':precio' => $price,
                        ':estado' => $values['Estado'],
                    ]
                );
                $packageId = (int) $pdo->lastInsertId();
            }

            db_exec('DELETE FROM paquete_destino WHERE FK_ID_Paquete = :id', [':id' => $packageId]);
            foreach ($selectedDestinations as $index => $destinationId) {
                db_exec(
                    'INSERT INTO paquete_destino (FK_ID_Paquete, FK_ID_Destino, Orden) VALUES (:package, :destination, :orden)',
                    [':package' => $packageId, ':destination' => $destinationId, ':orden' => $index + 1]
                );
            }

            db_exec('DELETE FROM paquete_categoria WHERE FK_ID_Paquete = :id', [':id' => $packageId]);
            foreach ($selectedCategories as $categoryId) {
                db_exec(
                    'INSERT INTO paquete_categoria (FK_ID_Paquete, FK_ID_CategoriaPaquete) VALUES (:package, :category)',
                    [':package' => $packageId, ':category' => $categoryId]
                );
            }

            db_exec('DELETE FROM paquete_servicio WHERE FK_ID_Paquete = :id', [':id' => $packageId]);
            foreach ($selectedServices as $serviceId) {
                db_exec(
                    'INSERT INTO paquete_servicio (FK_ID_Paquete, FK_ID_Servicio, Incluido) VALUES (:package, :service, 1)',
                    [':package' => $packageId, ':service' => $serviceId]
                );
            }

            db_exec(
                'DELETE FROM precios WHERE FK_ID_Paquete = :id AND Tipo = "base" AND FechaHasta IS NULL',
                [':id' => $packageId]
            );
            $currency = db_val('SELECT ID_Moneda FROM monedas WHERE Codigo = "ARS" LIMIT 1');
            if ($currency !== null) {
                db_exec(
                    "INSERT INTO precios (FK_ID_Paquete, FK_ID_Moneda, Importe, FechaDesde, FechaHasta, Tipo)
                     VALUES (:package, :currency, :price, CURDATE(), NULL, 'base')",
                    [':package' => $packageId, ':currency' => (int) $currency, ':price' => $price]
                );
            }

            $pdo->commit();
            audit('paquetes', $isEdit ? 'UPDATE' : 'INSERT', (string) $packageId, ($isEdit ? 'Actualización' : 'Alta') . ' del paquete ' . $values['Nombre']);
            redirect_with('admin/paquete_ver.php?id=' . $packageId, 'success', 'Paquete guardado correctamente.');
        } catch (Throwable $e) {
            if (db()->inTransaction()) {
                db()->rollBack();
            }
            $errors[] = 'No se pudo guardar el paquete. Revisá los datos e intentá nuevamente.';
            error_log('[AuraViajes] Error paquete_form: ' . $e->getMessage());
        }
    }
}

$pageTitle = $isEdit ? 'Editar paquete' : 'Nuevo paquete';
$activeSection = 'paquetes';
$breadcrumbs = ['Panel' => 'admin/index.php', 'Paquetes' => 'admin/paquetes.php', $pageTitle => ''];
require __DIR__ . '/../includes/admin_header.php';
?>
<form method="post" action="<?= e(base_url('admin/paquete_form.php' . ($isEdit ? '?id=' . $id : ''))) ?>" data-confirm="¿Guardar el paquete?">
    <?= csrf_field() ?>
    <div class="detail-grid">
        <div class="stack">
            <div class="card">
                <div class="card-header"><h2>Información del paquete</h2></div>
                <div class="card-body">
                    <?php foreach ($errors as $error): ?>
                        <div class="alert alert-error"><?= e($error) ?></div>
                    <?php endforeach; ?>
                    <div class="form-grid">
                        <div class="field">
                            <label for="Nombre">Nombre *</label>
                            <input type="text" id="Nombre" name="Nombre" value="<?= e($values['Nombre']) ?>" required maxlength="120">
                        </div>
                        <div class="field">
                            <label for="Codigo">Código</label>
                            <input type="text" id="Codigo" name="Codigo" value="<?= e($values['Codigo']) ?>" maxlength="30" placeholder="Se genera automáticamente">
                        </div>
                        <div class="field">
                            <label for="DuracionDias">Duración (días) *</label>
                            <input type="number" id="DuracionDias" name="DuracionDias" value="<?= (int) $values['DuracionDias'] ?>" min="1" max="365" required>
                        </div>
                        <div class="field">
                            <label for="PrecioBase">Precio base (ARS) *</label>
                            <input type="number" id="PrecioBase" name="PrecioBase" value="<?= e((string) $values['PrecioBase']) ?>" min="0" step="0.01" required>
                        </div>
                        <div class="field">
                            <label for="Estado">Estado</label>
                            <select id="Estado" name="Estado">
                                <?php foreach (['borrador', 'publicado', 'pausado', 'finalizado'] as $option): ?>
                                    <option value="<?= e($option) ?>" <?= $values['Estado'] === $option ? 'selected' : '' ?>><?= e(estado_label($option)) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="field span-2">
                            <label for="Descripcion">Descripción</label>
                            <textarea id="Descripcion" name="Descripcion" rows="6"><?= e($values['Descripcion']) ?></textarea>
                            <small>Se muestra en la ficha pública del paquete.</small>
                        </div>
                    </div>
                </div>
            </div>
            <div class="card">
                <div class="card-header"><h2>Servicios incluidos</h2></div>
                <div class="card-body">
                    <?php if ($services === []): ?>
                        <p class="muted small mb-0">No hay servicios cargados.</p>
                    <?php endif; ?>
                    <div class="check-grid">
                        <?php foreach ($services as $service): ?>
                            <label class="check-item">
                                <input type="checkbox" name="servicios[]" value="<?= (int) $service['ID_Servicio'] ?>" <?= in_array((int) $service['ID_Servicio'], $selectedServices, true) ? 'checked' : '' ?>>
                                <span><?= e($service['Nombre']) ?></span>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
        <div class="stack">
            <div class="card">
                <div class="card-header"><h2>Destinos</h2></div>
                <div class="card-body">
                    <div class="field">
                        <label for="destinos">Destinos del itinerario * <small class="muted">(Ctrl/Cmd para varios)</small></label>
                        <select id="destinos" name="destinos[]" multiple size="10" required>
                            <?php foreach ($destinations as $dest): ?>
                                <option value="<?= (int) $dest['ID_Destino'] ?>" <?= in_array((int) $dest['ID_Destino'], $selectedDestinations, true) ? 'selected' : '' ?>><?= e($dest['NombreUbicacion']) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <small>El orden de selección define el orden del recorrido.</small>
                    </div>
                </div>
            </div>
            <div class="card">
                <div class="card-header"><h2>Categorías</h2></div>
                <div class="card-body">
                    <div class="check-grid">
                        <?php foreach ($categories as $category): ?>
                            <label class="check-item">
                                <input type="checkbox" name="categorias[]" value="<?= (int) $category['ID_CategoriaPaquete'] ?>" <?= in_array((int) $category['ID_CategoriaPaquete'], $selectedCategories, true) ? 'checked' : '' ?>>
                                <span><?= e($category['Nombre']) ?></span>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            <div class="card">
                <div class="card-body">
                    <button class="btn btn-primary btn-block" type="submit"><?= $isEdit ? 'Guardar cambios' : 'Crear paquete' ?></button>
                    <a class="btn btn-ghost btn-block" href="<?= e(base_url('admin/paquetes.php')) ?>" style="margin-top:.5rem;">Cancelar</a>
                </div>
            </div>
        </div>
    </div>
</form>
<?php require __DIR__ . '/../includes/admin_footer.php'; ?>
