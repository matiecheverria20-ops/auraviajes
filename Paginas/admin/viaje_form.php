<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/data.php';
require_permission('gestionar_proveedores');

$id = get_int('id');
$isEdit = $id > 0;
$trip = $isEdit ? db_one('SELECT * FROM viajes WHERE ID_Viaje = :id', [':id' => $id]) : null;
if ($isEdit && $trip === null) {
    redirect_with('admin/viajes.php', 'error', 'El viaje solicitado no existe.');
}
$selectedDrivers = $isEdit ? array_map(static fn(array $row): int => (int) $row['FK_ID_Chofer'], db_all('SELECT FK_ID_Chofer FROM viaje_chofer WHERE FK_ID_Viaje = :id', [':id' => $id])) : [];
$routes = db_all(
    'SELECT r.*, d1.NombreUbicacion AS Origen, d2.NombreUbicacion AS Destino
     FROM rutas r INNER JOIN destino d1 ON d1.ID_Destino = r.ID_Inicio INNER JOIN destino d2 ON d2.ID_Destino = r.ID_Destino
     ORDER BY d1.NombreUbicacion ASC, d2.NombreUbicacion ASC'
);
$providers = db_all('SELECT * FROM proveedores ORDER BY Nombre');
$vehicles = db_all(
    'SELECT v.*, p.Nombre AS Proveedor, mt.Nombre AS Medio, c.Clase
     FROM vehiculo v INNER JOIN proveedores p ON p.ID_Proveedor = v.FK_ID_Proveedor
     LEFT JOIN mediotransporte mt ON mt.ID_MedioTrans = v.FK_ID_MedioTrans
     LEFT JOIN clases c ON c.ID_Clase = v.FK_ID_Clase
     ORDER BY p.Nombre, v.Patente'
);
$hotels = db_all('SELECT * FROM hoteles ORDER BY Nombre');
$pensions = db_all('SELECT * FROM pension ORDER BY Opcion');
$drivers = db_all('SELECT * FROM choferes ORDER BY Apellido, Nombre');
$destinations = all_destinations();
$values = [
    'FK_ID_Ruta' => $trip['FK_ID_Ruta'] ?? '',
    'FK_ID_Proveedor' => $trip['FK_ID_Proveedor'] ?? '',
    'FK_ID_Vehiculo' => $trip['FK_ID_Vehiculo'] ?? '',
    'FK_ID_Pension' => $trip['FK_ID_Pension'] ?? '',
    'FK_ID_Hotel' => $trip['FK_ID_Hotel'] ?? '',
    'FechaSalida' => isset($trip['FechaSalida']) ? fecha_input($trip['FechaSalida'], true) : date('Y-m-d\T08:00'),
    'FechaLlegada' => isset($trip['FechaLlegada']) ? fecha_input($trip['FechaLlegada'], true) : date('Y-m-d\T18:00', strtotime('+1 day')),
    'Estado' => $trip['Estado'] ?? 'programado',
    'origen' => '',
    'destino' => '',
    'distancia' => '',
    'tiempo' => '',
];
$errors = [];

