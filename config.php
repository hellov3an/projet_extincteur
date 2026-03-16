<?php
// ============================================================
//  GestionFeu — Configuration
// ============================================================

// Base de données
define('DB_HOST',    'localhost');
define('DB_NAME',    'gestionfeu');
define('DB_USER',    'root');
define('DB_PASS',    '');
define('DB_CHARSET', 'utf8mb4');

// Uploads
define('UPLOAD_DIR', __DIR__ . '/uploads/plans/');
define('UPLOAD_URL', '/gestionfeu/uploads/plans/');
define('UPLOAD_MAX', 10 * 1024 * 1024); // 10 Mo

// App
define('APP_NAME', 'GestionFeu');
define('BASE_URL',  '/gestionfeu');
