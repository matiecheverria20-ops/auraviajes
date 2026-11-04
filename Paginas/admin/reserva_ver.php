<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/data.php';
require_any_permission(['gestionar_reservas', 'ver_reservas', 'gestionar_pagos', 'ver_pagos']);

$id = get_int('id');
$reservation = get_reservation($id);
if ($reservation === null) {
    redirect_with('admin/reservas.php', 'error', 'La reserva solicitada no existe.');
}

$history = db_all(
    'SELECT h.*, er.Nombre AS Estado, e.Nombre AS EmpleadoNombre, e.Apellido AS EmpleadoApellido
     FROM historial_estados h
     INNER JOIN estados_reserva er ON er.ID_EstadoReserva = h.FK_ID_EstadoReserva
     LEFT JOIN empleados e ON e.ID_Empleado = h.FK_ID_Empleado
     WHERE h.FK_ID_Reserva = :id ORDER BY h.Fecha ASC, h.ID_Historial ASC',
    [':id' => $id]
);
$allStates = db_all('SELECT * FROM estados_reserva ORDER BY ID_EstadoReserva');
$motives = db_all('SELECT * FROM motivos_cancelacion ORDER BY Nombre');
$methods = payment_methods();
$currencyList = currencies();
$details = reservation_details($id);
$passengers = reservation_passengers($id);
$payments = reservation_payments($id);
$paid = reservation_paid($id);
$balance = max(0.0, (float) $reservation['Total'] - $paid);
$cancelInfo = db_one(
    'SELECT c.*, m.Nombre AS Motivo FROM cancelaciones c
     INNER JOIN motivos_cancelacion m ON m.ID_MotivoCancelacion = c.FK_ID_MotivoCancelacion
     WHERE c.FK_ID_Reserva = :id',
    [':id' => $id]
);
$isCancelled = $reservation['EstadoNombre'] === 'Cancelada';

