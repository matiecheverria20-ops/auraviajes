<?php
declare(strict_types=1);

define('APP_NAME', 'Aura Viajes');
define('APP_ROOT', dirname(__DIR__));
define('APP_TIMEZONE', 'America/Argentina/Buenos_Aires');
define('APP_VERSION', '1.0.0');

date_default_timezone_set(APP_TIMEZONE);

$config = [
    'db' => [
        'host' => getenv('AURA_DB_HOST') ?: '127.0.0.1',
        'port' => (int) (getenv('AURA_DB_PORT') ?: 3306),
        'name' => getenv('AURA_DB_NAME') ?: 'sistema_viajes3',
        'user' => getenv('AURA_DB_USER') ?: 'root',
        'pass' => getenv('AURA_DB_PASS') ?: '',
    ],
    'app' => [
        'debug' => getenv('APP_DEBUG') === '1',
    ],
];

$localConfig = __DIR__ . '/config.local.php';
if (is_file($localConfig)) {
    $local = require $localConfig;
    if (is_array($local)) {
        $config = array_replace_recursive($config, $local);
    }
}

define('APP_DEBUG', (bool) $config['app']['debug']);
define('DB_HOST', (string) $config['db']['host']);
define('DB_PORT', (int) $config['db']['port']);
define('DB_NAME', (string) $config['db']['name']);
define('DB_USER', (string) $config['db']['user']);
define('DB_PASS', (string) $config['db']['pass']);

error_reporting(E_ALL);
ini_set('display_errors', APP_DEBUG ? '1' : '0');
ini_set('log_errors', '1');

if (PHP_SAPI !== 'cli' && session_status() !== PHP_SESSION_ACTIVE) {
    $secure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (($_SERVER['SERVER_PORT'] ?? '') === '443');
    session_name('AURA_SESSID');
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'domain' => '',
        'secure' => $secure,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    ini_set('session.use_strict_mode', '1');
    session_start();
}

set_exception_handler(static function (Throwable $e): void {
    error_log('[AuraViajes] ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine());
    if (PHP_SAPI === 'cli') {
        fwrite(STDERR, $e->getMessage() . PHP_EOL);
        exit(1);
    }
    http_response_code(500);
    if (APP_DEBUG) {
        echo '<pre style="padding:20px;background:#111;color:#f88;white-space:pre-wrap;">';
        echo htmlspecialchars((string) $e, ENT_QUOTES, 'UTF-8');
        echo '</pre>';
        exit;
    }
    echo '<!DOCTYPE html><html lang="es"><head><meta charset="utf-8"><title>Error</title>';
    echo '<style>body{font-family:system-ui,sans-serif;background:#f4f1ec;color:#1c2b33;display:flex;min-height:100vh;align-items:center;justify-content:center;margin:0}.box{background:#fff;padding:2rem 2.5rem;border-radius:16px;max-width:460px;box-shadow:0 20px 60px rgba(0,0,0,.12)}a{color:#005778}</style>';
    echo '</head><body><div class="box"><h1>Ocurrió un error</h1><p>No pudimos completar la operación. Intente nuevamente en unos instantes.</p><p><a href="./">Volver al inicio</a></p></div></body></html>';
    exit;
});

function app_web_base(): string
{
    static $base = null;
    if ($base !== null) {
        return $base;
    }
    $docRoot = isset($_SERVER['DOCUMENT_ROOT']) ? str_replace('\\', '/', rtrim($_SERVER['DOCUMENT_ROOT'], '/')) : '';
    $appRoot = str_replace('\\', '/', rtrim(APP_ROOT, '/'));
    if ($docRoot !== '' && str_starts_with($appRoot, $docRoot)) {
        $base = substr($appRoot, strlen($docRoot));
    } else {
        $base = '';
    }
    return $base;
}
