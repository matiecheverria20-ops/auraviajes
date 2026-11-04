<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/public_data.php';

$destinationId = get_int('destino');
$categoryId = get_int('categoria');
$fromDate = get_string('desde');
$people = max(1, min(20, get_int('personas', 1)));

$where = ["p.Estado = 'publicado'"];
$params = [];
if ($destinationId > 0) {
    $where[] = 'EXISTS (SELECT 1 FROM paquete_destino pd WHERE pd.FK_ID_Destino = :destino AND pd.FK_ID_Paquete = p.ID_Paquete)';
    $params[':destino'] = $destinationId;
}
if ($categoryId > 0) {
    $where[] = 'EXISTS (SELECT 1 FROM paquete_categoria pc WHERE pc.FK_ID_CategoriaPaquete = :categoria AND pc.FK_ID_Paquete = p.ID_Paquete)';
    $params[':categoria'] = $categoryId;
}
if ($fromDate !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $fromDate) === 1) {
    $where[] = "EXISTS (SELECT 1 FROM salidas s WHERE s.FK_ID_Paquete = p.ID_Paquete AND s.FechaSalida >= :desde AND s.Estado = 'abierta' AND s.CuposDisponibles >= :personas)";
    $params[':desde'] = $fromDate;
    $params[':personas'] = $people;
} elseif ($people > 1) {
    $where[] = "EXISTS (SELECT 1 FROM salidas s WHERE s.FK_ID_Paquete = p.ID_Paquete AND s.FechaSalida >= CURDATE() AND s.Estado = 'abierta' AND s.CuposDisponibles >= :personas)";
    $params[':personas'] = $people;
}
$whereSql = ' WHERE ' . implode(' AND ', $where);

$total = (int) db_val('SELECT COUNT(*) FROM paquetes p' . $whereSql, $params, 0);
$pagination = pagination_data($total, 9);
$packages = db_all(
    public_packages_query() . $whereSql . '
     ORDER BY (ProximaSalida IS NULL), ProximaSalida ASC, p.ID_Paquete ASC
     LIMIT ' . (int) $pagination['per_page'] . ' OFFSET ' . (int) $pagination['offset'],
    $params
);
$destinations = db_all('SELECT ID_Destino, NombreUbicacion FROM destino ORDER BY NombreUbicacion ASC');
$categories = db_all('SELECT * FROM categorias_paquete ORDER BY Nombre ASC');

$pageTitle = 'Paquetes turísticos — ' . APP_NAME;
$metaDescription = 'Paquetes turísticos con salidas confirmadas, hoteles, traslados y coordinación integral.';
$activeNav = 'paquetes';
require __DIR__ . '/includes/public_header.php';
?>
<section class="page-hero">
    <div class="container">
        <span class="eyebrow">Paquetes</span>
        <h1>Viajes listos para disfrutar</h1>
        <p>Elegí tu destino, coordinamos los detalles y salí a vivir la experiencia.</p>
    </div>
</section>

<section class="section">
    <div class="container">
        <div class="filter-panel">
            <form method="get" action="<?= e(base_url('paquetes.php')) ?>">
                <div class="field">
                    <label for="destino">Destino</label>
                    <select id="destino" name="destino">
                        <option value="">Todos</option>
                        <?php foreach ($destinations as $destination): ?>
                            <option value="<?= (int) $destination['ID_Destino'] ?>" <?= $destinationId === (int) $destination['ID_Destino'] ? 'selected' : '' ?>><?= e($destination['NombreUbicacion']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="field">
                    <label for="categoria">Categoría</label>
                    <select id="categoria" name="categoria">
                        <option value="">Todas</option>
                        <?php foreach ($categories as $category): ?>
                            <option value="<?= (int) $category['ID_CategoriaPaquete'] ?>" <?= $categoryId === (int) $category['ID_CategoriaPaquete'] ? 'selected' : '' ?>><?= e($category['Nombre']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="field">
                    <label for="desde">Salida desde</label>
                    <input type="date" id="desde" name="desde" min="<?= e(date('Y-m-d')) ?>" value="<?= e($fromDate) ?>">
                </div>
                <div class="field">
                    <label for="personas">Pasajeros</label>
                    <select id="personas" name="personas">
                        <?php for ($i = 1; $i <= 10; $i++): ?>
                            <option value="<?= $i ?>" <?= $people === $i ? 'selected' : '' ?>><?= $i ?> <?= $i === 1 ? 'pasajero' : 'pasajeros' ?></option>
                        <?php endfor; ?>
                    </select>
                </div>
                <div class="filters-actions">
                    <button class="btn btn-teal" type="submit">Buscar</button>
                    <a class="btn btn-outline" href="<?= e(base_url('paquetes.php')) ?>">Limpiar</a>
                </div>
            </form>
        </div>

        <div class="results-bar">
            <span><?= $total ?> <?= $total === 1 ? 'paquete disponible' : 'paquetes disponibles' ?><?= $people > 1 ? ' para ' . $people . ' pasajeros' : '' ?></span>
        </div>

        <?php if ($packages === []): ?>
            <div class="empty-state">
                <h3>No encontramos paquetes con esos filtros</h3>
                <p>Probá ampliar la búsqueda o contanos qué viaje tenés en mente.</p>
                <div class="mt-2" style="display:flex;gap:.6rem;justify-content:center;flex-wrap:wrap;">
                    <a class="btn btn-outline" href="<?= e(base_url('paquetes.php')) ?>">Ver todos</a>
                    <a class="btn btn-primary" href="<?= e(base_url('cotizar.php')) ?>">Pedir cotización</a>
                </div>
            </div>
        <?php else: ?>
            <div class="package-grid">
                <?php foreach ($packages as $package): ?>
                    <?= public_package_card($package) ?>
                <?php endforeach; ?>
            </div>
            <div class="mt-4"><?= pagination_links($pagination) ?></div>
        <?php endif; ?>
    </div>
</section>

<div class="container">
    <div class="quote-band" style="--band-image:url('<?= e(asset('img/barriloche.jpg')) ?>')">
        <div>
            <h2>¿Buscás algo diferente?</h2>
            <p>Diseñamos paquetes a medida según tus fechas, intereses y presupuesto.</p>
        </div>
        <a class="btn btn-primary" href="<?= e(base_url('cotizar.php')) ?>">Solicitar propuesta</a>
    </div>
</div>
<?php require __DIR__ . '/includes/public_footer.php'; ?>
