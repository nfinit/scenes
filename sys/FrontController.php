<?php

namespace Scenes;

use Exception;

/**
 * FrontController
 * 
 * Handles routing and dispatching requests to the appropriate controller
 */
class FrontController 
{
    /**
     * @var array Route definitions
     */
    protected $routes = [];
    
    /**
     * @var string Default controller
     */
    protected $defaultController = 'Collection';
    
    /**
     * @var string Default action
     */
    protected $defaultAction = 'view';
    
    /**
     * @var string Controller namespace
     */
    protected $controllerNamespace = 'Scenes\\Controllers\\';
    
    /**
     * Constructor
     */
    public function __construct() 
    {
        $this->initRoutes();
    }
    
    /**
     * Initialize routes
     * 
     * @return void
     */
    protected function initRoutes() 
    {
        // Define routes
        $this->routes = [
            // Collection routes
            'collections/view/{slug}' => ['controller' => 'Collection', 'action' => 'view'],
            'collections/create' => ['controller' => 'Collection', 'action' => 'create'],
            'collections/store' => ['controller' => 'Collection', 'action' => 'store'],
            'collections/edit/{id}' => ['controller' => 'Collection', 'action' => 'edit'],
            'collections/update/{id}' => ['controller' => 'Collection', 'action' => 'update'],
            'collections/confirm-delete/{id}' => ['controller' => 'Collection', 'action' => 'confirmDelete'],
            'collections/delete/{id}' => ['controller' => 'Collection', 'action' => 'delete'],
            'collections/manage-assets/{id}' => ['controller' => 'Collection', 'action' => 'manageAssets'],
            'collections/add-asset/{collectionId}/{assetId}' => ['controller' => 'Collection', 'action' => 'addAsset'],
            'collections/remove-asset/{collectionId}/{assetId}' => ['controller' => 'Collection', 'action' => 'removeAsset'],
            'collections/edit-asset-metadata/{membershipId}' => ['controller' => 'Collection', 'action' => 'editAssetMetadata'],
            'collections/update-asset-metadata/{membershipId}' => ['controller' => 'Collection', 'action' => 'updateAssetMetadata'],
            'collections/create-asset-group/{collectionId}' => ['controller' => 'Collection', 'action' => 'createAssetGroup'],
            'collections/add-asset-to-group/{groupId}/{membershipId}' => ['controller' => 'Collection', 'action' => 'addAssetToGroup'],
            'collections/remove-asset-from-group/{membershipId}' => ['controller' => 'Collection', 'action' => 'removeAssetFromGroup'],
            'collections/clone/{id}' => ['controller' => 'Collection', 'action' => 'cloneCollection'],
            'collections/clone-with-assets/{id}' => ['controller' => 'Collection', 'action' => 'cloneCollectionWithAssets'],
            
            // Asset routes (to be implemented)
            'assets/upload' => ['controller' => 'Asset', 'action' => 'upload'],
            'assets/view/{id}' => ['controller' => 'Asset', 'action' => 'view'],
            'assets/edit/{id}' => ['controller' => 'Asset', 'action' => 'edit'],
            'assets/update/{id}' => ['controller' => 'Asset', 'action' => 'update'],
            'assets/delete/{id}' => ['controller' => 'Asset', 'action' => 'delete'],
            
            // Auth routes (to be implemented)
            'auth/login' => ['controller' => 'Auth', 'action' => 'login'],
            'auth/logout' => ['controller' => 'Auth', 'action' => 'logout'],
            
            // Admin routes (to be implemented)
            'admin/users' => ['controller' => 'Admin', 'action' => 'listUsers'],
            'admin/users/create' => ['controller' => 'Admin', 'action' => 'createUser'],
            'admin/users/edit/{id}' => ['controller' => 'Admin', 'action' => 'editUser'],
            
            // API routes (to be implemented)
            'api/collections/{slug}' => ['controller' => 'API', 'action' => 'getCollection'],
            'api/assets/{id}' => ['controller' => 'API', 'action' => 'getAsset'],
            
            // Home page (root collection)
            '' => ['controller' => 'Collection', 'action' => 'view', 'params' => ['slug' => 'root']]
        ];
    }
    
