<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/public_data.php';

$offers = public_offer_packages(12);
$promotions = active_promotions(4);

$pageTitle = 'Ofertas y promociones — ' . APP_NAME;
$metaDescription = 'Paquetes con descuentos vigentes y promociones por tiempo limitado.';
$activeNav = 'ofertas';
require __DIR__ . '/includes/public_header.php';
?>
<section class="page-hero">
    <div class="container">
        <span class="eyebrow">Ofertas</span>
        <h1>Viajar también es una buenísima inversión</h1>
        <p>Aprovechá los descuentos vigentes en paquetes seleccionados. Los cupos son limitados.</p>
    </div>
</section>

<section class="section">
    <div class="container">
        <?php if ($promotions !== []): ?>
            <div class="chip-row" style="margin-bottom:1.5rem;">
                <?php foreach ($promotions as $promotion): ?>
                    <span class="chip">
                        <?= e($promotion['Nombre']) ?> ·
                        <?= e($promotion['TipoDescuento'] === 'porcentaje' ? rtrim(rtrim(number_format((float) $promotion['ValorDescuento'], 2, ',', '.'), '0'), ',') . '% off' : money($promotion['ValorDescuento']) . ' off') ?>
                        · hasta <?= e(fecha($promotion['FechaFin'])) ?>
                    </span>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <?php if ($offers === []): ?>
            <div class="empty-state">
                <h3>No hay ofertas activas en este momento</h3>
                <p>Consultanos por las próximas promociones o mirá todos los paquetes disponibles.</p>
                <div class="mt-2" style="display:flex;gap:.6rem;justify-content:center;flex-wrap:wrap;">
                    <a class="btn btn-outline" href="<?= e(base_url('paquetes.php')) ?>">Ver paquetes</a>
                    <a class="btn btn-primary" href="<?= e(base_url('cotizar.php')) ?>">Pedir cotización</a>
                </div>
            </div>
        <?php else: ?>
            <div class="results-bar">
                <span><?= count($offers) ?> <?= count($offers) === 1 ? 'paquete en oferta' : 'paquetes en oferta' ?></span>
            </div>
            <div class="package-grid">
                <?php foreach ($offers as $package): ?>
                    <?= public_package_card($package) ?>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</section>

<div class="container">
    <div class="quote-band" style="--band-image:url('<?= e(asset('img/rio.jpg')) ?>')">
        <div>
            <h2>¿Buscás una promo para tus fechas?</h2>
            <p>Contanos cuándo querés viajar y te enviamos las mejores opciones disponibles.</p>
        </div>
        <a class="btn btn-primary" href="<?= e(base_url('cotizar.php')) ?>">Consultar disponibilidad</a>
    </div>
</div>
<?php require __DIR__ . '/includes/public_footer.php'; ?>
