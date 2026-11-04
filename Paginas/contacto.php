<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/public_data.php';

$errors = [];
$values = ['Nombre' => '', 'Email' => '', 'Telefono' => '', 'Asunto' => '', 'Mensaje' => ''];

if (is_post()) {
    verify_csrf();
    $values = [
        'Nombre' => post_string('nombre'),
        'Email' => post_string('email'),
        'Telefono' => post_string('telefono'),
        'Asunto' => post_string('asunto'),
        'Mensaje' => post_string('mensaje'),
    ];

    if (post_string('website') !== '') {
        redirect('contacto.php?enviado=1');
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
        $errors[] = 'Escribinos un mensaje de al menos 10 caracteres.';
    }
    if (mb_strlen($values['Mensaje']) > 3000) {
        $errors[] = 'El mensaje no puede superar los 3000 caracteres.';
    }

    if ($errors === []) {
        try {
            public_store_message($values);
            flash('success', '¡Gracias por escribirnos! Te respondemos a la brevedad.');
            redirect('contacto.php?enviado=1');
        } catch (Throwable $e) {
            $errors[] = 'No pudimos enviar el mensaje. Intentá nuevamente en unos instantes.';
        }
    }
}

$branches = db_all('SELECT * FROM establecimiento ORDER BY ID_Establecimiento ASC');

$pageTitle = 'Contacto — ' . APP_NAME;
$metaDescription = 'Comunicate con Aura Viajes. Sucursales, teléfonos y formulario de contacto.';
$activeNav = 'contacto';
require __DIR__ . '/includes/public_header.php';
?>
<section class="page-hero">
    <div class="container">
        <span class="eyebrow">Contacto</span>
        <h1>Estamos para ayudarte</h1>
        <p>Elegí el canal que prefieras. Respondemos consultas, cotizaciones y soporte para viajeros.</p>
    </div>
</section>

<section class="section">
    <div class="container two-col-form">
        <div class="form-card">
            <h2>Escribinos</h2>
            <p class="muted">Completá el formulario y un asesor te responderá en menos de 24 horas hábiles.</p>

            <?php if ($errors !== []): ?>
                <div class="alert alert-error" role="alert"><?= e(implode(' ', $errors)) ?></div>
            <?php endif; ?>
            <?php if (get_string('enviado') === '1'): ?>
                <div class="alert alert-success" role="alert">Mensaje enviado. ¡Gracias por contactarte con Aura Viajes!</div>
            <?php endif; ?>

            <form method="post" action="<?= e(base_url('contacto.php')) ?>">
                <?= csrf_field() ?>
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
                        <label for="mensaje">Mensaje *</label>
                        <textarea id="mensaje" name="mensaje" maxlength="3000" required placeholder="¿En qué podemos ayudarte?"><?= e($values['Mensaje']) ?></textarea>
                    </div>
                </div>
                <div class="mt-3">
                    <button class="btn btn-primary" type="submit">Enviar mensaje</button>
                </div>
            </form>
        </div>

        <aside class="side-card">
            <h3>Atención al cliente</h3>
            <p class="small"><strong>Teléfono:</strong><br><a href="tel:08002222872">0800-222-AURA</a></p>
            <p class="small"><strong>Email:</strong><br><a href="mailto:info@auraviajes.com">info@auraviajes.com</a></p>
            <p class="small"><strong>WhatsApp:</strong><br><a href="https://wa.me/5493704000000" target="_blank" rel="noopener">+54 9 370 400-0000</a></p>
            <p class="small muted mb-0">Lunes a viernes de 9 a 20 hs<br>Sábados de 9 a 18 hs</p>
            <hr style="border:0;border-top:1px solid var(--sand-deep);margin:1.2rem 0;">
            <h3>Cotizá tu viaje</h3>
            <p class="small muted">Contanos destino y fechas para recibir una propuesta personalizada.</p>
            <a class="btn btn-teal btn-block" href="<?= e(base_url('cotizar.php')) ?>">Pedir cotización</a>
        </aside>
    </div>
</section>

<?php if ($branches !== []): ?>
<section class="section section-sand">
    <div class="container">
        <div class="section-head">
            <div>
                <span class="eyebrow">Sucursales</span>
                <h2>Encontranos</h2>
                <p>Cinco oficinas para acompañarte en cada etapa de tu viaje.</p>
            </div>
        </div>
        <div class="benefit-grid">
            <?php foreach ($branches as $branch): ?>
                <div class="benefit-card" style="align-items:flex-start;">
                    <span class="benefit-icon"><?= e(initials($branch['Localidad'] ?? $branch['Nombre'])) ?></span>
                    <div>
                        <h3><?= e($branch['Nombre']) ?></h3>
                        <p><?= e(trim(($branch['Ubicacion'] ?? '') . ' · ' . ($branch['Localidad'] ?? ''), ' ·')) ?></p>
                        <?php if (!empty($branch['TitularACargo'])): ?>
                            <p class="small muted mb-0">A cargo: <?= e($branch['TitularACargo']) ?></p>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>
<?php require __DIR__ . '/includes/public_footer.php'; ?>
