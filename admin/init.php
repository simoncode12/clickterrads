<?php

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

session_regenerate_id();

require_once __DIR__ . '/../config/database.php';

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/settings.php';
?>
