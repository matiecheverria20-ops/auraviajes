<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/public_data.php';

$sections = [];
foreach (db_all("SELECT * FROM secciones_landing WHERE Activo = 1 ORDER BY Orden ASC") as $section) {
    $sections[$section['Nombre']] = $section;
}

$banners = db_all("SELECT * FROM banners WHERE Activo = 1 ORDER BY Orden ASC, ID_Banner ASC");
$heroBanner = $banners[0] ?? null;
$heroSection = $sections['hero'] ?? null;
$heroImage = $heroBanner !== null ? public_banner_image($heroBanner) : destination_image('Iguazú');
$heroLink = $heroBanner !== null ? public_link($heroBanner['Link'] ?? null, 'paquetes.php') : base_url('paquetes.php');
$heroTitle = $heroBanner['Titulo'] ?? ($heroSection['Titulo'] ?? 'Viajá con nosotros');
$heroText = $heroBanner['Texto'] ?? ($heroSection['Contenido'] ?? 'Paquetes personalizados y atención profesional para que solo te ocupes de disfrutar.');

$destinations = db_all(
    "SELECT d.*, di.Descripcion,
            (SELECT c.Nombre FROM destino_categoria dc INNER JOIN categorias_destino c ON c.ID_CategoriaDestino = dc.FK_ID_CategoriaDestino WHERE dc.FK_ID_Destino = d.ID_Destino ORDER BY dc.ID_DestinoCategoria LIMIT 1) AS CategoriaNombre,
            (SELECT COUNT(DISTINCT pd.FK_ID_Paquete) FROM paquete_destino pd INNER JOIN paquetes p ON p.ID_Paquete = pd.FK_ID_Paquete WHERE pd.FK_ID_Destino = d.ID_Destino AND p.Estado = 'publicado') AS PaquetesCount
       FROM destino d
       LEFT JOIN destino_informacion di ON di.FK_ID_Destino = d.ID_Destino
      ORDER BY PaquetesCount DESC, d.NombreUbicacion ASC
      LIMIT 6"
);

$packages = db_all(
    public_packages_query() . " WHERE p.Estado = 'publicado'
     ORDER BY (ProximaSalida IS NULL), ProximaSalida ASC, p.ID_Paquete ASC
     LIMIT 3"
);

$promotions = active_promotions(3);
$faqs = db_all('SELECT * FROM preguntas_frecuentes WHERE Activo = 1 ORDER BY Orden ASC, ID_FAQ ASC LIMIT 4');
$publishedCount = (int) db_val("SELECT COUNT(*) FROM paquetes WHERE Estado = 'publicado'", [], 0);
$destinationCount = (int) db_val('SELECT COUNT(*) FROM destino', [], 0);

