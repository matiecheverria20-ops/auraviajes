<?php
declare(strict_types=1);

const STAFF_ROLES = ['Administrador', 'Gerente', 'Agente', 'Contador'];
const SESSION_IDLE_SECONDS = 7200;

function current_user(bool $refresh = false): ?array
{
    static $cached = null;
    static $loaded = false;

    if ($loaded && !$refresh) {
        return $cached;
    }
    $loaded = true;
    $cached = null;

    $userId = $_SESSION['user_id'] ?? null;
    if ($userId === null) {
        return null;
    }

    if (isset($_SESSION['last_activity']) && (time() - (int) $_SESSION['last_activity']) > SESSION_IDLE_SECONDS) {
        logout_user(false);
        flash('warning', 'La sesión expiró por inactividad. Volvé a ingresar.');
        return null;
    }
    $_SESSION['last_activity'] = time();

    $user = db_one(
        'SELECT u.ID_PasajeroOnline, u.Email, u.FechaRegistro,
                e.ID_Empleado, e.Nombre, e.Apellido, e.DNI, e.Telefono, e.Cargo,
                e.Estado AS EmpleadoEstado, e.FechaIngreso
         FROM pasajeroonline u
         LEFT JOIN empleados e ON e.FK_ID_PasajeroOnline = u.ID_PasajeroOnline
         WHERE u.ID_PasajeroOnline = :id',
        [':id' => (int) $userId]
    );

    if ($user === null) {
        logout_user(false);
        return null;
    }

    if ($user['ID_Empleado'] !== null && $user['EmpleadoEstado'] !== 'activo') {
        logout_user(false);
        flash('error', 'Tu cuenta de empleado está inactiva.');
        return null;
    }

    $roles = db_all(
        'SELECT r.ID_Rol, r.Nombre, r.Descripcion
         FROM usuario_rol ur
         INNER JOIN roles r ON r.ID_Rol = ur.FK_ID_Rol
         WHERE ur.FK_ID_PasajeroOnline = :id
         ORDER BY r.ID_Rol',
        [':id' => (int) $userId]
    );
    $roleNames = array_column($roles, 'Nombre');
    if (array_intersect(STAFF_ROLES, $roleNames) === []) {
        logout_user(false);
        return null;
    }

    $permissions = db_all(
        'SELECT DISTINCT p.Nombre
         FROM usuario_rol ur
         INNER JOIN rol_permiso rp ON rp.FK_ID_Rol = ur.FK_ID_Rol
         INNER JOIN permisos p ON p.ID_Permiso = rp.FK_ID_Permiso
         WHERE ur.FK_ID_PasajeroOnline = :id',
        [':id' => (int) $userId]
    );

    $user['roles'] = $roles;
    $user['role_names'] = $roleNames;
    $user['permissions'] = array_column($permissions, 'Nombre');
    $user['primary_role'] = user_primary_role($roleNames);
    $cached = $user;
    return $cached;
}

function user_primary_role(array $roleNames): string
{
    foreach (STAFF_ROLES as $role) {
        if (in_array($role, $roleNames, true)) {
            return $role;
        }
    }
    return $roleNames[0] ?? '—';
}

function is_admin(): bool
{
    $user = current_user();
    return $user !== null && in_array('Administrador', $user['role_names'], true);
}

function can(string $capability): bool
{
    $user = current_user();
    if ($user === null) {
        return false;
    }
    if (in_array('Administrador', $user['role_names'], true)) {
        return true;
    }
    if (in_array($capability, $user['permissions'], true)) {
        return true;
    }

    $fallback = [
        'ver_clientes' => ['Agente', 'Gerente', 'Contador'],
        'ver_reservas' => ['Agente', 'Gerente', 'Contador'],
        'ver_pagos' => ['Agente', 'Gerente', 'Contador'],
        'ver_paquetes' => ['Agente', 'Gerente'],
        'gestionar_paquetes' => ['Agente', 'Gerente'],
        'gestionar_proveedores' => ['Agente', 'Gerente'],
        'gestionar_contenido' => [],
        'gestionar_empleados' => [],
        'gestionar_usuarios' => [],
        'ver_auditoria' => ['Gerente'],
        'ver_reportes' => ['Gerente', 'Contador'],
    ];
    $allowedRoles = $fallback[$capability] ?? [];
    return array_intersect($allowedRoles, $user['role_names']) !== [];
}

function can_any(array $capabilities): bool
{
    foreach ($capabilities as $capability) {
        if (can($capability)) {
            return true;
        }
    }
    return false;
}

function require_login(): void
{
    if (current_user() === null) {
        $next = $_SERVER['REQUEST_URI'] ?? '';
        if ($next !== '') {
            $_SESSION['_intended'] = $next;
        }
        flash('warning', 'Ingresá con tu cuenta para acceder al sistema.');
        redirect('login.php');
    }
}