if (is_post()) {
    verify_csrf();
    $action = post_string('action');

    if ($action === 'update_state' && can('gestionar_reservas')) {
        $newStateId = post_int('estado');
        $state = db_one('SELECT * FROM estados_reserva WHERE ID_EstadoReserva = :id', [':id' => $newStateId]);
        if ($state === null || $state['Nombre'] === 'Cancelada') {
            redirect_with('admin/reserva_ver.php?id=' . $id, 'error', 'El estado seleccionado no es válido.');
        }
        if ($isCancelled) {
            redirect_with('admin/reserva_ver.php?id=' . $id, 'error', 'Una reserva cancelada no puede reactivarse desde este formulario.');
        }
        db_exec('UPDATE reservas SET FK_ID_EstadoReserva = :estado WHERE ID_Reserva = :id', [':estado' => $newStateId, ':id' => $id]);
        db_exec(
            'INSERT INTO historial_estados (FK_ID_Reserva, FK_ID_EstadoReserva, FK_ID_Empleado, Fecha, Observacion)
             VALUES (:reserva, :estado, :empleado, NOW(), :obs)',
            [
                ':reserva' => $id,
                ':estado' => $newStateId,
                ':empleado' => current_employee_id(),
                ':obs' => 'Cambio manual de estado a ' . $state['Nombre'],
            ]
        );
        audit('reservas', 'UPDATE', (string) $id, 'Cambio de estado a ' . $state['Nombre']);
        redirect_with('admin/reserva_ver.php?id=' . $id, 'success', 'Estado de la reserva actualizado.');
    }

    if ($action === 'payment' && can('gestionar_pagos')) {
        if ($isCancelled) {
            redirect_with('admin/reserva_ver.php?id=' . $id, 'error', 'No se pueden registrar pagos en una reserva cancelada.');
        }
        $amount = (float) str_replace(',', '.', post_string('importe'));
        $methodId = post_int('metodo');
        $currencyId = post_int('moneda');
        $operation = post_string('operacion');
        $state = post_string('estado');
        $date = post_string('fecha_pago');
        $errors = [];
        if ($amount <= 0) {
            $errors[] = 'El importe debe ser mayor a cero.';
        }
        if (db_val('SELECT ID_Metodo FROM metodopago WHERE ID_Metodo = :id', [':id' => $methodId]) === null) {
            $errors[] = 'Seleccioná un método de pago válido.';
        }
        if (db_val('SELECT ID_Moneda FROM monedas WHERE ID_Moneda = :id', [':id' => $currencyId]) === null) {
            $errors[] = 'Seleccioná una moneda válida.';
        }
        if (!in_array($state, ['pendiente', 'aprobado', 'rechazado'], true)) {
            $state = 'aprobado';
        }
        if ($operation !== '' && db_val('SELECT ID_PagoReserva FROM pagos_reserva WHERE NroOperacion = :op', [':op' => $operation]) !== null) {
            $errors[] = 'Ese número de operación ya fue registrado.';
        }
        if ($errors !== []) {
            foreach ($errors as $error) {
                flash('error', $error);
            }
            redirect('admin/reserva_ver.php?id=' . $id);
        }
        $paymentDate = $date !== '' ? str_replace('T', ' ', $date) . ':00' : date('Y-m-d H:i:s');
        db_exec(
            'INSERT INTO pagos_reserva (FK_ID_Reserva, FK_ID_Metodo, FK_ID_Moneda, Importe, FechaPago, NroOperacion, Estado)
             VALUES (:reserva, :metodo, :moneda, :importe, :fecha, :operacion, :estado)',
            [
                ':reserva' => $id,
                ':metodo' => $methodId,
                ':moneda' => $currencyId,
                ':importe' => round($amount, 2),
                ':fecha' => $paymentDate,
                ':operacion' => $operation !== '' ? $operation : null,
                ':estado' => $state,
            ]
        );
        $paymentId = (int) db()->lastInsertId();
        $newPaid = reservation_paid($id);
        if ($state === 'aprobado' && $newPaid + 0.01 >= (float) $reservation['Total'] && !in_array($reservation['EstadoNombre'], ['Pagada', 'Finalizada'], true)) {
            $paidState = reservation_status_id('Pagada');
            db_exec('UPDATE reservas SET FK_ID_EstadoReserva = :estado WHERE ID_Reserva = :id', [':estado' => $paidState, ':id' => $id]);
            db_exec(
                "INSERT INTO historial_estados (FK_ID_Reserva, FK_ID_EstadoReserva, FK_ID_Empleado, Fecha, Observacion)
                 VALUES (:reserva, :estado, :empleado, NOW(), 'Pago total registrado')",
                [':reserva' => $id, ':estado' => $paidState, ':empleado' => current_employee_id()]
            );
        }
        sync_cuenta_corriente((int) $reservation['FK_ID_Cliente']);
        audit('pagos_reserva', 'INSERT', (string) $paymentId, 'Pago de ' . money($amount) . ' en reserva ' . $reservation['CodigoReserva']);
        redirect_with('admin/reserva_ver.php?id=' . $id, 'success', 'Pago registrado correctamente.');
    }

    if ($action === 'void_payment' && can('gestionar_pagos')) {
        $paymentId = post_int('payment_id');
        $payment = db_one('SELECT * FROM pagos_reserva WHERE ID_PagoReserva = :id AND FK_ID_Reserva = :reserva', [':id' => $paymentId, ':reserva' => $id]);
        if ($payment === null) {
            redirect_with('admin/reserva_ver.php?id=' . $id, 'error', 'El pago indicado no existe.');
        }
        db_exec("UPDATE pagos_reserva SET Estado = 'anulado' WHERE ID_PagoReserva = :id", [':id' => $paymentId]);
        if ($reservation['EstadoNombre'] === 'Pagada' && reservation_balance($id) > 0.01) {
            db_exec('UPDATE reservas SET FK_ID_EstadoReserva = :estado WHERE ID_Reserva = :id', [':estado' => reservation_status_id('Confirmada'), ':id' => $id]);
        }
        sync_cuenta_corriente((int) $reservation['FK_ID_Cliente']);
        audit('pagos_reserva', 'UPDATE', (string) $paymentId, 'Anulación de pago de reserva ' . $reservation['CodigoReserva']);
        redirect_with('admin/reserva_ver.php?id=' . $id, 'success', 'Pago anulado.');
    }

    if ($action === 'observation' && can('gestionar_reservas')) {
        db_exec('UPDATE reservas SET Observaciones = :obs WHERE ID_Reserva = :id', [
            ':obs' => post_string('observaciones') !== '' ? post_string('observaciones') : null,
            ':id' => $id,
        ]);
        audit('reservas', 'UPDATE', (string) $id, 'Actualización de observaciones');
        redirect_with('admin/reserva_ver.php?id=' . $id, 'success', 'Observaciones guardadas.');
    }

    if ($action === 'cancel' && can('gestionar_reservas')) {
        if ($isCancelled) {
            redirect_with('admin/reserva_ver.php?id=' . $id, 'error', 'La reserva ya está cancelada.');
        }
        $motiveId = post_int('motivo');
        $refund = (float) str_replace(',', '.', post_string('importe_devuelto'));
        if ($motiveId <= 0) {
            redirect_with('admin/reserva_ver.php?id=' . $id, 'error', 'Seleccioná un motivo de cancelación.');
        }
        try {
            $pdo = db();
            $pdo->beginTransaction();
            $cancelState = reservation_status_id('Cancelada');
            db_exec('UPDATE reservas SET FK_ID_EstadoReserva = :estado WHERE ID_Reserva = :id', [':estado' => $cancelState, ':id' => $id]);
            foreach ($details as $detail) {
                if ($detail['FK_ID_Salida'] !== null) {
                    db_exec(
                        'UPDATE salidas SET CuposDisponibles = LEAST(CuposTotales, CuposDisponibles + :cantidad),
                                Estado = IF(Estado = "completa", "abierta", Estado)
                         WHERE ID_Salida = :id',
                        [':cantidad' => (int) $detail['Cantidad'], ':id' => (int) $detail['FK_ID_Salida']]
                    );
                }
            }
            db_exec(
                'INSERT INTO cancelaciones (FK_ID_Reserva, FK_ID_MotivoCancelacion, Fecha, ImporteDevuelto, Observaciones)
                 VALUES (:reserva, :motivo, NOW(), :devuelto, :obs)',
                [
                    ':reserva' => $id,
                    ':motivo' => $motiveId,
                    ':devuelto' => round($refund, 2),
                    ':obs' => post_string('observaciones_cancelacion') !== '' ? post_string('observaciones_cancelacion') : null,
                ]
            );
            db_exec(
                "INSERT INTO historial_estados (FK_ID_Reserva, FK_ID_EstadoReserva, FK_ID_Empleado, Fecha, Observacion)
                 VALUES (:reserva, :estado, :empleado, NOW(), :obs)",
                [':reserva' => $id, ':estado' => $cancelState, ':empleado' => current_employee_id(), ':obs' => 'Reserva cancelada']
            );
            $pdo->commit();
            sync_cuenta_corriente((int) $reservation['FK_ID_Cliente']);
            audit('reservas', 'UPDATE', (string) $id, 'Cancelación de reserva con devolución ' . money($refund));
            redirect_with('admin/reserva_ver.php?id=' . $id, 'success', 'Reserva cancelada y cupos restituidos.');
        } catch (Throwable $e) {
            if (db()->inTransaction()) {
                db()->rollBack();
            }
            error_log('[AuraViajes] Error cancelación: ' . $e->getMessage());
            redirect_with('admin/reserva_ver.php?id=' . $id, 'error', 'No se pudo cancelar la reserva.');
        }
    }
}

