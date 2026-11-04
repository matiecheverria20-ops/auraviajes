<?php
declare(strict_types=1);

function base_url(string $path = ''): string
{
    $base = app_web_base();
    if ($path === '') {
        return $base === '' ? '/' : $base . '/';
    }
    return ($base === '' ? '' : $base) . '/' . ltrim($path, '/');
}

function asset(string $path): string
{
    return base_url('assets/' . ltrim($path, '/'));
}

function redirect(string $path): never
{
    header('Location: ' . base_url($path));
    exit;
}

function redirect_back(string $fallback = 'admin/index.php'): never
{
    $ref = $_SERVER['HTTP_REFERER'] ?? '';
    if ($ref !== '') {
        header('Location: ' . $ref);
        exit;
    }
    redirect($fallback);
}

function e(mixed $value): string
{
    return htmlspecialchars((string) ($value ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function is_post(): bool
{
    return ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST';
}

function post_string(string $key, string $default = ''): string
{
    $value = $_POST[$key] ?? $default;
    return is_string($value) ? trim($value) : $default;
}

function get_string(string $key, string $default = ''): string
{
    $value = $_GET[$key] ?? $default;
    return is_string($value) ? trim($value) : $default;
}

function post_int(string $key, int $default = 0): int
{
    return (int) ($_POST[$key] ?? $default);
}

function post_float(string $key, float $default = 0.0): float
{
    $value = str_replace(['.', ','], ['', '.'], (string) ($_POST[$key] ?? ''));
    return is_numeric($value) ? (float) $value : $default;
}

function get_int(string $key, int $default = 0): int
{
    return (int) ($_GET[$key] ?? $default);
}

function flash(string $type, string $message): void
{
    $_SESSION['_flash'][] = ['type' => $type, 'message' => $message];
}

function get_flashes(): array
{
    $items = $_SESSION['_flash'] ?? [];
    unset($_SESSION['_flash']);
    return is_array($items) ? $items : [];
}

function csrf_token(): string
{
    if (empty($_SESSION['_csrf'])) {
        $_SESSION['_csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['_csrf'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="_csrf" value="' . e(csrf_token()) . '">';
}

function verify_csrf(): void
{
    if (!is_post()) {
        return;
    }
    $token = $_POST['_csrf'] ?? '';
    if (!is_string($token) || $token === '' || !hash_equals(csrf_token(), $token)) {
        http_response_code(419);
        echo '<!DOCTYPE html><html lang="es"><head><meta charset="utf-8"><title>Sesión expirada</title>';
        echo '<style>body{font-family:system-ui,sans-serif;background:#f4f1ec;display:flex;min-height:100vh;align-items:center;justify-content:center;margin:0}.box{background:#fff;padding:2rem;border-radius:16px;max-width:420px}</style></head><body>';
        echo '<div class="box"><h1>Sesión expirada</h1><p>El formulario venció o la sesión cambió. Vuelva atrás, recargue la página e intente nuevamente.</p></div></body></html>';
        exit;
    }
}

function db_all(string $sql, array $params = []): array
{
    $stmt = db()->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

function db_one(string $sql, array $params = []): ?array
{
    $stmt = db()->prepare($sql);
    $stmt->execute($params);
    $row = $stmt->fetch();
    return $row === false ? null : $row;
}

function db_val(string $sql, array $params = [], mixed $default = null): mixed
{
    $stmt = db()->prepare($sql);
    $stmt->execute($params);
    $value = $stmt->fetchColumn();
    return $value === false ? $default : $value;
}

function db_exec(string $sql, array $params = []): int
{
    $stmt = db()->prepare($sql);
    $stmt->execute($params);
    return $stmt->rowCount();
}

function money(mixed $value, bool $symbol = true): string
{
    $amount = number_format((float) $value, 2, ',', '.');
    return $symbol ? '$ ' . $amount : $amount;
}

function fecha(?string $value, bool $withTime = false): string
{
    if ($value === null || trim($value) === '') {
        return '—';
    }
    try {
        $date = new DateTime($value);
        return $date->format($withTime ? 'd/m/Y H:i' : 'd/m/Y');
    } catch (Throwable) {
        return '—';
    }
}

function fecha_input(?string $value, bool $withTime = false): string
{
    if ($value === null || trim($value) === '') {
        return '';
    }
    try {
        $date = new DateTime($value);
        return $date->format($withTime ? 'Y-m-d\TH:i' : 'Y-m-d');
    } catch (Throwable) {
        return '';
    }
}

function badge_class(string $estado): string
{
    $key = strtolower(trim($estado));
    return match (true) {
        in_array($key, ['activo', 'aprobado', 'publicado', 'confirmada', 'pagada', 'abierta', 'disponible', 'nuevo', 'activa'], true) => 'badge badge-success',
        in_array($key, ['pendiente', 'borrador', 'contactado', 'programado', 'en_curso', 'pausado', 'mantenimiento', 'ocupada'], true) => 'badge badge-warning',
        in_array($key, ['cancelada', 'cancelado', 'anulado', 'rechazado', 'inactivo', 'inactiva', 'finalizada', 'finalizado', 'completa', 'cerrado'], true) => 'badge badge-danger',
        default => 'badge badge-neutral',
    };
}

function estado_label(string $estado): string
{
    return ucfirst(str_replace('_', ' ', $estado));
}

function initials(string $name): string
{
    $parts = preg_split('/\s+/', trim($name)) ?: [];
    $letters = '';
    foreach (array_slice($parts, 0, 2) as $part) {
        $letters .= mb_strtoupper(mb_substr($part, 0, 1));
    }
    return $letters !== '' ? $letters : 'AV';
}

function summary_text(?string $text, int $limit = 150): string
{
    $clean = trim(preg_replace('/\s+/', ' ', strip_tags((string) $text)) ?? '');
    return mb_strlen($clean) > $limit ? mb_substr($clean, 0, $limit - 1) . '…' : $clean;
}

function audit(string $table, string $operation, ?string $recordId = null, string $detail = ''): void
{
    try {
        $userId = $_SESSION['user_id'] ?? null;
        if ($userId === null && function_exists('current_user')) {
            $user = current_user();
            $userId = $user['ID_PasajeroOnline'] ?? null;
        }
        db_exec(
            'INSERT INTO auditoria (FK_ID_PasajeroOnline, TablaAfectada, Operacion, IDRegistro, Detalle)
             VALUES (:user_id, :tabla, :operacion, :registro, :detalle)',
            [
                ':user_id' => $userId,
                ':tabla' => mb_substr($table, 0, 100),
                ':operacion' => $operation,
                ':registro' => $recordId !== null ? mb_substr($recordId, 0, 50) : null,
                ':detalle' => $detail !== '' ? $detail : null,
            ]
        );
    } catch (Throwable $e) {
        error_log('[AuraViajes] No se pudo registrar auditoría: ' . $e->getMessage());
    }
}

function generate_code(string $table, string $column, string $prefix): string
{
    if (!preg_match('/^[A-Za-z0-9_]+$/', $table) || !preg_match('/^[A-Za-z0-9_]+$/', $column)) {
        throw new InvalidArgumentException('Identificador inválido para generar código.');
    }
    $stmt = db()->prepare("SELECT `$column` FROM `$table` WHERE `$column` LIKE :like");
    $stmt->execute([':like' => $prefix . '-%']);
    $max = 0;
    foreach ($stmt->fetchAll(PDO::FETCH_COLUMN) as $code) {
        if (preg_match('/-(\d+)$/', (string) $code, $m)) {
            $max = max($max, (int) $m[1]);
        }
    }
    return sprintf('%s-%04d', $prefix, $max + 1);
}

function save_upload(array $file, string $subdir, int $maxBytes = 4_194_304): ?string
{
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return null;
    }
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        throw new RuntimeException('No se pudo subir el archivo (código ' . (int) $file['error'] . ').');
    }
    if (($file['size'] ?? 0) > $maxBytes) {
        throw new RuntimeException('El archivo supera el tamaño máximo de 4 MB.');
    }

    $allowed = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
        'image/gif' => 'gif',
    ];
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->file($file['tmp_name']) ?: '';
    if (!isset($allowed[$mime])) {
        throw new RuntimeException('Formato de imagen no permitido. Use JPG, PNG, WEBP o GIF.');
    }

    $relativeDir = 'uploads/' . trim($subdir, '/');
    $absoluteDir = APP_ROOT . '/' . $relativeDir;
    if (!is_dir($absoluteDir) && !mkdir($absoluteDir, 0775, true) && !is_dir($absoluteDir)) {
        throw new RuntimeException('No se pudo crear el directorio de destino.');
    }

    $filename = bin2hex(random_bytes(12)) . '.' . $allowed[$mime];
    $destination = $absoluteDir . '/' . $filename;
    if (!move_uploaded_file($file['tmp_name'], $destination)) {
        throw new RuntimeException('No se pudo guardar la imagen.');
    }
    return $relativeDir . '/' . $filename;
}

function remove_upload(?string $relativePath): void
{
    if ($relativePath === null || $relativePath === '') {
        return;
    }
    $normalized = str_replace('\\', '/', $relativePath);
    if (!str_starts_with($normalized, 'uploads/') || str_contains($normalized, '..')) {
        return;
    }
    $absolute = APP_ROOT . '/' . $normalized;
    if (is_file($absolute)) {
        @unlink($absolute);
    }
}

function pagination_data(int $total, int $perPage = 20): array
{
    $perPage = max(1, $perPage);
    $pages = max(1, (int) ceil($total / $perPage));
    $page = max(1, min($pages, get_int('page', 1)));
    return [
        'page' => $page,
        'pages' => $pages,
        'per_page' => $perPage,
        'offset' => ($page - 1) * $perPage,
        'total' => $total,
    ];
}

function pagination_links(array $pagination): string
{
    if (($pagination['pages'] ?? 1) <= 1) {
        return '';
    }
    $page = (int) $pagination['page'];
    $pages = (int) $pagination['pages'];
    $query = $_GET;
    unset($query['page']);
    $build = static function (int $target) use ($query): string {
        $query['page'] = $target;
        return '?' . http_build_query($query);
    };
    $html = '<nav class="pagination" aria-label="Paginación">';
    $html .= $page > 1
        ? '<a class="page-link" href="' . e($build($page - 1)) . '">&laquo; Anterior</a>'
        : '<span class="page-link disabled">&laquo; Anterior</span>';
    $start = max(1, $page - 2);
    $end = min($pages, $page + 2);
    for ($i = $start; $i <= $end; $i++) {
        $html .= $i === $page
            ? '<span class="page-link current">' . $i . '</span>'
            : '<a class="page-link" href="' . e($build($i)) . '">' . $i . '</a>';
    }
    $html .= $page < $pages
        ? '<a class="page-link" href="' . e($build($page + 1)) . '">Siguiente &raquo;</a>'
        : '<span class="page-link disabled">Siguiente &raquo;</span>';
    $html .= '</nav>';
    return $html;
}

function destination_image(?string $name): string
{
    $key = mb_strtolower((string) $name);
    $map = [
        'catarata' => 'iguazu.jpg',
        'iguaz' => 'iguazu.jpg',
        'misiones' => 'iguazu.jpg',
        'bariloche' => 'barriloche.jpg',
        'patagonia' => 'barriloche.jpg',
        'rio negro' => 'barriloche.jpg',
        'salta' => 'salta.jpeg',
        'mendoza' => 'mendoza.jpg',
        'buenos aires' => 'BA.jpg',
        'rio de janeiro' => 'rio.jpg',
        'brasil' => 'rio.jpg',
        'asuncion' => 'asuncion.jpg',
        'paraguay' => 'asuncion.jpg',
        'formosa' => 'asuncion.jpg',
        'litoral' => 'asuncion.jpg',
    ];
    foreach ($map as $needle => $image) {
        if (str_contains($key, $needle)) {
            return asset('img/' . $image);
        }
    }
    return asset('img/BA.jpg');
}

function render_flashes(): string
{
    $html = '';
    foreach (get_flashes() as $item) {
        $type = in_array($item['type'], ['success', 'error', 'warning', 'info'], true) ? $item['type'] : 'info';
        $html .= '<div class="alert alert-' . $type . '" role="alert">' . e($item['message']) . '<button type="button" class="alert-close" aria-label="Cerrar">&times;</button></div>';
    }
    return $html;
}

function client_display_name(?array $client): string
{
    if (!$client) {
        return 'Cliente';
    }
    $name = trim((string) ($client['Nombre'] ?? '') . ' ' . (string) ($client['Apellido'] ?? ''));
    return $name !== '' ? $name : (string) ($client['Email'] ?? 'Cliente');
}

function redirect_with(string $path, string $type, string $message): never
{
    flash($type, $message);
    redirect($path);
}

function validate_required(array $fields, array $labels): array
{
    $errors = [];
    foreach ($fields as $field => $value) {
        if (trim((string) $value) === '') {
            $errors[] = 'El campo ' . ($labels[$field] ?? $field) . ' es obligatorio.';
        }
    }
    return $errors;
}
