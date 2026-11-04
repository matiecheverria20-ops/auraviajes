<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/data.php';
require_permission('gestionar_cotizaciones');

$search = trim(get_string('q'));
$state = get_string('estado');
$where = [];
$params = [];
if ($search !== '') {
    $where[] = '(Nombre LIKE :search OR Email LIKE :search OR Asunto LIKE :search OR Mensaje LIKE :search)';
    $params[':search'] = '%' . $search . '%';
}
if (in_array($state, ['nuevo', 'contactado', 'cerrado'], true)) {
    $where[] = 'Estado = :estado';
    $params[':estado'] = $state;
}
$whereSql = $where !== [] ? ' WHERE ' . implode(' AND ', $where) : '';

if (is_post()) {
    verify_csrf();
    $action = post_string('action');
    $messageId = post_int('message_id');
    $message = db_one('SELECT * FROM formularios_contacto WHERE ID_Formulario = :id', [':id' => $messageId]);
    if ($message === null) {
        redirect_with('admin/mensajes.php', 'error', 'La consulta indicada no existe.');
    }
    if ($action === 'state') {
        $newState = post_string('estado');
        if (!in_array($newState, ['nuevo', 'contactado', 'cerrado'], true)) {
            $newState = 'contactado';
        }
        db_exec('UPDATE formularios_contacto SET Estado = :estado WHERE ID_Formulario = :id', [':estado' => $newState, ':id' => $messageId]);
        audit('formularios_contacto', 'UPDATE', (string) $messageId, 'Cambio de estado a ' . $newState);
        redirect_with('admin/mensajes.php', 'success', 'Consulta actualizada.');
    }
}

$total = (int) db_val('SELECT COUNT(*) FROM formularios_contacto' . $whereSql, $params, 0);
$pagination = pagination_data($total, 15);
$messages = db_all(
    'SELECT * FROM formularios_contacto' . $whereSql . ' ORDER BY FIELD(Estado, "nuevo", "contactado", "cerrado"), Fecha DESC LIMIT ' . (int) $pagination['per_page'] . ' OFFSET ' . (int) $pagination['offset'],
    $params
);
$counts = db_one('SELECT SUM(Estado = "nuevo") AS nuevos, SUM(Estado = "contactado") AS contactados, SUM(Estado = "cerrado") AS cerrados FROM formularios_contacto') ?? [];

$pageTitle = 'Consultas y cotizaciones';
$activeSection = 'mensajes';
$breadcrumbs = ['Panel' => 'admin/index.php', 'Consultas' => ''];
require __DIR__ . '/../includes/admin_header.php';
?>
<div class="mini-stats">
    <span><strong><?= (int) ($counts['nuevos'] ?? 0) ?></strong> nuevas</span>
    <span><strong><?= (int) ($counts['contactados'] ?? 0) ?></strong> contactadas</span>
    <span><strong><?= (int) ($counts['cerrados'] ?? 0) ?></strong> cerradas</span>
</div>

<div class="card">
    <div class="card-header"><h2>Bandeja de consultas</h2></div>
    <div class="card-body">
        <form class="filters" method="get" action="<?= e(base_url('admin/mensajes.php')) ?>">
            <div class="field">
                <label for="q">Buscar</label>
                <input type="search" id="q" name="q" value="<?= e($search) ?>" placeholder="Nombre, email, asunto o mensaje">
            </div>
            <div class="field" style="max-width:200px;">
                <label for="estado">Estado</label>
                <select id="estado" name="estado">
                    <option value="">Todos</option>
                    <?php foreach (['nuevo', 'contactado', 'cerrado'] as $option): ?>
                        <option value="<?= e($option) ?>" <?= $state === $option ? 'selected' : '' ?>><?= e(estado_label($option)) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="filters-actions">
                <button class="btn btn-primary" type="submit">Filtrar</button>
                <a class="btn btn-ghost" href="<?= e(base_url('admin/mensajes.php')) ?>">Limpiar</a>
            </div>
        </form>
    </div>
</div>

<div class="stack">
    <?php if ($messages === []): ?>
        <div class="card"><div class="empty-state"><h3>Sin consultas</h3><p>No hay mensajes que coincidan con los filtros.</p></div></div>
    <?php endif; ?>
    <?php foreach ($messages as $message): ?>
        <article class="card">
            <div class="card-body">
                <div class="flex-between" style="align-items:flex-start;gap:1rem;flex-wrap:wrap;">
                    <div class="table-user">
                        <span class="avatar"><?= e(initials($message['Nombre'])) ?></span>
                        <span>
                            <strong><?= e($message['Nombre']) ?></strong>
                            <small><?= e($message['Email']) ?><?= $message['Telefono'] ? ' · ' . e($message['Telefono']) : '' ?></small>
                        </span>
                    </div>
                    <div class="flex gap-1" style="align-items:center;">
                        <span class="<?= e(badge_class($message['Estado'])) ?>"><?= e(estado_label($message['Estado'])) ?></span>
                        <span class="small muted"><?= e(fecha($message['Fecha'], true)) ?></span>
                    </div>
                </div>
                <h3 style="margin:.8rem 0 .3rem;font-size:1.05rem;"><?= e($message['Asunto'] ?: 'Consulta general') ?></h3>
                <p class="small" style="white-space:pre-line;"><?= e($message['Mensaje']) ?></p>
                <div class="flex gap-1" style="flex-wrap:wrap;">
                    <a class="btn btn-primary btn-sm" href="mailto:<?= e($message['Email']) ?>?subject=<?= e(rawurlencode('Re: ' . ($message['Asunto'] ?: 'Tu consulta'))) ?>">Responder</a>
                    <form method="post" action="<?= e(base_url('admin/mensajes.php')) ?>">
                        <?= csrf_field() ?>
                        <input type="hidden" name="action" value="state">
                        <input type="hidden" name="message_id" value="<?= (int) $message['ID_Formulario'] ?>">
                        <input type="hidden" name="estado" value="contactado">
                        <button class="btn btn-ghost btn-sm" type="submit" <?= $message['Estado'] === 'contactado' ? 'disabled' : '' ?>>Marcar contactada</button>
                    </form>
                    <form method="post" action="<?= e(base_url('admin/mensajes.php')) ?>">
                        <?= csrf_field() ?>
                        <input type="hidden" name="action" value="state">
                        <input type="hidden" name="message_id" value="<?= (int) $message['ID_Formulario'] ?>">
                        <input type="hidden" name="estado" value="cerrado">
                        <button class="btn btn-ghost btn-sm" type="submit" <?= $message['Estado'] === 'cerrado' ? 'disabled' : '' ?>>Cerrar</button>
                    </form>
                </div>
            </div>
        </article>
    <?php endforeach; ?>
</div>
<div class="card" style="margin-top:1rem;"><div class="card-footer"><?= pagination_links($pagination) ?></div></div>
<?php require __DIR__ . '/../includes/admin_footer.php'; ?>