$clientName = trim((string) ($reservation['Nombre'] ?? '') . ' ' . (string) ($reservation['Apellido'] ?? '')) ?: ($reservation['Email'] ?? 'Cliente');
$pageTitle = $reservation['CodigoReserva'];
$activeSection = 'reservas';
$breadcrumbs = ['Panel' => 'admin/index.php', 'Reservas' => 'admin/reservas.php', $reservation['CodigoReserva'] => ''];
$pageActions = '<a class="btn btn-ghost" target="_blank" rel="noopener" href="' . e(base_url('admin/reserva_imprimir.php?id=' . $id)) . '">Imprimir</a>';
require __DIR__ . '/../includes/admin_header.php';
?>
<div class="profile-head">
    <div class="profile-main">
        <span class="avatar avatar-lg"><?= e(initials($clientName)) ?></span>
        <div>
            <div class="flex gap-1" style="align-items:center;flex-wrap:wrap;">
                <h2 class="mb-0"><?= e($reservation['CodigoReserva']) ?></h2>
                <span class="<?= e(badge_class($reservation['EstadoNombre'])) ?>"><?= e($reservation['EstadoNombre']) ?></span>
            </div>
            <p class="muted mb-0">
                <a href="<?= e(base_url('admin/cliente_ver.php?id=' . (int) $reservation['FK_ID_Cliente'])) ?>"><?= e($clientName) ?></a>
                · <?= e($reservation['CodigoCliente']) ?> · Creada el <?= e(fecha($reservation['FechaReserva'], true)) ?>
            </p>
        </div>
    </div>
    <div class="profile-actions">
        <a class="btn btn-primary" href="<?= e(base_url('admin/cliente_ver.php?id=' . (int) $reservation['FK_ID_Cliente'])) ?>">Ver cliente</a>
    </div>
