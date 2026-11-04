<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/public_data.php';

$id = get_int('id');
$package = $id > 0 ? get_package($id) : null;

if ($package === null || $package['Estado'] !== 'publicado') {
    http_response_code(404);
    $pageTitle = 'Paquete no encontrado — ' . APP_NAME;
    $activeNav = 'paquetes';
    require __DIR__ . '/includes/public_header.php';
    echo '<section class="section"><div class="container"><div class="empty-state"><h3>Paquete no disponible</h3><p>La propuesta que buscás no existe o ya no está publicada.</p><a class="btn btn-outline" href="' . e(base_url('paquetes.php')) . '">Ver todos los paquetes</a></div></div></section>';
    require __DIR__ . '/includes/public_footer.php';
    exit;
}

$destinations = package_destinations($id);
$itinerary = package_itinerary($id);
$services = package_services($id);
$categories = package_categories($id);
$departures = available_departures($id);
$price = package_current_price($id);
$mainDestination = $destinations[0]['NombreUbicacion'] ?? $package['Nombre'];

$included = array_values(array_filter($services, static fn (array $service): bool => (int) $service['Incluido'] === 1));
$notIncluded = array_values(array_filter($services, static fn (array $service): bool => (int) $service['Incluido'] !== 1));
$destinationIds = array_map(static fn (array $destination): int => (int) $destination['ID_Destino'], $destinations);

$related = db_all(
    public_packages_query() . " WHERE p.Estado = 'publicado' AND p.ID_Paquete <> :actual
       AND EXISTS (SELECT 1 FROM paquete_destino pd WHERE pd.FK_ID_Paquete = p.ID_Paquete AND pd.FK_ID_Destino IN (SELECT pd2.FK_ID_Destino FROM paquete_destino pd2 WHERE pd2.FK_ID_Paquete = :current))
     ORDER BY (ProximaSalida IS NULL), ProximaSalida ASC, p.Nombre ASC LIMIT 3",
    [':actual' => $id, ':current' => $id]
);
if ($related === [] && $destinationIds !== []) {
    $related = db_all(
        public_packages_query() . " WHERE p.Estado = 'publicado' AND p.ID_Paquete <> :id ORDER BY p.ID_Paquete DESC LIMIT 3",
        [':id' => $id]
    );
}

$pageTitle = $package['Nombre'] . ' — ' . APP_NAME;
$metaDescription = summary_text($package['Descripcion'] ?? ('Paquete ' . $package['Nombre'] . ' con Aura Viajes.'), 160);
$activeNav = 'paquetes';
require __DIR__ . '/includes/public_header.php';
?>
<section class="detail-hero" style="--detail-image:url('<?= e(destination_image($mainDestination)) ?>')">
    <div class="container">
        <div class="breadcrumb">
            <a href="<?= e(base_url('index.php')) ?>">Inicio</a><span>/</span>
            <a href="<?= e(base_url('paquetes.php')) ?>">Paquetes</a><span>/</span>
            <span><?= e($package['Nombre']) ?></span>
        </div>
        <span class="hero-badge"><?= (int) $package['DuracionDias'] ?> días · <?= e($mainDestination) ?></span>
        <h1><?= e($package['Nombre']) ?></h1>
        <p><?= e(summary_text($package['Descripcion'] ?? 'Una experiencia coordinada de principio a fin.', 220)) ?></p>
    </div>
</section>

