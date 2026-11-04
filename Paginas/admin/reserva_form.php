<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/data.php';
require_permission('gestionar_reservas');

$clients = db_all(client_select_sql() . " WHERE c.Estado = 'activo' ORDER BY p.Apellido ASC, p.Nombre ASC");
$packagePrices = [];
foreach (all_packages() as $pkg) {
    $packagePrices[(int) $pkg['ID_Paquete']] = package_current_price((int) $pkg['ID_Paquete']);
}
$departures = db_all(
    "SELECT s.*, p.Nombre AS Paquete, p.Codigo AS PaqueteCodigo
     FROM salidas s
     INNER JOIN paquetes p ON p.ID_Paquete = s.FK_ID_Paquete
     WHERE s.FechaSalida >= CURDATE() AND s.Estado = 'abierta' AND s.CuposDisponibles > 0
       AND p.Estado IN ('publicado', 'pausado')
     ORDER BY p.Nombre ASC, s.FechaSalida ASC"
);
$preselectedClient = get_int('cliente');
$initialClient = $preselectedClient > 0 ? get_client($preselectedClient) : null;
$statuses = db_all("SELECT * FROM estados_reserva WHERE Nombre IN ('Pendiente', 'Confirmada') ORDER BY ID_EstadoReserva");
$errors = [];
$postValues = [
    'cliente' => $preselectedClient,
    'estado' => reservation_status_id('Pendiente'),
    'observaciones' => '',
    'salida' => [],
    'cantidad' => [],
    'precio' => [],
    'pasajero_nombre' => [$initialClient['Nombre'] ?? ''],
    'pasajero_apellido' => [$initialClient['Apellido'] ?? ''],
    'pasajero_dni' => [$initialClient['DNI'] ?? ''],
    'pasajero_telefono' => [$initialClient['Telefono'] ?? ''],
    'pasajero_nacionalidad' => [$initialClient['Nacionalidad'] ?? ''],
];

