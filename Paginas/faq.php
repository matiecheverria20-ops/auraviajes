<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/public_data.php';

$faqs = db_all('SELECT * FROM preguntas_frecuentes WHERE Activo = 1 ORDER BY Orden ASC, ID_FAQ ASC');

$pageTitle = 'Preguntas frecuentes — ' . APP_NAME;
$metaDescription = 'Respuestas sobre cotizaciones, reservas, pagos, cancelaciones, seguros e itinerarios.';
$activeNav = 'faq';
require __DIR__ . '/includes/public_header.php';
?>
<section class="page-hero">
    <div class="container">
        <span class="eyebrow">Ayuda</span>
        <h1>Preguntas frecuentes</h1>
        <p>Todo lo que necesitás saber antes de reservar tu próximo viaje.</p>
    </div>
</section>

<section class="section">
    <div class="container">
        <?php if ($faqs === []): ?>
            <div class="empty-state">
                <h3>Estamos actualizando esta sección</h3>
                <p>Mientras tanto, podés escribirnos y te respondemos a la brevedad.</p>
                <a class="btn btn-primary" href="<?= e(base_url('contacto.php')) ?>">Contactarnos</a>
            </div>
        <?php else: ?>
            <div class="faq-list">
                <?php foreach ($faqs as $faq): ?>
                    <details class="faq-item">
                        <summary><?= e($faq['Pregunta']) ?></summary>
                        <p><?= nl2br(e($faq['Respuesta'])) ?></p>
                    </details>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</section>

<div class="container">
    <div class="quote-band" style="--band-image:url('<?= e(asset('img/asuncion.jpg')) ?>')">
        <div>
            <h2>¿Te quedó otra duda?</h2>
            <p>Escribinos y un asesor te responde con la información que necesitás.</p>
        </div>
        <div style="display:flex;gap:.7rem;flex-wrap:wrap;">
            <a class="btn btn-primary" href="<?= e(base_url('contacto.php')) ?>">Contacto</a>
            <a class="btn btn-ghost" href="<?= e(base_url('cotizar.php')) ?>">Cotizar</a>
        </div>
    </div>
</div>
<?php require __DIR__ . '/includes/public_footer.php'; ?>