</div>

<section class="stat-grid stat-grid-3">
    <div class="stat-card">
        <span class="stat-label">Total</span>
        <span class="stat-value"><?= e(money($reservation['Total'])) ?></span>
        <span class="stat-hint"><?= count($details) ?> ítems · <?= count($passengers) ?> pasajeros</span>
    </div>
    <div class="stat-card">
        <span class="stat-label">Cobrado</span>
        <span class="stat-value"><?= e(money($paid)) ?></span>
        <span class="stat-hint"><?= count(array_filter($payments, static fn(array $p): bool => $p['Estado'] === 'aprobado')) ?> pagos aprobados</span>
    </div>
    <div class="stat-card <?= $balance > 0.01 ? 'coral' : '' ?>">
        <span class="stat-label">Saldo</span>
        <span class="stat-value"><?= e(money($balance)) ?></span>
        <span class="stat-hint"><?= $balance > 0.01 ? 'Pendiente de cobro' : 'Reserva saldada' ?></span>
    </div>
</section>

<?php if ($cancelInfo !== null): ?>
    <div class="alert alert-error">
        Reserva cancelada: <?= e($cancelInfo['Motivo']) ?> · Devuelto <?= e(money($cancelInfo['ImporteDevuelto'])) ?> · <?= e(fecha($cancelInfo['Fecha'], true)) ?>
    </div>
<?php endif; ?>

