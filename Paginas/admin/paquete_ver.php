<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/data.php';
require_permission('ver_paquetes');

$id = get_int('id');
$package = get_package($id);
if ($package === null) {
    redirect_with('admin/paquetes.php', 'error', 'El paquete solicitado no existe.');
}

$manage = can('gestionar_paquetes');

if (is_post()) {
    verify_csrf();
    if (!$manage) {
        forbidden('No tenés permisos para modificar paquetes.');
    }
    $action = post_string('action');

    if ($action === 'toggle_publish') {
        $newState = $package['Estado'] === 'publicado' ? 'pausado' : 'publicado';
        db_exec('UPDATE paquetes SET Estado = :estado WHERE ID_Paquete = :id', [':estado' => $newState, ':id' => $id]);
        audit('paquetes', 'UPDATE', (string) $id, 'Cambio de estado a ' . $newState);
        redirect_with('admin/paquete_ver.php?id=' . $id, 'success', 'Estado del paquete actualizado a ' . $newState . '.');
    }

    if ($action === 'add_departure') {
        $departure = post_string('fecha_salida');
        $return = post_string('fecha_regreso');
        $total = post_int('cupos_totales');
        $state = post_string('estado');
        if ($departure === '' || $return === '' || $total < 1 || $return < $departure) {
            redirect_with('admin/paquete_ver.php?id=' . $id, 'error', 'Revisá las fechas y los cupos de la salida.');
        }
        if (!in_array($state, ['abierta', 'completa', 'cancelada', 'finalizada'], true)) {
            $state = 'abierta';
        }
        db_exec(
            'INSERT INTO salidas (FK_ID_Paquete, FechaSalida, FechaRegreso, CuposTotales, CuposDisponibles, Estado)
             VALUES (:package, :salida, :regreso, :total, :disponibles, :estado)',
            [':package' => $id, ':salida' => $departure, ':regreso' => $return, ':total' => $total, ':disponibles' => $total, ':estado' => $state]
        );
        audit('salidas', 'INSERT', (string) db()->lastInsertId(), 'Alta de salida para paquete ' . $id);
        redirect_with('admin/paquete_ver.php?id=' . $id, 'success', 'Salida agregada correctamente.');
    }

    if ($action === 'update_departure') {
        $departureId = post_int('departure_id');
        $row = db_one('SELECT * FROM salidas WHERE ID_Salida = :id AND FK_ID_Paquete = :package', [':id' => $departureId, ':package' => $id]);
        if ($row === null) {
            redirect_with('admin/paquete_ver.php?id=' . $id, 'error', 'La salida indicada no existe.');
        }
        $departure = post_string('fecha_salida');
        $return = post_string('fecha_regreso');
        $total = post_int('cupos_totales');
        $available = post_int('cupos_disponibles');
        $state = post_string('estado');
        if ($departure === '' || $return === '' || $total < 1 || $available < 0 || $available > $total || $return < $departure) {
            redirect_with('admin/paquete_ver.php?id=' . $id, 'error', 'Revisá las fechas y cupos de la salida.');
        }
        if (!in_array($state, ['abierta', 'completa', 'cancelada', 'finalizada'], true)) {
            $state = $row['Estado'];
        }
        db_exec(
            'UPDATE salidas SET FechaSalida = :salida, FechaRegreso = :regreso, CuposTotales = :total,
                    CuposDisponibles = :disponibles, Estado = :estado WHERE ID_Salida = :id',
            [
                ':salida' => $departure,
                ':regreso' => $return,
                ':total' => $total,
                ':disponibles' => $available,
                ':estado' => $state,
                ':id' => $departureId,
            ]
        );
        audit('salidas', 'UPDATE', (string) $departureId, 'Actualización de salida');
        redirect_with('admin/paquete_ver.php?id=' . $id, 'success', 'Salida actualizada.');
    }

    if ($action === 'delete_departure') {
        $departureId = post_int('departure_id');
        $bookings = (int) db_val('SELECT COUNT(*) FROM reserva_detalle WHERE FK_ID_Salida = :id', [':id' => $departureId], 0);
        if ($bookings > 0) {
            db_exec("UPDATE salidas SET Estado = 'cancelada' WHERE ID_Salida = :id AND FK_ID_Paquete = :package", [':id' => $departureId, ':package' => $id]);
            redirect_with('admin/paquete_ver.php?id=' . $id, 'warning', 'La salida tiene reservas asociadas y fue cancelada en lugar de eliminada.');
        }
        db_exec('DELETE FROM salidas WHERE ID_Salida = :id AND FK_ID_Paquete = :package', [':id' => $departureId, ':package' => $id]);
        audit('salidas', 'DELETE', (string) $departureId, 'Baja de salida');
        redirect_with('admin/paquete_ver.php?id=' . $id, 'success', 'Salida eliminada.');
    }

    if ($action === 'add_itinerary') {
        $day = post_int('dia');
        $title = post_string('titulo');
        $description = post_string('descripcion');
        if ($day < 1 || $day > (int) $package['DuracionDias'] || $title === '') {
            redirect_with('admin/paquete_ver.php?id=' . $id, 'error', 'El día debe estar dentro de la duración del paquete y el título es obligatorio.');
        }
        try {
            db_exec(
                'INSERT INTO paquete_itinerario (FK_ID_Paquete, Dia, Titulo, Descripcion) VALUES (:package, :dia, :titulo, :descripcion)',
                [':package' => $id, ':dia' => $day, ':titulo' => $title, ':descripcion' => $description !== '' ? $description : null]
            );
            audit('paquete_itinerario', 'INSERT', (string) db()->lastInsertId(), 'Alta de día ' . $day);
            redirect_with('admin/paquete_ver.php?id=' . $id, 'success', 'Día del itinerario agregado.');
        } catch (Throwable $e) {
            redirect_with('admin/paquete_ver.php?id=' . $id, 'error', 'Ya existe un día con ese número en el itinerario.');
        }
    }

    if ($action === 'delete_itinerary') {
        $itineraryId = post_int('itinerary_id');
        db_exec('DELETE FROM paquete_itinerario WHERE ID_Itinerario = :id AND FK_ID_Paquete = :package', [':id' => $itineraryId, ':package' => $id]);
        audit('paquete_itinerario', 'DELETE', (string) $itineraryId, 'Baja de día del itinerario');
        redirect_with('admin/paquete_ver.php?id=' . $id, 'success', 'Día eliminado.');
    }
}

