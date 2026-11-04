<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/data.php';
require_permission('gestionar_empleados');

$id = get_int('id');
$isEdit = $id > 0;
$employee = $isEdit ? db_one('SELECT * FROM empleados WHERE ID_Empleado = :id', [':id' => $id]) : null;
if ($isEdit && $employee === null) {
    redirect_with('admin/empleados.php', 'error', 'El empleado solicitado no existe.');
}
$user = ($isEdit && $employee['FK_ID_PasajeroOnline'] !== null)
    ? db_one('SELECT * FROM pasajeroonline WHERE ID_PasajeroOnline = :id', [':id' => $employee['FK_ID_PasajeroOnline']])
    : null;
$roles = db_all("SELECT * FROM roles WHERE Nombre <> 'Cliente' ORDER BY ID_Rol ASC");
$selectedRoles = ($isEdit && $employee['FK_ID_PasajeroOnline'] !== null)
    ? array_map(static fn(array $row): int => (int) $row['FK_ID_Rol'], db_all(
        'SELECT ur.FK_ID_Rol FROM usuario_rol ur INNER JOIN roles r ON r.ID_Rol = ur.FK_ID_Rol WHERE ur.FK_ID_PasajeroOnline = :id AND r.Nombre <> "Cliente"',
        [':id' => $employee['FK_ID_PasajeroOnline']]
    ))
    : [];
$branches = db_all('SELECT * FROM establecimiento ORDER BY Nombre');
$selectedBranch = $isEdit
    ? (int) db_val('SELECT FK_ID_Establecimiento FROM empleado_sucursal WHERE FK_ID_Empleado = :id ORDER BY Principal DESC, ID_EmpleadoSucursal ASC LIMIT 1', [':id' => $id], 0)
    : 0;
$values = [
    'Nombre' => $employee['Nombre'] ?? '',
    'Apellido' => $employee['Apellido'] ?? '',
    'DNI' => $employee['DNI'] ?? '',
    'Email' => $user['Email'] ?? ($employee['Email'] ?? ''),
    'Telefono' => $employee['Telefono'] ?? '',
    'Cargo' => $employee['Cargo'] ?? 'Agente',
    'FechaIngreso' => $employee['FechaIngreso'] ?? date('Y-m-d'),
    'Estado' => $employee['Estado'] ?? 'activo',
];
$errors = [];

