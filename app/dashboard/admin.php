<?php
require_once __DIR__ . '/../includes/app.php';
require_permission('usuarios', 'READ');
redirect_to('/dashboard/users.php');