    /**
     * Process the request
     * 
     * @return void
     */
    public function processRequest() 
    {
        try {
            // Start a session
            if (session_status() === PHP_SESSION_NONE) {
                session_start();
            }
            
            // Get the URI path
            $uri = $this->getRequestPath();
            
            // Find matching route
            $route = $this->findRoute($uri);
            
            if (!$route) {
                $this->handleNotFound();
                return;
            }
            
            // Get controller and action from route
            $controllerName = $route['controller'] ?? $this->defaultController;
            $actionName = $route['action'] ?? $this->defaultAction;
            $params = $route['params'] ?? [];
            
            // Instantiate the controller
            $controllerClass = $this->controllerNamespace . $controllerName . 'Controller';
            
            if (!class_exists($controllerClass)) {
                $this->handleNotFound("Controller not found: {$controllerName}");
                return;
            }
            
            $controller = new $controllerClass();
            
            // Check if the action method exists
            if (!method_exists($controller, $actionName)) {
                $this->handleNotFound("Action not found: {$actionName}");
                return;
            }
            
            // Call the action method with parameters
            call_user_func_array([$controller, $actionName], $params);
        } catch (Exception $e) {
            $this->handleError($e);
        }
    }
    
    /**
     * Get the request path from the URI
     * 
     * @return string The request path
     */
    protected function getRequestPath() 
    {
        $uri = $_SERVER['REQUEST_URI'];
        
        // Remove query string
        if (($pos = strpos($uri, '?')) !== false) {
            $uri = substr($uri, 0, $pos);
        }
        
        // Remove base path
        $basePath = $this->getBasePath();
        if ($basePath !== '/') {
            $uri = substr($uri, strlen($basePath));
        }
        
        // Remove leading/trailing slashes
        return trim($uri, '/');
    }
    
    /**
     * Get the base path of the application
     * 
     * @return string The base path
     */
    protected function getBasePath() 
    {
        // This should be configured based on your deployment
        // For example, if your app is at /scenes/, the base path would be '/scenes'
        return '/scenes';
    }
    
    /**
     * Find a matching route for the given URI
     * 
     * @param string $uri The URI to match
     * @return array|null Matched route or null if no match
     */
    protected function findRoute($uri) 
    {
        // First, check for direct matches
        if (isset($this->routes[$uri])) {
            return $this->routes[$uri];
        }
        
        // If no direct match, check for parameterized routes
        foreach ($this->routes as $routePattern => $routeConfig) {
            // Convert route pattern to regex
            $pattern = preg_replace('/{([a-zA-Z0-9_]+)}/', '(?P<$1>[^/]+)', $routePattern);
            $pattern = '@^' . $pattern . '$@';
            
            if (preg_match($pattern, $uri, $matches)) {
                // Extract parameters
                $params = [];
                foreach ($matches as $key => $value) {
                    if (is_string($key)) {
                        $params[$key] = $value;
                    }
                }
                
                // Merge with any predefined parameters
                $routeConfig['params'] = array_merge($routeConfig['params'] ?? [], $params);
                
                return $routeConfig;
            }
        }
        
        return null;
    }
    
    /**
     * Handle a 404 Not Found error
     * 
     * @param string $message Error message
     * @return void
     */
    protected function handleNotFound($message = 'Page not found.') 
    {
        header('HTTP/1.0 404 Not Found');
        
        // Check if there's an error view
        $errorViewPath = dirname(__DIR__) . '/sys/views/errors/404.php';
        if (file_exists($errorViewPath)) {
            include $errorViewPath;
        } else {
            echo '<h1>404 Not Found</h1>';
            echo '<p>' . htmlspecialchars($message) . '</p>';
        }
        
        exit;
    }
    
    /**
     * Handle an exception
     * 
     * @param Exception $e The exception
     * @return void
     */
    protected function handleError(Exception $e) 
    {
        // Log the error
        error_log($e->getMessage());
        
        header('HTTP/1.1 500 Internal Server Error');
        
        // In development, show the error details
        $isDevelopment = true; // Set this based on your environment configuration
        
        if ($isDevelopment) {
            echo '<h1>500 Internal Server Error</h1>';
            echo '<h2>' . htmlspecialchars($e->getMessage()) . '</h2>';
            echo '<pre>' . htmlspecialchars($e->getTraceAsString()) . '</pre>';
        } else {
            // Check if there's an error view
            $errorViewPath = dirname(__DIR__) . '/sys/views/errors/500.php';
            if (file_exists($errorViewPath)) {
                include $errorViewPath;
            } else {
                echo '<h1>500 Internal Server Error</h1>';
                echo '<p>An error occurred. Please try again later.</p>';
            }
        }
        
        exit;
    }
}
