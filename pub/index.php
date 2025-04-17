<?php
/**
 * Scenes - Universal Online Hierarchical Data Album
 * 
 * Entry point for all requests
 */

// Define application path
define('APP_PATH', dirname(__DIR__));

// Enable error reporting in development
$isDevelopment = true; // Change this based on your environment
if ($isDevelopment) {
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);
}

// Load the autoloader
require_once APP_PATH . '/sys/autoload.php';

// Initialize and process the request
$frontController = new Scenes\FrontController();
$frontController->processRequest();
