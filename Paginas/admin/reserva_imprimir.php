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
$details = reservation_details($id);
$passengers = reservation_passengers($id);
$payments = reservation_payments($id);
$paid = reservation_paid($id);
$balance = max(0.0, (float) $reservation['Total'] - $paid);
$clientName = trim((string) ($reservation['Nombre'] ?? '') . ' ' . (string) ($reservation['Apellido'] ?? '')) ?: ($reservation['Email'] ?? 'Cliente');
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reserva <?= e($reservation['CodigoReserva']) ?> · <?= e(APP_NAME) ?></title>
    <link rel="stylesheet" href="<?= e(asset('css/public.css')) ?>">
    <style>
        body { background: #eef2f4; }
        .print-sheet { max-width: 900px; margin: 1.5rem auto; background: #fff; padding: 2.5rem; box-shadow: 0 10px 30px rgba(0,0,0,.08); }
        .print-head { display: flex; justify-content: space-between; gap: 1rem; border-bottom: 2px solid #005778; padding-bottom: 1rem; }
        .print-head img { height: 46px; width: auto; }
        .print-title { text-align: right; }
        .print-title h1 { margin: 0; font-size: 1.5rem; color: #003549; }
        .print-title span { color: #62747d; font-size: .85rem; }
        .print-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem; margin: 1.5rem 0; }
        .print-grid h3 { font-size: .78rem; text-transform: uppercase; letter-spacing: .08em; color: #62747d; margin: 0 0 .4rem; }
        .print-grid p { margin: .15rem 0; font-size: .9rem; }
        .print-table { width: 100%; border-collapse: collapse; margin: 1rem 0; font-size: .85rem; }
        .print-table th, .print-table td { border: 1px solid #d7e0e5; padding: .5rem .6rem; text-align: left; }
        .print-table th { background: #f0f5f7; color: #003549; }
        .print-table .num { text-align: right; }
        .print-totals { margin-left: auto; width: 300px; }
        .print-totals div { display: flex; justify-content: space-between; padding: .35rem 0; border-bottom: 1px dashed #d7e0e5; }
        .print-totals div:last-child { font-weight: 800; font-size: 1.05rem; border-bottom: 0; }
        .print-footer { margin-top: 2.5rem; padding-top: 1rem; border-top: 1px solid #d7e0e5; font-size: .75rem; color: #62747d; display: flex; justify-content: space-between; gap: 1rem; }
        .print-actions { max-width: 900px; margin: 0 auto; display: flex; gap: .5rem; justify-content: flex-end; }
        @media print {
            body { background: #fff; }
            .print-sheet { margin: 0; padding: 0; box-shadow: none; max-width: none; }
            .print-actions { display: none; }
            @page { margin: 18mm; }
        }
    </style>
</head>
<body>
<div class="print-actions">
    <a class="btn btn-ghost" href="<?= e(base_url('admin/reserva_ver.php?id=' . $id)) ?>">Volver</a>
    <button class="btn btn-primary" type="button" onclick="window.print()">Imprimir</button>
</div>
<main class="print-sheet">
    <header class="print-head">
        <div>
            <img src="<?= e(asset('img/logo.png')) ?>" alt="Aura Viajes">
            <p style="margin:.5rem 0 0;font-size:.8rem;color:#62747d;">Aura Viajes S.A. · Formosa, Argentina<br>WhatsApp +54 370 400 0000 · hola@auraviajes.com</p>
        </div>
        <div class="print-title">
            <h1>Reserva <?= e($reservation['CodigoReserva']) ?></h1>
            <span>Emitida el <?= e(fecha($reservation['FechaReserva'], true)) ?></span><br>
            <span>Estado: <?= e($reservation['EstadoNombre']) ?></span>
        </div>
    </header>

    <div class="print-grid">
        <section>
            <h3>Cliente</h3>
            <p><strong><?= e($clientName) ?></strong></p>
            <p><?= e($reservation['CodigoCliente']) ?> · DNI <?= e($reservation['DNI'] ?? '—') ?></p>
            <p><?= e($reservation['Email'] ?? '—') ?></p>
            <p><?= e($reservation['Telefono'] ?? '—') ?></p>
        </section>
        <section>
            <h3>Datos de la reserva</h3>
            <p>Agente: <?= e(trim(($reservation['EmpleadoNombre'] ?? '') . ' ' . ($reservation['EmpleadoApellido'] ?? '')) ?: '—') ?></p>
            <p>Pasajeros: <?= count($passengers) ?></p>
            <p>Saldo pendiente: <strong><?= e(money($balance)) ?></strong></p>
        </section>
    </div>

    <h3 style="font-size:.78rem;text-transform:uppercase;letter-spacing:.08em;color:#62747d;">Detalle</h3>
    <table class="print-table">
        <thead><tr><th>Descripción</th><th>Salida</th><th class="num">Cant.</th><th class="num">Precio</th><th class="num">Subtotal</th></tr></thead>
        <tbody>
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

    <h3 style="font-size:.78rem;text-transform:uppercase;letter-spacing:.08em;color:#62747d;">Pasajeros</h3>
    <table class="print-table">
        <thead><tr><th>Nombre</th><th>DNI</th><th>Teléfono</th><th>Nacionalidad</th></tr></thead>
        <tbody>
        <?php foreach ($passengers as $passenger): ?>
            <tr>
                <td><?= e(trim($passenger['Nombre'] . ' ' . $passenger['Apellido'])) ?><?= $passenger['Titular'] ? ' (Titular)' : '' ?></td>
                <td><?= e($passenger['DNI']) ?></td>
                <td><?= e($passenger['Telefono'] ?? '—') ?></td>
                <td><?= e($passenger['Nacionalidad'] ?? '—') ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>

    <div class="print-totals">
        <div><span>Total reserva</span><strong><?= e(money($reservation['Total'])) ?></strong></div>
        <div><span>Pagos aprobados</span><strong><?= e(money($paid)) ?></strong></div>
        <div><span>Saldo</span><strong><?= e(money($balance)) ?></strong></div>
    </div>

    <h3 style="font-size:.78rem;text-transform:uppercase;letter-spacing:.08em;color:#62747d;">Pagos</h3>
    <table class="print-table">
        <thead><tr><th>Fecha</th><th>Método</th><th>Operación</th><th>Estado</th><th class="num">Importe</th></tr></thead>
        <tbody>
        <?php if ($payments === []): ?>
            <tr><td colspan="5">Sin pagos registrados.</td></tr>
        <?php endif; ?>
        <?php foreach ($payments as $pay): ?>
            <tr>
                <td><?= e(fecha($pay['FechaPago'], true)) ?></td>
                <td><?= e($pay['Metodo']) ?></td>
                <td><?= e($pay['NroOperacion'] ?? '—') ?></td>
                <td><?= e(estado_label($pay['Estado'])) ?></td>
                <td class="num"><?= e(money($pay['Importe'])) ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>

    <?php if (!empty($reservation['Observaciones'])): ?>
        <h3 style="font-size:.78rem;text-transform:uppercase;letter-spacing:.08em;color:#62747d;">Observaciones</h3>
        <p style="font-size:.85rem;white-space:pre-line;"><?= e($reservation['Observaciones']) ?></p>
    <?php endif; ?>

    <footer class="print-footer">
        <span>Documento generado por el sistema de gestión de Aura Viajes.</span>
        <span>Reserva <?= e($reservation['CodigoReserva']) ?> · <?= e(date('d/m/Y H:i')) ?></span>
    </footer>
</main>
</body>
</html>
