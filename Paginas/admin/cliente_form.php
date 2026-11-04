<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/data.php';
require_permission('gestionar_clientes');

$id = get_int('id');
$isEdit = $id > 0;
$client = $isEdit ? get_client($id) : null;
if ($isEdit && $client === null) {
    redirect_with('admin/clientes.php', 'error', 'El cliente solicitado no existe.');
}

$values = [
    'Nombre' => $client['Nombre'] ?? '',
    'Apellido' => $client['Apellido'] ?? '',
    'DNI' => $client['DNI'] ?? '',
    'Email' => $client['Email'] ?? '',
    'Telefono' => $client['Telefono'] ?? '',
    'Nacionalidad' => $client['Nacionalidad'] ?? 'Argentina',
    'FechaNacimiento' => isset($client['FechaNacimiento']) ? fecha_input($client['FechaNacimiento']) : '',
    'Estado' => $client['Estado'] ?? 'activo',
    'Observaciones' => $client['Observaciones'] ?? '',
];
$errors = [];

if (is_post()) {
    verify_csrf();
    foreach (array_keys($values) as $key) {
        $values[$key] = post_string($key);
    }
    $values['FechaNacimiento'] = post_string('FechaNacimiento');
    $password = (string) ($_POST['password'] ?? '');

    if ($values['Nombre'] === '') {
        $errors[] = 'El nombre es obligatorio.';
    }
    if ($values['Apellido'] === '') {
        $errors[] = 'El apellido es obligatorio.';
    }
    if ($values['DNI'] === '') {
        $errors[] = 'El DNI es obligatorio.';
    }
    if (!filter_var($values['Email'], FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Ingresá un correo electrónico válido.';
    }
    if (!in_array($values['Estado'], ['activo', 'inactivo'], true)) {
        $values['Estado'] = 'activo';
    }
    if ($password !== '' && mb_strlen($password) < 8) {
        $errors[] = 'La contraseña debe tener al menos 8 caracteres.';
    }

    if ($values['Email'] !== '') {
        $emailParams = [':email' => mb_strtolower($values['Email'])];
        $emailSql = 'SELECT ID_PasajeroOnline FROM pasajeroonline WHERE LOWER(Email) = :email';
        if ($isEdit && $client['FK_ID_PasajeroOnline'] !== null) {
            $emailSql .= ' AND ID_PasajeroOnline <> :id';
            $emailParams[':id'] = (int) $client['FK_ID_PasajeroOnline'];
        }
        if (db_val($emailSql, $emailParams) !== null) {
            $errors[] = 'Ese correo ya está registrado en otro usuario.';
        }
    }
    if ($values['DNI'] !== '') {
        $dniParams = [':dni' => $values['DNI']];
        $dniSql = 'SELECT ID_Pasajero FROM pasajero WHERE DNI = :dni';
        if ($isEdit && isset($client['ID_Pasajero'])) {
            $dniSql .= ' AND ID_Pasajero <> :id';
            $dniParams[':id'] = (int) $client['ID_Pasajero'];
        }
        if (db_val($dniSql, $dniParams) !== null) {
            $errors[] = 'Ese DNI ya está registrado en otro pasajero.';
        }
    }

    if ($errors === []) {
        try {
            $pdo = db();
            $pdo->beginTransaction();
            $userId = $isEdit ? (int) $client['FK_ID_PasajeroOnline'] : 0;
            $birthDate = $values['FechaNacimiento'] !== '' ? $values['FechaNacimiento'] : null;
            $temporaryPassword = null;

            if (!$isEdit || $userId <= 0) {
                $temporaryPassword = $password !== '' ? $password : bin2hex(random_bytes(6));
                db_exec(
                    'INSERT INTO pasajeroonline (Email, Contrasena_Hash, FechaNacimiento) VALUES (:email, :hash, :birth)',
                    [
                        ':email' => mb_strtolower($values['Email']),
                        ':hash' => password_hash($temporaryPassword, PASSWORD_DEFAULT),
                        ':birth' => $birthDate,
                    ]
                );
                $userId = (int) $pdo->lastInsertId();
                $clientRole = db_val('SELECT ID_Rol FROM roles WHERE Nombre = :name LIMIT 1', [':name' => 'Cliente']);
                if ($clientRole !== null) {
                    db_exec(
                        'INSERT INTO usuario_rol (FK_ID_PasajeroOnline, FK_ID_Rol) VALUES (:user, :role)',
                        [':user' => $userId, ':role' => (int) $clientRole]
                    );
                }
                if ($isEdit) {
                    db_exec(
                        'UPDATE clientes SET FK_ID_PasajeroOnline = :user WHERE ID_Cliente = :id',
                        [':user' => $userId, ':id' => $id]
                    );
                }
            } else {
                $userSql = 'UPDATE pasajeroonline SET Email = :email, FechaNacimiento = :birth';
                $userParams = [':email' => mb_strtolower($values['Email']), ':birth' => $birthDate, ':id' => $userId];
                if ($password !== '') {
                    $userSql .= ', Contrasena_Hash = :hash';
                    $userParams[':hash'] = password_hash($password, PASSWORD_DEFAULT);
                }
                $userSql .= ' WHERE ID_PasajeroOnline = :id';
                db_exec($userSql, $userParams);
            }

            if ($isEdit && isset($client['ID_Pasajero'])) {
                db_exec(
                    'UPDATE pasajero SET DNI = :dni, Nombre = :nombre, Apellido = :apellido, Telefono = :telefono, Nacionalidad = :nacionalidad
                     WHERE ID_Pasajero = :id',
                    [
                        ':dni' => $values['DNI'],
                        ':nombre' => $values['Nombre'],
                        ':apellido' => $values['Apellido'],
                        ':telefono' => $values['Telefono'] !== '' ? $values['Telefono'] : null,
                        ':nacionalidad' => $values['Nacionalidad'] !== '' ? $values['Nacionalidad'] : null,
                        ':id' => (int) $client['ID_Pasajero'],
                    ]
                );
            } else {
                db_exec(
                    'INSERT INTO pasajero (FK_ID_PasajeroOnline, DNI, Nombre, Apellido, Telefono, Nacionalidad)
                     VALUES (:user, :dni, :nombre, :apellido, :telefono, :nacionalidad)',
                    [
                        ':user' => $userId,
                        ':dni' => $values['DNI'],
                        ':nombre' => $values['Nombre'],
                        ':apellido' => $values['Apellido'],
                        ':telefono' => $values['Telefono'] !== '' ? $values['Telefono'] : null,
                        ':nacionalidad' => $values['Nacionalidad'] !== '' ? $values['Nacionalidad'] : null,
                    ]
                );
            }

            if ($isEdit) {
                db_exec(
                    'UPDATE clientes SET Estado = :estado, Observaciones = :observaciones WHERE ID_Cliente = :id',
                    [
                        ':estado' => $values['Estado'],
                        ':observaciones' => $values['Observaciones'] !== '' ? $values['Observaciones'] : null,
                        ':id' => $id,
                    ]
                );
                $clientId = $id;
                audit('clientes', 'UPDATE', (string) $id, 'Actualización de cliente ' . $values['Nombre'] . ' ' . $values['Apellido']);
            } else {
                $code = generate_code('clientes', 'CodigoCliente', 'CLI');
                db_exec(
                    'INSERT INTO clientes (FK_ID_PasajeroOnline, CodigoCliente, Estado, Observaciones)
                     VALUES (:user, :code, :estado, :observaciones)',
                    [
                        ':user' => $userId,
                        ':code' => $code,
                        ':estado' => $values['Estado'],
                        ':observaciones' => $values['Observaciones'] !== '' ? $values['Observaciones'] : null,
                    ]
                );
                $clientId = (int) $pdo->lastInsertId();
                db_exec(
                    'INSERT INTO cuentas_corrientes (FK_ID_Cliente, Saldo) VALUES (:client, 0)',
                    [':client' => $clientId]
                );
                audit('clientes', 'INSERT', (string) $clientId, 'Alta de cliente ' . $values['Nombre'] . ' ' . $values['Apellido'] . ' (' . $code . ')');
            }

            $pdo->commit();
            $message = $isEdit ? 'Cliente actualizado correctamente.' : 'Cliente creado correctamente. Código: ' . $code . '.';
            if ($temporaryPassword !== null && $password === '') {
                $message .= ' Contraseña temporal del portal: ' . $temporaryPassword;
            }
            redirect_with('admin/cliente_ver.php?id=' . $clientId, 'success', $message);
        } catch (Throwable $e) {
            if (db()->inTransaction()) {
                db()->rollBack();
            }
            $errors[] = 'No se pudo guardar el cliente. Verificá los datos e intentá nuevamente.';
            error_log('[AuraViajes] Error cliente_form: ' . $e->getMessage());
        }
    }
}

$pageTitle = $isEdit ? 'Editar cliente' : 'Nuevo cliente';
$activeSection = 'clientes';
$breadcrumbs = ['Panel' => 'admin/index.php', 'Clientes' => 'admin/clientes.php', $pageTitle => ''];
require __DIR__ . '/../includes/admin_header.php';
?>
<div class="card">
    <div class="card-header">
        <h2><?= e($pageTitle) ?></h2>
        <a class="small" href="<?= e(base_url($isEdit ? 'admin/cliente_ver.php?id=' . $id : 'admin/clientes.php')) ?>">Volver</a>
    </div>
    <div class="card-body">
        <?php foreach ($errors as $error): ?>
            <div class="alert alert-error"><?= e($error) ?></div>
        <?php endforeach; ?>
        <form method="post" action="<?= e(base_url('admin/cliente_form.php' . ($isEdit ? '?id=' . $id : ''))) ?>" data-confirm="<?= $isEdit ? '¿Guardar los cambios del cliente?' : '¿Crear el cliente?' ?>">
            <?= csrf_field() ?>
            <div class="form-section">
                <h3>Datos personales</h3>
                <div class="form-grid">
                    <div class="field">
                        <label for="Nombre">Nombre *</label>
                        <input type="text" id="Nombre" name="Nombre" value="<?= e($values['Nombre']) ?>" required maxlength="60">
                    </div>
                    <div class="field">
                        <label for="Apellido">Apellido *</label>
                        <input type="text" id="Apellido" name="Apellido" value="<?= e($values['Apellido']) ?>" required maxlength="60">
                    </div>
                    <div class="field">
                        <label for="DNI">DNI *</label>
                        <input type="text" id="DNI" name="DNI" value="<?= e($values['DNI']) ?>" required maxlength="20">
                    </div>
                    <div class="field">
                        <label for="FechaNacimiento">Fecha de nacimiento</label>
                        <input type="date" id="FechaNacimiento" name="FechaNacimiento" value="<?= e($values['FechaNacimiento']) ?>">
                    </div>
                    <div class="field">
                        <label for="Nacionalidad">Nacionalidad</label>
                        <input type="text" id="Nacionalidad" name="Nacionalidad" value="<?= e($values['Nacionalidad']) ?>" maxlength="60">
                    </div>
                    <div class="field">
                        <label for="Estado">Estado</label>
                        <select id="Estado" name="Estado">
                            <option value="activo" <?= $values['Estado'] === 'activo' ? 'selected' : '' ?>>Activo</option>
                            <option value="inactivo" <?= $values['Estado'] === 'inactivo' ? 'selected' : '' ?>>Inactivo</option>
                        </select>
                    </div>
                </div>
            </div>
            <div class="form-section">
                <h3>Contacto y acceso</h3>
                <div class="form-grid">
                    <div class="field">
                        <label for="Email">Correo electrónico *</label>
                        <input type="email" id="Email" name="Email" value="<?= e($values['Email']) ?>" required maxlength="100">
                    </div>
                    <div class="field">
                        <label for="Telefono">Teléfono</label>
                        <input type="text" id="Telefono" name="Telefono" value="<?= e($values['Telefono']) ?>" maxlength="20">
                    </div>
                    <div class="field span-2">
                        <label for="password"><?= $isEdit ? 'Nueva contraseña del portal (opcional)' : 'Contraseña del portal (opcional)' ?></label>
                        <input type="password" id="password" name="password" minlength="8" autocomplete="new-password">
                        <small><?= $isEdit ? 'Dejala vacía para conservar la contraseña actual.' : 'Si se deja vacía se generará una contraseña temporal que se mostrará al guardar.' ?></small>
                    </div>
                    <div class="field span-2">
                        <label for="Observaciones">Observaciones internas</label>
                        <textarea id="Observaciones" name="Observaciones" rows="3"><?= e($values['Observaciones']) ?></textarea>
                    </div>
                </div>
            </div>
            <div class="form-actions">
                <button class="btn btn-primary" type="submit"><?= $isEdit ? 'Guardar cambios' : 'Crear cliente' ?></button>
                <a class="btn btn-ghost" href="<?= e(base_url('admin/clientes.php')) ?>">Cancelar</a>
            </div>
        </form>
    </div>
</div>
<?php require __DIR__ . '/../includes/admin_footer.php'; ?>
