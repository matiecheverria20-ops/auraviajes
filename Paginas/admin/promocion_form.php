<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/data.php';
require_permission('gestionar_contenido');

$id = get_int('id');
$isEdit = $id > 0;
$promotion = $isEdit ? db_one('SELECT * FROM promociones WHERE ID_Promocion = :id', [':id' => $id]) : null;
if ($isEdit && $promotion === null) {
    redirect_with('admin/promociones.php', 'error', 'La promoción solicitada no existe.');
}
$values = [
    'Nombre' => $promotion['Nombre'] ?? '',
    'Descripcion' => $promotion['Descripcion'] ?? '',
    'TipoDescuento' => $promotion['TipoDescuento'] ?? 'porcentaje',
    'ValorDescuento' => $promotion['ValorDescuento'] ?? 10,
    'FechaInicio' => $promotion['FechaInicio'] ?? date('Y-m-d'),
    'FechaFin' => $promotion['FechaFin'] ?? date('Y-m-d', strtotime('+30 days')),
    'Estado' => $promotion['Estado'] ?? 'activa',
];
$selectedPackages = $isEdit
    ? array_map(static fn(array $row): int => (int) $row['FK_ID_Paquete'], db_all('SELECT FK_ID_Paquete FROM promocion_paquete WHERE FK_ID_Promocion = :id', [':id' => $id]))
    : [];
$packages = all_packages(false);
$errors = [];

if (is_post()) {
    verify_csrf();
    $values = [
        'Nombre' => post_string('Nombre'),
        'Descripcion' => post_string('Descripcion'),
        'TipoDescuento' => post_string('TipoDescuento'),
        'ValorDescuento' => post_string('ValorDescuento'),
        'FechaInicio' => post_string('FechaInicio'),
        'FechaFin' => post_string('FechaFin'),
        'Estado' => post_string('Estado'),
    ];
    $selectedPackages = array_values(array_filter(array_map('intval', $_POST['paquetes'] ?? [])));
    $value = (float) str_replace(',', '.', (string) $values['ValorDescuento']);
    if ($values['Nombre'] === '') {
        $errors[] = 'El nombre es obligatorio.';
    }
    if (!in_array($values['TipoDescuento'], ['porcentaje', 'importe'], true)) {
        $values['TipoDescuento'] = 'porcentaje';
    }
    if ($value <= 0 || ($values['TipoDescuento'] === 'porcentaje' && $value > 100)) {
        $errors[] = 'El descuento debe ser mayor a cero y, si es porcentaje, no puede superar 100.';
    }
    if ($values['FechaInicio'] === '' || $values['FechaFin'] === '' || $values['FechaFin'] < $values['FechaInicio']) {
        $errors[] = 'La vigencia de la promoción es inválida.';
    }
    if (!in_array($values['Estado'], ['activa', 'inactiva', 'finalizada'], true)) {
        $values['Estado'] = 'activa';
    }
    if ($selectedPackages === []) {
        $errors[] = 'Seleccioná al menos un paquete.';
    }

    if ($errors === []) {
        try {
            $pdo = db();
            $pdo->beginTransaction();
            $params = [
                ':nombre' => $values['Nombre'],
                ':descripcion' => $values['Descripcion'] !== '' ? $values['Descripcion'] : null,
                ':tipo' => $values['TipoDescuento'],
                ':valor' => round($value, 2),
                ':inicio' => $values['FechaInicio'],
                ':fin' => $values['FechaFin'],
                ':estado' => $values['Estado'],
            ];
            if ($isEdit) {
                $params[':id'] = $id;
                db_exec(
                    'UPDATE promociones SET Nombre = :nombre, Descripcion = :descripcion, TipoDescuento = :tipo, ValorDescuento = :valor,
                            FechaInicio = :inicio, FechaFin = :fin, Estado = :estado WHERE ID_Promocion = :id',
                    $params
                );
                $promotionId = $id;
            } else {
                db_exec(
                    'INSERT INTO promociones (Nombre, Descripcion, TipoDescuento, ValorDescuento, FechaInicio, FechaFin, Estado)
                     VALUES (:nombre, :descripcion, :tipo, :valor, :inicio, :fin, :estado)',
                    $params
                );
                $promotionId = (int) $pdo->lastInsertId();
            }
            db_exec('DELETE FROM promocion_paquete WHERE FK_ID_Promocion = :id', [':id' => $promotionId]);
            foreach ($selectedPackages as $packageId) {
                db_exec(
                    'INSERT INTO promocion_paquete (FK_ID_Promocion, FK_ID_Paquete) VALUES (:promocion, :paquete)',
                    [':promocion' => $promotionId, ':paquete' => $packageId]
                );
            }
            $pdo->commit();
            audit('promociones', $isEdit ? 'UPDATE' : 'INSERT', (string) $promotionId, ($isEdit ? 'Actualización' : 'Alta') . ' de promoción ' . $values['Nombre']);
            redirect_with('admin/promociones.php', 'success', 'Promoción guardada correctamente.');
        } catch (Throwable $e) {
            if (db()->inTransaction()) {
                db()->rollBack();
            }
            error_log('[AuraViajes] Error promocion_form: ' . $e->getMessage());
            $errors[] = 'No se pudo guardar la promoción.';
        }
    }
}

