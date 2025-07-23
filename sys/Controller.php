<?php
abstract class Controller {
    protected $request;
    protected $view;
    
    public function __construct(Request $request) {
        $this->request = $request;
        $this->view = new View();
    }
    
    protected function json($data, $status = 200) {
        http_response_code($status);
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }
    
    protected function redirect($url) {
        header("Location: " . BASE_URL . $url);
        exit;
    }
    
    protected function requireAuth() {
        if (!isset($_SESSION['user_id'])) {
            $this->redirect('/login');
        }
    }
    
    protected function requireApiKey() {
        $key = $_SERVER['HTTP_X_API_KEY'] ?? $this->request->getParam('api_key');
        
        if (!$key || $key !== API_KEY) {
            $this->json(['error' => 'Invalid API key'], 401);
        }
    }
}
