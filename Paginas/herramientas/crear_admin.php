<?php
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('Este script solo puede ejecutarse desde la línea de comandos.');
}

require_once __DIR__ . '/../includes/bootstrap.php';

function prompt(string $label, bool $hidden = false): string
{
    fwrite(STDOUT, $label . ': ');
    if ($hidden && DIRECTORY_SEPARATOR === '/') {
        system('stty -echo');
        $value = trim((string) fgets(STDIN));
        system('stty echo');
        fwrite(STDOUT, PHP_EOL);
        return $value;
    }
    return trim((string) fgets(STDIN));
}

$email = $argv[1] ?? prompt('Correo electrónico');
$password = $argv[2] ?? prompt('Contraseña (mínimo 8 caracteres)', true);
$nombre = $argv[3] ?? prompt('Nombre');
$apellido = $argv[4] ?? prompt('Apellido');
$dni = $argv[5] ?? null;

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    fwrite(STDERR, "Correo electrónico inválido.\n");
    exit(1);
}
if (mb_strlen($password) < 8) {
    fwrite(STDERR, "La contraseña debe tener al menos 8 caracteres.\n");
    exit(1);
}
if ($nombre === '' || $apellido === '') {
    fwrite(STDERR, "Nombre y apellido son obligatorios.\n");
    exit(1);
}

try {
    db()->beginTransaction();

    $user = db_one('SELECT ID_PasajeroOnline FROM pasajeroonline WHERE LOWER(Email) = :email', [':email' => mb_strtolower($email)]);
    if ($user === null) {
        db_exec(
            'INSERT INTO pasajeroonline (Email, Contrasena_Hash, FechaRegistro) VALUES (:email, :hash, NOW())',
            [':email' => mb_strtolower($email), ':hash' => password_hash($password, PASSWORD_DEFAULT)]
        );
        $userId = (int) db()->lastInsertId();
        fwrite(STDOUT, "Usuario creado.\n");
    } else {
        $userId = (int) $user['ID_PasajeroOnline'];
        db_exec(
            'UPDATE pasajeroonline SET Email = :email, Contrasena_Hash = :hash WHERE ID_PasajeroOnline = :id',
            [':email' => mb_strtolower($email), ':hash' => password_hash($password, PASSWORD_DEFAULT), ':id' => $userId]
        );
        fwrite(STDOUT, "Usuario existente actualizado.\n");
    }

    $employee = db_one('SELECT ID_Empleado FROM empleados WHERE FK_ID_PasajeroOnline = :id LIMIT 1', [':id' => $userId]);
    if ($employee === null) {
        if ($dni === null || trim((string) $dni) === '') {
            $dni = prompt('DNI del empleado');
        }
        $dni = trim((string) $dni);
        if ($dni === '') {
            throw new RuntimeException('El DNI es obligatorio para crear el empleado.');
        }
        $exists = db_val('SELECT ID_Empleado FROM empleados WHERE DNI = :dni', [':dni' => $dni]);
        if ($exists !== null) {
            throw new RuntimeException('Ya existe un empleado con ese DNI.');
        }
        db_exec(
            'INSERT INTO empleados (FK_ID_PasajeroOnline, Nombre, Apellido, DNI, Email, Cargo, FechaIngreso, Estado)
             VALUES (:user_id, :nombre, :apellido, :dni, :email, :cargo, CURDATE(), :estado)',
            [
                ':user_id' => $userId,
                ':nombre' => $nombre,
                ':apellido' => $apellido,
                ':dni' => $dni,
                ':email' => mb_strtolower($email),
                ':cargo' => 'Administrador',
                ':estado' => 'activo',
            ]
        );
        $employeeId = (int) db()->lastInsertId();
        $branchId = db_val('SELECT ID_Establecimiento FROM establecimiento ORDER BY ID_Establecimiento LIMIT 1');
        if ($branchId !== null) {
            db_exec(
                'INSERT INTO empleado_sucursal (FK_ID_Empleado, FK_ID_Establecimiento, FechaDesde, Principal)
                 VALUES (:empleado, :sucursal, CURDATE(), 1)',
                [':empleado' => $employeeId, ':sucursal' => (int) $branchId]
            );
        }
        fwrite(STDOUT, "Empleado creado.\n");
    } else {
        $employeeId = (int) $employee['ID_Empleado'];
        db_exec(
            "UPDATE empleados SET Nombre = :nombre, Apellido = :apellido, Email = :email, Cargo = 'Administrador', Estado = 'activo' WHERE ID_Empleado = :id",
            [':nombre' => $nombre, ':apellido' => $apellido, ':email' => mb_strtolower($email), ':id' => $employeeId]
        );
        fwrite(STDOUT, "Empleado existente actualizado.\n");
    }

    $adminRoleId = (int) db_val('SELECT ID_Rol FROM roles WHERE Nombre = :nombre LIMIT 1', [':nombre' => 'Administrador'], 0);
    if ($adminRoleId <= 0) {
        throw new RuntimeException('No existe el rol Administrador en la base de datos.');
    }
    db_exec(
        'INSERT INTO usuario_rol (FK_ID_PasajeroOnline, FK_ID_Rol) VALUES (:user_id, :rol)
         ON DUPLICATE KEY UPDATE FK_ID_Rol = VALUES(FK_ID_Rol)',
        [':user_id' => $userId, ':rol' => $adminRoleId]
    );

    db()->commit();
    fwrite(STDOUT, "\nAdministrador configurado correctamente.\n");
    fwrite(STDOUT, "Correo: {$email}\n");
    fwrite(STDOUT, "Contraseña: {$password}\n");
    fwrite(STDOUT, "Ingresá en: " . (getenv('AURA_APP_URL') ?: 'http://localhost/auraviajes/Paginas/') . "login.php\n");
} catch (Throwable $e) {
    if (db()->inTransaction()) {
        db()->rollBack();
    }
    fwrite(STDERR, 'Error: ' . $e->getMessage() . PHP_EOL);
    exit(1);
}
