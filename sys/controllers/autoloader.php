<?php
/**
 * Scenes Autoloader
 * 
 * A simple PSR-4 compatible autoloader for the Scenes application
 */

/**
 * Autoloader for Scenes classes
 * 
 * @param string $class The fully-qualified class name
 * @return void
 */
spl_autoload_register(function($class) {
    // Project-specific namespace prefix
    $prefix = 'Scenes\\';
    
    // Base directory for the namespace prefix
    $baseDir = dirname(__DIR__) . '/sys/';
    
    // Does the class use the namespace prefix?
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        // No, move to the next registered autoloader
        return;
    }
    
    // Get the relative class name
    $relativeClass = substr($class, $len);
    
    // Convert namespace separators to directory separators
    // Also convert StudlyCaps to lowercase-with-hyphens for directories
    $parts = explode('\\', $relativeClass);
    $lastPart = array_pop($parts);
    
    $parts = array_map(function($part) {
        return strtolower(preg_replace('/(?<!^)[A-Z]/', '-$0', $part));
    }, $parts);
    
    $parts[] = $lastPart;
    $relativeClass = implode('/', $parts);
    
    // Replace the class name part with its lowercase version
    $relativeClass = preg_replace('/\/([^\/]+)$/', '/\\1', $relativeClass);
    
    // Build the file path
    $file = $baseDir . str_replace('\\', '/', $relativeClass) . '.php';
    
    // If the file exists, require it
    if (file_exists($file)) {
        require $file;
    }
});
