<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/data.php';
require_permission('gestionar_clientes');

$id = get_int('id');
$passenger = $id > 0 ? db_one(
    'SELECT p.*, u.Email, c.ID_Cliente, c.CodigoCliente
     FROM pasajero p
     LEFT JOIN pasajeroonline u ON u.ID_PasajeroOnline = p.FK_ID_PasajeroOnline
     LEFT JOIN clientes c ON c.FK_ID_PasajeroOnline = p.FK_ID_PasajeroOnline
     WHERE p.ID_Pasajero = :id',
    [':id' => $id]
) : null;
if ($passenger === null) {
    redirect_with('admin/pasajeros.php', 'error', 'El pasajero solicitado no existe.');
}
$values = [
    'Nombre' => $passenger['Nombre'],
    'Apellido' => $passenger['Apellido'],
    'DNI' => $passenger['DNI'],
    'Telefono' => $passenger['Telefono'] ?? '',
    'Nacionalidad' => $passenger['Nacionalidad'] ?? '',
];
$errors = [];
$reservations = db_all(
    'SELECT r.CodigoReserva, r.FechaReserva, rp.Titular, er.Nombre AS Estado
     FROM reserva_pasajero rp INNER JOIN reservas r ON r.ID_Reserva = rp.FK_ID_Reserva
     INNER JOIN estados_reserva er ON er.ID_EstadoReserva = r.FK_ID_EstadoReserva
     WHERE rp.FK_ID_Pasajero = :id ORDER BY r.FechaReserva DESC',
    [':id' => $id]
);

if (is_post()) {
    verify_csrf();
    foreach (array_keys($values) as $key) {
        $values[$key] = post_string($key);
    }
    if ($values['Nombre'] === '' || $values['Apellido'] === '' || $values['DNI'] === '') {
        $errors[] = 'Nombre, apellido y DNI son obligatorios.';
    }
    if (db_val('SELECT ID_Pasajero FROM pasajero WHERE DNI = :dni AND ID_Pasajero <> :id', [':dni' => $values['DNI'], ':id' => $id]) !== null) {
        $errors[] = 'Ese DNI ya está registrado en otro pasajero.';
    }
    if ($errors === []) {
        db_exec(
            'UPDATE pasajero SET Nombre = :nombre, Apellido = :apellido, DNI = :dni, Telefono = :telefono, Nacionalidad = :nacionalidad WHERE ID_Pasajero = :id',
            [
                ':nombre' => $values['Nombre'],
                ':apellido' => $values['Apellido'],
                ':dni' => $values['DNI'],
                ':telefono' => $values['Telefono'] !== '' ? $values['Telefono'] : null,
                ':nacionalidad' => $values['Nacionalidad'] !== '' ? $values['Nacionalidad'] : null,
                ':id' => $id,
            ]
        );
        audit('pasajero', 'UPDATE', (string) $id, 'Actualización de pasajero ' . $values['Nombre'] . ' ' . $values['Apellido']);
        redirect_with('admin/pasajeros.php', 'success', 'Pasajero actualizado correctamente.');
    }
}

$pageTitle = 'Editar pasajero';
$activeSection = 'pasajeros';
$breadcrumbs = ['Panel' => 'admin/index.php', 'Pasajeros' => 'admin/pasajeros.php', $passenger['Apellido'] . ', ' . $passenger['Nombre'] => ''];
require __DIR__ . '/../includes/admin_header.php';
?>
<div class="detail-grid">
    <div class="card">
        <div class="card-header"><h2>Datos del pasajero</h2><a class="small" href="<?= e(base_url('admin/pasajeros.php')) ?>">Volver</a></div>
        <div class="card-body">
            <?php foreach ($errors as $error): ?><div class="alert alert-error"><?= e($error) ?></div><?php endforeach; ?>
            <form method="post" action="<?= e(base_url('admin/pasajero_form.php?id=' . $id)) ?>" data-confirm="¿Guardar los datos del pasajero?">
                <?= csrf_field() ?>
                <div class="form-grid">
                    <div class="field"><label for="Nombre">Nombre *</label><input type="text" id="Nombre" name="Nombre" value="<?= e($values['Nombre']) ?>" required maxlength="60"></div>
                    <div class="field"><label for="Apellido">Apellido *</label><input type="text" id="Apellido" name="Apellido" value="<?= e($values['Apellido']) ?>" required maxlength="60"></div>
                    <div class="field"><label for="DNI">DNI *</label><input type="text" id="DNI" name="DNI" value="<?= e($values['DNI']) ?>" required maxlength="20"></div>
                    <div class="field"><label for="Telefono">Teléfono</label><input type="text" id="Telefono" name="Telefono" value="<?= e($values['Telefono']) ?>" maxlength="20"></div>
                    <div class="field"><label for="Nacionalidad">Nacionalidad</label><input type="text" id="Nacionalidad" name="Nacionalidad" value="<?= e($values['Nacionalidad']) ?>" maxlength="60"></div>
                    <div class="field">
                        <label>Cuenta asociada</label>
                        <p style="margin:.4rem 0;"><?= e($passenger['Email'] ?? 'Sin cuenta') ?></p>
                        <?php if ($passenger['ID_Cliente'] !== null): ?>
                            <a class="small" href="<?= e(base_url('admin/cliente_ver.php?id=' . (int) $passenger['ID_Cliente'])) ?>">Ver cliente <?= e($passenger['CodigoCliente']) ?></a>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="form-actions">
                    <button class="btn btn-primary" type="submit">Guardar cambios</button>
                    <a class="btn btn-ghost" href="<?= e(base_url('admin/pasajeros.php')) ?>">Cancelar</a>
                </div>
            </form>
        </div>
    </div>
    <div class="card">
        <div class="card-header"><h2>Reservas del pasajero</h2></div>
        <div class="card-body">
            <?php if ($reservations === []): ?>
                <p class="muted small mb-0">Sin reservas asociadas.</p>
            <?php endif; ?>
            <?php foreach ($reservations as $reservation): ?>
                <div class="flex-between" style="padding:.55rem 0;border-bottom:1px dashed var(--line);">
                    <div>
                        <strong class="small"><?= e($reservation['CodigoReserva']) ?></strong>
                        <div class="small muted"><?= e(fecha($reservation['FechaReserva'])) ?><?= $reservation['Titular'] ? ' · Titular' : '' ?></div>
                    </div>
                    <span class="<?= e(badge_class($reservation['Estado'])) ?>"><?= e($reservation['Estado']) ?></span>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>
<?php require __DIR__ . '/../includes/admin_footer.php'; ?>
