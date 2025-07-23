<?php
class Router {
    private $request;
    private $controller = 'Home';
    private $action = 'index';
    private $params = [];
    
    public function __construct(Request $request) {
        $this->request = $request;
    }
    
    public function dispatch() {
        $segments = $this->request->getSegments();
        
        // Handle API routes separately
        if ($this->request->isApi()) {
            $this->handleApiRoute($segments);
            return;
        }
        
        // Convention-based routing: /controller/action/param1/param2...
        if (!empty($segments[0])) {
            $this->controller = ucfirst($segments[0]);
        }
        
        if (!empty($segments[1])) {
            $this->action = $segments[1];
        }
        
        // Remaining segments become parameters
        if (count($segments) > 2) {
            $this->params = array_slice($segments, 2);
        }
        
        $this->request->setParams($this->params);
        
        // For hierarchical pages with slugs, check if it's a page first
        if (!$this->loadController()) {
            // Try to load as a page slug
            $this->loadPageBySlug(implode('/', $segments));
        }
    }
    
    private function loadController() {
        $controllerClass = $this->controller . 'Controller';
        $controllerFile = CONTROLLERS . $controllerClass . '.php';
        
        if (!file_exists($controllerFile)) {
            return false;
        }
        
        $controller = new $controllerClass($this->request);
        
        if (!method_exists($controller, $this->action)) {
            throw new Exception("Method {$this->action} not found in controller {$controllerClass}");
        }
        
        call_user_func_array([$controller, $this->action], $this->params);
        return true;
    }
    
    private function loadPageBySlug($slug) {
        // Fall back to PageController for dynamic pages
        $controller = new PageController($this->request);
        $controller->show($slug);
    }
    
    private function handleApiRoute($segments) {
        array_shift($segments); // Remove 'api'
        
        $this->controller = 'Api';
        $this->action = !empty($segments[0]) ? $segments[0] : 'index';
        
        if (count($segments) > 1) {
            $this->params = array_slice($segments, 1);
        }
        
        $this->request->setParams($this->params);
        $this->loadController();
    }
}
