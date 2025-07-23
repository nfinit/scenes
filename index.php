<?php
session_start();

// Define paths
define('ROOT', __DIR__ . '/');
define('APP', ROOT . 'sys/');
define('CONFIG', ROOT . 'config/');
define('CONTROLLERS', ROOT . 'controllers/');
define('MODELS', ROOT . 'models/');
define('VIEWS', ROOT . 'views/');

// Autoloader
spl_autoload_register(function ($class) {
    $paths = [APP, CONTROLLERS, MODELS];
    foreach ($paths as $path) {
        $file = $path . $class . '.php';
        if (file_exists($file)) {
            require_once $file;
            return;
        }
    }
});

// Load config
require_once CONFIG . 'config.php';

// Initialize and run application
$app = new App();
$app->run();
?>