$destinations = package_destinations($id);
$itinerary = package_itinerary($id);
$services = package_services($id);
$categories = package_categories($id);
$departures = db_all(
    "SELECT s.*, (SELECT COUNT(*) FROM reserva_detalle rd WHERE rd.FK_ID_Salida = s.ID_Salida) AS Reservas
     FROM salidas s WHERE s.FK_ID_Paquete = :id ORDER BY s.FechaSalida ASC",
    [':id' => $id]
);
$pricing = package_current_price($id);
$promotions = db_all(
    'SELECT pr.* FROM promocion_paquete pp INNER JOIN promociones pr ON pr.ID_Promocion = pp.FK_ID_Promocion
     WHERE pp.FK_ID_Paquete = :id ORDER BY pr.FechaInicio DESC',
    [':id' => $id]
);
$destinationNames = implode(' · ', array_column($destinations, 'NombreUbicacion'));

$pageTitle = $package['Nombre'];
$activeSection = 'paquetes';
$breadcrumbs = ['Panel' => 'admin/index.php', 'Paquetes' => 'admin/paquetes.php', $package['Nombre'] => ''];
$pageActions = '<a class="btn btn-ghost" target="_blank" rel="noopener" href="' . e(base_url('paquete.php?id=' . $id)) . '">Ver en el sitio</a>'
    . ($manage ? '<a class="btn btn-primary" href="' . e(base_url('admin/paquete_form.php?id=' . $id)) . '">Editar</a>' : '');
