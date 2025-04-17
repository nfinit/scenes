<?php

namespace Scenes\Controllers;

use Scenes\Models\User;
use Exception;

/**
 * AdminController
 * 
 * Handles administrative functions for the Scenes application
 */
class AdminController extends BaseController 
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
        
        // Ensure user is authenticated and is an admin
        if (!$this->isAuthenticated() || !$this->isAdmin()) {
            $this->forbidden('You do not have permission to access the admin area.');
            return;
        }
    }
    
    /**
     * Display admin dashboard
     * 
     * @return void
     */
    public function index() 
    {
        try {
            // Get counts for dashboard
            $db = $this->user->getDb();
            
            // Count users
            $stmt = $db->prepare("SELECT COUNT(*) as count FROM users WHERE active = 1");
            $stmt->execute();
            $userCount = $stmt->fetch()['count'];
            
            // Count collections
            $stmt = $db->prepare("SELECT COUNT(*) as count FROM collections");
            $stmt->execute();
            $collectionCount = $stmt->fetch()['count'];
            
            // Count assets
            $stmt = $db->prepare("SELECT COUNT(*) as count FROM assets");
            $stmt->execute();
            $assetCount = $stmt->fetch()['count'];
            
            // Prepare the data for the view
            $viewData = [
                'userCount' => $userCount,
                'collectionCount' => $collectionCount,
                'assetCount' => $assetCount
            ];
            
            $this->renderTemplate('admin/index.php', $viewData);
        } catch (Exception $e) {
            $this->handleError($e);
        }
    }
    
    /**
     * List users
     * 
     * @return void
     */
    public function listUsers() 
    {
        try {
            // Get all users
            $users = $this->user->all();
            
            // Prepare the data for the view
            $viewData = [
                'users' => $users
            ];
            
            $this->renderTemplate('admin/users/index.php', $viewData);
        } catch (Exception $e) {
            $this->handleError($e);
        }
    }
    
    /**
     * Display form to create a new user
     * 
     * @return void
     */
    public function createUser() 
    {
        try {
            // Get all available roles
            $roles = $this->user->getAllRoles();
            
            // Prepare the data for the view
            $viewData = [
                'roles' => $roles
            ];
            
            $this->renderTemplate('admin/users/create.php', $viewData);
        } catch (Exception $e) {
            $this->handleError($e);
        }
    }
    
    /**
     * Process form to store a new user
     * 
     * @return void
     */
    public function storeUser() 
    {
        try {
            // Validate form inputs
            $this->validateRequest([
                'username' => 'required|alpha_dash',
                'password' => 'required',
                'confirm_password' => 'required'
            ]);
            
            // Check if passwords match
            if ($_POST['password'] !== $_POST['confirm_password']) {
                throw new Exception('Passwords do not match.');
            }
            
            // Check if username is already taken
            $existingUser = $this->user->findByUsername($_POST['username']);
            if ($existingUser) {
                throw new Exception('Username is already taken.');
            }
            
            // Check if email is already taken (if provided)
            if (!empty($_POST['email'])) {
                $existingEmail = $this->user->findByEmail($_POST['email']);
                if ($existingEmail) {
                    throw new Exception('Email is already in use.');
                }
            }
            
            // Prepare user data
            $userData = [
                'username' => $_POST['username'],
                'password' => $_POST['password'],
                'email' => $_POST['email'] ?? null,
                'full_name' => $_POST['full_name'] ?? null,
                'active' => isset($_POST['active']) ? 1 : 0
            ];
            
            // Begin transaction
            $this->user->beginTransaction();
            
            // Create the user
            $userId = $this->user->createUser($userData);
            
            if (!$userId) {
                throw new Exception('Failed to create user.');
            }
            
            // Assign roles if specified
            if (isset($_POST['roles']) && is_array($_POST['roles'])) {
                foreach ($_POST['roles'] as $roleName) {
                    $this->user->assignRole($userId, $roleName);
                }
            }
            
            // Commit the transaction
            $this->user->commit();
            
            // Set flash message
            $this->setFlashMessage('success', 'User created successfully.');
            
            // Redirect to user list
            $this->redirect('/admin/users');
        } catch (Exception $e) {
            $this->user->rollback();
            $this->setFlashMessage('error', 'Failed to create user: ' . $e->getMessage());
            $this->redirect('/admin/users/create');
        }
    }
    
    /**
     * Display form to edit a user
     * 
     * @param int $id User ID
     * @return void
     */
    public function editUser($id) 
    {
        try {
            // Get the user
            $user = $this->user->find($id);
            
            if (!$user) {
                $this->notFound('User not found.');
                return;
            }
            
            // Get user roles
            $userRoles = $this->user->getRoles($id);
            $userRoleNames = array_column($userRoles, 'name');
            
            // Get all available roles
            $allRoles = $this->user->getAllRoles();
            
            // Prepare the data for the view
            $viewData = [
                'user' => $user,
                'userRoles' => $userRoleNames,
                'allRoles' => $allRoles
            ];
            
            $this->renderTemplate('admin/users/edit.php', $viewData);
        } catch (Exception $e) {
            $this->handleError($e);
        }
    }
    
    /**
     * Process form to update a user
     * 
     * @param int $id User ID
     * @return void
     */
    public function updateUser($id) 
    {
        try {
            // Validate form inputs
            $this->validateRequest([
                'username' => 'required|alpha_dash',
                'email' => ''  // Email is optional
            ]);
            
            // Get the user
            $user = $this->user->find($id);
            
            if (!$user) {
                $this->notFound('User not found.');
                return;
            }
            
            // Check if username is already taken (by another user)
            $existingUser = $this->user->findByUsername($_POST['username']);
            if ($existingUser && $existingUser['id'] != $id) {
                throw new Exception('Username is already taken.');
            }
            
            // Check if email is already taken (if provided)
            if (!empty($_POST['email'])) {
                $existingEmail = $this->user->findByEmail($_POST['email']);
                if ($existingEmail && $existingEmail['id'] != $id) {
                    throw new Exception('Email is already in use.');
                }
            }
            
            // Prepare user data
            $userData = [
                'username' => $_POST['username'],
                'email' => $_POST['email'] ?? null,
                'full_name' => $_POST['full_name'] ?? null,
                'active' => isset($_POST['active']) ? 1 : 0
            ];
            
            // Handle password change if provided
            if (!empty($_POST['password'])) {
                if ($_POST['password'] !== $_POST['confirm_password']) {
                    throw new Exception('Passwords do not match.');
                }
                
                $userData['password'] = $_POST['password'];
            }
            
            // Begin transaction
            $this->user->beginTransaction();
            
            // Update the user
            $updated = $this->user->updateUser($id, $userData);
            
            if (!$updated) {
                throw new Exception('Failed to update user.');
            }
            
            // Get current user roles
            $currentRoles = $this->user->getRoles($id);
            $currentRoleNames = array_column($currentRoles, 'name');
            
            // Get new roles
            $newRoleNames = isset($_POST['roles']) && is_array($_POST['roles']) 
                ? $_POST['roles'] 
                : [];
            
            // Remove roles that were unselected
            foreach ($currentRoleNames as $roleName) {
                if (!in_array($roleName, $newRoleNames)) {
                    $this->user->removeRole($id, $roleName);
                }
            }
            
            // Add newly selected roles
            foreach ($newRoleNames as $roleName) {
                if (!in_array($roleName, $currentRoleNames)) {
                    $this->user->assignRole($id, $roleName);
                }
            }
            
            // Commit the transaction
            $this->user->commit();
            
            // Set flash message
            $this->setFlashMessage('success', 'User updated successfully.');
            
            // Redirect to user list
            $this->redirect('/admin/users');
        } catch (Exception $e) {
            $this->user->rollback();
            $this->setFlashMessage('error', 'Failed to update user: ' . $e->getMessage());
            $this->redirect('/admin/users/edit/' . $id);
        }
    }
    
    /**
     * Confirm deletion of a user
     * 
     * @param int $id User ID
     * @return void
     */
    public function confirmDeleteUser($id) 
    {
        try {
            // Get the user
            $user = $this->user->find($id);
            
            if (!$user) {
                $this->notFound('User not found.');
                return;
            }
            
            // Prevent deletion of the default admin user
            if ($user['username'] === 'scenes') {
                $this->forbidden('The default administrator account cannot be deleted.');
                return;
            }
            
            // Prepare the data for the view
            $viewData = [
                'user' => $user
            ];
            
            $this->renderTemplate('admin/users/confirm_delete.php', $viewData);
        } catch (Exception $e) {
            $this->handleError($e);
        }
    }
    
    /**
     * Delete a user
     * 
     * @param int $id User ID
     * @return void
     */
    public function deleteUser($id) 
    {
        try {
            // Get the user
            $user = $this->user->find($id);
            
            if (!$user) {
                $this->notFound('User not found.');
                return;
            }
            
            // Prevent deletion of the default admin user
            if ($user['username'] === 'scenes') {
                $this->forbidden('The default administrator account cannot be deleted.');
                return;
            }
            
            // Prevent deletion of the current user
            if ($id == $_SESSION['user_id']) {
                $this->forbidden('You cannot delete your own account.');
                return;
            }
            
            // Delete the user
            $deleted = $this->user->delete($id);
            
            if (!$deleted) {
                throw new Exception('Failed to delete user.');
            }
            
            // Set flash message
            $this->setFlashMessage('success', 'User deleted successfully.');
            
            // Redirect to user list
            $this->redirect('/admin/users');
        } catch (Exception $e) {
            $this->setFlashMessage('error', 'Failed to delete user: ' . $e->getMessage());
            $this->redirect('/admin/users');
        }
    }
    
    /**
     * Manage IP whitelist
     * 
     * @return void
     */
    public function ipWhitelist() 
    {
        try {
            // Get all IP whitelist entries
            $whitelist = $this->user->getIpWhitelist();
            
            // Get current client IP
            $clientIp = $_SERVER['REMOTE_ADDR'];
            
            // Prepare the data for the view
            $viewData = [
                'whitelist' => $whitelist,
                'clientIp' => $clientIp
            ];
            
            $this->renderTemplate('admin/ip_whitelist.php', $viewData);
        } catch (Exception $e) {
            $this->handleError($e);
        }
    }
    
    /**
     * Add an IP to the whitelist
     * 
     * @return void
     */
    public function addIpToWhitelist() 
    {
        try {
            // Validate form inputs
            $this->validateRequest([
                'ip_address' => 'required'
            ]);
            
            // Add the IP to the whitelist
            $ipAddress = $_POST['ip_address'];
            $description = $_POST['description'] ?? null;
            
            $added = $this->user->addIpToWhitelist($ipAddress, $description);
            
            if (!$added) {
                throw new Exception('Failed to add IP to whitelist.');
            }
            
            // Set flash message
            $this->setFlashMessage('success', 'IP address added to whitelist successfully.');
            
            // Redirect back to IP whitelist
            $this->redirect('/admin/ip-whitelist');
        } catch (Exception $e) {
            $this->setFlashMessage('error', 'Failed to add IP to whitelist: ' . $e->getMessage());
            $this->redirect('/admin/ip-whitelist');
        }
    }
    
    /**
     * Remove an IP from the whitelist
     * 
     * @param int $id Whitelist entry ID
     * @return void
     */
    public function removeIpFromWhitelist($id) 
    {
        try {
            // Remove the IP from the whitelist
            $removed = $this->user->removeIpFromWhitelist($id);
            
            if (!$removed) {
                throw new Exception('Failed to remove IP from whitelist.');
            }
            
            // Set flash message
            $this->setFlashMessage('success', 'IP address removed from whitelist successfully.');
            
            // Redirect back to IP whitelist
            $this->redirect('/admin/ip-whitelist');
        } catch (Exception $e) {
            $this->setFlashMessage('error', 'Failed to remove IP from whitelist: ' . $e->getMessage());
            $this->redirect('/admin/ip-whitelist');
        }
    }
    
    /**
     * Toggle IP whitelist entry status
     * 
     * @param int $id Whitelist entry ID
     * @param int $status New status (0 or 1)
     * @return void
     */
    public function toggleIpWhitelistStatus($id, $status) 
    {
        try {
            // Update the status
            $updated = $this->user->setIpWhitelistStatus($id, $status == 1);
            
            if (!$updated) {
                throw new Exception('Failed to update IP whitelist status.');
            }
            
            // Set flash message
            $statusText = $status == 1 ? 'enabled' : 'disabled';
            $this->setFlashMessage('success', 'IP whitelist entry ' . $statusText . ' successfully.');
            
            // Redirect back to IP whitelist
            $this->redirect('/admin/ip-whitelist');
        } catch (Exception $e) {
            $this->setFlashMessage('error', 'Failed to update IP whitelist status: ' . $e->getMessage());
            $this->redirect('/admin/ip-whitelist');
        }
    }
    
    /**
     * Check if the current user is an administrator
     * 
     * @return bool True if the user is an admin
     */
    protected function isAdmin() 
    {
        return isset($_SESSION['is_admin']) && $_SESSION['is_admin'];
    }
}
