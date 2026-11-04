<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/data.php';
require_permission('gestionar_proveedores');

$id = get_int('id');
$isEdit = $id > 0;
$hotel = $isEdit ? db_one('SELECT * FROM hoteles WHERE ID_Hotel = :id', [':id' => $id]) : null;
if ($isEdit && $hotel === null) {
    redirect_with('admin/hoteles.php', 'error', 'El hotel solicitado no existe.');
}
$serviceIds = $isEdit ? array_map(static fn(array $row): int => (int) $row['FK_ID_Servicio'], db_all('SELECT FK_ID_Servicio FROM hotel_servicios WHERE FK_ID_Hotel = :id', [':id' => $id])) : [];
$values = [
    'Nombre' => $hotel['Nombre'] ?? '',
    'FK_ID_ValoracionH' => $hotel['FK_ID_ValoracionH'] ?? '',
    'Ubicacion' => $hotel['Ubicacion'] ?? '',
    'CantidadHabitaciones' => $hotel['CantidadHabitaciones'] ?? '',
    'FK_ID_THabitacion' => $hotel['FK_ID_THabitacion'] ?? '',
];
$ratings = db_all('SELECT * FROM valoracionh ORDER BY Valoracion DESC');
$roomTypes = db_all('SELECT * FROM tipohabitacion ORDER BY Tipo');
$services = db_all('SELECT * FROM servicios ORDER BY Nombre');
$errors = [];

if (is_post()) {
    verify_csrf();
    $values = [
        'Nombre' => post_string('Nombre'),
        'FK_ID_ValoracionH' => post_int('FK_ID_ValoracionH'),
        'Ubicacion' => post_string('Ubicacion'),
        'CantidadHabitaciones' => post_int('CantidadHabitaciones'),
        'FK_ID_THabitacion' => post_int('FK_ID_THabitacion'),
    ];
    $serviceIds = array_values(array_filter(array_map('intval', $_POST['servicios'] ?? [])));
    if ($values['Nombre'] === '') {
        $errors[] = 'El nombre del hotel es obligatorio.';
    }
    if ($values['CantidadHabitaciones'] < 0) {
        $values['CantidadHabitaciones'] = 0;
    }
    if ($errors === []) {
        try {
            $pdo = db();
            $pdo->beginTransaction();
            $params = [
                ':nombre' => $values['Nombre'],
                ':valoracion' => $values['FK_ID_ValoracionH'] > 0 ? $values['FK_ID_ValoracionH'] : null,
                ':ubicacion' => $values['Ubicacion'] !== '' ? $values['Ubicacion'] : null,
                ':cantidad' => $values['CantidadHabitaciones'],
                ':tipo' => $values['FK_ID_THabitacion'] > 0 ? $values['FK_ID_THabitacion'] : null,
            ];
            if ($isEdit) {
                $params[':id'] = $id;
                db_exec(
                    'UPDATE hoteles SET Nombre = :nombre, FK_ID_ValoracionH = :valoracion, Ubicacion = :ubicacion,
                            CantidadHabitaciones = :cantidad, FK_ID_THabitacion = :tipo WHERE ID_Hotel = :id',
                    $params
                );
                $hotelId = $id;
            } else {
                db_exec(
                    'INSERT INTO hoteles (Nombre, FK_ID_ValoracionH, Ubicacion, CantidadHabitaciones, FK_ID_THabitacion)
                     VALUES (:nombre, :valoracion, :ubicacion, :cantidad, :tipo)',
                    $params
                );
                $hotelId = (int) $pdo->lastInsertId();
            }
            db_exec('DELETE FROM hotel_servicios WHERE FK_ID_Hotel = :id', [':id' => $hotelId]);
            foreach ($serviceIds as $serviceId) {
                db_exec('INSERT INTO hotel_servicios (FK_ID_Hotel, FK_ID_Servicio) VALUES (:hotel, :servicio)', [':hotel' => $hotelId, ':servicio' => $serviceId]);
            }
            $pdo->commit();
            audit('hoteles', $isEdit ? 'UPDATE' : 'INSERT', (string) $hotelId, ($isEdit ? 'Actualización' : 'Alta') . ' de hotel ' . $values['Nombre']);
            redirect_with('admin/hoteles.php', 'success', 'Hotel guardado correctamente.');
        } catch (Throwable $e) {
            if (db()->inTransaction()) {
                db()->rollBack();
            }
            error_log('[AuraViajes] Error hotel_form: ' . $e->getMessage());
            $errors[] = 'No se pudo guardar el hotel.';
        }
    }
}