if (is_post()) {
    verify_csrf();
    $postValues = [
        'cliente' => post_int('cliente'),
        'estado' => post_int('estado'),
        'observaciones' => post_string('observaciones'),
        'salida' => array_values($_POST['salida'] ?? []),
        'cantidad' => array_values($_POST['cantidad'] ?? []),
        'precio' => array_values($_POST['precio'] ?? []),
        'pasajero_nombre' => array_values($_POST['pasajero_nombre'] ?? []),
        'pasajero_apellido' => array_values($_POST['pasajero_apellido'] ?? []),
        'pasajero_dni' => array_values($_POST['pasajero_dni'] ?? []),
        'pasajero_telefono' => array_values($_POST['pasajero_telefono'] ?? []),
        'pasajero_nacionalidad' => array_values($_POST['pasajero_nacionalidad'] ?? []),
    ];

    $client = get_client((int) $postValues['cliente']);
    if ($client === null) {
        $errors[] = 'Seleccioná un cliente válido.';
    }
    $validStatus = array_map(static fn(array $row): int => (int) $row['ID_EstadoReserva'], $statuses);
    if (!in_array((int) $postValues['estado'], $validStatus, true)) {
        $postValues['estado'] = reservation_status_id('Pendiente');
    }

    $lines = [];
    $totalQuantity = 0;
    $totalAmount = 0.0;
    foreach ($postValues['salida'] as $index => $salidaId) {
        $salidaId = (int) $salidaId;
        $quantity = (int) ($postValues['cantidad'][$index] ?? 0);
        $price = (float) str_replace(',', '.', (string) ($postValues['precio'][$index] ?? 0));
        if ($salidaId <= 0 || $quantity <= 0) {
            continue;
        }
        if ($price < 0) {
            $errors[] = 'Los precios no pueden ser negativos.';
        }
        $departure = db_one(
            'SELECT s.*, p.Nombre AS Paquete, p.Codigo AS PaqueteCodigo FROM salidas s
             INNER JOIN paquetes p ON p.ID_Paquete = s.FK_ID_Paquete WHERE s.ID_Salida = :id',
            [':id' => $salidaId]
        );
        if ($departure === null) {
            $errors[] = 'Una de las salidas seleccionadas no existe.';
            continue;
        }
        if ((int) $departure['CuposDisponibles'] < $quantity) {
            $errors[] = 'No hay cupos suficientes para ' . $departure['Paquete'] . '.';
        }
        $subtotal = round($quantity * $price, 2);
        $lines[] = [
            'salida_id' => $salidaId,
            'description' => $departure['Paquete'] . ' · Salida ' . fecha($departure['FechaSalida']),
            'quantity' => $quantity,
            'price' => $price,
            'subtotal' => $subtotal,
        ];
        $totalQuantity += $quantity;
        $totalAmount += $subtotal;
    }
    if ($lines === []) {
        $errors[] = 'Agregá al menos un paquete o salida a la reserva.';
    }

    $passengers = [];
    foreach ($postValues['pasajero_nombre'] as $index => $nombre) {
        $nombre = trim((string) $nombre);
        $apellido = trim((string) ($postValues['pasajero_apellido'][$index] ?? ''));
        $dni = trim((string) ($postValues['pasajero_dni'][$index] ?? ''));
        $telefono = trim((string) ($postValues['pasajero_telefono'][$index] ?? ''));
        $nacionalidad = trim((string) ($postValues['pasajero_nacionalidad'][$index] ?? ''));
        if ($nombre === '' && $apellido === '' && $dni === '') {
            continue;
        }
        if ($nombre === '' || $apellido === '' || $dni === '') {
            $errors[] = 'Completá nombre, apellido y DNI de todos los pasajeros.';
            continue;
        }
        $passengers[] = compact('nombre', 'apellido', 'dni', 'telefono', 'nacionalidad');
    }
    if ($passengers === []) {
        $errors[] = 'Cargá al menos un pasajero.';
    } elseif (count($passengers) !== $totalQuantity) {
        $errors[] = 'La cantidad de pasajeros (' . count($passengers) . ') debe coincidir con los cupos reservados (' . $totalQuantity . ').';
    }
    $seenDni = [];
    foreach ($passengers as $passenger) {
        $key = mb_strtoupper($passenger['dni']);
        if (isset($seenDni[$key])) {
            $errors[] = 'Hay pasajeros repetidos con el DNI ' . $passenger['dni'] . '.';
        }
        $seenDni[$key] = true;
    }

    if ($errors === [] && $client !== null) {
        try {
            $pdo = db();
            $pdo->beginTransaction();
            $reservationCode = generate_code('reservas', 'CodigoReserva', 'RES');
            db_exec(
                'INSERT INTO reservas (CodigoReserva, FK_ID_Cliente, FK_ID_Empleado, FK_ID_EstadoReserva, Total, Observaciones)
                 VALUES (:codigo, :cliente, :empleado, :estado, :total, :observaciones)',
                [
                    ':codigo' => $reservationCode,
                    ':cliente' => (int) $postValues['cliente'],
                    ':empleado' => current_employee_id(),
                    ':estado' => (int) $postValues['estado'],
                    ':total' => round($totalAmount, 2),
                    ':observaciones' => $postValues['observaciones'] !== '' ? $postValues['observaciones'] : null,
                ]
            );
            $reservationId = (int) $pdo->lastInsertId();

            foreach ($lines as $line) {
                $locked = db_one('SELECT CuposDisponibles FROM salidas WHERE ID_Salida = :id FOR UPDATE', [':id' => $line['salida_id']]);
                if ($locked === null || (int) $locked['CuposDisponibles'] < $line['quantity']) {
                    throw new RuntimeException('Los cupos de la salida cambiaron durante la operación. Volvé a intentar.');
                }
                db_exec(
                    'INSERT INTO reserva_detalle (FK_ID_Reserva, FK_ID_Salida, Descripcion, Cantidad, PrecioUnitario, Subtotal)
                     VALUES (:reserva, :salida, :descripcion, :cantidad, :precio, :subtotal)',
                    [
                        ':reserva' => $reservationId,
                        ':salida' => $line['salida_id'],
                        ':descripcion' => $line['description'],
                        ':cantidad' => $line['quantity'],
                        ':precio' => $line['price'],
                        ':subtotal' => $line['subtotal'],
                    ]
                );
                db_exec(
                    'UPDATE salidas SET CuposDisponibles = CuposDisponibles - :cantidad,
                            Estado = IF(CuposDisponibles - :cantidad2 <= 0, "completa", Estado)
                     WHERE ID_Salida = :id',
                    [':cantidad' => $line['quantity'], ':cantidad2' => $line['quantity'], ':id' => $line['salida_id']]
                );
            }

            foreach ($passengers as $index => $passenger) {
                $existing = db_one('SELECT ID_Pasajero FROM pasajero WHERE DNI = :dni', [':dni' => $passenger['dni']]);
                if ($existing !== null) {
                    $passengerId = (int) $existing['ID_Pasajero'];
                } else {
                    db_exec(
                        'INSERT INTO pasajero (FK_ID_PasajeroOnline, DNI, Nombre, Apellido, Telefono, Nacionalidad)
                         VALUES (:user, :dni, :nombre, :apellido, :telefono, :nacionalidad)',
                        [
                            ':user' => $client['FK_ID_PasajeroOnline'],
                            ':dni' => $passenger['dni'],
                            ':nombre' => $passenger['nombre'],
                            ':apellido' => $passenger['apellido'],
                            ':telefono' => $passenger['telefono'] !== '' ? $passenger['telefono'] : null,
                            ':nacionalidad' => $passenger['nacionalidad'] !== '' ? $passenger['nacionalidad'] : null,
                        ]
                    );
                    $passengerId = (int) $pdo->lastInsertId();
                }
                db_exec(
                    'INSERT INTO reserva_pasajero (FK_ID_Reserva, FK_ID_Pasajero, Titular) VALUES (:reserva, :pasajero, :titular)',
                    [':reserva' => $reservationId, ':pasajero' => $passengerId, ':titular' => $index === 0 ? 1 : 0]
                );
            }

            db_exec(
                "INSERT INTO historial_estados (FK_ID_Reserva, FK_ID_EstadoReserva, FK_ID_Empleado, Fecha, Observacion)
                 VALUES (:reserva, :estado, :empleado, NOW(), 'Alta de reserva')",
                [
                    ':reserva' => $reservationId,
                    ':estado' => (int) $postValues['estado'],
                    ':empleado' => current_employee_id(),
                ]
            );

            $pdo->commit();
            sync_cuenta_corriente((int) $postValues['cliente']);
            audit('reservas', 'INSERT', (string) $reservationId, 'Alta de reserva ' . $reservationCode . ' por ' . money($totalAmount));
            redirect_with('admin/reserva_ver.php?id=' . $reservationId, 'success', 'Reserva ' . $reservationCode . ' creada correctamente.');
        } catch (Throwable $e) {
            if (db()->inTransaction()) {
                db()->rollBack();
            }
            error_log('[AuraViajes] Error reserva_form: ' . $e->getMessage());
            $errors[] = $e instanceof RuntimeException ? $e->getMessage() : 'No se pudo crear la reserva. Intentá nuevamente.';
        }
    }
}
$clientOptions = [];
foreach ($clients as $client) {
    $clientOptions[(int) $client['ID_Cliente']] = $client;
}

