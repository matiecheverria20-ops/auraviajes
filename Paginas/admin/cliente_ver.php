<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/data.php';
require_any_permission(['gestionar_clientes', 'ver_clientes']);

$id = get_int('id');
$client = get_client($id);
if ($client === null) {
    redirect_with('admin/clientes.php', 'error', 'El cliente solicitado no existe.');
}

if (is_post()) {
    verify_csrf();
    if (!can('gestionar_clientes')) {
        forbidden('No tenés permisos para modificar clientes.');
    }
    $action = post_string('action');

    if ($action === 'toggle_status') {
        $newStatus = $client['Estado'] === 'activo' ? 'inactivo' : 'activo';
        db_exec('UPDATE clientes SET Estado = :estado WHERE ID_Cliente = :id', [':estado' => $newStatus, ':id' => $id]);
        audit('clientes', 'UPDATE', (string) $id, 'Cambio de estado a ' . $newStatus);
        redirect_with('admin/cliente_ver.php?id=' . $id, 'success', 'Estado del cliente actualizado a ' . $newStatus . '.');
    }

    if ($action === 'observation') {
        db_exec('UPDATE clientes SET Observaciones = :obs WHERE ID_Cliente = :id', [
            ':obs' => post_string('Observaciones') !== '' ? post_string('Observaciones') : null,
            ':id' => $id,
        ]);
        audit('clientes', 'UPDATE', (string) $id, 'Actualización de observaciones');
        redirect_with('admin/cliente_ver.php?id=' . $id, 'success', 'Observaciones guardadas.');
    }

    if ($action === 'add_document') {
        $typeId = post_int('tipo');
        $number = post_string('numero');
        if ($typeId <= 0 || $number === '') {
            redirect_with('admin/cliente_ver.php?id=' . $id, 'error', 'Seleccioná el tipo de documento e ingresá el número.');
        }
        db_exec(
            'INSERT INTO documentos_cliente (FK_ID_Cliente, FK_ID_TipoDocumento, Numero, FechaEmision, FechaVencimiento, PaisEmision)
             VALUES (:cliente, :tipo, :numero, :emision, :vencimiento, :pais)',
            [
                ':cliente' => $id,
                ':tipo' => $typeId,
                ':numero' => $number,
                ':emision' => post_string('emision') !== '' ? post_string('emision') : null,
                ':vencimiento' => post_string('vencimiento') !== '' ? post_string('vencimiento') : null,
                ':pais' => post_string('pais') !== '' ? post_string('pais') : null,
            ]
        );
        audit('documentos_cliente', 'INSERT', (string) db()->lastInsertId(), 'Alta de documento para cliente ' . $id);
        redirect_with('admin/cliente_ver.php?id=' . $id, 'success', 'Documento agregado.');
    }

    if ($action === 'delete_document') {
        $docId = post_int('document_id');
        $doc = db_one('SELECT * FROM documentos_cliente WHERE ID_DocumentoCliente = :id AND FK_ID_Cliente = :client', [':id' => $docId, ':client' => $id]);
        if ($doc !== null) {
            db_exec('DELETE FROM documentos_cliente WHERE ID_DocumentoCliente = :id', [':id' => $docId]);
            audit('documentos_cliente', 'DELETE', (string) $docId, 'Baja de documento del cliente ' . $id);
            redirect_with('admin/cliente_ver.php?id=' . $id, 'success', 'Documento eliminado.');
        }
        redirect_with('admin/cliente_ver.php?id=' . $id, 'error', 'El documento no existe.');
    }

    if ($action === 'add_address') {
        $type = post_string('tipo', 'domicilio');
        if (!in_array($type, ['domicilio', 'trabajo', 'otro'], true)) {
            $type = 'domicilio';
        }
        $street = post_string('calle');
        $city = post_string('localidad');
        if ($street === '' && $city === '') {
            redirect_with('admin/cliente_ver.php?id=' . $id, 'error', 'Ingresá al menos la calle o la localidad de la dirección.');
        }
        $pdo = db();
        $pdo->beginTransaction();
        try {
            db_exec(
                'INSERT INTO direcciones (Calle, Numero, Localidad, Provincia, Pais, CodigoPostal)
                 VALUES (:calle, :numero, :localidad, :provincia, :pais, :cp)',
                [
                    ':calle' => $street !== '' ? $street : null,
                    ':numero' => post_string('numero') !== '' ? post_string('numero') : null,
                    ':localidad' => $city !== '' ? $city : null,
                    ':provincia' => post_string('provincia') !== '' ? post_string('provincia') : null,
                    ':pais' => post_string('pais') !== '' ? post_string('pais') : 'Argentina',
                    ':cp' => post_string('codigo_postal') !== '' ? post_string('codigo_postal') : null,
                ]
            );
            $addressId = (int) $pdo->lastInsertId();
            $principal = post_int('principal') === 1;
            if ($principal) {
                db_exec('UPDATE cliente_direccion SET Principal = 0 WHERE FK_ID_Cliente = :client', [':client' => $id]);
            }
            db_exec(
                'INSERT INTO cliente_direccion (FK_ID_Cliente, FK_ID_Direccion, Tipo, Principal)
                 VALUES (:client, :address, :tipo, :principal)',
                [':client' => $id, ':address' => $addressId, ':tipo' => $type, ':principal' => $principal ? 1 : 0]
            );
            $pdo->commit();
            audit('cliente_direccion', 'INSERT', (string) $addressId, 'Alta de dirección del cliente ' . $id);
            redirect_with('admin/cliente_ver.php?id=' . $id, 'success', 'Dirección agregada.');
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            error_log('[AuraViajes] Error al agregar dirección: ' . $e->getMessage());
            redirect_with('admin/cliente_ver.php?id=' . $id, 'error', 'No se pudo guardar la dirección.');
        }
    }

    if ($action === 'delete_address') {
        $relationId = post_int('address_relation_id');
        $relation = db_one(
            'SELECT cd.*, d.ID_Direccion FROM cliente_direccion cd
             INNER JOIN direcciones d ON d.ID_Direccion = cd.FK_ID_Direccion
             WHERE cd.ID_ClienteDireccion = :id AND cd.FK_ID_Cliente = :client',
            [':id' => $relationId, ':client' => $id]
        );
        if ($relation !== null) {
            db_exec('DELETE FROM cliente_direccion WHERE ID_ClienteDireccion = :id', [':id' => $relationId]);
            $remaining = (int) db_val('SELECT COUNT(*) FROM cliente_direccion WHERE FK_ID_Direccion = :address', [':address' => $relation['ID_Direccion']], 0);
            if ($remaining === 0) {
                db_exec('DELETE FROM direcciones WHERE ID_Direccion = :id', [':id' => $relation['ID_Direccion']]);
            }
            if ((int) $relation['Principal'] === 1) {
                db_exec(
                    'UPDATE cliente_direccion SET Principal = 1 WHERE FK_ID_Cliente = :client ORDER BY ID_ClienteDireccion ASC LIMIT 1',
                    [':client' => $id]
                );
            }
            audit('cliente_direccion', 'DELETE', (string) $relationId, 'Baja de dirección del cliente ' . $id);
            redirect_with('admin/cliente_ver.php?id=' . $id, 'success', 'Dirección eliminada.');
        }
        redirect_with('admin/cliente_ver.php?id=' . $id, 'error', 'La dirección no existe.');
    }

    if ($action === 'add_emergency') {
        $name = post_string('nombre');
        $phone = post_string('telefono');
        if ($name === '' || $phone === '') {
            redirect_with('admin/cliente_ver.php?id=' . $id, 'error', 'Ingresá nombre y teléfono del contacto de emergencia.');
        }
        db_exec(
            'INSERT INTO contactos_emergencia (FK_ID_Cliente, Nombre, Telefono, Relacion) VALUES (:client, :nombre, :telefono, :relacion)',
            [
                ':client' => $id,
                ':nombre' => mb_substr($name, 0, 100),
                ':telefono' => mb_substr($phone, 0, 30),
                ':relacion' => post_string('relacion') !== '' ? mb_substr(post_string('relacion'), 0, 60) : null,
            ]
        );
        audit('contactos_emergencia', 'INSERT', (string) db()->lastInsertId(), 'Alta de contacto de emergencia del cliente ' . $id);
        redirect_with('admin/cliente_ver.php?id=' . $id, 'success', 'Contacto de emergencia agregado.');
    }

    if ($action === 'delete_emergency') {
        $contactId = post_int('emergency_id');
        $contact = db_one('SELECT * FROM contactos_emergencia WHERE ID_ContactoEmergencia = :id AND FK_ID_Cliente = :client', [':id' => $contactId, ':client' => $id]);
        if ($contact !== null) {
            db_exec('DELETE FROM contactos_emergencia WHERE ID_ContactoEmergencia = :id', [':id' => $contactId]);
            audit('contactos_emergencia', 'DELETE', (string) $contactId, 'Baja de contacto de emergencia del cliente ' . $id);
            redirect_with('admin/cliente_ver.php?id=' . $id, 'success', 'Contacto de emergencia eliminado.');
        }
        redirect_with('admin/cliente_ver.php?id=' . $id, 'error', 'El contacto no existe.');
    }

    if ($action === 'add_preference') {
        $type = post_string('tipo_preferencia');
        $value = post_string('valor_preferencia');
        if ($type === '' || $value === '') {
            redirect_with('admin/cliente_ver.php?id=' . $id, 'error', 'Ingresá el tipo y el valor de la preferencia.');
        }
        db_exec(
            'INSERT INTO preferencias_cliente (FK_ID_Cliente, Tipo, Valor) VALUES (:client, :tipo, :valor)',
            [':client' => $id, ':tipo' => mb_substr($type, 0, 60), ':valor' => mb_substr($value, 0, 150)]
        );
        audit('preferencias_cliente', 'INSERT', (string) db()->lastInsertId(), 'Alta de preferencia del cliente ' . $id);
        redirect_with('admin/cliente_ver.php?id=' . $id, 'success', 'Preferencia agregada.');
    }

    if ($action === 'delete_preference') {
        $preferenceId = post_int('preference_id');
        $preference = db_one('SELECT * FROM preferencias_cliente WHERE ID_Preferencia = :id AND FK_ID_Cliente = :client', [':id' => $preferenceId, ':client' => $id]);
        if ($preference !== null) {
            db_exec('DELETE FROM preferencias_cliente WHERE ID_Preferencia = :id', [':id' => $preferenceId]);
            audit('preferencias_cliente', 'DELETE', (string) $preferenceId, 'Baja de preferencia del cliente ' . $id);
            redirect_with('admin/cliente_ver.php?id=' . $id, 'success', 'Preferencia eliminada.');
        }
        redirect_with('admin/cliente_ver.php?id=' . $id, 'error', 'La preferencia no existe.');
    }
}

