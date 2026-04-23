<?php
require_once __DIR__ . '/includes/app.php';
session_init();

if (!empty($_SESSION['user'])) {
    redirect_to(app_home_path());
}

redirect_to('/login.php');
