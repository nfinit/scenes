<?php
class Request {
    private $method;
    private $url;
    private $params = [];
    private $postData = [];
    
    public function __construct() {
        $this->method = $_SERVER['REQUEST_METHOD'];
        $this->url = $this->parseUrl();
        $this->postData = $_POST;
        
        // Handle JSON requests
        if ($this->isJson()) {
            $input = json_decode(file_get_contents('php://input'), true);
            if ($input) {
                $this->postData = $input;
            }
        }
    }
    
    private function parseUrl() {
        if (isset($_GET['url'])) {
            return filter_var(rtrim($_GET['url'], '/'), FILTER_SANITIZE_URL);
        }
        return '';
    }
    
    public function getMethod() {
        return $this->method;
    }
    
    public function getUrl() {
        return $this->url;
    }
    
    public function getSegments() {
        return $this->url ? explode('/', $this->url) : [];
    }
    
    public function getParam($key, $default = null) {
        return $this->params[$key] ?? $_GET[$key] ?? $default;
    }
    
    public function setParams($params) {
        $this->params = $params;
    }
    
    public function getPost($key = null, $default = null) {
        if ($key === null) {
            return $this->postData;
        }
        return $this->postData[$key] ?? $default;
    }
    
    public function isPost() {
        return $this->method === 'POST';
    }
    
    public function isJson() {
        return strpos($_SERVER['CONTENT_TYPE'] ?? '', 'application/json') !== false;
    }
    
    public function isApi() {
        $segments = $this->getSegments();
        return isset($segments[0]) && $segments[0] === 'api';
    }
}
