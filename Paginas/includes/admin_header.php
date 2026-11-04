<?php
declare(strict_types=1);

$user = current_user();
$pageTitle = $pageTitle ?? 'Panel';
$activeSection = $activeSection ?? '';
$breadcrumbs = $breadcrumbs ?? [];
$pageActions = $pageActions ?? '';
$navGroups = [
    'Operación' => [
        ['key' => 'panel', 'label' => 'Panel', 'href' => 'admin/index.php', 'cap' => null],
        ['key' => 'clientes', 'label' => 'Clientes', 'href' => 'admin/clientes.php', 'cap' => 'ver_clientes'],
        ['key' => 'pasajeros', 'label' => 'Pasajeros', 'href' => 'admin/pasajeros.php', 'cap' => 'ver_clientes'],
        ['key' => 'reservas', 'label' => 'Reservas y expedientes', 'href' => 'admin/reservas.php', 'cap' => 'ver_reservas'],
        ['key' => 'pagos', 'label' => 'Pagos y cobranzas', 'href' => 'admin/pagos.php', 'cap' => 'ver_pagos'],
    ],
    'Productos' => [
        ['key' => 'paquetes', 'label' => 'Paquetes y salidas', 'href' => 'admin/paquetes.php', 'cap' => 'ver_paquetes'],
        ['key' => 'destinos', 'label' => 'Destinos', 'href' => 'admin/destinos.php', 'cap' => 'gestionar_paquetes'],
        ['key' => 'proveedores', 'label' => 'Proveedores', 'href' => 'admin/proveedores.php', 'cap' => 'gestionar_proveedores'],
        ['key' => 'hoteles', 'label' => 'Hoteles y servicios', 'href' => 'admin/hoteles.php', 'cap' => 'gestionar_proveedores'],
        ['key' => 'viajes', 'label' => 'Viajes y transporte', 'href' => 'admin/viajes.php', 'cap' => 'gestionar_proveedores'],
    ],
    'Gestión' => [
        ['key' => 'mensajes', 'label' => 'Consultas y cotizaciones', 'href' => 'admin/mensajes.php', 'cap' => 'gestionar_cotizaciones'],
        ['key' => 'contenido', 'label' => 'Contenido público', 'href' => 'admin/contenido.php', 'cap' => 'gestionar_contenido'],
        ['key' => 'empleados', 'label' => 'Empleados y roles', 'href' => 'admin/empleados.php', 'cap' => 'gestionar_empleados'],
        ['key' => 'reportes', 'label' => 'Reportes', 'href' => 'admin/reportes.php', 'cap' => 'ver_reportes'],
        ['key' => 'auditoria', 'label' => 'Auditoría', 'href' => 'admin/auditoria.php', 'cap' => 'ver_auditoria'],
    ],
];
$fullName = trim((string) ($user['Nombre'] ?? '') . ' ' . (string) ($user['Apellido'] ?? ''));
if ($fullName === '') {
    $fullName = (string) ($user['Email'] ?? 'Usuario');
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title><?= e($pageTitle) ?> · <?= e(APP_NAME) ?></title>
    <link rel="stylesheet" href="<?= e(asset('css/admin.css')) ?>">
</head>
<body class="admin-body">
<div class="admin-shell">
    <aside class="admin-sidebar" id="admin-sidebar">
        <div class="sidebar-brand">
            <a href="<?= e(base_url('admin/index.php')) ?>" class="brand brand-light">
                <img src="<?= e(asset('img/logo.png')) ?>" alt="" class="brand-logo">
                <span class="brand-text">Aura<span>Viajes</span></span>
            </a>
            <button class="sidebar-close" id="sidebar-close" aria-label="Cerrar menú">&times;</button>
        </div>
        <nav class="sidebar-nav" aria-label="Navegación del back office">
            <?php foreach ($navGroups as $group => $items): ?>
                <?php
                $visible = array_filter($items, static fn(array $item): bool => $item['cap'] === null || can($item['cap']));
                if ($visible === []) {
                    continue;
                }
                ?>
                <div class="nav-group">
                    <span class="nav-group-title"><?= e($group) ?></span>
                    <?php foreach ($visible as $item): ?>
                        <a class="nav-link <?= $activeSection === $item['key'] ? 'active' : '' ?>" href="<?= e(base_url($item['href'])) ?>">
                            <span class="nav-bullet" aria-hidden="true"></span>
                            <span><?= e($item['label']) ?></span>
                        </a>
                    <?php endforeach; ?>
                </div>
            <?php endforeach; ?>
        </nav>
        <div class="sidebar-footer">
            <a href="<?= e(base_url('index.php')) ?>" target="_blank" rel="noopener">Ver sitio público &nearr;</a>
            <span>v<?= e(APP_VERSION) ?></span>
        </div>
    </aside>

    <div class="admin-frame">
        <header class="admin-topbar">
            <button class="sidebar-toggle" id="sidebar-toggle" aria-label="Abrir menú">
                <span></span><span></span><span></span>
            </button>
            <div class="topbar-title">
                <?php if ($breadcrumbs !== []): ?>
                    <nav class="breadcrumbs" aria-label="Migas de pan">
                        <?php foreach ($breadcrumbs as $label => $href): ?>
                            <?php if ($href !== ''): ?>
                                <a href="<?= e(base_url($href)) ?>"><?= e($label) ?></a><span>/</span>
                            <?php else: ?>
                                <span><?= e($label) ?></span>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </nav>
                <?php endif; ?>
                <h1><?= e($pageTitle) ?></h1>
            </div>
            <div class="topbar-actions">
                <?= $pageActions ?>
                <div class="user-menu">
                    <button class="user-button" id="user-button" aria-haspopup="true" aria-expanded="false">
                        <span class="avatar"><?= e(initials($fullName)) ?></span>
                        <span class="user-meta">
                            <strong><?= e($fullName) ?></strong>
                            <small><?= e($user['primary_role'] ?? '') ?></small>
                        </span>
                    </button>
                    <div class="user-dropdown" id="user-dropdown">
                        <span class="dropdown-email"><?= e($user['Email'] ?? '') ?></span>
                        <a href="<?= e(base_url('admin/perfil.php')) ?>">Mi perfil</a>
                        <a href="<?= e(base_url('index.php')) ?>" target="_blank" rel="noopener">Abrir sitio</a>
                        <a class="danger" href="<?= e(base_url('logout.php')) ?>">Cerrar sesión</a>
                    </div>
                </div>
            </div>
        </header>
        <main class="admin-main">
            <div class="admin-content">
                <?= render_flashes() ?>
