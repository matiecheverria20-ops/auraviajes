<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/data.php';
require_permission('gestionar_contenido');

$counts = [
    'banners' => (int) db_val('SELECT COUNT(*) FROM banners', [], 0),
    'banners_activos' => (int) db_val('SELECT COUNT(*) FROM banners WHERE Activo = 1', [], 0),
    'faqs' => (int) db_val('SELECT COUNT(*) FROM preguntas_frecuentes', [], 0),
    'secciones' => (int) db_val('SELECT COUNT(*) FROM secciones_landing', [], 0),
    'promociones' => (int) db_val("SELECT COUNT(*) FROM promociones WHERE Estado = 'activa'", [], 0),
];
$sections = db_all('SELECT * FROM secciones_landing ORDER BY Orden ASC LIMIT 5');
$recentPromotions = db_all('SELECT * FROM promociones ORDER BY FechaInicio DESC LIMIT 4');

$pageTitle = 'Contenido público';
$activeSection = 'contenido';
$breadcrumbs = ['Panel' => 'admin/index.php', 'Contenido' => ''];
require __DIR__ . '/../includes/admin_header.php';
?>
<section class="stat-grid">
    <div class="stat-card accent">
        <span class="stat-label">Banners</span>
        <span class="stat-value"><?= $counts['banners'] ?></span>
        <span class="stat-hint"><?= $counts['banners_activos'] ?> activos</span>
    </div>
    <div class="stat-card">
        <span class="stat-label">Preguntas frecuentes</span>
        <span class="stat-value"><?= $counts['faqs'] ?></span>
        <span class="stat-hint">Contenido de ayuda</span>
    </div>
    <div class="stat-card">
        <span class="stat-label">Secciones</span>
        <span class="stat-value"><?= $counts['secciones'] ?></span>
        <span class="stat-hint">Bloques de la landing</span>
    </div>
    <div class="stat-card coral">
        <span class="stat-label">Promociones activas</span>
        <span class="stat-value"><?= $counts['promociones'] ?></span>
        <span class="stat-hint">Vigentes y publicadas</span>
    </div>
</section>

<div class="content-grid">
    <a class="content-tile" href="<?= e(base_url('admin/banners.php')) ?>">
        <span class="content-tile-icon">◈</span>
        <h3>Banners</h3>
        <p>Imágenes y mensajes principales del hero de la landing.</p>
        <span class="small muted"><?= $counts['banners'] ?> banners</span>
    </a>
    <a class="content-tile" href="<?= e(base_url('admin/faqs.php')) ?>">
        <span class="content-tile-icon">?</span>
        <h3>Preguntas frecuentes</h3>
        <p>Respuestas rápidas para reducir consultas repetitivas.</p>
        <span class="small muted"><?= $counts['faqs'] ?> preguntas</span>
    </a>
    <a class="content-tile" href="<?= e(base_url('admin/secciones.php')) ?>">
        <span class="content-tile-icon">▤</span>
        <h3>Secciones</h3>
        <p>Textos institucionales y bloques informativos del sitio.</p>
        <span class="small muted"><?= $counts['secciones'] ?> secciones</span>
    </a>
    <a class="content-tile" href="<?= e(base_url('admin/promociones.php')) ?>">
        <span class="content-tile-icon">%</span>
        <h3>Promociones</h3>
        <p>Descuentos por tiempo limitado sobre paquetes publicados.</p>
        <span class="small muted"><?= $counts['promociones'] ?> activas</span>
    </a>
</div>

<div class="detail-grid">
    <div class="card">
        <div class="card-header"><h2>Secciones de la landing</h2><a class="small" href="<?= e(base_url('admin/secciones.php')) ?>">Editar</a></div>
        <div class="card-body">
            <?php foreach ($sections as $section): ?>
                <div class="flex-between" style="padding:.55rem 0;border-bottom:1px dashed var(--line);">
                    <div>
                        <strong class="small"><?= e($section['Titulo'] ?: $section['Nombre']) ?></strong>
                        <div class="small muted"><?= e(summary_text($section['Contenido'] ?? '', 90)) ?></div>
                    </div>
                    <span class="<?= e(badge_class($section['Activo'] ? 'activo' : 'inactivo')) ?>"><?= $section['Activo'] ? 'Visible' : 'Oculta' ?></span>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
    <div class="card">
        <div class="card-header"><h2>Últimas promociones</h2><a class="small" href="<?= e(base_url('admin/promociones.php')) ?>">Ver todas</a></div>
        <div class="card-body">
            <?php if ($recentPromotions === []): ?>
                <p class="muted small mb-0">No hay promociones cargadas.</p>
            <?php endif; ?>
            <?php foreach ($recentPromotions as $promo): ?>
                <div class="flex-between" style="padding:.55rem 0;border-bottom:1px dashed var(--line);">
                    <div>
                        <strong class="small"><?= e($promo['Nombre']) ?></strong>
                        <div class="small muted"><?= e(fecha($promo['FechaInicio'])) ?> al <?= e(fecha($promo['FechaFin'])) ?></div>
                    </div>
                    <span class="<?= e(badge_class($promo['Estado'])) ?>"><?= e(estado_label($promo['Estado'])) ?></span>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>
<?php require __DIR__ . '/../includes/admin_footer.php'; ?>
