<?php
// ============================================================
//  GestionFeu — Configuration
// ============================================================

// Base de données
define('DB_HOST',    '127.0.0.1');
define('DB_NAME',    'gestionfeu');
define('DB_USER',    'root');
define('DB_PASS',    '');
define('DB_CHARSET', 'utf8mb4');

// Uploads — DIRECTORY_SEPARATOR assure la compatibilité Windows et Linux
define('UPLOAD_DIR', __DIR__ . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'plans' . DIRECTORY_SEPARATOR);
define('UPLOAD_URL', '/uploads/plans/');
define('UPLOAD_MAX', 10 * 1024 * 1024); // 10 Mo

// App
define('APP_NAME', 'GestionFeu');
define('BASE_URL',  '/gestionfeu');  // Adapter si le dossier s'appelle différemment
