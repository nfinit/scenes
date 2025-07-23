<?php
class App {
    private $router;
    private $request;
    
    public function __construct() {
        $this->request = new Request();
        $this->router = new Router($this->request);
    }
    
    public function run() {
        try {
            $this->router->dispatch();
        } catch (Exception $e) {
            $this->handleError($e);
        }
    }
    
    private function handleError($exception) {
        // In production, log this and show a friendly error page
        if (DEBUG) {
            echo '<pre>';
            echo 'Error: ' . $exception->getMessage() . "\n";
            echo 'File: ' . $exception->getFile() . ' (' . $exception->getLine() . ")\n\n";
            echo $exception->getTraceAsString();
            echo '</pre>';
        } else {
            header('HTTP/1.1 500 Internal Server Error');
            echo 'An error occurred. Please try again later.';
        }
    }
}
