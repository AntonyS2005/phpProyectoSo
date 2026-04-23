<?php
require_once __DIR__ . '/../includes/app.php';
require_permission('reportes', 'READ');
redirect_to('/dashboard/overview.php');