<div class="container detail-grid">
    <div class="detail-main">
        <?php if (!empty($package['Descripcion'])): ?>
            <section>
                <span class="eyebrow">La propuesta</span>
                <h2>Sobre este viaje</h2>
                <p><?= nl2br(e($package['Descripcion'])) ?></p>
            </section>
        <?php endif; ?>

        <?php if ($destinations !== []): ?>
            <section>
                <h2>Destinos del paquete</h2>
                <div class="chip-row">
                    <?php foreach ($destinations as $destination): ?>
                        <a class="chip" href="<?= e(base_url('destino.php?id=' . (int) $destination['ID_Destino'])) ?>"><?= e($destination['NombreUbicacion']) ?><?= (int) $destination['Noches'] > 0 ? ' · ' . (int) $destination['Noches'] . ' noches' : '' ?></a>
                    <?php endforeach; ?>
                </div>
                <?php if (!empty($destinations[0]['Descripcion'])): ?>
                    <p class="mt-2"><?= nl2br(e($destinations[0]['Descripcion'])) ?></p>
                <?php endif; ?>
            </section>
        <?php endif; ?>

        <?php if ($itinerary !== []): ?>
            <section>
                <h2>Itinerario</h2>
                <div class="itinerary">
                    <?php foreach ($itinerary as $day): ?>
                        <div class="itinerary-item">
                            <span class="day-badge">Día<br><?= (int) $day['Dia'] ?></span>
                            <div>
                                <h3><?= e($day['Titulo']) ?></h3>
                                <?php if (!empty($day['Descripcion'])): ?>
                                    <p><?= e($day['Descripcion']) ?></p>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </section>
        <?php endif; ?>

        <?php if ($services !== []): ?>
            <section>
                <h2>Servicios</h2>
                <?php if ($included !== []): ?>
                    <h3 class="small" style="text-transform:uppercase;letter-spacing:.08em;color:var(--muted);">Incluye</h3>
                    <ul class="service-list">
                        <?php foreach ($included as $service): ?>
                            <li><?= e($service['Nombre']) ?></li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
                <?php if ($notIncluded !== []): ?>
                    <h3 class="small mt-3" style="text-transform:uppercase;letter-spacing:.08em;color:var(--muted);">No incluye</h3>
                    <ul class="service-list">
                        <?php foreach ($notIncluded as $service): ?>
                            <li><?= e($service['Nombre']) ?></li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </section>
        <?php endif; ?>

        <section>
            <h2>Salidas disponibles</h2>
            <?php if ($departures === []): ?>
                <div class="empty-state">
                    <h3>Sin fechas publicadas</h3>
                    <p>Consultanos por la próxima salida disponible para este paquete.</p>
                </div>
            <?php else: ?>
                <div class="departure-list">
                    <?php foreach ($departures as $departure): ?>
                        <div class="departure-item">
                            <div>
                                <strong><?= e(fecha($departure['FechaSalida'])) ?> al <?= e(fecha($departure['FechaRegreso'])) ?></strong>
                                <small><?= (int) $departure['CuposDisponibles'] ?> cupos disponibles</small>
                            </div>
                            <div style="display:flex;align-items:center;gap:.6rem;flex-wrap:wrap;">
                                <?php if ((int) $departure['CuposDisponibles'] <= 5): ?>
                                    <span class="badge badge-warning">Últimos cupos</span>
                                <?php endif; ?>
                                <a class="btn btn-outline btn-sm" href="<?= e(base_url('cotizar.php?paquete=' . $id . '&salida=' . (int) $departure['ID_Salida'])) ?>">Consultar</a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>

        <section>
            <h2>Condiciones generales</h2>
            <ul class="check-list">
                <li>La reserva se confirma con seña y disponibilidad de cupos al momento.</li>
                <li>Los precios son por persona en base doble, sujetos a cambios sin previo aviso.</li>
                <li>Documentación vigente obligatoria para todos los pasajeros.</li>
                <li>Las cancelaciones se rigen por las políticas del operador y del servicio contratado.</li>
            </ul>
        </section>
    </div>

    <aside class="detail-side">
        <span class="eyebrow">Desde</span>
        <?php if (!empty($price['has_discount'])): ?>
            <span class="price-old"><?= e(money($price['base'])) ?></span>
            <span class="badge badge-success"><?= e($price['discount_label']) ?></span>
        <?php endif; ?>
        <span class="price"><?= e(money($price['final'])) ?><small>por persona</small></span>
        <?php if (!empty($price['promo'])): ?>
            <p class="small muted">Promoción: <?= e($price['promo']['Nombre']) ?> · hasta <?= e(fecha($price['promo']['FechaFin'])) ?></p>
        <?php endif; ?>
        <div class="meta-list">
            <div><span>Duración</span><span><?= (int) $package['DuracionDias'] ?> días</span></div>
            <div><span>Destino principal</span><span><?= e($mainDestination) ?></span></div>
            <div><span>Código</span><span><?= e($package['Codigo']) ?></span></div>
            <?php if ($departures !== []): ?>
                <div><span>Próxima salida</span><span><?= e(fecha($departures[0]['FechaSalida'])) ?></span></div>
            <?php endif; ?>
        </div>
        <?php if ($categories !== []): ?>
            <div class="chip-row mb-0">
                <?php foreach ($categories as $category): ?>
                    <span class="chip"><?= e($category['Nombre']) ?></span>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        <a class="btn btn-primary btn-block mt-3" href="<?= e(base_url('cotizar.php?paquete=' . $id)) ?>">Pedir cotización</a>
        <a class="btn btn-outline btn-block mt-2" href="<?= e(base_url('contacto.php')) ?>">Hablar con un asesor</a>
        <p class="form-note text-center">Respuesta en menos de 24 horas hábiles.</p>
    </aside>
</div>

<?php if ($related !== []): ?>
<section class="section section-sand">
    <div class="container">
        <div class="section-head">
            <div>
                <span class="eyebrow">También te puede interesar</span>
                <h2>Otros paquetes</h2>
            </div>
            <a class="btn btn-outline" href="<?= e(base_url('paquetes.php')) ?>">Ver todos</a>
        </div>
        <div class="package-grid">
            <?php foreach ($related as $relatedPackage): ?>
                <?= public_package_card($relatedPackage) ?>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<div class="container">
    <div class="quote-band" style="--band-image:url('<?= e(destination_image($mainDestination)) ?>')">
        <div>
            <h2>¿Querés reservar <?= e($package['Nombre']) ?>?</h2>
            <p>Te enviamos la propuesta completa con fechas, servicios y formas de pago.</p>
        </div>
        <a class="btn btn-primary" href="<?= e(base_url('cotizar.php?paquete=' . $id)) ?>">Solicitar ahora</a>
    </div>
</div>
<?php require __DIR__ . '/includes/public_footer.php'; ?>
