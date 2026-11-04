<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/public_data.php';

$id = get_int('id');
$destination = $id > 0 ? public_destination($id) : null;

if ($destination === null) {
    http_response_code(404);
    $pageTitle = 'Destino no encontrado — ' . APP_NAME;
    $activeNav = 'destinos';
    require __DIR__ . '/includes/public_header.php';
    echo '<section class="section"><div class="container"><div class="empty-state"><h3>Destino no encontrado</h3><p>El destino que buscás no existe o ya no está disponible.</p><a class="btn btn-outline" href="' . e(base_url('destinos.php')) . '">Ver todos los destinos</a></div></div></section>';
    require __DIR__ . '/includes/public_footer.php';
    exit;
}

$packages = db_all(
    public_packages_query() . " WHERE p.Estado = 'publicado'
       AND EXISTS (SELECT 1 FROM paquete_destino pd WHERE pd.FK_ID_Destino = :destino AND pd.FK_ID_Paquete = p.ID_Paquete)
     ORDER BY (ProximaSalida IS NULL), ProximaSalida ASC, p.Nombre ASC",
    [':destino' => $id]
);

$related = db_all(
    "SELECT d.*, di.Descripcion,
            (SELECT c.Nombre FROM destino_categoria dc INNER JOIN categorias_destino c ON c.ID_CategoriaDestino = dc.FK_ID_CategoriaDestino WHERE dc.FK_ID_Destino = d.ID_Destino ORDER BY dc.ID_DestinoCategoria LIMIT 1) AS CategoriaNombre,
            (SELECT COUNT(DISTINCT pd.FK_ID_Paquete) FROM paquete_destino pd INNER JOIN paquetes p ON p.ID_Paquete = pd.FK_ID_Paquete WHERE pd.FK_ID_Destino = d.ID_Destino AND p.Estado = 'publicado') AS PaquetesCount
       FROM destino d
       LEFT JOIN destino_informacion di ON di.FK_ID_Destino = d.ID_Destino
      WHERE d.ID_Destino <> :id
        AND EXISTS (SELECT 1 FROM destino_categoria dc INNER JOIN destino_categoria dc2 ON dc2.FK_ID_CategoriaDestino = dc.FK_ID_CategoriaDestino WHERE dc.FK_ID_Destino = d.ID_Destino AND dc2.FK_ID_Destino = :actual)
      ORDER BY d.NombreUbicacion ASC LIMIT 3",
    [':id' => $id, ':actual' => $id]
);

$pageTitle = $destination['NombreUbicacion'] . ' — ' . APP_NAME;
$metaDescription = summary_text($destination['Descripcion'] ?? ('Descubrí ' . $destination['NombreUbicacion'] . ' con Aura Viajes.'), 160);
$activeNav = 'destinos';
require __DIR__ . '/includes/public_header.php';
?>
<section class="detail-hero" style="--detail-image:url('<?= e(destination_image($destination['NombreUbicacion'])) ?>')">
    <div class="container">
        <div class="breadcrumb">
            <a href="<?= e(base_url('index.php')) ?>">Inicio</a><span>/</span>
            <a href="<?= e(base_url('destinos.php')) ?>">Destinos</a><span>/</span>
            <span><?= e($destination['NombreUbicacion']) ?></span>
        </div>
        <?php if (!empty($destination['CategoriaNombre'])): ?>
            <span class="hero-badge"><?= e($destination['CategoriaNombre']) ?></span>
        <?php endif; ?>
        <h1><?= e($destination['NombreUbicacion']) ?></h1>
        <p><?= e(summary_text($destination['Descripcion'] ?? 'Un destino para descubrir a tu ritmo.', 200)) ?></p>
    </div>
</section>

<div class="container detail-grid">
    <div class="detail-main">
        <?php if (!empty($destination['Descripcion'])): ?>
            <section>
                <span class="eyebrow">Sobre el destino</span>
                <h2>Qué vas a encontrar</h2>
                <p><?= nl2br(e($destination['Descripcion'])) ?></p>
            </section>
        <?php endif; ?>

        <?php if (!empty($destination['Recomendaciones'])): ?>
            <section>
                <h2>Recomendaciones</h2>
                <p><?= nl2br(e($destination['Recomendaciones'])) ?></p>
            </section>
        <?php endif; ?>

        <section>
            <div class="section-head">
                <div>
                    <span class="eyebrow">Propuestas</span>
                    <h2>Paquetes a este destino</h2>
                </div>
            </div>
            <?php if ($packages === []): ?>
                <div class="empty-state">
                    <h3>Sin salidas publicadas por ahora</h3>
                    <p>Consultanos y armamos una propuesta a medida para <?= e($destination['NombreUbicacion']) ?>.</p>
                    <a class="btn btn-primary" href="<?= e(base_url('cotizar.php?destino=' . urlencode($destination['NombreUbicacion']))) ?>">Solicitar cotización</a>
                </div>
            <?php else: ?>
                <div class="package-grid">
                    <?php foreach ($packages as $package): ?>
                        <?= public_package_card($package) ?>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>
    </div>

    <aside class="detail-side">
        <h2>Datos útiles</h2>
        <div class="meta-list">
            <div><span>Destino</span><span><?= e($destination['NombreUbicacion']) ?></span></div>
            <div><span>Paquetes disponibles</span><span><?= (int) $destination['PaquetesCount'] ?></span></div>
            <?php if (!empty($destination['Clima'])): ?>
                <div><span>Clima</span><span><?= e($destination['Clima']) ?></span></div>
            <?php endif; ?>
            <?php if (!empty($destination['Moneda'])): ?>
                <div><span>Moneda</span><span><?= e($destination['Moneda']) ?></span></div>
            <?php endif; ?>
        </div>
        <a class="btn btn-primary btn-block" href="<?= e(base_url('cotizar.php?destino=' . urlencode($destination['NombreUbicacion']))) ?>">Pedir cotización</a>
        <a class="btn btn-outline btn-block mt-2" href="<?= e(base_url('contacto.php')) ?>">Hablar con un asesor</a>
    </aside>
</div>

<?php if ($related !== []): ?>
<section class="section section-sand">
    <div class="container">
        <div class="section-head">
            <div>
                <span class="eyebrow">Seguí explorando</span>
                <h2>Destinos relacionados</h2>
            </div>
            <a class="btn btn-outline" href="<?= e(base_url('destinos.php')) ?>">Ver todos</a>
        </div>
        <div class="destination-grid">
            <?php foreach ($related as $destinationCard): ?>
                <?= public_destination_card($destinationCard) ?>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>
<?php require __DIR__ . '/includes/public_footer.php'; ?>
