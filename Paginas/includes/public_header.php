<?php
declare(strict_types=1);

$pageTitle = $pageTitle ?? APP_NAME . ' — Agencia de viajes';
$metaDescription = $metaDescription ?? 'Paquetes turísticos, destinos y asesoramiento personalizado. Viví tu próximo viaje con Aura Viajes.';
$activeNav = $activeNav ?? '';
$navItems = [
    'inicio' => ['label' => 'Inicio', 'href' => 'index.php'],
    'destinos' => ['label' => 'Destinos', 'href' => 'destinos.php'],
    'paquetes' => ['label' => 'Paquetes', 'href' => 'paquetes.php'],
    'ofertas' => ['label' => 'Ofertas', 'href' => 'ofertas.php'],
    'novedades' => ['label' => 'Novedades', 'href' => 'novedades.php'],
    'faq' => ['label' => 'Preguntas frecuentes', 'href' => 'faq.php'],
    'contacto' => ['label' => 'Contacto', 'href' => 'contacto.php'],
];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="<?= e($metaDescription) ?>">
    <title><?= e($pageTitle) ?></title>
    <link rel="stylesheet" href="<?= e(asset('css/public.css')) ?>">
</head>
<body class="<?= e($bodyClass ?? '') ?>">
<header class="site-header" id="site-header">
    <div class="container header-inner">
        <a class="brand" href="<?= e(base_url('index.php')) ?>" aria-label="Aura Viajes, inicio">
            <img src="<?= e(asset('img/logo.png')) ?>" alt="Aura Viajes" class="brand-logo">
            <span class="brand-text">Aura<span>Viajes</span></span>
        </a>
        <button class="nav-toggle" id="nav-toggle" aria-label="Abrir menú" aria-expanded="false">
            <span></span><span></span><span></span>
        </button>
        <nav class="main-nav" id="main-nav" aria-label="Navegación principal">
            <?php foreach ($navItems as $key => $item): ?>
                <a href="<?= e(base_url($item['href'])) ?>" class="<?= $activeNav === $key ? 'active' : '' ?>"><?= e($item['label']) ?></a>
            <?php endforeach; ?>
            <a class="nav-cta" href="<?= e(base_url('cotizar.php')) ?>">Pedir cotización</a>
            <a class="nav-admin" href="<?= e(base_url('login.php')) ?>" title="Acceso al sistema de gestión">Back Office</a>
        </nav>
    </div>
</header>
<main class="site-main">
<?= render_flashes() ?>