$pageTitle = APP_NAME . ' — Viajes que se disfrutan';
$metaDescription = 'Paquetes turísticos, destinos y asesoramiento personalizado. Viví tu próximo viaje con Aura Viajes.';
$activeNav = 'inicio';
require __DIR__ . '/includes/public_header.php';
?>
<section class="hero" style="--hero-image:url('<?= e($heroImage) ?>')">
    <div class="container hero-inner">
        <div class="hero-copy">
            <span class="hero-badge">Agencia de viajes · Formosa</span>
            <h1><?= e($heroTitle) ?></h1>
            <p><?= e($heroText) ?></p>
            <div class="mt-3" style="display:flex;gap:.8rem;flex-wrap:wrap;">
                <a class="btn btn-primary" href="<?= e($heroLink) ?>">Ver propuestas</a>
                <a class="btn btn-ghost" href="<?= e(base_url('cotizar.php')) ?>">Pedir cotización</a>
            </div>
            <div class="hero-stats">
                <div><strong><?= $publishedCount ?></strong><span>paquetes publicados</span></div>
                <div><strong><?= $destinationCount ?></strong><span>destinos disponibles</span></div>
                <div><strong>15</strong><span>años acompañándote</span></div>
            </div>
        </div>
        <div class="search-panel">
            <h2>¿A dónde querés ir?</h2>
            <form class="form-grid" method="get" action="<?= e(base_url('paquetes.php')) ?>">
                <div class="field full">
                    <label for="destino">Destino</label>
                    <select id="destino" name="destino">
                        <option value="">Todos los destinos</option>
                        <?php foreach ($destinations as $destination): ?>
                            <option value="<?= (int) $destination['ID_Destino'] ?>"><?= e($destination['NombreUbicacion']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="field">
                    <label for="desde">Fecha tentativa</label>
                    <input type="date" id="desde" name="desde" min="<?= e(date('Y-m-d')) ?>">
                </div>
                <div class="field">
                    <label for="personas">Pasajeros</label>
                    <select id="personas" name="personas">
                        <?php for ($i = 1; $i <= 10; $i++): ?>
                            <option value="<?= $i ?>"><?= $i ?> <?= $i === 1 ? 'pasajero' : 'pasajeros' ?></option>
                        <?php endfor; ?>
                    </select>
                </div>
                <div class="field full">
                    <button class="btn btn-teal btn-block" type="submit">Buscar viajes</button>
                </div>
            </form>
        </div>
    </div>
</section>

<section class="benefits">
    <div class="container benefit-grid">
        <div class="benefit-card">
            <span class="benefit-icon">01</span>
            <div><h3>Asesoramiento real</h3><p>Atención personalizada antes y durante el viaje.</p></div>
        </div>
        <div class="benefit-card">
            <span class="benefit-icon">02</span>
            <div><h3>Cuotas y financiación</h3><p>Opciones de pago adaptadas a tu presupuesto.</p></div>
        </div>
        <div class="benefit-card">
            <span class="benefit-icon">03</span>
            <div><h3>Operadores verificados</h3><p>Hoteles, transporte y servicios de confianza.</p></div>
        </div>
        <div class="benefit-card">
            <span class="benefit-icon">04</span>
            <div><h3>Asistencia al viajero</h3><p>Acompañamiento ante cualquier imprevisto.</p></div>
        </div>
    </div>
</section>

<?php if ($destinations !== []): ?>
<section class="section">
    <div class="container">
        <div class="section-head">
            <div>
                <span class="eyebrow">Destinos</span>
                <h2><?= e($sections['destinos']['Titulo'] ?? 'Destinos destacados') ?></h2>
                <p><?= e($sections['destinos']['Contenido'] ?? 'Conocé nuestras principales propuestas.') ?></p>
            </div>
            <a class="btn btn-outline" href="<?= e(base_url('destinos.php')) ?>">Ver todos los destinos</a>
        </div>
        <div class="destination-grid">
            <?php foreach ($destinations as $destination): ?>
                <?= public_destination_card($destination) ?>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<?php if ($packages !== []): ?>
<section class="section section-sand">
    <div class="container">
        <div class="section-head">
            <div>
                <span class="eyebrow">Paquetes</span>
                <h2>Próximas salidas</h2>
                <p>Propuestas listas para reservar, con coordinación integral y cupos actualizados.</p>
            </div>
            <a class="btn btn-outline" href="<?= e(base_url('paquetes.php')) ?>">Ver todos los paquetes</a>
        </div>
        <div class="package-grid">
            <?php foreach ($packages as $package): ?>
                <?= public_package_card($package) ?>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<?php if ($promotions !== []): ?>
<section class="section section-teal">
    <div class="container">
        <div class="section-head">
            <div>
                <span class="eyebrow">Beneficios</span>
                <h2>Promociones vigentes</h2>
                <p>Aprovechá descuentos por tiempo limitado en tus próximas vacaciones.</p>
            </div>
            <a class="btn btn-ghost" href="<?= e(base_url('ofertas.php')) ?>">Ver ofertas</a>
        </div>
        <div class="promo-grid">
            <?php foreach ($promotions as $promotion): ?>
                <article class="promo-card">
                    <span class="promo-value"><?= e($promotion['TipoDescuento'] === 'porcentaje' ? rtrim(rtrim(number_format((float) $promotion['ValorDescuento'], 2, ',', '.'), '0'), ',') . '% off' : money($promotion['ValorDescuento']) . ' off') ?></span>
                    <h3 class="mt-2"><?= e($promotion['Nombre']) ?></h3>
                    <p><?= e($promotion['Descripcion'] ?? 'Descuento especial en paquetes seleccionados.') ?></p>
                    <p class="promo-dates">Válida hasta el <?= e(fecha($promotion['FechaFin'])) ?></p>
                </article>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<section class="section">
    <div class="container split">
        <div class="split-media">
            <img src="<?= e(asset('img/iguazu.jpg')) ?>" alt="Cataratas del Iguazú" loading="lazy">
        </div>
        <div>
            <span class="eyebrow">Experiencia Aura</span>
            <h2>Tu viaje, a tu medida</h2>
            <p class="muted">Diseñamos cada itinerario según tus tiempos, intereses y presupuesto. Nos ocupamos de los detalles para que disfrutes de principio a fin.</p>
            <ul class="check-list">
                <li>Itinerarios flexibles y coordinados con operadores locales.</li>
                <li>Gestión de reservas, pagos y documentación en un solo lugar.</li>
                <li>Acompañamiento por WhatsApp antes, durante y después del viaje.</li>
            </ul>
            <a class="btn btn-teal" href="<?= e(base_url('contacto.php')) ?>">Hablemos de tu próximo viaje</a>
        </div>
    </div>
</section>

<?php if ($faqs !== []): ?>
<section class="section section-sand">
    <div class="container">
        <div class="section-head">
            <div>
                <span class="eyebrow">Antes de viajar</span>
                <h2>Preguntas frecuentes</h2>
                <p>Resolvemos las dudas más comunes para que reserves con tranquilidad.</p>
            </div>
            <a class="btn btn-outline" href="<?= e(base_url('faq.php')) ?>">Ver todas</a>
        </div>
        <div class="faq-list">
            <?php foreach ($faqs as $faq): ?>
                <details class="faq-item">
                    <summary><?= e($faq['Pregunta']) ?></summary>
                    <p><?= e($faq['Respuesta']) ?></p>
                </details>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<div class="container">
    <div class="quote-band" style="--band-image:url('<?= e(asset('img/rio.jpg')) ?>')">
        <div>
            <h2>¿Listo para tu próximo viaje?</h2>
            <p>Contanos qué tenés en mente y te armamos una propuesta sin compromiso.</p>
        </div>
        <a class="btn btn-primary" href="<?= e(base_url('cotizar.php')) ?>">Solicitar cotización</a>
    </div>
</div>
<?php require __DIR__ . '/includes/public_footer.php'; ?>
