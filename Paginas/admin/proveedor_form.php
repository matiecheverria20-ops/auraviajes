<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/data.php';
require_permission('gestionar_proveedores');

$id = get_int('id');
$isEdit = $id > 0;
$provider = $isEdit ? db_one('SELECT * FROM proveedores WHERE ID_Proveedor = :id', [':id' => $id]) : null;
if ($isEdit && $provider === null) {
    redirect_with('admin/proveedores.php', 'error', 'El proveedor solicitado no existe.');
}
$contact = $isEdit ? db_one('SELECT * FROM proveedor_contacto WHERE FK_ID_Proveedor = :id ORDER BY ID_ProveedorContacto ASC LIMIT 1', [':id' => $id]) : null;
$typeIds = $isEdit ? array_map(static fn(array $row): int => (int) $row['FK_ID_TipoProveedor'], db_all('SELECT FK_ID_TipoProveedor FROM proveedor_tipo WHERE FK_ID_Proveedor = :id', [':id' => $id])) : [];
$serviceIds = $isEdit ? array_map(static fn(array $row): int => (int) $row['FK_ID_Servicio'], db_all('SELECT FK_ID_Servicio FROM proveedor_servicio WHERE FK_ID_Proveedor = :id', [':id' => $id])) : [];

$values = [
    'Nombre' => $provider['Nombre'] ?? '',
    'Contacto' => $provider['Contacto'] ?? '',
    'ContactoNombre' => $contact['Nombre'] ?? '',
    'Cargo' => $contact['Cargo'] ?? '',
    'Telefono' => $contact['Telefono'] ?? '',
    'Email' => $contact['Email'] ?? '',
];
$types = db_all('SELECT * FROM tipos_proveedor ORDER BY Nombre');
$services = db_all('SELECT * FROM servicios ORDER BY Nombre');
$errors = [];