if (is_post()) {
    verify_csrf();
    $values = [
        'Nombre' => post_string('Nombre'),
        'Apellido' => post_string('Apellido'),
        'DNI' => post_string('DNI'),
        'Email' => mb_strtolower(post_string('Email')),
        'Telefono' => post_string('Telefono'),
        'Cargo' => post_string('Cargo'),
        'FechaIngreso' => post_string('FechaIngreso'),
        'Estado' => post_string('Estado'),
    ];
    $password = (string) ($_POST['password'] ?? '');
    $selectedRoles = array_values(array_filter(array_map('intval', $_POST['roles'] ?? [])));
    $selectedBranch = post_int('sucursal');
    $currentUser = current_user();

    if ($values['Nombre'] === '' || $values['Apellido'] === '' || $values['DNI'] === '' || $values['Cargo'] === '') {
        $errors[] = 'Nombre, apellido, DNI y cargo son obligatorios.';
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
    if ($password === '' && (!$isEdit || $employee['FK_ID_PasajeroOnline'] === null)) {
        $errors[] = 'Definí una contraseña para habilitar el acceso al sistema.';
    }
    if ($selectedRoles === []) {
        $errors[] = 'Seleccioná al menos un rol de acceso.';
    }
    if (db_val('SELECT ID_Empleado FROM empleados WHERE DNI = :dni AND ID_Empleado <> :id', [':dni' => $values['DNI'], ':id' => $id]) !== null) {
        $errors[] = 'Ya existe un empleado con ese DNI.';
    }
    if ($isEdit && (int) ($currentUser['ID_Empleado'] ?? 0) === $id) {
        if ($values['Estado'] !== 'activo') {
            $errors[] = 'No podés desactivar tu propia cuenta.';
        }
        $adminRoleId = (int) db_val('SELECT ID_Rol FROM roles WHERE Nombre = "Administrador" LIMIT 1', [], 0);
        if (!in_array($adminRoleId, $selectedRoles, true)) {
            $errors[] = 'No podés quitarte el rol Administrador a tu propia cuenta.';
        }
    }

    if ($errors === []) {
        try {
            $pdo = db();
            $pdo->beginTransaction();
            $userId = $isEdit ? ($employee['FK_ID_PasajeroOnline'] !== null ? (int) $employee['FK_ID_PasajeroOnline'] : 0) : 0;
            if ($userId <= 0) {
                $existing = db_one('SELECT ID_PasajeroOnline FROM pasajeroonline WHERE LOWER(Email) = :email', [':email' => $values['Email']]);
                if ($existing !== null) {
                    $linked = db_val('SELECT ID_Empleado FROM empleados WHERE FK_ID_PasajeroOnline = :id AND ID_Empleado <> :employee', [':id' => (int) $existing['ID_PasajeroOnline'], ':employee' => $id]);
                    if ($linked !== null) {
                        throw new RuntimeException('La cuenta de ese correo ya está vinculada a otro empleado.');
                    }
                    $userId = (int) $existing['ID_PasajeroOnline'];
                    db_exec(
                        'UPDATE pasajeroonline SET Contrasena_Hash = :hash WHERE ID_PasajeroOnline = :id',
                        [':hash' => password_hash($password, PASSWORD_DEFAULT), ':id' => $userId]
                    );
                } else {
                    db_exec(
                        'INSERT INTO pasajeroonline (Email, Contrasena_Hash) VALUES (:email, :hash)',
                        [':email' => $values['Email'], ':hash' => password_hash($password, PASSWORD_DEFAULT)]
                    );
                    $userId = (int) $pdo->lastInsertId();
                }
            } else {
                $params = [':email' => $values['Email'], ':id' => $userId];
                $sql = 'UPDATE pasajeroonline SET Email = :email';
                if ($password !== '') {
                    $sql .= ', Contrasena_Hash = :hash';
                    $params[':hash'] = password_hash($password, PASSWORD_DEFAULT);
                }
                $sql .= ' WHERE ID_PasajeroOnline = :id';
                db_exec($sql, $params);
            }

            $params = [
                ':user' => $userId,
                ':nombre' => $values['Nombre'],
                ':apellido' => $values['Apellido'],
                ':dni' => $values['DNI'],
                ':email' => $values['Email'],
                ':telefono' => $values['Telefono'] !== '' ? $values['Telefono'] : null,
                ':cargo' => $values['Cargo'],
                ':fecha' => $values['FechaIngreso'] !== '' ? $values['FechaIngreso'] : null,
                ':estado' => $values['Estado'],
            ];
            if ($isEdit) {
                $params[':id'] = $id;
                db_exec(
                    'UPDATE empleados SET FK_ID_PasajeroOnline = :user, Nombre = :nombre, Apellido = :apellido, DNI = :dni,
                            Email = :email, Telefono = :telefono, Cargo = :cargo, FechaIngreso = :fecha, Estado = :estado
                     WHERE ID_Empleado = :id',
                    $params
                );
                $employeeId = $id;
            } else {
                db_exec(
                    'INSERT INTO empleados (FK_ID_PasajeroOnline, Nombre, Apellido, DNI, Email, Telefono, Cargo, FechaIngreso, Estado)
                     VALUES (:user, :nombre, :apellido, :dni, :email, :telefono, :cargo, :fecha, :estado)',
                    $params
                );
                $employeeId = (int) $pdo->lastInsertId();
            }

            db_exec(
                'DELETE ur FROM usuario_rol ur INNER JOIN roles r ON r.ID_Rol = ur.FK_ID_Rol
                 WHERE ur.FK_ID_PasajeroOnline = :user AND r.Nombre <> "Cliente"',
                [':user' => $userId]
            );
            foreach ($selectedRoles as $roleId) {
                db_exec(
                    'INSERT IGNORE INTO usuario_rol (FK_ID_PasajeroOnline, FK_ID_Rol) VALUES (:user, :rol)',
                    [':user' => $userId, ':rol' => $roleId]
                );
            }

            db_exec('DELETE FROM empleado_sucursal WHERE FK_ID_Empleado = :id', [':id' => $employeeId]);
            if ($selectedBranch > 0) {
                db_exec(
                    'INSERT INTO empleado_sucursal (FK_ID_Empleado, FK_ID_Establecimiento, FechaDesde, Principal) VALUES (:empleado, :sucursal, CURDATE(), 1)',
                    [':empleado' => $employeeId, ':sucursal' => $selectedBranch]
                );
            }

            $pdo->commit();
            audit('empleados', $isEdit ? 'UPDATE' : 'INSERT', (string) $employeeId, ($isEdit ? 'Actualización' : 'Alta') . ' de empleado ' . $values['Nombre'] . ' ' . $values['Apellido']);
            redirect_with('admin/empleados.php', 'success', 'Empleado guardado correctamente.');
        } catch (Throwable $e) {
            if (db()->inTransaction()) {
                db()->rollBack();
            }
            error_log('[AuraViajes] Error empleado_form: ' . $e->getMessage());
            $errors[] = $e instanceof RuntimeException ? $e->getMessage() : 'No se pudo guardar el empleado.';
        }
    }
}

$pageTitle = $isEdit ? 'Editar empleado' : 'Nuevo empleado';
$activeSection = 'empleados';
$breadcrumbs = ['Panel' => 'admin/index.php', 'Empleados' => 'admin/empleados.php', $pageTitle => ''];
require __DIR__ . '/../includes/admin_header.php';
?>
<form method="post" action="<?= e(base_url('admin/empleado_form.php' . ($isEdit ? '?id=' . $id : ''))) ?>" data-confirm="¿Guardar el empleado?">
    <?= csrf_field() ?>
    <div class="detail-grid">
        <div class="stack">
            <div class="card">
                <div class="card-header"><h2>Datos del empleado</h2></div>
                <div class="card-body">
                    <?php foreach ($errors as $error): ?><div class="alert alert-error"><?= e($error) ?></div><?php endforeach; ?>
                    <div class="form-grid">
                        <div class="field"><label for="Nombre">Nombre *</label><input type="text" id="Nombre" name="Nombre" value="<?= e($values['Nombre']) ?>" required maxlength="60"></div>
                        <div class="field"><label for="Apellido">Apellido *</label><input type="text" id="Apellido" name="Apellido" value="<?= e($values['Apellido']) ?>" required maxlength="60"></div>
                        <div class="field"><label for="DNI">DNI *</label><input type="text" id="DNI" name="DNI" value="<?= e($values['DNI']) ?>" required maxlength="20"></div>
                        <div class="field"><label for="Telefono">Teléfono</label><input type="text" id="Telefono" name="Telefono" value="<?= e($values['Telefono']) ?>" maxlength="30"></div>
                        <div class="field"><label for="Cargo">Cargo *</label><input type="text" id="Cargo" name="Cargo" value="<?= e($values['Cargo']) ?>" required maxlength="80"></div>
                        <div class="field"><label for="FechaIngreso">Fecha de ingreso</label><input type="date" id="FechaIngreso" name="FechaIngreso" value="<?= e($values['FechaIngreso']) ?>"></div>
                        <div class="field"><label for="Estado">Estado</label>
                            <select id="Estado" name="Estado">
                                <option value="activo" <?= $values['Estado'] === 'activo' ? 'selected' : '' ?>>Activo</option>
                                <option value="inactivo" <?= $values['Estado'] === 'inactivo' ? 'selected' : '' ?>>Inactivo</option>
                            </select>
                        </div>
                        <div class="field"><label for="sucursal">Sucursal</label>
                            <select id="sucursal" name="sucursal">
                                <option value="">Sin sucursal</option>
                                <?php foreach ($branches as $branch): ?>
                                    <option value="<?= (int) $branch['ID_Establecimiento'] ?>" <?= $selectedBranch === (int) $branch['ID_Establecimiento'] ? 'selected' : '' ?>><?= e($branch['Nombre']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                </div>
            </div>
            <div class="card">
                <div class="card-header"><h2>Acceso al sistema</h2></div>
                <div class="card-body">
                    <div class="form-grid">
                        <div class="field"><label for="Email">Correo de acceso *</label><input type="email" id="Email" name="Email" value="<?= e($values['Email']) ?>" required maxlength="100"></div>
                        <div class="field"><label for="password">Contraseña <?= $isEdit ? '(opcional)' : '*' ?></label><input type="password" id="password" name="password" minlength="8" autocomplete="new-password" <?= !$isEdit ? 'required' : '' ?>><small><?= $isEdit ? 'Dejala vacía para conservar la contraseña actual.' : 'Mínimo 8 caracteres.' ?></small></div>
                    </div>
                </div>
            </div>
        </div>
        <div class="stack">
            <div class="card">
                <div class="card-header"><h2>Roles *</h2></div>
                <div class="card-body">
                    <div class="check-grid" style="grid-template-columns:1fr;">
                        <?php foreach ($roles as $role): ?>
                            <label class="check-item">
                                <input type="checkbox" name="roles[]" value="<?= (int) $role['ID_Rol'] ?>" <?= in_array((int) $role['ID_Rol'], $selectedRoles, true) ? 'checked' : '' ?>>
                                <span><strong><?= e($role['Nombre']) ?></strong><br><small class="muted"><?= e($role['Descripcion'] ?? '') ?></small></span>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            <div class="card">
                <div class="card-body">
                    <button class="btn btn-primary btn-block" type="submit"><?= $isEdit ? 'Guardar cambios' : 'Crear empleado' ?></button>
                    <a class="btn btn-ghost btn-block" href="<?= e(base_url('admin/empleados.php')) ?>" style="margin-top:.5rem;">Cancelar</a>
                </div>
            </div>
        </div>
    </div>
</form>
<?php require __DIR__ . '/../includes/admin_footer.php'; ?>