if (is_post()) {
    verify_csrf();
    $values = [
        'FK_ID_Ruta' => post_int('FK_ID_Ruta'),
        'FK_ID_Proveedor' => post_int('FK_ID_Proveedor'),
        'FK_ID_Vehiculo' => post_int('FK_ID_Vehiculo'),
        'FK_ID_Pension' => post_int('FK_ID_Pension'),
        'FK_ID_Hotel' => post_int('FK_ID_Hotel'),
        'FechaSalida' => post_string('FechaSalida'),
        'FechaLlegada' => post_string('FechaLlegada'),
        'Estado' => post_string('Estado'),
        'origen' => post_int('origen'),
        'destino' => post_int('destino'),
        'distancia' => post_int('distancia'),
        'tiempo' => post_int('tiempo'),
    ];
    $selectedDrivers = array_values(array_filter(array_map('intval', $_POST['choferes'] ?? [])));

    if ($values['FK_ID_Proveedor'] <= 0 || db_val('SELECT ID_Proveedor FROM proveedores WHERE ID_Proveedor = :id', [':id' => $values['FK_ID_Proveedor']]) === null) {
        $errors[] = 'Seleccioná un proveedor válido.';
    }
    if ($values['FK_ID_Vehiculo'] <= 0 || db_val('SELECT ID_Vehiculo FROM vehiculo WHERE ID_Vehiculo = :id', [':id' => $values['FK_ID_Vehiculo']]) === null) {
        $errors[] = 'Seleccioná un vehículo válido.';
    }
    if ($values['FechaSalida'] === '' || $values['FechaLlegada'] === '' || str_replace('T', ' ', $values['FechaLlegada']) <= str_replace('T', ' ', $values['FechaSalida'])) {
        $errors[] = 'La llegada debe ser posterior a la salida.';
    }
    if (!in_array($values['Estado'], ['programado', 'en_curso', 'completado', 'cancelado'], true)) {
        $values['Estado'] = 'programado';
    }
    if ($values['FK_ID_Ruta'] <= 0) {
        if ($values['origen'] <= 0 || $values['destino'] <= 0 || $values['origen'] === $values['destino']) {
            $errors[] = 'Seleccioná una ruta existente o indicá origen y destino distintos para crear una nueva.';
        }
    } elseif (db_val('SELECT ID_Ruta FROM rutas WHERE ID_Ruta = :id', [':id' => $values['FK_ID_Ruta']]) === null) {
        $errors[] = 'La ruta seleccionada no existe.';
    }

    if ($errors === []) {
        try {
            $pdo = db();
            $pdo->beginTransaction();
            $routeId = (int) $values['FK_ID_Ruta'];
            if ($routeId <= 0) {
                $existingRoute = db_val(
                    'SELECT ID_Ruta FROM rutas WHERE ID_Inicio = :origen AND ID_Destino = :destino LIMIT 1',
                    [':origen' => $values['origen'], ':destino' => $values['destino']]
                );
                if ($existingRoute !== null) {
                    $routeId = (int) $existingRoute;
                } else {
                    db_exec(
                        'INSERT INTO rutas (ID_Inicio, ID_Destino, DistanciaTotal, TiempoEstimado) VALUES (:origen, :destino, :distancia, :tiempo)',
                        [
                            ':origen' => $values['origen'],
                            ':destino' => $values['destino'],
                            ':distancia' => $values['distancia'] > 0 ? $values['distancia'] : null,
                            ':tiempo' => $values['tiempo'] > 0 ? $values['tiempo'] : null,
                        ]
                    );
                    $routeId = (int) $pdo->lastInsertId();
                }
            }

            $params = [
                ':proveedor' => $values['FK_ID_Proveedor'],
                ':ruta' => $routeId,
                ':vehiculo' => $values['FK_ID_Vehiculo'],
                ':pension' => $values['FK_ID_Pension'] > 0 ? $values['FK_ID_Pension'] : null,
                ':hotel' => $values['FK_ID_Hotel'] > 0 ? $values['FK_ID_Hotel'] : null,
                ':salida' => str_replace('T', ' ', $values['FechaSalida']) . ':00',
                ':llegada' => str_replace('T', ' ', $values['FechaLlegada']) . ':00',
                ':estado' => $values['Estado'],
            ];
            if ($isEdit) {
                $params[':id'] = $id;
                db_exec(
                    'UPDATE viajes SET FK_ID_Proveedor = :proveedor, FK_ID_Ruta = :ruta, FK_ID_Vehiculo = :vehiculo,
                            FK_ID_Pension = :pension, FK_ID_Hotel = :hotel, FechaSalida = :salida, FechaLlegada = :llegada, Estado = :estado
                     WHERE ID_Viaje = :id',
                    $params
                );
                $tripId = $id;
            } else {
                db_exec(
                    'INSERT INTO viajes (FK_ID_Proveedor, FK_ID_Ruta, FK_ID_Vehiculo, FK_ID_Pension, FK_ID_Hotel, FechaSalida, FechaLlegada, Estado)
                     VALUES (:proveedor, :ruta, :vehiculo, :pension, :hotel, :salida, :llegada, :estado)',
                    $params
                );
                $tripId = (int) $pdo->lastInsertId();
            }
            db_exec('DELETE FROM viaje_chofer WHERE FK_ID_Viaje = :id', [':id' => $tripId]);
            foreach ($selectedDrivers as $driverId) {
                db_exec('INSERT INTO viaje_chofer (FK_ID_Viaje, FK_ID_Chofer) VALUES (:viaje, :chofer)', [':viaje' => $tripId, ':chofer' => $driverId]);
            }
            $pdo->commit();
            audit('viajes', $isEdit ? 'UPDATE' : 'INSERT', (string) $tripId, ($isEdit ? 'Actualización' : 'Alta') . ' de viaje');
            redirect_with('admin/viajes.php', 'success', 'Viaje guardado correctamente.');
        } catch (Throwable $e) {
            if (db()->inTransaction()) {
                db()->rollBack();
            }
            error_log('[AuraViajes] Error viaje_form: ' . $e->getMessage());
            $errors[] = 'No se pudo guardar el viaje.';
        }
    }
}