require __DIR__ . '/../includes/admin_header.php';
?>
<div class="profile-head">
    <div class="profile-main">
        <span class="avatar avatar-lg" style="background:linear-gradient(135deg,#0a6f8f,#2a93b5);"><?= e(initials($package['Nombre'])) ?></span>
        <div>
            <div class="flex gap-1" style="align-items:center;flex-wrap:wrap;">
                <h2 class="mb-0"><?= e($package['Nombre']) ?></h2>
                <span class="<?= e(badge_class($package['Estado'])) ?>"><?= e(estado_label($package['Estado'])) ?></span>
            </div>
            <p class="muted mb-0"><?= e($package['Codigo']) ?> · <?= (int) $package['DuracionDias'] ?> días · <?= e($destinationNames ?: 'Sin destinos') ?></p>
        </div>
    </div>
    <div class="profile-actions">
        <?php if ($manage): ?>
            <form method="post" action="<?= e(base_url('admin/paquete_ver.php?id=' . $id)) ?>" data-confirm="¿Cambiar el estado de publicación?">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="toggle_publish">
                <button class="btn btn-ghost" type="submit"><?= $package['Estado'] === 'publicado' ? 'Pausar publicación' : 'Publicar' ?></button>
            </form>
        <?php endif; ?>
    </div>
</div>

<section class="stat-grid stat-grid-3">
    <div class="stat-card">
        <span class="stat-label">Precio actual</span>
        <span class="stat-value"><?= e(money($pricing['final'])) ?></span>
        <span class="stat-hint"><?= $pricing['has_discount'] ? 'Con promoción aplicada' : 'Precio de lista' ?></span>
    </div>
    <div class="stat-card">
        <span class="stat-label">Salidas futuras</span>
        <span class="stat-value"><?= count(array_filter($departures, static fn(array $row): bool => $row['FechaSalida'] >= date('Y-m-d'))) ?></span>
        <span class="stat-hint">Salidas registradas en total: <?= count($departures) ?></span>
    </div>
    <div class="stat-card">
        <span class="stat-label">Reservas asociadas</span>
        <span class="stat-value"><?= (int) $package['reservas_count'] ?></span>
        <span class="stat-hint">Items de reserva sobre este paquete</span>
    </div>
</section>

