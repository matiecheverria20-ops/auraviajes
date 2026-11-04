<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/bootstrap.php';
require_login();

$user = current_user(true);
$errors = [];

if (is_post()) {
    verify_csrf();
    $action = post_string('action');
    if ($action === 'password') {
        $current = (string) ($_POST['current_password'] ?? '');
        $new = (string) ($_POST['new_password'] ?? '');
        $confirm = (string) ($_POST['confirm_password'] ?? '');
        $row = db_one('SELECT Contrasena_Hash FROM pasajeroonline WHERE ID_PasajeroOnline = :id', [':id' => (int) $user['ID_PasajeroOnline']]);
        if ($row === null || !password_verify($current, (string) $row['Contrasena_Hash'])) {
            $errors[] = 'La contraseña actual no es correcta.';
        }
        if (mb_strlen($new) < 8) {
            $errors[] = 'La nueva contraseña debe tener al menos 8 caracteres.';
        }
        if ($new !== $confirm) {
            $errors[] = 'La confirmación de la nueva contraseña no coincide.';
        }
        if ($errors === []) {
            db_exec(
                'UPDATE pasajeroonline SET Contrasena_Hash = :hash WHERE ID_PasajeroOnline = :id',
                [':hash' => password_hash($new, PASSWORD_DEFAULT), ':id' => (int) $user['ID_PasajeroOnline']]
            );
            audit('pasajeroonline', 'UPDATE', (string) $user['ID_PasajeroOnline'], 'Cambio de contraseña propio');
            flash('success', 'Contraseña actualizada correctamente.');
            redirect('admin/perfil.php');
        }
    }
}

$fullName = trim((string) ($user['Nombre'] ?? '') . ' ' . (string) ($user['Apellido'] ?? ''));
$pageTitle = 'Mi perfil';
$activeSection = 'perfil';
$breadcrumbs = ['Panel' => 'admin/index.php', 'Mi perfil' => ''];
require __DIR__ . '/../includes/admin_header.php';
?>
<div class="detail-grid">
    <div class="card">
        <div class="card-header">
            <h2>Datos de la cuenta</h2>
        </div>
        <div class="card-body">
            <dl class="summary-list">
                <div><dt>Nombre</dt><dd><?= e($fullName !== '' ? $fullName : 'Sin datos') ?></dd></div>
                <div><dt>Correo electrónico</dt><dd><?= e($user['Email']) ?></dd></div>
                <div><dt>Rol principal</dt><dd><?= e($user['primary_role']) ?></dd></div>
                <div><dt>Cargo</dt><dd><?= e($user['Cargo'] ?? '—') ?></dd></div>
                <div><dt>DNI</dt><dd><?= e($user['DNI'] ?? '—') ?></dd></div>
                <div><dt>Teléfono</dt><dd><?= e($user['Telefono'] ?? '—') ?></dd></div>
                <div><dt>Fecha de ingreso</dt><dd><?= e(fecha($user['FechaIngreso'] ?? null)) ?></dd></div>
                <div><dt>Roles asignados</dt><dd><?= e(implode(', ', $user['role_names'])) ?></dd></div>
            </dl>
            <p class="small muted mt-2">Para modificar tus datos laborales, contactá al administrador del sistema.</p>
        </div>
    </div>
    <div class="stack">
        <div class="card">
            <div class="card-header"><h2>Cambiar contraseña</h2></div>
            <div class="card-body">
                <?php foreach ($errors as $error): ?>
                    <div class="alert alert-error"><?= e($error) ?></div>
                <?php endforeach; ?>
                <form method="post" action="<?= e(base_url('admin/perfil.php')) ?>" data-confirm="¿Confirmás el cambio de contraseña?">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="password">
                    <div class="form-grid" style="grid-template-columns:1fr;">
                        <div class="field">
                            <label for="current_password">Contraseña actual</label>
                            <input type="password" id="current_password" name="current_password" required autocomplete="current-password">
                        </div>
                        <div class="field">
                            <label for="new_password">Nueva contraseña</label>
                            <input type="password" id="new_password" name="new_password" required minlength="8" autocomplete="new-password">
                            <small>Mínimo 8 caracteres. Usá letras, números y símbolos.</small>
                        </div>
                        <div class="field">
                            <label for="confirm_password">Repetir nueva contraseña</label>
                            <input type="password" id="confirm_password" name="confirm_password" required minlength="8" autocomplete="new-password">
                        </div>
                    </div>
                    <div class="form-actions">
                        <button class="btn btn-primary" type="submit">Actualizar contraseña</button>
                    </div>
                </form>
            </div>
        </div>
        <div class="card">
            <div class="card-header"><h2>Permisos efectivos</h2></div>
            <div class="card-body">
                <?php if ($user['permissions'] === []): ?>
                    <p class="muted small mb-0">Sin permisos explícitos registrados. Se aplican los permisos por rol.</p>
                <?php else: ?>
                    <div class="flex gap-1" style="flex-wrap:wrap;">
                        <?php foreach ($user['permissions'] as $permission): ?>
                            <span class="chip"><?= e(str_replace('_', ' ', $permission)) ?></span>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
<?php require __DIR__ . '/../includes/admin_footer.php'; ?>