$reservations = db_all(
    "SELECT r.*, er.Nombre AS Estado,
            (SELECT COALESCE(SUM(p.Importe), 0) FROM pagos_reserva p WHERE p.FK_ID_Reserva = r.ID_Reserva AND p.Estado = 'aprobado') AS Pagado
     FROM reservas r
     INNER JOIN estados_reserva er ON er.ID_EstadoReserva = r.FK_ID_EstadoReserva
     WHERE r.FK_ID_Cliente = :id
     ORDER BY r.FechaReserva DESC",
    [':id' => $id]
);
$documents = db_all(
    'SELECT dc.*, td.Nombre AS Tipo FROM documentos_cliente dc
     INNER JOIN tipos_documento td ON td.ID_TipoDocumento = dc.FK_ID_TipoDocumento
     WHERE dc.FK_ID_Cliente = :id ORDER BY dc.FechaVencimiento IS NULL, dc.FechaVencimiento ASC',
    [':id' => $id]
);
$documentTypes = db_all('SELECT * FROM tipos_documento ORDER BY Nombre');
$addresses = db_all(
    'SELECT cd.*, d.Calle, d.Numero, d.Localidad, d.Provincia, d.Pais, d.CodigoPostal
     FROM cliente_direccion cd
     INNER JOIN direcciones d ON d.ID_Direccion = cd.FK_ID_Direccion
     WHERE cd.FK_ID_Cliente = :id
     ORDER BY cd.Principal DESC, cd.ID_ClienteDireccion DESC',
    [':id' => $id]
);
$emergencyContacts = db_all('SELECT * FROM contactos_emergencia WHERE FK_ID_Cliente = :id ORDER BY Nombre ASC', [':id' => $id]);
$preferences = db_all('SELECT * FROM preferencias_cliente WHERE FK_ID_Cliente = :id ORDER BY Tipo ASC, ID_Preferencia ASC', [':id' => $id]);
$messages = [];
if (!empty($client['Email'])) {
    $messages = db_all(
        'SELECT * FROM formularios_contacto WHERE LOWER(Email) = :email ORDER BY Fecha DESC LIMIT 8',
        [':email' => mb_strtolower((string) $client['Email'])]
    );
}
$paid = (float) $client['total_pagado'];
$balance = (float) $client['total_facturado'] - $paid;
$name = client_full_name($client);