if (is_post()) {
    verify_csrf();
    $values = [
        'Nombre' => post_string('Nombre'),
        'Contacto' => post_string('Contacto'),
        'ContactoNombre' => post_string('ContactoNombre'),
        'Cargo' => post_string('Cargo'),
        'Telefono' => post_string('Telefono'),
        'Email' => post_string('Email'),
    ];
    $typeIds = array_values(array_filter(array_map('intval', $_POST['tipos'] ?? [])));
    $serviceIds = array_values(array_filter(array_map('intval', $_POST['servicios'] ?? [])));
    if ($values['Nombre'] === '') {
        $errors[] = 'El nombre del proveedor es obligatorio.';
    }
    if ($values['Email'] !== '' && !filter_var($values['Email'], FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'El email de contacto no es válido.';
    }
    if ($typeIds === []) {
        $errors[] = 'Seleccioná al menos un tipo de proveedor.';
    }

    if ($errors === []) {
        try {
            $pdo = db();
            $pdo->beginTransaction();
            if ($isEdit) {
                db_exec(
                    'UPDATE proveedores SET Nombre = :nombre, Contacto = :contacto WHERE ID_Proveedor = :id',
                    [':nombre' => $values['Nombre'], ':contacto' => $values['Contacto'] !== '' ? $values['Contacto'] : null, ':id' => $id]
                );
                $providerId = $id;
            } else {
                db_exec(
                    'INSERT INTO proveedores (Nombre, Contacto) VALUES (:nombre, :contacto)',
                    [':nombre' => $values['Nombre'], ':contacto' => $values['Contacto'] !== '' ? $values['Contacto'] : null]
                );
                $providerId = (int) $pdo->lastInsertId();
            }

            if ($values['ContactoNombre'] !== '' || $values['Email'] !== '' || $values['Telefono'] !== '') {
                $params = [
                    ':proveedor' => $providerId,
                    ':nombre' => $values['ContactoNombre'] !== '' ? $values['ContactoNombre'] : 'Contacto principal',
                    ':cargo' => $values['Cargo'] !== '' ? $values['Cargo'] : null,
                    ':telefono' => $values['Telefono'] !== '' ? $values['Telefono'] : null,
                    ':email' => $values['Email'] !== '' ? $values['Email'] : null,
                ];
                if ($contact !== null) {
                    $params[':id'] = (int) $contact['ID_ProveedorContacto'];
                    db_exec('UPDATE proveedor_contacto SET Nombre = :nombre, Cargo = :cargo, Telefono = :telefono, Email = :email WHERE ID_ProveedorContacto = :id', $params);
                } else {
                    db_exec(
                        'INSERT INTO proveedor_contacto (FK_ID_Proveedor, Nombre, Cargo, Telefono, Email) VALUES (:proveedor, :nombre, :cargo, :telefono, :email)',
                        $params
                    );
                }
            }

            db_exec('DELETE FROM proveedor_tipo WHERE FK_ID_Proveedor = :id', [':id' => $providerId]);
            foreach ($typeIds as $typeId) {
                db_exec('INSERT INTO proveedor_tipo (FK_ID_Proveedor, FK_ID_TipoProveedor) VALUES (:proveedor, :tipo)', [':proveedor' => $providerId, ':tipo' => $typeId]);
            }
            db_exec('DELETE FROM proveedor_servicio WHERE FK_ID_Proveedor = :id', [':id' => $providerId]);
            foreach ($serviceIds as $serviceId) {
                db_exec('INSERT INTO proveedor_servicio (FK_ID_Proveedor, FK_ID_Servicio) VALUES (:proveedor, :servicio)', [':proveedor' => $providerId, ':servicio' => $serviceId]);
            }

            $pdo->commit();
            audit('proveedores', $isEdit ? 'UPDATE' : 'INSERT', (string) $providerId, ($isEdit ? 'Actualización' : 'Alta') . ' de proveedor ' . $values['Nombre']);
            redirect_with('admin/proveedores.php', 'success', 'Proveedor guardado correctamente.');
        } catch (Throwable $e) {
            if (db()->inTransaction()) {
                db()->rollBack();
            }
            error_log('[AuraViajes] Error proveedor_form: ' . $e->getMessage());
            $errors[] = 'No se pudo guardar el proveedor.';
        }
    }
}

$pageTitle = $isEdit ? 'Editar proveedor' : 'Nuevo proveedor';
$activeSection = 'proveedores';
$breadcrumbs = ['Panel' => 'admin/index.php', 'Proveedores' => 'admin/proveedores.php', $pageTitle => ''];
require __DIR__ . '/../includes/admin_header.php';
?>
<form method="post" action="<?= e(base_url('admin/proveedor_form.php' . ($isEdit ? '?id=' . $id : ''))) ?>" data-confirm="¿Guardar el proveedor?">
    <?= csrf_field() ?>
    <div class="detail-grid">
        <div class="stack">
            <div class="card">
                <div class="card-header"><h2>Datos del proveedor</h2></div>
                <div class="card-body">
                    <?php foreach ($errors as $error): ?><div class="alert alert-error"><?= e($error) ?></div><?php endforeach; ?>
                    <div class="form-grid">
                        <div class="field span-2">
                            <label for="Nombre">Nombre *</label>
                            <input type="text" id="Nombre" name="Nombre" value="<?= e($values['Nombre']) ?>" required maxlength="100">
                        </div>
                        <div class="field span-2">
                            <label for="Contacto">Contacto general</label>
                            <input type="text" id="Contacto" name="Contacto" value="<?= e($values['Contacto']) ?>" maxlength="200">
                        </div>
                        <div class="field"><label for="ContactoNombre">Persona de contacto</label><input type="text" id="ContactoNombre" name="ContactoNombre" value="<?= e($values['ContactoNombre']) ?>" maxlength="100"></div>
                        <div class="field"><label for="Cargo">Cargo</label><input type="text" id="Cargo" name="Cargo" value="<?= e($values['Cargo']) ?>" maxlength="80"></div>
                        <div class="field"><label for="Telefono">Teléfono</label><input type="text" id="Telefono" name="Telefono" value="<?= e($values['Telefono']) ?>" maxlength="30"></div>
                        <div class="field"><label for="Email">Email</label><input type="email" id="Email" name="Email" value="<?= e($values['Email']) ?>" maxlength="100"></div>
                    </div>
                </div>
            </div>
        </div>
        <div class="stack">
            <div class="card">
                <div class="card-header"><h2>Tipo de proveedor *</h2></div>
                <div class="card-body">
                    <div class="check-grid" style="grid-template-columns:1fr;">
                        <?php foreach ($types as $type): ?>
                            <label class="check-item">
                                <input type="checkbox" name="tipos[]" value="<?= (int) $type['ID_TipoProveedor'] ?>" <?= in_array((int) $type['ID_TipoProveedor'], $typeIds, true) ? 'checked' : '' ?>>
                                <span><?= e($type['Nombre']) ?></span>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            <div class="card">
                <div class="card-header"><h2>Servicios</h2></div>
                <div class="card-body">
                    <div class="check-grid" style="grid-template-columns:1fr;">
                        <?php foreach ($services as $service): ?>
                            <label class="check-item">
                                <input type="checkbox" name="servicios[]" value="<?= (int) $service['ID_Servicio'] ?>" <?= in_array((int) $service['ID_Servicio'], $serviceIds, true) ? 'checked' : '' ?>>
                                <span><?= e($service['Nombre']) ?></span>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            <div class="card">
                <div class="card-body">
                    <button class="btn btn-primary btn-block" type="submit"><?= $isEdit ? 'Guardar cambios' : 'Crear proveedor' ?></button>
                    <a class="btn btn-ghost btn-block" href="<?= e(base_url('admin/proveedores.php')) ?>" style="margin-top:.5rem;">Cancelar</a>
                </div>
            </div>
        </div>
    </div>
</form>
<?php require __DIR__ . '/../includes/admin_footer.php'; ?>
