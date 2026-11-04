<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/data.php';
require_any_permission(['gestionar_clientes', 'ver_clientes']);

$search = trim(get_string('q'));
$where = '';
$params = [];
if ($search !== '') {
    $where = ' WHERE (p.Nombre LIKE :search OR p.Apellido LIKE :search OR p.DNI LIKE :search OR p.Telefono LIKE :search OR u.Email LIKE :search)';
    $params[':search'] = '%' . $search . '%';
}
$passengers = db_all(
    'SELECT p.*, u.Email, c.ID_Cliente, c.CodigoCliente,
            (SELECT COUNT(*) FROM reserva_pasajero rp WHERE rp.FK_ID_Pasajero = p.ID_Pasajero) AS Reservas
     FROM pasajero p
     LEFT JOIN pasajeroonline u ON u.ID_PasajeroOnline = p.FK_ID_PasajeroOnline
     LEFT JOIN clientes c ON c.FK_ID_PasajeroOnline = p.FK_ID_PasajeroOnline' . $where . '
     ORDER BY p.Apellido ASC, p.Nombre ASC',
    $params
);
$pageTitle = 'Pasajeros';
$activeSection = 'pasajeros';
$breadcrumbs = ['Panel' => 'admin/index.php', 'Pasajeros' => ''];
require __DIR__ . '/../includes/admin_header.php';
?>
<div class="card">
    <div class="card-header"><h2>Registro de pasajeros</h2><span class="small muted"><?= count($passengers) ?> registros</span></div>
    <div class="card-body">
        <form class="filters" method="get" action="<?= e(base_url('admin/pasajeros.php')) ?>">
            <div class="field">
                <label for="q">Buscar</label>
                <input type="search" id="q" name="q" value="<?= e($search) ?>" placeholder="Nombre, DNI, teléfono o email">
            </div>
            <div class="filters-actions">
                <button class="btn btn-primary" type="submit">Buscar</button>
                <a class="btn btn-ghost" href="<?= e(base_url('admin/pasajeros.php')) ?>">Limpiar</a>
            </div>
        </form>
    </div>
    <div class="table-wrap">
        <table class="data-table">
            <thead><tr><th>Pasajero</th><th>DNI</th><th>Contacto</th><th>Cuenta / cliente</th><th class="num">Reservas</th><th></th></tr></thead>
            <tbody>
            <?php if ($passengers === []): ?>
                <tr><td colspan="6" class="table-empty">No se encontraron pasajeros.</td></tr>
            <?php endif; ?>
            <?php foreach ($passengers as $passenger): ?>
                <tr>
                    <td>
                        <div class="table-user">
                            <span class="avatar"><?= e(initials($passenger['Nombre'] . ' ' . $passenger['Apellido'])) ?></span>
                            <span><strong><?= e($passenger['Apellido'] . ', ' . $passenger['Nombre']) ?></strong><small><?= e($passenger['Nacionalidad'] ?? '—') ?></small></span>
                        </div>
                    </td>
                    <td><?= e($passenger['DNI']) ?></td>
                    <td><span class="small"><?= e($passenger['Telefono'] ?? '—') ?></span><br><small class="muted"><?= e($passenger['Email'] ?? 'Sin cuenta') ?></small></td>
                    <td>
                        <?php if ($passenger['ID_Cliente'] !== null): ?>
                            <a class="small" href="<?= e(base_url('admin/cliente_ver.php?id=' . (int) $passenger['ID_Cliente'])) ?>"><?= e($passenger['CodigoCliente']) ?></a>
                        <?php else: ?>
                            <span class="muted small">Sin cliente</span>
                        <?php endif; ?>
                    </td>
                    <td class="num"><?= (int) $passenger['Reservas'] ?></td>
                    <td class="actions">
                        <?php if (can('gestionar_clientes')): ?>
                            <a class="btn btn-ghost btn-sm" href="<?= e(base_url('admin/pasajero_form.php?id=' . (int) $passenger['ID_Pasajero'])) ?>">Editar</a>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php require __DIR__ . '/../includes/admin_footer.php'; ?>
