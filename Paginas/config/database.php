<?php
declare(strict_types=1);

function db(): PDO
{
    static $pdo = null;
    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $dsn = sprintf(
        'mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4',
        DB_HOST,
        DB_PORT,
        DB_NAME
    );

    try {
        $pdo = new PDO($dsn, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
        $pdo->exec("SET time_zone = '-03:00'");
        return $pdo;
    } catch (PDOException $e) {
        error_log('[AuraViajes] Error de conexión: ' . $e->getMessage());
        http_response_code(503);
        $message = APP_DEBUG
            ? 'No se pudo conectar a la base de datos: ' . $e->getMessage()
            : 'No se pudo conectar con la base de datos. Verifique que MariaDB esté iniciado e importe <code>sistema_viajes.sql</code>.';
        echo '<!DOCTYPE html><html lang="es"><head><meta charset="utf-8"><title>Base de datos no disponible</title>';
        echo '<style>body{font-family:system-ui,sans-serif;background:#f4f1ec;color:#1c2b33;display:flex;min-height:100vh;align-items:center;justify-content:center;margin:0}.box{background:#fff;padding:2rem 2.5rem;border-radius:16px;max-width:560px;box-shadow:0 20px 60px rgba(0,0,0,.12)}code{background:#f1f1f1;padding:2px 6px;border-radius:4px}</style>';
        echo '</head><body><div class="box"><h1>Base de datos no disponible</h1><p>' . $message . '</p>';
        echo '<p>Revise <code>config/config.php</code> o cree <code>config/config.local.php</code> con sus credenciales.</p></div></body></html>';
        exit;
    }
}
