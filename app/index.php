<?php
require_once __DIR__ . '/includes/auth.php';
session_init();

if (!empty($_SESSION['user'])) {
    $rol = strtolower($_SESSION['user']['rol']);
    header("Location: /dashboard/{$rol}.php");
} else {
    header('Location: /login.php');
}
exit;
