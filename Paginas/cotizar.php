<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/public_data.php';

$packageId = get_int('paquete');
$departureId = get_int('salida');
$errors = [];
$values = ['Nombre' => '', 'Email' => '', 'Telefono' => '', 'Asunto' => '', 'Mensaje' => ''];

if (is_post()) {
    verify_csrf();
    $packageId = post_int('paquete_id');
    $departureId = post_int('salida_id');
    $values = [
        'Nombre' => post_string('nombre'),
        'Email' => post_string('email'),
        'Telefono' => post_string('telefono'),
        'Asunto' => post_string('asunto'),
        'Mensaje' => post_string('mensaje'),
    ];

    if (post_string('website') !== '') {
        redirect('cotizar.php?enviado=1');
    }
    if ($values['Nombre'] === '' || mb_strlen($values['Nombre']) > 100) {
        $errors[] = 'Ingresá tu nombre completo (máximo 100 caracteres).';
    }
    if ($values['Email'] === '' || filter_var($values['Email'], FILTER_VALIDATE_EMAIL) === false || mb_strlen($values['Email']) > 120) {
        $errors[] = 'Ingresá un correo electrónico válido.';
    }
    if ($values['Telefono'] !== '' && mb_strlen($values['Telefono']) > 30) {
        $errors[] = 'El teléfono no puede superar los 30 caracteres.';
    }
    if ($values['Asunto'] !== '' && mb_strlen($values['Asunto']) > 150) {
        $errors[] = 'El asunto no puede superar los 150 caracteres.';
    }
    if (mb_strlen($values['Mensaje']) < 10) {
        $errors[] = 'Contanos un poco más sobre tu viaje (al menos 10 caracteres).';
    }
    if (mb_strlen($values['Mensaje']) > 3000) {
        $errors[] = 'El mensaje no puede superar los 3000 caracteres.';
    }

    if ($errors === []) {
        try {
            public_store_message($values, $packageId, $departureId);
            flash('success', '¡Recibimos tu solicitud! Un asesor se va a comunicar con vos a la brevedad.');
            redirect('cotizar.php?enviado=1');
        } catch (Throwable $e) {
            $errors[] = 'No pudimos enviar la solicitud. Intentá nuevamente en unos instantes.';
        }
    }
}

$context = public_message_context($packageId, $departureId);
if (!is_post() || $errors !== []) {
    if (!is_post()) {
        $values['Asunto'] = $context['subject'];
        if ($context['package'] !== null) {
            $values['Mensaje'] = 'Hola, quiero recibir una cotización para el paquete ' . $context['package']['Nombre'] . '.';
        }
    }
}

$pageTitle = 'Pedir cotización — ' . APP_NAME;
$metaDescription = 'Solicitá una cotización personalizada para tu próximo viaje con Aura Viajes.';
$activeNav = '';
require __DIR__ . '/includes/public_header.php';
?>
<section class="page-hero">
    <div class="container">
        <span class="eyebrow">Cotización</span>
        <h1>Armemos tu próximo viaje</h1>
        <p>Contanos qué tenés en mente. Te respondemos con una propuesta personalizada en menos de 24 horas hábiles.</p>
    </div>
</section>

<section class="section">
    <div class="container two-col-form">
        <div class="form-card">
            <h2>Solicitud de cotización</h2>
            <p class="muted">Los campos marcados con * son obligatorios.</p>

            <?php if ($errors !== []): ?>
                <div class="alert alert-error" role="alert">
                    <?= e(implode(' ', $errors)) ?>
                </div>
            <?php endif; ?>
            <?php if (get_string('enviado') === '1'): ?>
                <div class="alert alert-success" role="alert">Solicitud enviada. ¡Gracias por elegirnos!</div>
            <?php endif; ?>

            <?php if ($context['package'] !== null): ?>
                <div class="side-card mb-0" style="margin-bottom:1.2rem;">
                    <span class="eyebrow">Paquete seleccionado</span>
                    <h3><?= e($context['package']['Nombre']) ?></h3>
                    <?php if ($context['departure'] !== null): ?>
                        <p class="small muted mb-0">Salida: <?= e(fecha($context['departure']['FechaSalida'])) ?> al <?= e(fecha($context['departure']['FechaRegreso'])) ?></p>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <form method="post" action="<?= e(base_url('cotizar.php')) ?>">
                <?= csrf_field() ?>
                <input type="hidden" name="paquete_id" value="<?= (int) $packageId ?>">
                <input type="hidden" name="salida_id" value="<?= (int) $departureId ?>">
                <div class="hidden" aria-hidden="true">
                    <label for="website">Sitio web</label>
                    <input type="text" id="website" name="website" tabindex="-1" autocomplete="off">
                </div>
                <div class="form-grid">
                    <div class="field">
                        <label for="nombre">Nombre y apellido *</label>
                        <input type="text" id="nombre" name="nombre" maxlength="100" required value="<?= e($values['Nombre']) ?>">
                    </div>
                    <div class="field">
                        <label for="email">Email *</label>
                        <input type="email" id="email" name="email" maxlength="120" required value="<?= e($values['Email']) ?>">
                    </div>
                    <div class="field">
                        <label for="telefono">Teléfono</label>
                        <input type="tel" id="telefono" name="telefono" maxlength="30" value="<?= e($values['Telefono']) ?>">
                    </div>
                    <div class="field">
                        <label for="asunto">Asunto</label>
                        <input type="text" id="asunto" name="asunto" maxlength="150" value="<?= e($values['Asunto']) ?>">
                    </div>
                    <div class="field full">
                        <label for="mensaje">Contanos sobre tu viaje *</label>
                        <textarea id="mensaje" name="mensaje" maxlength="3000" required placeholder="Destino, fechas estimadas, cantidad de pasajeros, intereses..."><?= e($values['Mensaje']) ?></textarea>
                    </div>
                </div>
                <div class="mt-3" style="display:flex;gap:.8rem;flex-wrap:wrap;align-items:center;">
                    <button class="btn btn-primary" type="submit">Enviar solicitud</button>
                    <a class="btn btn-outline" href="<?= e(base_url('contacto.php')) ?>">Prefiero llamar</a>
                </div>
                <p class="form-note">Al enviar aceptás ser contactado por Aura Viajes para responder tu consulta.</p>
            </form>
        </div>

        <aside class="side-card">
            <h3>¿Por qué cotizar con nosotros?</h3>
            <ul class="check-list">
                <li>Propuestas personalizadas sin costo.</li>
                <li>Asesoramiento de agentes con experiencia.</li>
                <li>Gestión integral de reservas y pagos.</li>
                <li>Opciones de financiación y cuotas.</li>
            </ul>
            <hr style="border:0;border-top:1px solid var(--sand-deep);margin:1.2rem 0;">
            <h3>Contacto directo</h3>
            <p class="small mb-0"><strong>Teléfono:</strong> <a href="tel:08002222872">0800-222-AURA</a></p>
            <p class="small"><strong>Email:</strong> <a href="mailto:info@auraviajes.com">info@auraviajes.com</a></p>
            <p class="small muted">Lunes a viernes de 9 a 20 hs. Sábados de 9 a 18 hs.</p>
            <a class="btn btn-teal btn-block mt-2" href="<?= e(base_url('paquetes.php')) ?>">Ver paquetes</a>
        </aside>
    </div>
</section>
<?php require __DIR__ . '/includes/public_footer.php'; ?>
