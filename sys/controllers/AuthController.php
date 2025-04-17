<?php

namespace Scenes\Controllers;

use Scenes\Models\User;
use Exception;

/**
 * AuthController
 * 
 * Handles authentication and user management operations
 */
class AuthController extends BaseController 
{
    /**
     * @var User User model instance
     */
    protected $user;
    
    /**
     * Constructor
     */
    public function __construct() 
    {
        parent::__construct();
        $this->user = new User();
    }
    
    /**
     * Display login form
     * 
     * @return void
     */
    public function login() 
    {
        // If already authenticated, redirect to home
        if ($this->isAuthenticated()) {
            $this->redirect('/');
            return;
        }
        
        // Get redirect URL if any
        $redirect = $_GET['redirect'] ?? '/';
        
        // Check if IP whitelist is enabled
        $isIpWhitelistingEnabled = $this->isIpWhitelistingEnabled();
        
        // If IP whitelisting is enabled, check if current IP is whitelisted
        $isIpWhitelisted = false;
        if ($isIpWhitelistingEnabled) {
            $isIpWhitelisted = $this->isIpWhitelisted();
        }
        
        // Get setup status
        $setupComplete = $this->user->isAdminInitialized();
        
        // Prepare the data for the view
        $viewData = [
            'redirect' => $redirect,
            'isIpWhitelistingEnabled' => $isIpWhitelistingEnabled,
            'isIpWhitelisted' => $isIpWhitelisted,
            'setupComplete' => $setupComplete
        ];
        
        $this->renderTemplate('auth/login.php', $viewData);
    }
    
    /**
     * Process login form
     * 
     * @return void
     */
    public function authenticate() 
    {
        // Get login data
        $username = $_POST['username'] ?? '';
        $password = $_POST['password'] ?? '';
        $redirect = $_POST['redirect'] ?? '/';
        
        // Check if IP whitelist is enabled
        $isIpWhitelistingEnabled = $this->isIpWhitelistingEnabled();
        
        // If IP whitelisting is enabled, check if current IP is whitelisted
        if ($isIpWhitelistingEnabled && !$this->isIpWhitelisted()) {
            $this->setFlashMessage('error', 'Your IP address is not whitelisted for access.');
            $this->redirect('/auth/login');
            return;
        }
        
        try {
            // Authenticate the user
            $user = $this->user->authenticate($username, $password);
            
            if (!$user) {
                $this->setFlashMessage('error', 'Invalid username or password.');
                $this->redirect('/auth/login?redirect=' . urlencode($redirect));
                return;
            }
            
            // Set session data
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            
            // Get user roles
            $roles = $this->user->getRoles($user['id']);
            $_SESSION['roles'] = array_column($roles, 'name');
            
            // Set admin flag if user has administrator role
            $_SESSION['is_admin'] = in_array('administrator', $_SESSION['roles']);
            
            // Set flash message
            $this->setFlashMessage('success', 'Login successful. Welcome, ' . $user['username'] . '!');
            
            // Redirect to the specified URL
            $this->redirect($redirect);
        } catch (Exception $e) {
            $this->setFlashMessage('error', 'Login failed: ' . $e->getMessage());
            $this->redirect('/auth/login?redirect=' . urlencode($redirect));
        }
    }
    
