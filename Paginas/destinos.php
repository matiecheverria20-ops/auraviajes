<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/public_data.php';

$search = trim(get_string('q'));
$categoryId = get_int('categoria');

$where = [];
$params = [];
if ($search !== '') {
    $where[] = '(d.NombreUbicacion LIKE :search OR di.Descripcion LIKE :search)';
    $params[':search'] = '%' . $search . '%';
}
if ($categoryId > 0) {
    $where[] = 'EXISTS (SELECT 1 FROM destino_categoria dc2 WHERE dc2.FK_ID_Destino = d.ID_Destino AND dc2.FK_ID_CategoriaDestino = :categoria)';
    $params[':categoria'] = $categoryId;
}
$whereSql = $where !== [] ? ' WHERE ' . implode(' AND ', $where) : '';

$total = (int) db_val(
    'SELECT COUNT(*) FROM destino d LEFT JOIN destino_informacion di ON di.FK_ID_Destino = d.ID_Destino' . $whereSql,
    $params,
    0
);
$pagination = pagination_data($total, 9);
$destinations = db_all(
    "SELECT d.*, di.Descripcion,
            (SELECT c.Nombre FROM destino_categoria dc INNER JOIN categorias_destino c ON c.ID_CategoriaDestino = dc.FK_ID_CategoriaDestino WHERE dc.FK_ID_Destino = d.ID_Destino ORDER BY dc.ID_DestinoCategoria LIMIT 1) AS CategoriaNombre,
            (SELECT COUNT(DISTINCT pd.FK_ID_Paquete) FROM paquete_destino pd INNER JOIN paquetes p ON p.ID_Paquete = pd.FK_ID_Paquete WHERE pd.FK_ID_Destino = d.ID_Destino AND p.Estado = 'publicado') AS PaquetesCount
       FROM destino d
       LEFT JOIN destino_informacion di ON di.FK_ID_Destino = d.ID_Destino"
    . $whereSql
    . ' ORDER BY PaquetesCount DESC, d.NombreUbicacion ASC LIMIT ' . (int) $pagination['per_page'] . ' OFFSET ' . (int) $pagination['offset'],
    $params
);
$categories = db_all('SELECT * FROM categorias_destino ORDER BY Nombre ASC');

$pageTitle = 'Destinos — ' . APP_NAME;
$metaDescription = 'Explorá los destinos disponibles y encontrá el próximo lugar para visitar con Aura Viajes.';
$activeNav = 'destinos';
require __DIR__ . '/includes/public_header.php';
?>
<section class="page-hero">
    <div class="container">
        <span class="eyebrow">Destinos</span>
        <h1>Lugares que se quedan con vos</h1>
        <p>Naturaleza, cultura y gastronomía en propuestas pensadas para cada estilo de viaje.</p>
    </div>
</section>

<section class="section">
    <div class="container">
        <div class="filter-panel">
            <form method="get" action="<?= e(base_url('destinos.php')) ?>">
                <div class="field">
                    <label for="q">Buscar destino</label>
                    <input type="search" id="q" name="q" value="<?= e($search) ?>" placeholder="Ciudad, provincia...">
                </div>
                <div class="field">
                    <label for="categoria">Categoría</label>
                    <select id="categoria" name="categoria">
                        <option value="">Todas</option>
                        <?php foreach ($categories as $category): ?>
                            <option value="<?= (int) $category['ID_CategoriaDestino'] ?>" <?= $categoryId === (int) $category['ID_CategoriaDestino'] ? 'selected' : '' ?>><?= e($category['Nombre']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="filters-actions">
                    <button class="btn btn-teal" type="submit">Filtrar</button>
                    <a class="btn btn-outline" href="<?= e(base_url('destinos.php')) ?>">Limpiar</a>
                </div>
            </form>
        </div>

        <div class="results-bar">
            <span><?= $total ?> <?= $total === 1 ? 'destino encontrado' : 'destinos encontrados' ?></span>
        </div>

        <?php if ($destinations === []): ?>
            <div class="empty-state">
                <h3>No encontramos destinos</h3>
                <p>Probá con otra búsqueda o mirá todos los destinos disponibles.</p>
                <a class="btn btn-outline" href="<?= e(base_url('destinos.php')) ?>">Ver todos</a>
            </div>
        <?php else: ?>
            <div class="destination-grid">
                <?php foreach ($destinations as $destination): ?>
                    <?= public_destination_card($destination) ?>
                <?php endforeach; ?>
            </div>
            <div class="mt-4"><?= pagination_links($pagination) ?></div>
        <?php endif; ?>
    </div>
</section>

<div class="container">
    <div class="quote-band" style="--band-image:url('<?= e(asset('img/salta.jpeg')) ?>')">
        <div>
            <h2>¿No encontrás tu destino ideal?</h2>
            <p>Armamos propuestas a medida en Argentina y el exterior.</p>
        </div>
        <a class="btn btn-primary" href="<?= e(base_url('cotizar.php')) ?>">Contanos tu idea</a>
    </div>
</div>
<?php require __DIR__ . '/includes/public_footer.php'; ?>
