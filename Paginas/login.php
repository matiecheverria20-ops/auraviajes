<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';
require_guest();

$error = '';
$email = '';

if (is_post()) {
    verify_csrf();
    $email = post_string('email');
    $password = (string) ($_POST['password'] ?? '');
    [$ok, $message] = login_user($email, $password);
    if ($ok) {
        $intended = $_SESSION['_intended'] ?? '';
        unset($_SESSION['_intended']);
        if (is_string($intended) && $intended !== '' && str_starts_with($intended, '/') && !str_starts_with($intended, '//')) {
            header('Location: ' . $intended);
            exit;
        }
        redirect('admin/index.php');
    }
    $error = $message;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title>Ingreso al sistema · <?= e(APP_NAME) ?></title>
    <link rel="stylesheet" href="<?= e(asset('css/admin.css')) ?>">
</head>
<body class="auth-body">
<main class="login-card">
    <section class="login-visual">
        <a class="brand brand-light" href="<?= e(base_url('index.php')) ?>">
            <img src="<?= e(asset('img/logo.png')) ?>" alt="" class="brand-logo">
            <span class="brand-text">Aura<span>Viajes</span></span>
        </a>
        <div>
            <span class="chip" style="background:rgba(255,255,255,.16);color:#ffd9cd;">Back Office</span>
            <h2>Todo tu negocio, en un solo lugar.</h2>
            <p>Gestioná clientes, reservas, cobros y contenidos con información centralizada y trazabilidad.</p>
            <ul class="login-points">
                <li>Clientes y pasajeros con historial completo</li>
                <li>Reservas, salidas y cupos controlados</li>
                <li>Pagos, saldos y reportes reales</li>
            </ul>
        </div>
        <span style="font-size:.78rem;color:rgba(255,255,255,.55);">&copy; <?= date('Y') ?> Aura Viajes S.A.</span>
    </section>
    <section class="login-form-wrap">
        <h1>Ingresar</h1>
        <p class="subtitle">Acceso exclusivo para personal autorizado de la agencia.</p>
        <?= render_flashes() ?>
        <?php if ($error !== ''): ?>
            <div class="alert alert-error"><?= e($error) ?></div>
        <?php endif; ?>
        <form method="post" action="<?= e(base_url('login.php')) ?>" autocomplete="on" novalidate>
            <?= csrf_field() ?>
            <div class="form-grid">
                <div class="field">
                    <label for="email">Correo electrónico</label>
                    <input type="email" id="email" name="email" value="<?= e($email) ?>" required autofocus autocomplete="username" placeholder="nombre@agencia.com">
                </div>
                <div class="field">
                    <label for="password">Contraseña</label>
                    <input type="password" id="password" name="password" required autocomplete="current-password" placeholder="Tu contraseña">
                </div>
                <button class="btn btn-primary btn-block" type="submit">Ingresar al panel</button>
            </div>
        </form>
        <div class="login-footer">
            <span>¿Problemas para ingresar? Contactá al administrador.</span>
            <a href="<?= e(base_url('index.php')) ?>">Volver al sitio</a>
        </div>
    </section>
</main>
<script src="<?= e(asset('js/admin.js')) ?>"></script>
</body>
</html>