function require_guest(): void
{
    if (current_user() !== null) {
        redirect('admin/index.php');
    }
}

function require_permission(string $capability): void
{
    require_login();
    if (!can($capability)) {
        forbidden('No tenés permisos para acceder a este módulo.');
    }
}

function require_any_permission(array $capabilities): void
{
    require_login();
    if (!can_any($capabilities)) {
        forbidden('No tenés permisos para acceder a este módulo.');
    }
}

function forbidden(string $message = 'Acceso denegado.'): never
{
    http_response_code(403);
    echo '<!DOCTYPE html><html lang="es"><head><meta charset="utf-8"><title>Acceso denegado</title>';
    echo '<link rel="stylesheet" href="' . e(asset('css/admin.css')) . '"></head><body class="auth-body">';
    echo '<main class="error-card"><span class="error-code">403</span><h1>Acceso denegado</h1><p>' . e($message) . '</p>';
    echo '<div class="error-actions"><a class="btn btn-primary" href="' . e(base_url('admin/index.php')) . '">Volver al panel</a>';
    echo '<a class="btn btn-ghost" href="' . e(base_url('logout.php')) . '">Cerrar sesión</a></div></main></body></html>';
    exit;
}

function login_user(string $email, string $password): array
{
    $email = mb_strtolower(trim($email));
    if ($email === '' || $password === '') {
        return [false, 'Completá tu correo y contraseña.'];
    }

    $attempts = (int) ($_SESSION['_login_attempts'] ?? 0);
    $blockedUntil = (int) ($_SESSION['_login_blocked_until'] ?? 0);
    if ($blockedUntil > time()) {
        return [false, 'Demasiados intentos fallidos. Esperá un minuto antes de volver a intentar.'];
    }

    $user = db_one(
        'SELECT ID_PasajeroOnline, Email, Contrasena_Hash
         FROM pasajeroonline
         WHERE LOWER(Email) = :email',
        [':email' => $email]
    );

    $valid = false;
    if ($user !== null) {
        $hash = (string) $user['Contrasena_Hash'];
        $valid = str_starts_with($hash, '$2') && password_verify($password, $hash);
    }

    if (!$valid) {
        $attempts++;
        $_SESSION['_login_attempts'] = $attempts;
        if ($attempts >= 5) {
            $_SESSION['_login_blocked_until'] = time() + 60;
            $_SESSION['_login_attempts'] = 0;
        }
        usleep(250_000);
        return [false, 'Correo o contraseña incorrectos.'];
    }

    $roles = db_all(
        'SELECT r.Nombre
         FROM usuario_rol ur
         INNER JOIN roles r ON r.ID_Rol = ur.FK_ID_Rol
         WHERE ur.FK_ID_PasajeroOnline = :id',
        [':id' => (int) $user['ID_PasajeroOnline']]
    );
    $roleNames = array_column($roles, 'Nombre');
    if (array_intersect(STAFF_ROLES, $roleNames) === []) {
        return [false, 'La cuenta no tiene acceso al sistema de gestión.'];
    }

    $employee = db_one(
        'SELECT Estado FROM empleados WHERE FK_ID_PasajeroOnline = :id LIMIT 1',
        [':id' => (int) $user['ID_PasajeroOnline']]
    );
    if ($employee !== null && $employee['Estado'] !== 'activo') {
        return [false, 'La cuenta de empleado está inactiva.'];
    }

    if (password_needs_rehash((string) $user['Contrasena_Hash'], PASSWORD_DEFAULT)) {
        db_exec(
            'UPDATE pasajeroonline SET Contrasena_Hash = :hash WHERE ID_PasajeroOnline = :id',
            [':hash' => password_hash($password, PASSWORD_DEFAULT), ':id' => (int) $user['ID_PasajeroOnline']]
        );
    }

    unset($_SESSION['_login_attempts'], $_SESSION['_login_blocked_until']);
    session_regenerate_id(true);
    $_SESSION['user_id'] = (int) $user['ID_PasajeroOnline'];
    $_SESSION['last_activity'] = time();
    current_user(true);
    audit('pasajeroonline', 'LOGIN', (string) $user['ID_PasajeroOnline'], 'Inicio de sesión en el back office');
    return [true, ''];
}

function logout_user(bool $audit = true): void
{
    if ($audit) {
        $userId = $_SESSION['user_id'] ?? null;
        if ($userId !== null) {
            audit('pasajeroonline', 'LOGOUT', (string) $userId, 'Cierre de sesión');
        }
    }
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], (bool) $params['secure'], (bool) $params['httponly']);
    }
    if (session_status() === PHP_SESSION_ACTIVE) {
        session_destroy();
    }
}

function current_employee_id(): ?int
{
    $user = current_user();
    return isset($user['ID_Empleado']) && $user['ID_Empleado'] !== null ? (int) $user['ID_Empleado'] : null;
}
