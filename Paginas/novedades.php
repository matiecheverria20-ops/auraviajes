<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/public_data.php';

$promotions = active_promotions(6);
$newPackages = db_all(
    public_packages_query() . " WHERE p.Estado = 'publicado' ORDER BY p.FechaAlta DESC, p.ID_Paquete DESC LIMIT 3"
);
$banners = db_all('SELECT * FROM banners WHERE Activo = 1 ORDER BY Orden ASC, ID_Banner ASC LIMIT 3');

$pageTitle = 'Novedades y promociones — ' . APP_NAME;
$metaDescription = 'Enterate de las promociones vigentes, nuevas salidas y novedades de Aura Viajes.';
$activeNav = 'novedades';
require __DIR__ . '/includes/public_header.php';
?>
<section class="page-hero">
    <div class="container">
        <span class="eyebrow">Novedades</span>
        <h1>Ideas y beneficios para tu próximo viaje</h1>
        <p>Promociones vigentes, nuevas propuestas y todo lo que necesitás para decidirte.</p>
    </div>
</section>

<?php if ($promotions !== []): ?>
<section class="section section-teal">
    <div class="container">
        <div class="section-head">
            <div>
                <span class="eyebrow">Promociones</span>
                <h2>Beneficios vigentes</h2>
                <p>Descuentos por tiempo limitado en paquetes seleccionados.</p>
            </div>
            <a class="btn btn-ghost" href="<?= e(base_url('ofertas.php')) ?>">Ver paquetes en oferta</a>
        </div>
        <div class="promo-grid">
            <?php foreach ($promotions as $promotion): ?>
                <article class="promo-card">
                    <span class="promo-value"><?= e($promotion['TipoDescuento'] === 'porcentaje' ? rtrim(rtrim(number_format((float) $promotion['ValorDescuento'], 2, ',', '.'), '0'), ',') . '% off' : money($promotion['ValorDescuento']) . ' off') ?></span>
                    <h3 class="mt-2"><?= e($promotion['Nombre']) ?></h3>
                    <p><?= e($promotion['Descripcion'] ?? 'Beneficio exclusivo en paquetes seleccionados.') ?></p>
                    <p class="promo-dates">Desde el <?= e(fecha($promotion['FechaInicio'])) ?> hasta el <?= e(fecha($promotion['FechaFin'])) ?></p>
                </article>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<?php if ($banners !== []): ?>
<section class="section">
    <div class="container">
        <div class="section-head">
            <div>
                <span class="eyebrow">Para inspirarte</span>
                <h2>Experiencias destacadas</h2>
            </div>
        </div>
        <div class="destination-grid">
            <?php foreach ($banners as $banner): ?>
                <a class="destination-card" href="<?= e(public_link($banner['Link'] ?? null, 'paquetes.php')) ?>" style="--card-image:url('<?= e(public_banner_image($banner)) ?>')">
                    <div>
                        <h3><?= e($banner['Titulo']) ?></h3>
                        <p><?= e(summary_text($banner['Texto'] ?? 'Una experiencia para recordar.', 100)) ?></p>
                        <span class="card-link">Conocer más &rarr;</span>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<?php if ($newPackages !== []): ?>
<section class="section section-sand">
    <div class="container">
        <div class="section-head">
            <div>
                <span class="eyebrow">Nuevas propuestas</span>
                <h2>Últimos paquetes publicados</h2>
                <p>Propuestas recientes con salidas coordinadas y cupos disponibles.</p>
            </div>
            <a class="btn btn-outline" href="<?= e(base_url('paquetes.php')) ?>">Ver todos</a>
        </div>
        <div class="package-grid">
            <?php foreach ($newPackages as $package): ?>
                <?= public_package_card($package) ?>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<div class="container">
    <div class="quote-band" style="--band-image:url('<?= e(asset('img/mendoza.jpg')) ?>')">
        <div>
            <h2>¿Querés que te avisemos de nuevas salidas?</h2>
            <p>Dejanos tu consulta y te contactamos con las mejores opciones.</p>
        </div>
        <a class="btn btn-primary" href="<?= e(base_url('contacto.php')) ?>">Quiero información</a>
    </div>
</div>
<?php require __DIR__ . '/includes/public_footer.php'; ?>