    /**
     * Log out the current user
     * 
     * @return void
     */
    public function logout() 
    {
        // Clear session data
        $_SESSION = [];
        
        // If a session cookie is used, destroy it
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params["path"],
                $params["domain"],
                $params["secure"],
                $params["httponly"]
            );
        }
        
        // Destroy the session
        session_destroy();
        
        // Set flash message (stored in a temporary cookie since session is destroyed)
        $this->setCookieFlashMessage('success', 'You have been logged out successfully.');
        
        // Redirect to the login page
        $this->redirect('/auth/login');
    }
    
    /**
     * Display setup form (for first-time admin account setup)
     * 
     * @return void
     */
    public function setup() 
    {
        // Check if admin is already initialized
        if ($this->user->isAdminInitialized()) {
            $this->setFlashMessage('error', 'Admin account is already initialized.');
            $this->redirect('/auth/login');
            return;
        }
        
        // Prepare the data for the view
        $viewData = [];
        
        $this->renderTemplate('auth/setup.php', $viewData);
    }
    
    /**
     * Process setup form
     * 
     * @return void
     */
    public function processSetup() 
    {
        // Check if admin is already initialized
        if ($this->user->isAdminInitialized()) {
            $this->setFlashMessage('error', 'Admin account is already initialized.');
            $this->redirect('/auth/login');
            return;
        }
        
        // Get setup data
        $password = $_POST['password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';
        
        // Validate passwords
        if ($password !== $confirmPassword) {
            $this->setFlashMessage('error', 'Passwords do not match.');
            $this->redirect('/auth/setup');
            return;
        }
        
        if (strlen($password) < 8) {
            $this->setFlashMessage('error', 'Password must be at least 8 characters long.');
            $this->redirect('/auth/setup');
            return;
        }
        
        try {
            // Initialize admin account
            $initialized = $this->user->initializeAdminAccount($password);
            
            if (!$initialized) {
                throw new Exception('Failed to initialize admin account.');
            }
            
            // Set flash message
            $this->setFlashMessage('success', 'Admin account initialized successfully. You can now log in using the username "scenes" and the password you just set.');
            
            // Redirect to the login page
            $this->redirect('/auth/login');
        } catch (Exception $e) {
            $this->setFlashMessage('error', 'Setup failed: ' . $e->getMessage());
            $this->redirect('/auth/setup');
        }
    }
    
    /**
     * Display form to change password
     * 
     * @return void
     */
    public function changePassword() 
    {
        // Check if user is authenticated
        if (!$this->isAuthenticated()) {
            $this->redirect('/auth/login?redirect=' . urlencode($_SERVER['REQUEST_URI']));
            return;
        }
        
        // Prepare the data for the view
        $viewData = [];
        
        $this->renderTemplate('auth/change_password.php', $viewData);
    }
    
    /**
     * Process form to change password
     * 
     * @return void
     */
    public function updatePassword() 
    {
        // Check if user is authenticated
        if (!$this->isAuthenticated()) {
            $this->redirect('/auth/login?redirect=' . urlencode($_SERVER['REQUEST_URI']));
            return;
        }
        
        // Get form data
        $currentPassword = $_POST['current_password'] ?? '';
        $newPassword = $_POST['new_password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';
        
        // Validate passwords
        if ($newPassword !== $confirmPassword) {
            $this->setFlashMessage('error', 'New passwords do not match.');
            $this->redirect('/auth/change-password');
            return;
        }
        
        if (strlen($newPassword) < 8) {
            $this->setFlashMessage('error', 'New password must be at least 8 characters long.');
            $this->redirect('/auth/change-password');
            return;
        }
        
        try {
            // Verify current password
            $userId = $_SESSION['user_id'];
            $passwordVerified = $this->user->verifyPassword($userId, $currentPassword);
            
            if (!$passwordVerified) {
                $this->setFlashMessage('error', 'Current password is incorrect.');
                $this->redirect('/auth/change-password');
                return;
            }
            
            // Update password
            $userData = [
                'password' => $newPassword
            ];
            
            $updated = $this->user->updateUser($userId, $userData);
            
            if (!$updated) {
                throw new Exception('Failed to update password.');
            }
            
            // Set flash message
            $this->setFlashMessage('success', 'Your password has been updated successfully.');
            
            // Redirect to the home page
            $this->redirect('/');
        } catch (Exception $e) {
            $this->setFlashMessage('error', 'Failed to update password: ' . $e->getMessage());
            $this->redirect('/auth/change-password');
        }
    }
    
    /**
     * Check if IP whitelisting is enabled
     * 
     * @return bool True if IP whitelisting is enabled
     */
    protected function isIpWhitelistingEnabled() 
    {
        // Check if there are any active IP whitelist entries
        $whitelist = $this->user->getIpWhitelist(true);
        return !empty($whitelist);
    }
    
    /**
     * Check if the current IP is whitelisted
     * 
     * @return bool True if current IP is whitelisted
     */
    protected function isIpWhitelisted() 
    {
        $ipAddress = $_SERVER['REMOTE_ADDR'];
        return $this->user->isIpWhitelisted($ipAddress);
    }
    
    /**
     * Set a flash message in a cookie (for use after session destruction)
     * 
     * @param string $type Message type (success, error, warning, info)
     * @param string $message Message content
     * @return void
     */
    protected function setCookieFlashMessage($type, $message) 
    {
        $flashMessage = json_encode([
            [
                'type' => $type,
                'message' => $message
            ]
        ]);
        
        setcookie(
            'flash_message',
            $flashMessage,
            time() + 60, // 1 minute expiration
            '/',
            '',
            false,
            true
        );
    }
}
