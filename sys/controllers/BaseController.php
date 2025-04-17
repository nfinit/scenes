<?php

namespace Scenes\Controllers;

use Exception;

/**
 * BaseController
 * 
 * Base controller that provides common functionality for all controllers
 */
abstract class BaseController 
{
    /**
     * @var string Path to views directory
     */
    protected $viewPath;
    
    /**
     * @var array Data to be shared with all views
     */
    protected $sharedViewData = [];
    
    /**
     * Constructor
     */
    public function __construct() 
    {
        // Set the view path
        $this->viewPath = dirname(dirname(__DIR__)) . '/sys/views/';
        
        // Initialize shared view data
        $this->initSharedViewData();
    }
    
    /**
     * Initialize data that will be shared with all views
     * 
     * @return void
     */
    protected function initSharedViewData() 
    {
        $this->sharedViewData = [
            'isAuthenticated' => $this->isAuthenticated(),
            'currentUser' => $this->getCurrentUser(),
            'flashMessages' => $this->getFlashMessages()
        ];
    }
    
    /**
     * Check if a user is authenticated
     * 
     * @return bool True if user is authenticated
     */
    protected function isAuthenticated() 
    {
        return isset($_SESSION['user_id']);
    }
    
    /**
     * Get the current authenticated user
     * 
     * @return array|null User data or null if not authenticated
     */
    protected function getCurrentUser() 
    {
        if ($this->isAuthenticated()) {
            // Get user data from the session or from the database
            $userId = $_SESSION['user_id'];
            
            // For simplicity, we'll just return the user ID here
            // In a real application, you might want to fetch the user from the database
            return [
                'id' => $userId,
                'username' => $_SESSION['username'] ?? 'Unknown'
            ];
        }
        
        return null;
    }
    
    /**
     * Check if the current user has a specific permission
     * 
     * @param string $permission Permission name to check
     * @return bool True if user has the permission
     */
    protected function hasPermission($permission) 
    {
        // For now, we'll just check if the user is an administrator
        // In a real application, you would implement a proper permission system
        if (!$this->isAuthenticated()) {
            return false;
        }
        
        // If the user is an administrator, they have all permissions
        if (isset($_SESSION['is_admin']) && $_SESSION['is_admin']) {
            return true;
        }
        
        // Check for specific permissions
        // This is just a placeholder; you would implement a proper permission check
        $userPermissions = $_SESSION['permissions'] ?? [];
        return in_array($permission, $userPermissions);
    }
    
    /**
     * Render a template with the given data
     * 
     * @param string $template Path to the template file (relative to view path)
     * @param array $data Data to pass to the template
     * @return void
     */
    protected function renderTemplate($template, array $data = []) 
    {
        // Combine shared data with template-specific data
        $viewData = array_merge($this->sharedViewData, $data);
        
        // Extract variables for use in the template
        extract($viewData);
        
        // Start output buffering
        ob_start();
        
        // Include the template
        $templatePath = $this->viewPath . $template;
        if (file_exists($templatePath)) {
            include $templatePath;
        } else {
            throw new Exception("Template not found: {$template}");
        }
        
        // Get the buffered content
        $content = ob_get_clean();
        
        // Output the content
        echo $content;
    }
    
    /**
     * Redirect to a URL
     * 
     * @param string $url URL to redirect to
     * @return void
     */
    protected function redirect($url) 
    {
        header("Location: {$url}");
        exit;
    }
    
    /**
     * Handle a 404 Not Found error
     * 
     * @param string $message Error message
     * @return void
     */
    protected function notFound($message = 'Page not found.') 
    {
        header('HTTP/1.0 404 Not Found');
        $this->renderTemplate('errors/404.php', ['message' => $message]);
        exit;
    }
    
    /**
     * Handle a 403 Forbidden error
     * 
     * @param string $message Error message
     * @return void
     */
    protected function forbidden($message = 'Access denied.') 
    {
        header('HTTP/1.0 403 Forbidden');
        $this->renderTemplate('errors/403.php', ['message' => $message]);
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
        
        // In development, show the error details
        $isDevelopment = true; // Set this based on your environment configuration
        
        if ($isDevelopment) {
            header('HTTP/1.1 500 Internal Server Error');
            $this->renderTemplate('errors/500_dev.php', ['exception' => $e]);
        } else {
            header('HTTP/1.1 500 Internal Server Error');
            $this->renderTemplate('errors/500.php', ['message' => 'An error occurred.']);
        }
        
        exit;
    }
    
    /**
     * Set a flash message
     * 
     * @param string $type Message type (success, error, warning, info)
     * @param string $message Message content
     * @return void
     */
    protected function setFlashMessage($type, $message) 
    {
        if (!isset($_SESSION['flash_messages'])) {
            $_SESSION['flash_messages'] = [];
        }
        
        $_SESSION['flash_messages'][] = [
            'type' => $type,
            'message' => $message
        ];
    }
    
    /**
     * Get and clear flash messages
     * 
     * @return array Flash messages
     */
    protected function getFlashMessages() 
    {
        $messages = $_SESSION['flash_messages'] ?? [];
        unset($_SESSION['flash_messages']);
        return $messages;
    }
    
    /**
     * Validate a request against a set of rules
     * 
     * @param array $rules Validation rules
     * @return bool True if validation passes
     * @throws Exception If validation fails
     */
    protected function validateRequest(array $rules) 
    {
        // This is a simplified validation system
        // In a real application, you would want to use a more robust validation library
        
        foreach ($rules as $field => $ruleString) {
            $fieldValue = $_POST[$field] ?? null;
            $ruleList = explode('|', $ruleString);
            
            foreach ($ruleList as $rule) {
                // Check for rule with parameters
                if (strpos($rule, ':') !== false) {
                    list($ruleName, $ruleParams) = explode(':', $rule, 2);
                    $ruleParams = explode(',', $ruleParams);
                } else {
                    $ruleName = $rule;
                    $ruleParams = [];
                }
                
                // Apply the rule
                switch ($ruleName) {
                    case 'required':
                        if (empty($fieldValue)) {
                            throw new Exception("The {$field} field is required.");
                        }
                        break;
                        
                    case 'alpha_dash':
                        if ($fieldValue && !preg_match('/^[a-zA-Z0-9_-]+$/', $fieldValue)) {
                            throw new Exception("The {$field} field may only contain letters, numbers, dashes, and underscores.");
                        }
                        break;
                        
                    case 'in':
                        if ($fieldValue && !in_array($fieldValue, $ruleParams)) {
                            throw new Exception("The {$field} field must be one of: " . implode(', ', $ruleParams));
                        }
                        break;
                        
                    case 'unique':
                        // This is a simplified example; in a real application, you would check the database
                        $table = $ruleParams[0] ?? null;
                        $exceptId = isset($ruleParams[1]) ? explode(',', $ruleParams[1]) : null;
                        
                        if ($fieldValue && $table) {
                            // For now, we'll just assume it's valid
                            // In a real application, you would check if the value is unique in the table
                        }
                        break;
                }
            }
        }
        
        return true;
    }
}