$pageTitle = 'Nueva reserva';
$activeSection = 'reservas';
$breadcrumbs = ['Panel' => 'admin/index.php', 'Reservas' => 'admin/reservas.php', 'Nueva reserva' => ''];
require __DIR__ . '/../includes/admin_header.php';
?>
<?php if ($departures === []): ?>
    <div class="alert alert-warning">No hay salidas abiertas con cupos disponibles. Creá una salida desde el módulo de paquetes antes de registrar reservas.</div>
<?php endif; ?>
<?php foreach ($errors as $error): ?>
    <div class="alert alert-error"><?= e($error) ?></div>
<?php endforeach; ?>

<form method="post" action="<?= e(base_url('admin/reserva_form.php')) ?>" data-confirm="¿Crear la reserva? Se descontarán los cupos seleccionados.">
    <?= csrf_field() ?>
    <div class="detail-grid">
        <div class="stack">
            <div class="card">
                <div class="card-header"><h2>Cliente y datos generales</h2></div>
                <div class="card-body">
                    <div class="form-grid">
                        <div class="field">
                            <label for="cliente">Cliente *</label>
                            <select id="cliente" name="cliente" required>
                                <option value="">Seleccionar cliente</option>
                                <?php foreach ($clients as $client): ?>
                                    <?php $cName = client_full_name($client); ?>
                                    <option value="<?= (int) $client['ID_Cliente'] ?>"
                                            data-name="<?= e($client['Nombre'] ?? '') ?>"
                                            data-lastname="<?= e($client['Apellido'] ?? '') ?>"
                                            data-dni="<?= e($client['DNI'] ?? '') ?>"
                                            data-phone="<?= e($client['Telefono'] ?? '') ?>"
                                            data-nationality="<?= e($client['Nacionalidad'] ?? '') ?>"
                                            <?= (int) $postValues['cliente'] === (int) $client['ID_Cliente'] ? 'selected' : '' ?>>
                                        <?= e($cName . ' · ' . $client['CodigoCliente'] . ' · ' . ($client['DNI'] ?? 'sin DNI')) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <small><a href="<?= e(base_url('admin/cliente_form.php')) ?>">Crear cliente nuevo</a></small>
                        </div>
                        <div class="field">
                            <label for="estado">Estado inicial</label>
                            <select id="estado" name="estado">
                                <?php foreach ($statuses as $status): ?>
                                    <option value="<?= (int) $status['ID_EstadoReserva'] ?>" <?= (int) $postValues['estado'] === (int) $status['ID_EstadoReserva'] ? 'selected' : '' ?>><?= e($status['Nombre']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="field span-2">
                            <label for="observaciones">Observaciones</label>
                            <textarea id="observaciones" name="observaciones" rows="2"><?= e((string) $postValues['observaciones']) ?></textarea>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h2>Paquetes y cupos</h2>
                    <button class="btn btn-ghost btn-sm" type="button" data-add-line>Agregar línea</button>
                </div>
                <div class="table-wrap">
                    <table class="data-table" id="lines-table">
                        <thead><tr><th>Salida</th><th style="width:110px;">Cupos</th><th style="width:150px;">Precio unitario</th><th style="width:130px;">Subtotal</th><th style="width:50px;"></th></tr></thead>
                        <tbody>
                        <?php
                        $lineSalidas = $postValues['salida'] !== [] ? $postValues['salida'] : [''];
                        $lineCantidades = $postValues['cantidad'] !== [] ? $postValues['cantidad'] : [1];
                        $linePrecios = $postValues['precio'] !== [] ? $postValues['precio'] : [''];
                        $lineCount = max(count($lineSalidas), count($lineCantidades), count($linePrecios));
                        for ($i = 0; $i < $lineCount; $i++):
                            $selectedSalida = (int) ($lineSalidas[$i] ?? 0);
                            ?>
                            <tr class="line-row">
                                <td>
                                    <select name="salida[]" class="line-departure" required>
                                        <option value="">Seleccionar salida</option>
                                        <?php
                                        $currentPackage = '';
                                        foreach ($departures as $departure):
                                            $packageLabel = $departure['Paquete'] . ' (' . $departure['PaqueteCodigo'] . ')';
                                            if ($packageLabel !== $currentPackage):
                                                if ($currentPackage !== '') echo '</optgroup>';
                                                echo '<optgroup label="' . e($packageLabel) . '">';
                                                $currentPackage = $packageLabel;
                                            endif;
                                            $price = $packagePrices[(int) $departure['FK_ID_Paquete']]['final'] ?? 0;
                                            ?>
                                            <option value="<?= (int) $departure['ID_Salida'] ?>"
                                                    data-price="<?= e(number_format((float) $price, 2, '.', '')) ?>"
                                                    <?= $selectedSalida === (int) $departure['ID_Salida'] ? 'selected' : '' ?>>
                                                <?= e(fecha($departure['FechaSalida']) . ' al ' . fecha($departure['FechaRegreso']) . ' · ' . $departure['CuposDisponibles'] . ' cupos · ' . money($price)) ?>
                                            </option>
                                        <?php endforeach; ?>
                                        <?php if ($currentPackage !== '') echo '</optgroup>'; ?>
                                    </select>
                                </td>
                                <td><input type="number" name="cantidad[]" class="line-quantity" min="1" value="<?= (int) ($lineCantidades[$i] ?? 1) ?>" required></td>
                                <td><input type="number" name="precio[]" class="line-price" min="0" step="0.01" value="<?= e((string) ($linePrecios[$i] ?? '')) ?>" required></td>
                                <td class="line-subtotal">$ 0,00</td>
                                <td><button class="icon-btn danger" type="button" data-remove-line title="Quitar">&times;</button></td>
                            </tr>
                        <?php endfor; ?>
                        </tbody>
                        <tfoot>
                        <tr><td colspan="3" class="num"><strong>Total</strong></td><td class="num"><strong id="reservation-total">$ 0,00</strong></td><td></td></tr>
                        </tfoot>
                    </table>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h2>Pasajeros</h2>
                    <button class="btn btn-ghost btn-sm" type="button" data-add-passenger>Agregar pasajero</button>
                </div>
                <div class="table-wrap">
                    <table class="data-table" id="passengers-table">
                        <thead><tr><th>Nombre</th><th>Apellido</th><th>DNI</th><th>Teléfono</th><th>Nacionalidad</th><th></th></tr></thead>
                        <tbody>
                        <?php
                        $passengerCount = max(1, count($postValues['pasajero_nombre']));
                        for ($i = 0; $i < $passengerCount; $i++):
                            ?>
                            <tr class="passenger-row">
                                <td><input type="text" name="pasajero_nombre[]" value="<?= e((string) ($postValues['pasajero_nombre'][$i] ?? '')) ?>" maxlength="60"></td>
                                <td><input type="text" name="pasajero_apellido[]" value="<?= e((string) ($postValues['pasajero_apellido'][$i] ?? '')) ?>" maxlength="60"></td>
                                <td><input type="text" name="pasajero_dni[]" value="<?= e((string) ($postValues['pasajero_dni'][$i] ?? '')) ?>" maxlength="20"></td>
                                <td><input type="text" name="pasajero_telefono[]" value="<?= e((string) ($postValues['pasajero_telefono'][$i] ?? '')) ?>" maxlength="20"></td>
                                <td><input type="text" name="pasajero_nacionalidad[]" value="<?= e((string) ($postValues['pasajero_nacionalidad'][$i] ?? '')) ?>" maxlength="60"></td>
                                <td><button class="icon-btn danger" type="button" data-remove-passenger title="Quitar">&times;</button></td>
                            </tr>
                        <?php endfor; ?>
                        </tbody>
                    </table>
                </div>
                <div class="card-body" style="border-top:1px solid var(--line);">
                    <p class="small muted mb-0">La cantidad de pasajeros debe coincidir con la cantidad total de cupos reservados. El primer pasajero se registra como titular.</p>
                </div>
            </div>
        </div>

        <div class="stack">
            <div class="card">
                <div class="card-header"><h2>Resumen</h2></div>
                <div class="card-body">
                    <dl class="summary-list">
                        <div><dt>Cupos</dt><dd id="summary-quantity">0</dd></div>
                        <div><dt>Total</dt><dd id="summary-total">$ 0,00</dd></div>
                        <div><dt>Estado</dt><dd>Se puede ajustar luego</dd></div>
                    </dl>
                    <button class="btn btn-primary btn-block" type="submit" <?= $departures === [] ? 'disabled' : '' ?>>Crear reserva</button>
                    <a class="btn btn-ghost btn-block" href="<?= e(base_url('admin/reservas.php')) ?>" style="margin-top:.5rem;">Cancelar</a>
                </div>
            </div>
            <div class="card">
                <div class="card-header"><h2>Buenas prácticas</h2></div>
                <div class="card-body">
                    <ul class="small muted" style="padding-left:1.1rem;margin:0;">
                        <li>Verificá los cupos antes de confirmar.</li>
                        <li>Cargá los pasajeros con los datos del documento.</li>
                        <li>Registrá el pago desde la ficha de la reserva.</li>
                        <li>Los cupos se descuentan automáticamente.</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</form>

<template id="line-template">
    <tr class="line-row">
        <td>
            <select name="salida[]" class="line-departure" required>
                <option value="">Seleccionar salida</option>
                <?php
                $currentPackage = '';
                foreach ($departures as $departure):
                    $packageLabel = $departure['Paquete'] . ' (' . $departure['PaqueteCodigo'] . ')';
                    if ($packageLabel !== $currentPackage):
                        if ($currentPackage !== '') echo '</optgroup>';
                        echo '<optgroup label="' . e($packageLabel) . '">';
                        $currentPackage = $packageLabel;
                    endif;
                    $price = $packagePrices[(int) $departure['FK_ID_Paquete']]['final'] ?? 0;
                    ?>
                    <option value="<?= (int) $departure['ID_Salida'] ?>" data-price="<?= e(number_format((float) $price, 2, '.', '')) ?>"><?= e(fecha($departure['FechaSalida']) . ' al ' . fecha($departure['FechaRegreso']) . ' · ' . $departure['CuposDisponibles'] . ' cupos · ' . money($price)) ?></option>
                <?php endforeach; ?>
                <?php if ($currentPackage !== '') echo '</optgroup>'; ?>
            </select>
        </td>
        <td><input type="number" name="cantidad[]" class="line-quantity" min="1" value="1" required></td>
        <td><input type="number" name="precio[]" class="line-price" min="0" step="0.01" required></td>
        <td class="line-subtotal">$ 0,00</td>
        <td><button class="icon-btn danger" type="button" data-remove-line title="Quitar">&times;</button></td>
    </tr>
</template>
<template id="passenger-template">
    <tr class="passenger-row">
        <td><input type="text" name="pasajero_nombre[]" maxlength="60"></td>
        <td><input type="text" name="pasajero_apellido[]" maxlength="60"></td>
        <td><input type="text" name="pasajero_dni[]" maxlength="20"></td>
        <td><input type="text" name="pasajero_telefono[]" maxlength="20"></td>
        <td><input type="text" name="pasajero_nacionalidad[]" maxlength="60"></td>
        <td><button class="icon-btn danger" type="button" data-remove-passenger title="Quitar">&times;</button></td>
    </tr>
</template>
<script>
(() => {
    const money = (value) => new Intl.NumberFormat('es-AR', { style: 'currency', currency: 'ARS' }).format(value || 0);
    const linesBody = document.querySelector('#lines-table tbody');
    const passengersBody = document.querySelector('#passengers-table tbody');
    const totalEl = document.querySelector('#reservation-total');
    const summaryTotal = document.querySelector('#summary-total');
    const summaryQuantity = document.querySelector('#summary-quantity');

    const recalc = () => {
        let total = 0;
        let quantity = 0;
        linesBody.querySelectorAll('.line-row').forEach((row) => {
            const qty = Math.max(0, parseInt(row.querySelector('.line-quantity').value, 10) || 0);
            const price = Math.max(0, parseFloat(row.querySelector('.line-price').value) || 0);
            const subtotal = qty * price;
            total += subtotal;
            quantity += qty;
            row.querySelector('.line-subtotal').textContent = money(subtotal);
        });
        totalEl.textContent = money(total);
        summaryTotal.textContent = money(total);
        summaryQuantity.textContent = String(quantity);
    };

    const bindLine = (row) => {
        row.querySelector('.line-departure').addEventListener('change', (event) => {
            const selected = event.target.selectedOptions[0];
            if (selected && selected.dataset.price) {
                row.querySelector('.line-price').value = selected.dataset.price;
            }
            recalc();
        });
        row.querySelector('.line-quantity').addEventListener('input', recalc);
        row.querySelector('.line-price').addEventListener('input', recalc);
        row.querySelector('[data-remove-line]')?.addEventListener('click', () => {
            if (linesBody.querySelectorAll('.line-row').length > 1) {
                row.remove();
                recalc();
            }
        });
    };
    linesBody.querySelectorAll('.line-row').forEach(bindLine);
    document.querySelector('[data-add-line]')?.addEventListener('click', () => {
        const fragment = document.querySelector('#line-template').content.cloneNode(true);
        linesBody.appendChild(fragment);
        bindLine(linesBody.lastElementChild);
        recalc();
    });

    const bindPassenger = (row) => {
        row.querySelector('[data-remove-passenger]')?.addEventListener('click', () => {
            if (passengersBody.querySelectorAll('.passenger-row').length > 1) {
                row.remove();
            }
        });
    };
    passengersBody.querySelectorAll('.passenger-row').forEach(bindPassenger);
    document.querySelector('[data-add-passenger]')?.addEventListener('click', () => {
        const fragment = document.querySelector('#passenger-template').content.cloneNode(true);
        passengersBody.appendChild(fragment);
        bindPassenger(passengersBody.lastElementChild);
    });

    document.querySelector('#cliente')?.addEventListener('change', (event) => {
        const option = event.target.selectedOptions[0];
        const row = passengersBody.querySelector('.passenger-row');
        if (!option || !row) return;
        const fields = {
            nombre: row.querySelector('[name="pasajero_nombre[]"]'),
            apellido: row.querySelector('[name="pasajero_apellido[]"]'),
            dni: row.querySelector('[name="pasajero_dni[]"]'),
            telefono: row.querySelector('[name="pasajero_telefono[]"]'),
            nacionalidad: row.querySelector('[name="pasajero_nacionalidad[]"]'),
        };
        if (Object.values(fields).every((field) => !field.value.trim())) {
            fields.nombre.value = option.dataset.name || '';
            fields.apellido.value = option.dataset.lastname || '';
            fields.dni.value = option.dataset.dni || '';
            fields.telefono.value = option.dataset.phone || '';
            fields.nacionalidad.value = option.dataset.nationality || '';
        }
    });

    recalc();
})();
</script>
<?php require __DIR__ . '/../includes/admin_footer.php'; ?>
