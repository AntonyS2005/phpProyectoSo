<?php
require_once __DIR__ . '/includes/auth.php';
session_init();
logout();
header('Location: /login.php');
exit;