$pageTitle = $isEdit ? 'Editar hotel' : 'Nuevo hotel';
$activeSection = 'hoteles';
$breadcrumbs = ['Panel' => 'admin/index.php', 'Hoteles' => 'admin/hoteles.php', $pageTitle => ''];
require __DIR__ . '/../includes/admin_header.php';
?>
<form method="post" action="<?= e(base_url('admin/hotel_form.php' . ($isEdit ? '?id=' . $id : ''))) ?>" data-confirm="¿Guardar el hotel?">
    <?= csrf_field() ?>
    <div class="detail-grid">
        <div class="stack">
            <div class="card">
                <div class="card-header"><h2>Datos del alojamiento</h2></div>
                <div class="card-body">
                    <?php foreach ($errors as $error): ?><div class="alert alert-error"><?= e($error) ?></div><?php endforeach; ?>
                    <div class="form-grid">
                        <div class="field span-2">
                            <label for="Nombre">Nombre *</label>
                            <input type="text" id="Nombre" name="Nombre" value="<?= e($values['Nombre']) ?>" required maxlength="100">
                        </div>
                        <div class="field span-2">
                            <label for="Ubicacion">Ubicación</label>
                            <input type="text" id="Ubicacion" name="Ubicacion" value="<?= e($values['Ubicacion']) ?>" maxlength="200">
                        </div>
                        <div class="field">
                            <label for="FK_ID_ValoracionH">Valoración</label>
                            <select id="FK_ID_ValoracionH" name="FK_ID_ValoracionH">
                                <option value="">Sin valoración</option>
                                <?php foreach ($ratings as $rating): ?>
                                    <option value="<?= (int) $rating['ID_ValoracionH'] ?>" <?= (int) $values['FK_ID_ValoracionH'] === (int) $rating['ID_ValoracionH'] ? 'selected' : '' ?>><?= e(number_format((float) $rating['Valoracion'], 1, ',', '.')) ?> estrellas</option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="field">
                            <label for="CantidadHabitaciones">Cantidad de habitaciones</label>
                            <input type="number" id="CantidadHabitaciones" name="CantidadHabitaciones" value="<?= (int) $values['CantidadHabitaciones'] ?>" min="0">
                        </div>
                        <div class="field">
                            <label for="FK_ID_THabitacion">Tipo principal</label>
                            <select id="FK_ID_THabitacion" name="FK_ID_THabitacion">
                                <option value="">Sin tipo</option>
                                <?php foreach ($roomTypes as $roomType): ?>
                                    <option value="<?= (int) $roomType['ID_THabitacion'] ?>" <?= (int) $values['FK_ID_THabitacion'] === (int) $roomType['ID_THabitacion'] ? 'selected' : '' ?>><?= e($roomType['Tipo']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="stack">
            <div class="card">
                <div class="card-header"><h2>Servicios</h2></div>
                <div class="card-body">
                    <div class="check-grid" style="grid-template-columns:1fr;">
                        <?php foreach ($services as $service): ?>
                            <label class="check-item">
                                <input type="checkbox" name="servicios[]" value="<?= (int) $service['ID_Servicio'] ?>" <?= in_array((int) $service['ID_Servicio'], $serviceIds, true) ? 'checked' : '' ?>>
                                <span><?= e($service['Nombre']) ?></span>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            <div class="card">
                <div class="card-body">
                    <button class="btn btn-primary btn-block" type="submit"><?= $isEdit ? 'Guardar cambios' : 'Crear hotel' ?></button>
                    <a class="btn btn-ghost btn-block" href="<?= e(base_url('admin/hoteles.php')) ?>" style="margin-top:.5rem;">Cancelar</a>
                </div>
            </div>
        </div>
    </div>
</form>
<?php require __DIR__ . '/../includes/admin_footer.php'; ?>
