<?php
// index.php - Página de inicio que redirige al login o dashboard

require_once 'config.php';
require_once 'auth.php';

// Si ya está autenticado, ir al dashboard
if (Auth::isLoggedIn()) {
    header("Location: dashboard.php");
    exit();
}

// Si no está autenticado, ir al login
header("Location: login.php");
exit();
?>