$pageTitle = $isEdit ? 'Editar promoción' : 'Nueva promoción';
$activeSection = 'contenido';
$breadcrumbs = ['Panel' => 'admin/index.php', 'Contenido' => 'admin/contenido.php', 'Promociones' => 'admin/promociones.php', $pageTitle => ''];
require __DIR__ . '/../includes/admin_header.php';
?>
<form method="post" action="<?= e(base_url('admin/promocion_form.php' . ($isEdit ? '?id=' . $id : ''))) ?>" data-confirm="¿Guardar la promoción?">
    <?= csrf_field() ?>
    <div class="detail-grid">
        <div class="stack">
            <div class="card">
                <div class="card-header"><h2>Datos de la promoción</h2></div>
                <div class="card-body">
                    <?php foreach ($errors as $error): ?><div class="alert alert-error"><?= e($error) ?></div><?php endforeach; ?>
                    <div class="form-grid">
                        <div class="field span-2">
                            <label for="Nombre">Nombre *</label>
                            <input type="text" id="Nombre" name="Nombre" value="<?= e($values['Nombre']) ?>" required maxlength="120">
                        </div>
                        <div class="field span-2">
                            <label for="Descripcion">Descripción</label>
                            <textarea id="Descripcion" name="Descripcion" rows="3"><?= e($values['Descripcion']) ?></textarea>
                        </div>
                        <div class="field">
                            <label for="TipoDescuento">Tipo de descuento</label>
                            <select id="TipoDescuento" name="TipoDescuento">
                                <option value="porcentaje" <?= $values['TipoDescuento'] === 'porcentaje' ? 'selected' : '' ?>>Porcentaje</option>
                                <option value="importe" <?= $values['TipoDescuento'] === 'importe' ? 'selected' : '' ?>>Importe fijo</option>
                            </select>
                        </div>
                        <div class="field">
                            <label for="ValorDescuento">Valor *</label>
                            <input type="number" id="ValorDescuento" name="ValorDescuento" value="<?= e((string) $values['ValorDescuento']) ?>" min="0.01" step="0.01" required>
                        </div>
                        <div class="field">
                            <label for="FechaInicio">Inicio *</label>
                            <input type="date" id="FechaInicio" name="FechaInicio" value="<?= e($values['FechaInicio']) ?>" required>
                        </div>
                        <div class="field">
                            <label for="FechaFin">Fin *</label>
                            <input type="date" id="FechaFin" name="FechaFin" value="<?= e($values['FechaFin']) ?>" required>
                        </div>
                        <div class="field">
                            <label for="Estado">Estado</label>
                            <select id="Estado" name="Estado">
                                <?php foreach (['activa', 'inactiva', 'finalizada'] as $option): ?>
                                    <option value="<?= e($option) ?>" <?= $values['Estado'] === $option ? 'selected' : '' ?>><?= e(estado_label($option)) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="stack">
            <div class="card">
                <div class="card-header"><h2>Paquetes alcanzados</h2></div>
                <div class="card-body">
                    <div class="check-grid" style="grid-template-columns:1fr;">
                        <?php foreach ($packages as $package): ?>
                            <label class="check-item">
                                <input type="checkbox" name="paquetes[]" value="<?= (int) $package['ID_Paquete'] ?>" <?= in_array((int) $package['ID_Paquete'], $selectedPackages, true) ? 'checked' : '' ?>>
                                <span><?= e($package['Nombre']) ?> <small class="muted">(<?= e($package['Codigo']) ?>)</small></span>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            <div class="card">
                <div class="card-body">
                    <button class="btn btn-primary btn-block" type="submit"><?= $isEdit ? 'Guardar cambios' : 'Crear promoción' ?></button>
                    <a class="btn btn-ghost btn-block" href="<?= e(base_url('admin/promociones.php')) ?>" style="margin-top:.5rem;">Cancelar</a>
                </div>
            </div>
        </div>
    </div>
</form>
<?php require __DIR__ . '/../includes/admin_footer.php'; ?>