$pageTitle = $name;
$activeSection = 'clientes';
$breadcrumbs = ['Panel' => 'admin/index.php', 'Clientes' => 'admin/clientes.php', $name => ''];
$pageActions = can('gestionar_reservas')
    ? '<a class="btn btn-accent" href="' . e(base_url('admin/reserva_form.php?cliente=' . $id)) . '">Nueva reserva</a>'
    : '';
require __DIR__ . '/../includes/admin_header.php';
?>
<div class="profile-head">
    <div class="profile-main">
        <span class="avatar avatar-lg"><?= e(initials($name)) ?></span>
        <div>
            <div class="flex gap-1" style="align-items:center;flex-wrap:wrap;">
                <h2 class="mb-0"><?= e($name) ?></h2>
                <span class="<?= e(badge_class($client['Estado'])) ?>"><?= e(estado_label($client['Estado'])) ?></span>
            </div>
            <p class="muted mb-0"><?= e($client['CodigoCliente']) ?> · Cliente desde <?= e(fecha($client['FechaAlta'])) ?></p>
        </div>
    </div>
    <div class="profile-actions">
        <?php if (can('gestionar_clientes')): ?>
            <a class="btn btn-primary" href="<?= e(base_url('admin/cliente_form.php?id=' . $id)) ?>">Editar cliente</a>
            <form method="post" action="<?= e(base_url('admin/cliente_ver.php?id=' . $id)) ?>" data-confirm="¿Cambiar el estado del cliente?">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="toggle_status">
                <button class="btn btn-ghost" type="submit"><?= $client['Estado'] === 'activo' ? 'Desactivar' : 'Activar' ?></button>
            </form>
        <?php endif; ?>
    </div>
