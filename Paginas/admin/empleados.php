<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/data.php';
require_permission('gestionar_empleados');

$employees = db_all(
    "SELECT e.*, u.Email AS LoginEmail,
            (SELECT GROUP_CONCAT(r.Nombre ORDER BY r.ID_Rol SEPARATOR ', ') FROM usuario_rol ur INNER JOIN roles r ON r.ID_Rol = ur.FK_ID_Rol WHERE ur.FK_ID_PasajeroOnline = e.FK_ID_PasajeroOnline AND r.Nombre <> 'Cliente') AS Roles,
            (SELECT est.Nombre FROM empleado_sucursal es INNER JOIN establecimiento est ON est.ID_Establecimiento = es.FK_ID_Establecimiento WHERE es.FK_ID_Empleado = e.ID_Empleado ORDER BY es.Principal DESC, es.ID_EmpleadoSucursal ASC LIMIT 1) AS Sucursal,
            (SELECT COUNT(*) FROM reservas r WHERE r.FK_ID_Empleado = e.ID_Empleado) AS Reservas
     FROM empleados e
     LEFT JOIN pasajeroonline u ON u.ID_PasajeroOnline = e.FK_ID_PasajeroOnline
     ORDER BY FIELD(e.Estado, 'activo', 'inactivo'), e.Apellido ASC, e.Nombre ASC"
);
$pageTitle = 'Empleados y roles';
$activeSection = 'empleados';
$breadcrumbs = ['Panel' => 'admin/index.php', 'Empleados' => ''];
$pageActions = '<a class="btn btn-accent" href="' . e(base_url('admin/empleado_form.php')) . '">Nuevo empleado</a>';
require __DIR__ . '/../includes/admin_header.php';
?>
<div class="card">
    <div class="card-header"><h2>Equipo de trabajo</h2><span class="small muted"><?= count($employees) ?> empleados</span></div>
    <div class="table-wrap">
        <table class="data-table">
            <thead><tr><th>Empleado</th><th>Contacto</th><th>Rol</th><th>Sucursal</th><th class="num">Reservas</th><th>Estado</th><th></th></tr></thead>
            <tbody>
            <?php if ($employees === []): ?>
                <tr><td colspan="7" class="table-empty">No hay empleados registrados.</td></tr>
            <?php endif; ?>
            <?php foreach ($employees as $employee): ?>
                <?php $name = $employee['Apellido'] . ', ' . $employee['Nombre']; ?>
                <tr>
                    <td>
                        <div class="table-user">
                            <span class="avatar"><?= e(initials($employee['Nombre'] . ' ' . $employee['Apellido'])) ?></span>
                            <span><strong><?= e($name) ?></strong><small><?= e($employee['Cargo']) ?> · DNI <?= e($employee['DNI']) ?></small></span>
                        </div>
                    </td>
                    <td><span class="small"><?= e($employee['LoginEmail'] ?: 'Sin cuenta') ?></span><br><small class="muted"><?= e($employee['Telefono'] ?? '—') ?></small></td>
                    <td><span class="small"><?= e($employee['Roles'] ?: 'Sin rol de acceso') ?></span></td>
                    <td><span class="small"><?= e($employee['Sucursal'] ?? '—') ?></span></td>
                    <td class="num"><?= (int) $employee['Reservas'] ?></td>
                    <td><span class="<?= e(badge_class($employee['Estado'])) ?>"><?= e(estado_label($employee['Estado'])) ?></span></td>
                    <td class="actions"><a class="btn btn-ghost btn-sm" href="<?= e(base_url('admin/empleado_form.php?id=' . (int) $employee['ID_Empleado'])) ?>">Editar</a></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php require __DIR__ . '/../includes/admin_footer.php'; ?>
