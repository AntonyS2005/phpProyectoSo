<?php
require_once __DIR__ . '/../includes/app.php';
require_auth();
redirect_to(app_home_path());