<div class="detail-grid">
    <div class="stack">
        <div class="card">
            <div class="card-header"><h2>Descripción</h2></div>
            <div class="card-body">
                <p style="white-space:pre-line;"><?= e($package['Descripcion'] ?: 'Sin descripción cargada.') ?></p>
            </div>
        </div>

        <div class="card">
            <div class="card-header"><h2>Itinerario</h2><span class="small muted"><?= count($itinerary) ?> días cargados</span></div>
            <div class="card-body">
                <?php if ($itinerary === []): ?>
                    <p class="muted small">Todavía no se cargó el itinerario.</p>
                <?php endif; ?>
                <?php foreach ($itinerary as $day): ?>
                    <div class="timeline-item">
                        <div class="timeline-badge">Día <?= (int) $day['Dia'] ?></div>
                        <div class="timeline-content">
                            <div class="flex-between">
                                <strong><?= e($day['Titulo']) ?></strong>
                                <?php if ($manage): ?>
                                    <form method="post" action="<?= e(base_url('admin/paquete_ver.php?id=' . $id)) ?>" data-confirm="¿Eliminar este día?">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="action" value="delete_itinerary">
                                        <input type="hidden" name="itinerary_id" value="<?= (int) $day['ID_Itinerario'] ?>">
                                        <button class="icon-btn danger" type="submit" title="Eliminar">&times;</button>
                                    </form>
                                <?php endif; ?>
                            </div>
                            <p class="small muted" style="margin:.2rem 0 0;white-space:pre-line;"><?= e($day['Descripcion'] ?: '') ?></p>
                        </div>
                    </div>
                <?php endforeach; ?>
                <?php if ($manage): ?>
                    <details style="margin-top:.8rem;">
                        <summary class="small" style="cursor:pointer;color:var(--accent-strong);">Agregar día al itinerario</summary>
                        <form method="post" action="<?= e(base_url('admin/paquete_ver.php?id=' . $id)) ?>" style="margin-top:.7rem;">
                            <?= csrf_field() ?>
                            <input type="hidden" name="action" value="add_itinerary">
                            <div class="form-grid">
                                <div class="field" style="max-width:120px;">
                                    <label for="dia">Día</label>
                                    <input type="number" id="dia" name="dia" min="1" max="<?= (int) $package['DuracionDias'] ?>" required>
                                </div>
                                <div class="field">
                                    <label for="titulo">Título</label>
                                    <input type="text" id="titulo" name="titulo" maxlength="120" required>
                                </div>
                                <div class="field span-2">
                                    <label for="descripcion">Descripción</label>
                                    <textarea id="descripcion" name="descripcion" rows="2"></textarea>
                                </div>
                            </div>
                            <button class="btn btn-primary btn-sm" type="submit" style="margin-top:.6rem;">Agregar día</button>
                        </form>
                    </details>
                <?php endif; ?>
            </div>
        </div>

        <div class="card">
            <div class="card-header"><h2>Salidas y cupos</h2></div>
            <div class="table-wrap">
                <table class="data-table">
                    <thead><tr><th>Salida</th><th>Regreso</th><th>Cupos</th><th>Reservas</th><th>Estado</th></tr></thead>
                    <tbody>
                    <?php if ($departures === []): ?>
                        <tr><td colspan="5" class="table-empty">No hay salidas registradas.</td></tr>
                    <?php endif; ?>
                    <?php foreach ($departures as $dep): ?>
                        <tr>
                            <td><?= e(fecha($dep['FechaSalida'])) ?></td>
                            <td><?= e(fecha($dep['FechaRegreso'])) ?></td>
                            <td><?= (int) $dep['CuposDisponibles'] ?> / <?= (int) $dep['CuposTotales'] ?></td>
                            <td><?= (int) $dep['Reservas'] ?></td>
                            <td><span class="<?= e(badge_class($dep['Estado'])) ?>"><?= e(estado_label($dep['Estado'])) ?></span></td>
                        </tr>
                        <?php if ($manage): ?>
                            <tr class="inline-form-row">
                                <td colspan="5">
                                    <form method="post" action="<?= e(base_url('admin/paquete_ver.php?id=' . $id)) ?>" class="inline-form">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="action" value="update_departure">
                                        <input type="hidden" name="departure_id" value="<?= (int) $dep['ID_Salida'] ?>">
                                        <label>Salida <input type="date" name="fecha_salida" value="<?= e(fecha_input($dep['FechaSalida'])) ?>" required></label>
                                        <label>Regreso <input type="date" name="fecha_regreso" value="<?= e(fecha_input($dep['FechaRegreso'])) ?>" required></label>
                                        <label>Totales <input type="number" name="cupos_totales" min="1" value="<?= (int) $dep['CuposTotales'] ?>" required style="width:80px;"></label>
                                        <label>Disponibles <input type="number" name="cupos_disponibles" min="0" max="<?= (int) $dep['CuposTotales'] ?>" value="<?= (int) $dep['CuposDisponibles'] ?>" required style="width:80px;"></label>
                                        <select name="estado">
                                            <?php foreach (['abierta', 'completa', 'cancelada', 'finalizada'] as $stateOption): ?>
                                                <option value="<?= e($stateOption) ?>" <?= $dep['Estado'] === $stateOption ? 'selected' : '' ?>><?= e(estado_label($stateOption)) ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                        <button class="btn btn-primary btn-sm" type="submit">Guardar</button>
                                    </form>
                                    <form method="post" action="<?= e(base_url('admin/paquete_ver.php?id=' . $id)) ?>" data-confirm="¿Eliminar esta salida? Si tiene reservas se cancelará." style="display:inline;">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="action" value="delete_departure">
                                        <input type="hidden" name="departure_id" value="<?= (int) $dep['ID_Salida'] ?>">
                                        <button class="btn btn-ghost btn-sm text-danger" type="submit">Eliminar</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endif; ?>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php if ($manage): ?>
                <div class="card-body" style="border-top:1px solid var(--line);">
                    <h3 class="small" style="margin-top:0;">Agregar salida</h3>
                    <form method="post" action="<?= e(base_url('admin/paquete_ver.php?id=' . $id)) ?>" class="inline-form">
                        <?= csrf_field() ?>
                        <input type="hidden" name="action" value="add_departure">
                        <label>Salida <input type="date" name="fecha_salida" required></label>
                        <label>Regreso <input type="date" name="fecha_regreso" required></label>
                        <label>Cupos <input type="number" name="cupos_totales" min="1" value="20" required style="width:90px;"></label>
                        <select name="estado">
                            <option value="abierta">Abierta</option>
                            <option value="completa">Completa</option>
                        </select>
                        <button class="btn btn-accent btn-sm" type="submit">Agregar salida</button>
                    </form>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="stack">
        <div class="card">
            <div class="card-header"><h2>Destinos</h2></div>
            <div class="card-body">
                <?php if ($destinations === []): ?>
                    <p class="muted small mb-0">Sin destinos asignados.</p>
                <?php endif; ?>
                <?php foreach ($destinations as $destination): ?>
                    <div class="flex-between" style="padding:.5rem 0;border-bottom:1px dashed var(--line);">
                        <div>
                            <strong class="small"><?= (int) $destination['Orden'] ?>. <?= e($destination['NombreUbicacion']) ?></strong>
                            <?php if ($destination['Noches'] !== null): ?>
                                <div class="small muted"><?= (int) $destination['Noches'] ?> noches</div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="card">
            <div class="card-header"><h2>Servicios incluidos</h2></div>
            <div class="card-body">
                <?php $included = array_filter($services, static fn(array $row): bool => (bool) $row['Incluido']); ?>
                <?php if ($included === []): ?>
                    <p class="muted small mb-0">Sin servicios incluidos.</p>
                <?php endif; ?>
                <?php foreach ($included as $service): ?>
                    <span class="chip" style="margin:.2rem;"><?= e($service['Nombre']) ?></span>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="card">
            <div class="card-header"><h2>Categorías</h2></div>
            <div class="card-body">
                <?php if ($categories === []): ?>
                    <p class="muted small mb-0">Sin categorías asignadas.</p>
                <?php endif; ?>
                <?php foreach ($categories as $category): ?>
                    <span class="chip" style="margin:.2rem;"><?= e($category['Nombre']) ?></span>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="card">
            <div class="card-header"><h2>Promociones</h2></div>
            <div class="card-body">
                <?php if ($promotions === []): ?>
                    <p class="muted small mb-0">Sin promociones asociadas.</p>
                <?php endif; ?>
                <?php foreach ($promotions as $promo): ?>
                    <div style="padding:.5rem 0;border-bottom:1px dashed var(--line);">
                        <div class="flex-between">
                            <strong class="small"><?= e($promo['Nombre']) ?></strong>
                            <span class="<?= e(badge_class($promo['Estado'])) ?>"><?= e(estado_label($promo['Estado'])) ?></span>
                        </div>
                        <div class="small muted"><?= e(fecha($promo['FechaInicio'])) ?> al <?= e(fecha($promo['FechaFin'])) ?> · <?= $promo['TipoDescuento'] === 'porcentaje' ? (float) $promo['ValorDescuento'] . '%' : money($promo['ValorDescuento']) ?></div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>
<?php require __DIR__ . '/../includes/admin_footer.php'; ?>