<div class="detail-grid">
    <div class="stack">
        <div class="card">
            <div class="card-header"><h2>Detalle de la reserva</h2></div>
            <div class="table-wrap">
                <table class="data-table">
                    <thead><tr><th>Descripción</th><th>Salida</th><th class="num">Cantidad</th><th class="num">Precio</th><th class="num">Subtotal</th></tr></thead>
                    <tbody>
                    <?php if ($details === []): ?>
                        <tr><td colspan="5" class="table-empty">Sin ítems cargados.</td></tr>
                    <?php endif; ?>
                    <?php foreach ($details as $detail): ?>
                        <tr>
                            <td><?= e($detail['Descripcion']) ?></td>
                            <td><?= e(fecha($detail['FechaSalida'] ?? $detail['ViajeFechaSalida'] ?? null)) ?></td>
                            <td class="num"><?= (int) $detail['Cantidad'] ?></td>
                            <td class="num"><?= e(money($detail['PrecioUnitario'])) ?></td>
                            <td class="num"><?= e(money($detail['Subtotal'])) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="card">
            <div class="card-header"><h2>Pasajeros</h2></div>
            <div class="table-wrap">
                <table class="data-table">
                    <thead><tr><th>Pasajero</th><th>DNI</th><th>Teléfono</th><th>Nacionalidad</th><th>Titular</th></tr></thead>
                    <tbody>
                    <?php if ($passengers === []): ?>
                        <tr><td colspan="5" class="table-empty">Sin pasajeros registrados.</td></tr>
                    <?php endif; ?>
                    <?php foreach ($passengers as $passenger): ?>
                        <tr>
                            <td><?= e(trim($passenger['Nombre'] . ' ' . $passenger['Apellido'])) ?></td>
                            <td><?= e($passenger['DNI']) ?></td>
                            <td><?= e($passenger['Telefono'] ?? '—') ?></td>
                            <td><?= e($passenger['Nacionalidad'] ?? '—') ?></td>
                            <td><?= $passenger['Titular'] ? '<span class="badge badge-success">Titular</span>' : '—' ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="card">
            <div class="card-header"><h2>Pagos</h2></div>
            <div class="table-wrap">
                <table class="data-table">
                    <thead><tr><th>Fecha</th><th>Método</th><th>Moneda</th><th class="num">Importe</th><th>Operación</th><th>Estado</th><th></th></tr></thead>
                    <tbody>
                    <?php if ($payments === []): ?>
                        <tr><td colspan="7" class="table-empty">Sin pagos registrados.</td></tr>
                    <?php endif; ?>
                    <?php foreach ($payments as $pay): ?>
                        <tr>
                            <td><?= e(fecha($pay['FechaPago'], true)) ?></td>
                            <td><?= e($pay['Metodo']) ?></td>
                            <td><?= e($pay['Codigo']) ?></td>
                            <td class="num"><?= e(money($pay['Importe'])) ?></td>
                            <td><?= e($pay['NroOperacion'] ?? '—') ?></td>
                            <td><span class="<?= e(badge_class($pay['Estado'])) ?>"><?= e(estado_label($pay['Estado'])) ?></span></td>
                            <td class="actions">
                                <?php if (can('gestionar_pagos') && $pay['Estado'] === 'aprobado'): ?>
                                    <form method="post" action="<?= e(base_url('admin/reserva_ver.php?id=' . $id)) ?>" data-confirm="¿Anular este pago? El saldo de la reserva se recalculará.">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="action" value="void_payment">
                                        <input type="hidden" name="payment_id" value="<?= (int) $pay['ID_PagoReserva'] ?>">
                                        <button class="btn btn-ghost btn-sm text-danger" type="submit">Anular</button>
                                    </form>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php if (can('gestionar_pagos') && !$isCancelled): ?>
                <div class="card-body" style="border-top:1px solid var(--line);">
                    <h3 class="small" style="margin-top:0;">Registrar pago</h3>
                    <form method="post" action="<?= e(base_url('admin/reserva_ver.php?id=' . $id)) ?>" data-confirm="¿Registrar el pago?">
                        <?= csrf_field() ?>
                        <input type="hidden" name="action" value="payment">
                        <div class="form-grid">
                            <div class="field">
                                <label for="importe">Importe *</label>
                                <input type="number" id="importe" name="importe" min="0.01" step="0.01" value="<?= e(number_format($balance, 2, '.', '')) ?>" required>
                            </div>
                            <div class="field">
                                <label for="metodo">Método *</label>
                                <select id="metodo" name="metodo" required>
                                    <?php foreach ($methods as $method): ?>
                                        <option value="<?= (int) $method['ID_Metodo'] ?>"><?= e($method['Metodo']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="field">
                                <label for="moneda">Moneda *</label>
                                <select id="moneda" name="moneda" required>
                                    <?php foreach ($currencyList as $currency): ?>
                                        <option value="<?= (int) $currency['ID_Moneda'] ?>" <?= $currency['Codigo'] === 'ARS' ? 'selected' : '' ?>><?= e($currency['Codigo'] . ' · ' . $currency['Nombre']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="field">
                                <label for="fecha_pago">Fecha y hora</label>
                                <input type="datetime-local" id="fecha_pago" name="fecha_pago" value="<?= e(date('Y-m-d\TH:i')) ?>">
                            </div>
                            <div class="field">
                                <label for="operacion">Nro. de operación</label>
                                <input type="text" id="operacion" name="operacion" maxlength="80">
                            </div>
                            <div class="field">
                                <label for="estado_pago">Estado</label>
                                <select id="estado_pago" name="estado">
                                    <option value="aprobado">Aprobado</option>
                                    <option value="pendiente">Pendiente</option>
                                    <option value="rechazado">Rechazado</option>
                                </select>
                            </div>
                        </div>
                        <div class="form-actions">
                            <button class="btn btn-primary" type="submit">Registrar pago</button>
                        </div>
                    </form>
                </div>
            <?php endif; ?>
        </div>

        <div class="card">
            <div class="card-header"><h2>Historial de estados</h2></div>
            <div class="card-body">
                <?php if ($history === []): ?>
                    <p class="muted small mb-0">Sin cambios registrados.</p>
                <?php endif; ?>
                <?php foreach ($history as $entry): ?>
                    <div style="padding:.5rem 0;border-bottom:1px dashed var(--line);">
                        <strong class="small"><?= e($entry['Estado']) ?></strong>
                        <span class="small muted">· <?= e(fecha($entry['Fecha'], true)) ?></span>
                        <div class="small muted"><?= e($entry['Observacion'] ?? '') ?> <?= $entry['EmpleadoNombre'] ? '· ' . e($entry['EmpleadoNombre'] . ' ' . $entry['EmpleadoApellido']) : '' ?></div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <div class="stack">
        <div class="card">
            <div class="card-header"><h2>Acciones</h2></div>
            <div class="card-body">
                <?php if (can('gestionar_reservas') && !$isCancelled): ?>
                    <form method="post" action="<?= e(base_url('admin/reserva_ver.php?id=' . $id)) ?>" data-confirm="¿Cambiar el estado de la reserva?">
                        <?= csrf_field() ?>
                        <input type="hidden" name="action" value="update_state">
                        <div class="field">
                            <label for="estado">Estado</label>
                            <select id="estado" name="estado">
                                <?php foreach ($allStates as $state): ?>
                                    <?php if ($state['Nombre'] === 'Cancelada') continue; ?>
                                    <option value="<?= (int) $state['ID_EstadoReserva'] ?>" <?= (int) $state['ID_EstadoReserva'] === (int) $reservation['FK_ID_EstadoReserva'] ? 'selected' : '' ?>><?= e($state['Nombre']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <button class="btn btn-primary btn-sm btn-block" type="submit">Actualizar estado</button>
                    </form>
                <?php endif; ?>
                <?php if (can('gestionar_reservas') && !$isCancelled): ?>
                    <details style="margin-top:.8rem;">
                        <summary class="small" style="cursor:pointer;color:#b42318;">Cancelar reserva</summary>
                        <form method="post" action="<?= e(base_url('admin/reserva_ver.php?id=' . $id)) ?>" data-confirm="¿Cancelar la reserva? Se restituirán los cupos." style="margin-top:.6rem;">
                            <?= csrf_field() ?>
                            <input type="hidden" name="action" value="cancel">
                            <div class="field">
                                <label for="motivo">Motivo *</label>
                                <select id="motivo" name="motivo" required>
                                    <option value="">Seleccionar</option>
                                    <?php foreach ($motives as $motive): ?>
                                        <option value="<?= (int) $motive['ID_MotivoCancelacion'] ?>"><?= e($motive['Nombre']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="field"><label for="importe_devuelto">Importe devuelto</label><input type="number" id="importe_devuelto" name="importe_devuelto" min="0" step="0.01" value="0"></div>
                            <div class="field"><label for="observaciones_cancelacion">Observaciones</label><textarea id="observaciones_cancelacion" name="observaciones_cancelacion" rows="2"></textarea></div>
                            <button class="btn btn-danger btn-sm btn-block" type="submit">Confirmar cancelación</button>
                        </form>
                    </details>
                <?php endif; ?>
            </div>
        </div>

        <div class="card">
            <div class="card-header"><h2>Cliente</h2></div>
            <div class="card-body">
                <dl class="summary-list">
                    <div><dt>Nombre</dt><dd><?= e($clientName) ?></dd></div>
                    <div><dt>Email</dt><dd><?= e($reservation['Email'] ?? '—') ?></dd></div>
                    <div><dt>DNI</dt><dd><?= e($reservation['DNI'] ?? '—') ?></dd></div>
                    <div><dt>Teléfono</dt><dd><?= e($reservation['Telefono'] ?? '—') ?></dd></div>
                    <div><dt>Agente</dt><dd><?= e(trim(($reservation['EmpleadoNombre'] ?? '') . ' ' . ($reservation['EmpleadoApellido'] ?? '')) ?: '—') ?></dd></div>
                </dl>
            </div>
        </div>

        <div class="card">
            <div class="card-header"><h2>Observaciones</h2></div>
            <div class="card-body">
                <form method="post" action="<?= e(base_url('admin/reserva_ver.php?id=' . $id)) ?>">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="observation">
                    <div class="field">
                        <textarea name="observaciones" rows="4" <?= can('gestionar_reservas') ? '' : 'readonly' ?>><?= e($reservation['Observaciones'] ?? '') ?></textarea>
                    </div>
                    <?php if (can('gestionar_reservas')): ?>
                        <button class="btn btn-primary btn-sm" type="submit">Guardar</button>
                    <?php endif; ?>
                </form>
            </div>
        </div>
    </div>
</div>
<?php require __DIR__ . '/../includes/admin_footer.php'; ?>