</div>

<section class="stat-grid stat-grid-3">
    <div class="stat-card">
        <span class="stat-label">Reservas</span>
        <span class="stat-value"><?= number_format((int) $client['reservas_count'], 0, ',', '.') ?></span>
        <span class="stat-hint">Historial completo</span>
    </div>
    <div class="stat-card">
        <span class="stat-label">Facturado</span>
        <span class="stat-value"><?= e(money($client['total_facturado'])) ?></span>
        <span class="stat-hint">Reservas no canceladas</span>
    </div>
    <div class="stat-card <?= $balance > 0.01 ? 'coral' : '' ?>">
        <span class="stat-label">Saldo</span>
        <span class="stat-value"><?= e(money($balance)) ?></span>
        <span class="stat-hint">Cobrado: <?= e(money($paid)) ?></span>
    </div>
</section>

<div class="detail-grid">
    <div class="stack">
        <div class="card">
            <div class="card-header"><h2>Datos de contacto</h2></div>
            <div class="card-body">
                <dl class="summary-list">
                    <div><dt>Email</dt><dd><a href="mailto:<?= e($client['Email'] ?? '') ?>"><?= e($client['Email'] ?? '—') ?></a></dd></div>
                    <div><dt>Teléfono</dt><dd><?= e($client['Telefono'] ?? '—') ?></dd></div>
                    <div><dt>DNI</dt><dd><?= e($client['DNI'] ?? '—') ?></dd></div>
                    <div><dt>Nacionalidad</dt><dd><?= e($client['Nacionalidad'] ?? '—') ?></dd></div>
                    <div><dt>Fecha de nacimiento</dt><dd><?= e(fecha($client['FechaNacimiento'] ?? null)) ?></dd></div>
                </dl>
            </div>
        </div>

        <div class="card">
            <div class="card-header"><h2>Direcciones</h2></div>
            <div class="card-body">
                <?php if ($addresses === []): ?>
                    <p class="muted small">Sin direcciones registradas.</p>
                <?php endif; ?>
                <?php foreach ($addresses as $address): ?>
                    <div class="flex-between" style="padding:.55rem 0;border-bottom:1px dashed var(--line);gap:.5rem;">
                        <div>
                            <strong class="small">
                                <?= e(trim(($address['Calle'] ?? '') . ' ' . ($address['Numero'] ?? ''))) ?: 'Dirección' ?>
                                <span class="badge badge-neutral"><?= e(ucfirst($address['Tipo'])) ?></span>
                                <?php if ((int) $address['Principal'] === 1): ?>
                                    <span class="badge badge-success">Principal</span>
                                <?php endif; ?>
                            </strong>
                            <div class="small muted">
                                <?= e(trim(implode(', ', array_filter([$address['Localidad'], $address['Provincia'], $address['Pais'], $address['CodigoPostal']])))) ?>
                            </div>
                        </div>
                        <?php if (can('gestionar_clientes')): ?>
                            <form method="post" action="<?= e(base_url('admin/cliente_ver.php?id=' . $id)) ?>" data-confirm="¿Eliminar esta dirección?">
                                <?= csrf_field() ?>
                                <input type="hidden" name="action" value="delete_address">
                                <input type="hidden" name="address_relation_id" value="<?= (int) $address['ID_ClienteDireccion'] ?>">
                                <button class="icon-btn danger" type="submit" title="Eliminar">&times;</button>
                            </form>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
                <?php if (can('gestionar_clientes')): ?>
                    <details style="margin-top:.8rem;">
                        <summary class="small" style="cursor:pointer;color:var(--accent-strong);">Agregar dirección</summary>
                        <form method="post" action="<?= e(base_url('admin/cliente_ver.php?id=' . $id)) ?>" style="margin-top:.7rem;">
                            <?= csrf_field() ?>
                            <input type="hidden" name="action" value="add_address">
                            <div class="form-grid">
                                <div class="field">
                                    <label for="address_tipo">Tipo</label>
                                    <select id="address_tipo" name="tipo">
                                        <option value="domicilio">Domicilio</option>
                                        <option value="trabajo">Trabajo</option>
                                        <option value="otro">Otro</option>
                                    </select>
                                </div>
                                <div class="field"><label for="address_calle">Calle</label><input type="text" id="address_calle" name="calle" maxlength="120"></div>
                                <div class="field"><label for="address_numero">Número</label><input type="text" id="address_numero" name="numero" maxlength="20"></div>
                                <div class="field"><label for="address_localidad">Localidad</label><input type="text" id="address_localidad" name="localidad" maxlength="100"></div>
                                <div class="field"><label for="address_provincia">Provincia</label><input type="text" id="address_provincia" name="provincia" maxlength="100"></div>
                                <div class="field"><label for="address_pais">País</label><input type="text" id="address_pais" name="pais" maxlength="100" value="Argentina"></div>
                                <div class="field"><label for="address_cp">Código postal</label><input type="text" id="address_cp" name="codigo_postal" maxlength="20"></div>
                                <div class="field" style="display:flex;align-items:end;">
                                    <label class="check-item"><input type="checkbox" name="principal" value="1"> Dirección principal</label>
                                </div>
                            </div>
                            <button class="btn btn-primary btn-sm" type="submit" style="margin-top:.6rem;">Guardar dirección</button>
                        </form>
                    </details>
                <?php endif; ?>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h2>Reservas del cliente</h2>
                <a class="small" href="<?= e(base_url('admin/reservas.php?cliente=' . $id)) ?>">Ver todas</a>
            </div>
            <div class="table-wrap">
                <table class="data-table">
                    <thead><tr><th>Código</th><th>Fecha</th><th class="num">Total</th><th class="num">Saldo</th><th>Estado</th><th></th></tr></thead>
                    <tbody>
                    <?php if ($reservations === []): ?>
                        <tr><td colspan="6" class="table-empty">El cliente todavía no tiene reservas.</td></tr>
                    <?php endif; ?>
                    <?php foreach ($reservations as $res): ?>
                        <tr>
                            <td><strong><?= e($res['CodigoReserva']) ?></strong></td>
                            <td><?= e(fecha($res['FechaReserva'])) ?></td>
                            <td class="num"><?= e(money($res['Total'])) ?></td>
                            <td class="num <?= ((float) $res['Total'] - (float) $res['Pagado']) > 0.01 ? 'text-danger' : '' ?>"><?= e(money(max(0, (float) $res['Total'] - (float) $res['Pagado']))) ?></td>
                            <td><span class="<?= e(badge_class($res['Estado'])) ?>"><?= e($res['Estado']) ?></span></td>
                            <td class="actions"><a class="btn btn-ghost btn-sm" href="<?= e(base_url('admin/reserva_ver.php?id=' . (int) $res['ID_Reserva'])) ?>">Ver</a></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="card">
            <div class="card-header"><h2>Consultas recibidas</h2></div>
            <div class="card-body">
                <?php if ($messages === []): ?>
                    <p class="muted small mb-0">No hay consultas asociadas a este correo.</p>
                <?php endif; ?>
                <?php foreach ($messages as $msg): ?>
                    <div style="padding:.7rem 0;border-bottom:1px dashed var(--line);">
                        <div class="flex-between">
                            <strong class="small"><?= e($msg['Asunto'] ?: 'Consulta') ?></strong>
                            <span class="<?= e(badge_class($msg['Estado'])) ?>"><?= e($msg['Estado']) ?></span>
                        </div>
                        <div class="small muted"><?= e(fecha($msg['Fecha'], true)) ?></div>
                        <p class="small" style="margin:.35rem 0 0;"><?= e(summary_text($msg['Mensaje'], 220)) ?></p>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <div class="stack">
        <div class="card">
            <div class="card-header"><h2>Documentación</h2></div>
            <div class="card-body">
                <?php if ($documents === []): ?>
                    <p class="muted small">Sin documentos cargados.</p>
                <?php endif; ?>
                <?php foreach ($documents as $doc): ?>
                    <div class="flex-between" style="padding:.55rem 0;border-bottom:1px dashed var(--line);gap:.5rem;">
                        <div>
                            <strong class="small"><?= e($doc['Tipo']) ?>: <?= e($doc['Numero']) ?></strong>
                            <div class="small muted">Vence: <?= e(fecha($doc['FechaVencimiento'])) ?></div>
                        </div>
                        <?php if (can('gestionar_clientes')): ?>
                            <form method="post" action="<?= e(base_url('admin/cliente_ver.php?id=' . $id)) ?>" data-confirm="¿Eliminar este documento?">
                                <?= csrf_field() ?>
                                <input type="hidden" name="action" value="delete_document">
                                <input type="hidden" name="document_id" value="<?= (int) $doc['ID_DocumentoCliente'] ?>">
                                <button class="icon-btn danger" type="submit" title="Eliminar">&times;</button>
                            </form>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
                <?php if (can('gestionar_clientes') && $documentTypes !== []): ?>
                    <details style="margin-top:.8rem;">
                        <summary class="small" style="cursor:pointer;color:var(--accent-strong);">Agregar documento</summary>
                        <form method="post" action="<?= e(base_url('admin/cliente_ver.php?id=' . $id)) ?>" style="margin-top:.7rem;">
                            <?= csrf_field() ?>
                            <input type="hidden" name="action" value="add_document">
                            <div class="form-grid" style="grid-template-columns:1fr;">
                                <div class="field">
                                    <label for="tipo">Tipo</label>
                                    <select id="tipo" name="tipo" required>
                                        <option value="">Seleccionar</option>
                                        <?php foreach ($documentTypes as $type): ?>
                                            <option value="<?= (int) $type['ID_TipoDocumento'] ?>"><?= e($type['Nombre']) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="field"><label for="numero">Número</label><input type="text" id="numero" name="numero" required maxlength="50"></div>
                                <div class="field"><label for="emision">Emisión</label><input type="date" id="emision" name="emision"></div>
                                <div class="field"><label for="vencimiento">Vencimiento</label><input type="date" id="vencimiento" name="vencimiento"></div>
                                <div class="field"><label for="pais">País de emisión</label><input type="text" id="pais" name="pais" maxlength="80"></div>
                            </div>
                            <button class="btn btn-primary btn-sm" type="submit" style="margin-top:.6rem;">Guardar documento</button>
                        </form>
                    </details>
                <?php endif; ?>
            </div>
        </div>

        <div class="card">
            <div class="card-header"><h2>Contactos de emergencia</h2></div>
            <div class="card-body">
                <?php if ($emergencyContacts === []): ?>
                    <p class="muted small">Sin contactos registrados.</p>
                <?php endif; ?>
                <?php foreach ($emergencyContacts as $contact): ?>
                    <div class="flex-between" style="padding:.55rem 0;border-bottom:1px dashed var(--line);gap:.5rem;">
                        <div>
                            <strong class="small"><?= e($contact['Nombre']) ?></strong>
                            <div class="small muted"><?= e($contact['Telefono']) ?><?= $contact['Relacion'] ? ' · ' . e($contact['Relacion']) : '' ?></div>
                        </div>
                        <?php if (can('gestionar_clientes')): ?>
                            <form method="post" action="<?= e(base_url('admin/cliente_ver.php?id=' . $id)) ?>" data-confirm="¿Eliminar este contacto de emergencia?">
                                <?= csrf_field() ?>
                                <input type="hidden" name="action" value="delete_emergency">
                                <input type="hidden" name="emergency_id" value="<?= (int) $contact['ID_ContactoEmergencia'] ?>">
                                <button class="icon-btn danger" type="submit" title="Eliminar">&times;</button>
                            </form>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
                <?php if (can('gestionar_clientes')): ?>
                    <details style="margin-top:.8rem;">
                        <summary class="small" style="cursor:pointer;color:var(--accent-strong);">Agregar contacto</summary>
                        <form method="post" action="<?= e(base_url('admin/cliente_ver.php?id=' . $id)) ?>" style="margin-top:.7rem;">
                            <?= csrf_field() ?>
                            <input type="hidden" name="action" value="add_emergency">
                            <div class="form-grid" style="grid-template-columns:1fr;">
                                <div class="field"><label for="emergency_nombre">Nombre</label><input type="text" id="emergency_nombre" name="nombre" maxlength="100" required></div>
                                <div class="field"><label for="emergency_telefono">Teléfono</label><input type="tel" id="emergency_telefono" name="telefono" maxlength="30" required></div>
                                <div class="field"><label for="emergency_relacion">Relación</label><input type="text" id="emergency_relacion" name="relacion" maxlength="60" placeholder="Familiar, amigo, etc."></div>
                            </div>
                            <button class="btn btn-primary btn-sm" type="submit" style="margin-top:.6rem;">Guardar contacto</button>
                        </form>
                    </details>
                <?php endif; ?>
            </div>
        </div>

        <div class="card">
            <div class="card-header"><h2>Preferencias</h2></div>
            <div class="card-body">
                <?php if ($preferences === []): ?>
                    <p class="muted small">Sin preferencias registradas.</p>
                <?php endif; ?>
                <?php foreach ($preferences as $preference): ?>
                    <div class="flex-between" style="padding:.5rem 0;border-bottom:1px dashed var(--line);gap:.5rem;">
                        <div>
                            <strong class="small"><?= e($preference['Tipo']) ?></strong>
                            <div class="small muted"><?= e($preference['Valor']) ?></div>
                        </div>
                        <?php if (can('gestionar_clientes')): ?>
                            <form method="post" action="<?= e(base_url('admin/cliente_ver.php?id=' . $id)) ?>" data-confirm="¿Eliminar esta preferencia?">
                                <?= csrf_field() ?>
                                <input type="hidden" name="action" value="delete_preference">
                                <input type="hidden" name="preference_id" value="<?= (int) $preference['ID_Preferencia'] ?>">
                                <button class="icon-btn danger" type="submit" title="Eliminar">&times;</button>
                            </form>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
                <?php if (can('gestionar_clientes')): ?>
                    <details style="margin-top:.8rem;">
                        <summary class="small" style="cursor:pointer;color:var(--accent-strong);">Agregar preferencia</summary>
                        <form method="post" action="<?= e(base_url('admin/cliente_ver.php?id=' . $id)) ?>" style="margin-top:.7rem;">
                            <?= csrf_field() ?>
                            <input type="hidden" name="action" value="add_preference">
                            <div class="form-grid" style="grid-template-columns:1fr;">
                                <div class="field"><label for="preference_tipo">Tipo</label><input type="text" id="preference_tipo" name="tipo_preferencia" maxlength="60" placeholder="Aerolínea, hotel, asiento..." required></div>
                                <div class="field"><label for="preference_valor">Valor</label><input type="text" id="preference_valor" name="valor_preferencia" maxlength="150" required></div>
                            </div>
                            <button class="btn btn-primary btn-sm" type="submit" style="margin-top:.6rem;">Guardar preferencia</button>
                        </form>
                    </details>
                <?php endif; ?>
            </div>
        </div>

        <div class="card">
            <div class="card-header"><h2>Observaciones internas</h2></div>
            <div class="card-body">
                <form method="post" action="<?= e(base_url('admin/cliente_ver.php?id=' . $id)) ?>">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="observation">
                    <div class="field">
                        <textarea name="Observaciones" rows="5" <?= can('gestionar_clientes') ? '' : 'readonly' ?>><?= e($client['Observaciones'] ?? '') ?></textarea>
                    </div>
                    <?php if (can('gestionar_clientes')): ?>
                        <button class="btn btn-primary btn-sm" type="submit">Guardar</button>
                    <?php endif; ?>
                </form>
            </div>
        </div>
    </div>
</div>
<?php require __DIR__ . '/../includes/admin_footer.php'; ?>
