<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';

logout_user();
session_start();
session_regenerate_id(true);
flash('success', 'Tu sesión se cerró correctamente.');
redirect('login.php');