$pageTitle = $isEdit ? 'Editar viaje' : 'Nuevo viaje';
$activeSection = 'viajes';
$breadcrumbs = ['Panel' => 'admin/index.php', 'Viajes' => 'admin/viajes.php', $pageTitle => ''];
require __DIR__ . '/../includes/admin_header.php';
?>
<form method="post" action="<?= e(base_url('admin/viaje_form.php' . ($isEdit ? '?id=' . $id : ''))) ?>" data-confirm="¿Guardar el viaje?">
    <?= csrf_field() ?>
    <div class="detail-grid">
        <div class="stack">
            <div class="card">
                <div class="card-header"><h2>Operación</h2></div>
                <div class="card-body">
                    <?php foreach ($errors as $error): ?><div class="alert alert-error"><?= e($error) ?></div><?php endforeach; ?>
                    <div class="form-grid">
                        <div class="field">
                            <label for="FK_ID_Proveedor">Proveedor *</label>
                            <select id="FK_ID_Proveedor" name="FK_ID_Proveedor" required>
                                <option value="">Seleccionar</option>
                                <?php foreach ($providers as $provider): ?>
                                    <option value="<?= (int) $provider['ID_Proveedor'] ?>" <?= (int) $values['FK_ID_Proveedor'] === (int) $provider['ID_Proveedor'] ? 'selected' : '' ?>><?= e($provider['Nombre']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="field">
                            <label for="FK_ID_Vehiculo">Vehículo *</label>
                            <select id="FK_ID_Vehiculo" name="FK_ID_Vehiculo" required>
                                <option value="">Seleccionar</option>
                                <?php foreach ($vehicles as $vehicle): ?>
                                    <option value="<?= (int) $vehicle['ID_Vehiculo'] ?>" <?= (int) $values['FK_ID_Vehiculo'] === (int) $vehicle['ID_Vehiculo'] ? 'selected' : '' ?>><?= e(($vehicle['Patente'] ?: 'Sin patente') . ' · ' . $vehicle['Proveedor'] . ' · ' . ($vehicle['Medio'] ?? '') . ' ' . ($vehicle['Clase'] ?? '')) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="field">
                            <label for="FK_ID_Hotel">Hotel</label>
                            <select id="FK_ID_Hotel" name="FK_ID_Hotel">
                                <option value="">Sin hotel</option>
                                <?php foreach ($hotels as $hotel): ?>
                                    <option value="<?= (int) $hotel['ID_Hotel'] ?>" <?= (int) $values['FK_ID_Hotel'] === (int) $hotel['ID_Hotel'] ? 'selected' : '' ?>><?= e($hotel['Nombre']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="field">
                            <label for="FK_ID_Pension">Pensión</label>
                            <select id="FK_ID_Pension" name="FK_ID_Pension">
                                <option value="">Sin pensión</option>
                                <?php foreach ($pensions as $pension): ?>
                                    <option value="<?= (int) $pension['ID_Pension'] ?>" <?= (int) $values['FK_ID_Pension'] === (int) $pension['ID_Pension'] ? 'selected' : '' ?>><?= e($pension['Opcion']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="field">
                            <label for="FechaSalida">Salida *</label>
                            <input type="datetime-local" id="FechaSalida" name="FechaSalida" value="<?= e($values['FechaSalida']) ?>" required>
                        </div>
                        <div class="field">
                            <label for="FechaLlegada">Llegada *</label>
                            <input type="datetime-local" id="FechaLlegada" name="FechaLlegada" value="<?= e($values['FechaLlegada']) ?>" required>
                        </div>
                        <div class="field">
                            <label for="Estado">Estado</label>
                            <select id="Estado" name="Estado">
                                <?php foreach (['programado', 'en_curso', 'completado', 'cancelado'] as $option): ?>
                                    <option value="<?= e($option) ?>" <?= $values['Estado'] === $option ? 'selected' : '' ?>><?= e(estado_label($option)) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                </div>
            </div>
            <div class="card">
                <div class="card-header"><h2>Ruta</h2></div>
                <div class="card-body">
                    <div class="field">
                        <label for="FK_ID_Ruta">Ruta existente</label>
                        <select id="FK_ID_Ruta" name="FK_ID_Ruta">
                            <option value="0">Crear nueva ruta</option>
                            <?php foreach ($routes as $route): ?>
                                <option value="<?= (int) $route['ID_Ruta'] ?>" <?= (int) $values['FK_ID_Ruta'] === (int) $route['ID_Ruta'] ? 'selected' : '' ?>><?= e($route['Origen'] . ' → ' . $route['Destino'] . ' · ' . (int) $route['DistanciaTotal'] . ' km') ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-grid">
                        <div class="field">
                            <label for="origen">Origen (nueva ruta)</label>
                            <select id="origen" name="origen">
                                <option value="">Seleccionar</option>
                                <?php foreach ($destinations as $destination): ?>
                                    <option value="<?= (int) $destination['ID_Destino'] ?>" <?= (int) $values['origen'] === (int) $destination['ID_Destino'] ? 'selected' : '' ?>><?= e($destination['NombreUbicacion']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="field">
                            <label for="destino">Destino (nueva ruta)</label>
                            <select id="destino" name="destino">
                                <option value="">Seleccionar</option>
                                <?php foreach ($destinations as $destination): ?>
                                    <option value="<?= (int) $destination['ID_Destino'] ?>" <?= (int) $values['destino'] === (int) $destination['ID_Destino'] ? 'selected' : '' ?>><?= e($destination['NombreUbicacion']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="field"><label for="distancia">Distancia (km)</label><input type="number" id="distancia" name="distancia" min="0" value="<?= (int) $values['distancia'] ?>"></div>
                        <div class="field"><label for="tiempo">Tiempo estimado (min)</label><input type="number" id="tiempo" name="tiempo" min="0" value="<?= (int) $values['tiempo'] ?>"></div>
                    </div>
                    <p class="small muted">Si elegís una ruta existente, se ignoran los campos de nueva ruta.</p>
                </div>
            </div>
        </div>
        <div class="stack">
            <div class="card">
                <div class="card-header"><h2>Choferes</h2></div>
                <div class="card-body">
                    <div class="check-grid" style="grid-template-columns:1fr;">
                        <?php foreach ($drivers as $driver): ?>
                            <label class="check-item">
                                <input type="checkbox" name="choferes[]" value="<?= (int) $driver['ID_Chofer'] ?>" <?= in_array((int) $driver['ID_Chofer'], $selectedDrivers, true) ? 'checked' : '' ?>>
                                <span><?= e($driver['Nombre'] . ' ' . $driver['Apellido']) ?> <small class="muted">(<?= e($driver['DNI']) ?>)</small></span>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            <div class="card">
                <div class="card-body">
                    <button class="btn btn-primary btn-block" type="submit"><?= $isEdit ? 'Guardar cambios' : 'Crear viaje' ?></button>
                    <a class="btn btn-ghost btn-block" href="<?= e(base_url('admin/viajes.php')) ?>" style="margin-top:.5rem;">Cancelar</a>
                </div>
            </div>
        </div>
    </div>
</form>
<?php require __DIR__ . '/../includes/admin_footer.php'; ?>
