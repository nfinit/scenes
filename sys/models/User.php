<?php

namespace Scenes\Models;

use PDO;
use PDOException;
use Exception;

class User extends BaseModel 
{
    /**
     * The table associated with the model
     */
    protected $table = 'users';
    
    /**
     * The columns that can be filled via mass assignment
     */
    protected $fillable = [
        'username', 'email', 'full_name', 'active'
    ];
    
    /**
     * Find a user by username
     * 
     * @param string $username The username to search for
     * @return array|null User data or null if not found
     */
    public function findByUsername($username) 
    {
        return $this->where('username', $username)->first();
    }
    
    /**
     * Find a user by email
     * 
     * @param string $email The email to search for
     * @return array|null User data or null if not found
     */
    public function findByEmail($email) 
    {
        return $this->where('email', $email)->first();
    }
    
    /**
     * Create a new user with password
     * 
     * @param array $userData User data including 'password'
     * @return int|bool User ID or false on failure
     */
    public function createUser(array $userData) 
    {
        try {
            $this->beginTransaction();
            
            // Extract password from user data
            $password = $userData['password'] ?? null;
            if (!$password) {
                throw new Exception("Password is required");
            }
            unset($userData['password']);
            
            // Hash the password
            $passwordHash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
            
            // Set the password hash
            $userData['password_hash'] = $passwordHash;
            
            // Create the user
            $userId = $this->create($userData);
            
            // Assign the default 'user' role
            if ($userId) {
                $this->assignRole($userId, 'user');
            }
            
            $this->commit();
            return $userId;
        } catch (Exception $e) {
            $this->rollback();
            $this->handleError($e);
            return false;
        }
    }
    
    /**
     * Update a user, optionally with a new password
     * 
     * @param int $userId User ID
     * @param array $userData User data, may include 'password' to update it
     * @return bool Success status
     */
    public function updateUser($userId, array $userData) 
    {
        try {
            $this->beginTransaction();
            
            // Extract password if present
            $password = null;
            if (isset($userData['password'])) {
                $password = $userData['password'];
                unset($userData['password']);
            }
            
            // Update password if provided
            if ($password) {
                $passwordHash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
                $userData['password_hash'] = $passwordHash;
            }
            
            // Update the user
            $success = $this->update($userId, $userData);
            
            $this->commit();
            return $success;
        } catch (Exception $e) {
            $this->rollback();
            $this->handleError($e);
            return false;
        }
    }
    
    /**
     * Verify a user's password
     * 
     * @param int $userId User ID
     * @param string $password Password to verify
     * @return bool True if password is correct
     */
    public function verifyPassword($userId, $password) 
    {
        try {
            $user = $this->find($userId);
            if (!$user) {
                return false;
            }
            
            return password_verify($password, $user['password_hash']);
        } catch (Exception $e) {
            $this->handleError($e);
            return false;
        }
    }
    
    /**
     * Authenticate a user by username and password
     * 
     * @param string $username Username
     * @param string $password Password
     * @return array|bool User data if authenticated, false otherwise
     */
    public function authenticate($username, $password) 
    {
        try {
            $user = $this->where('username', $username)
                         ->where('active', 1)
                         ->first();
            
            if (!$user) {
                return false; // User not found or inactive
            }
            
            if (!password_verify($password, $user['password_hash'])) {
                return false; // Password incorrect
            }
            
            return $user;
        } catch (Exception $e) {
            $this->handleError($e);
            return false;
        }
    }
    
    /**
     * Get all roles for a user
     * 
     * @param int $userId User ID
     * @return array Roles assigned to the user
     */
    public function getRoles($userId) 
    {
        try {
            $sql = "
                SELECT r.*
                FROM roles r
                JOIN user_roles ur ON r.id = ur.role_id
                WHERE ur.user_id = :user_id
            ";
            
            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
            $stmt->execute();
            
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            $this->handleError($e);
            return [];
        }
    }
    
    /**
     * Check if a user has a specific role
     * 
     * @param int $userId User ID
     * @param string $roleName Role name to check
     * @return bool True if user has the role
     */
    public function hasRole($userId, $roleName) 
    {
        try {
            $sql = "
                SELECT COUNT(*) AS count
                FROM user_roles ur
                JOIN roles r ON ur.role_id = r.id
                WHERE ur.user_id = :user_id AND r.name = :role_name
            ";
            
            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
            $stmt->bindValue(':role_name', $roleName);
            $stmt->execute();
            
            $result = $stmt->fetch();
            return $result['count'] > 0;
        } catch (PDOException $e) {
            $this->handleError($e);
            return false;
        }
    }
    
    /**
     * Assign a role to a user
     * 
     * @param int $userId User ID
     * @param string $roleName Role name
     * @return bool Success status
     */
    public function assignRole($userId, $roleName) 
    {
        try {
            // First, check if the user already has this role
            if ($this->hasRole($userId, $roleName)) {
                return true; // Role already assigned
            }
            
            $sql = "
                INSERT INTO user_roles (user_id, role_id)
                VALUES (:user_id, (SELECT id FROM roles WHERE name = :role_name))
            ";
            
            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
            $stmt->bindValue(':role_name', $roleName);
            
            return $stmt->execute();
        } catch (PDOException $e) {
            $this->handleError($e);
            return false;
        }
    }
    
    /**
     * Remove a role from a user
     * 
     * @param int $userId User ID
     * @param string $roleName Role name
     * @return bool Success status
     */
    public function removeRole($userId, $roleName) 
    {
        try {
            $sql = "
                DELETE FROM user_roles
                WHERE user_id = :user_id AND role_id = (
                    SELECT id FROM roles WHERE name = :role_name
                )
            ";
            
            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
            $stmt->bindValue(':role_name', $roleName);
            
            return $stmt->execute();
        } catch (PDOException $e) {
            $this->handleError($e);
            return false;
        }
    }
    
    /**
     * Get all available roles in the system
     * 
     * @return array List of all roles
     */
    public function getAllRoles() 
    {
        try {
            $sql = "SELECT * FROM roles ORDER BY name";
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            $this->handleError($e);
            return [];
        }
    }
    
    /**
     * Add a new IP address to the whitelist
     * 
     * @param string $ipAddress IP address or CIDR range
     * @param string $description Optional description
     * @return int|bool Whitelist entry ID or false on failure
     */
    public function addIpToWhitelist($ipAddress, $description = null) 
    {
        try {
            $sql = "
                INSERT INTO ip_whitelist (ip_address, description, active)
                VALUES (:ip_address, :description, 1)
            ";
            
            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':ip_address', $ipAddress);
            $stmt->bindValue(':description', $description);
            $stmt->execute();
            
            return $this->db->lastInsertId();
        } catch (PDOException $e) {
            $this->handleError($e);
            return false;
        }
    }
    
    /**
     * Remove an IP address from the whitelist
     * 
     * @param int $whitelistId Whitelist entry ID
     * @return bool Success status
     */
    public function removeIpFromWhitelist($whitelistId) 
    {
        try {
            $sql = "DELETE FROM ip_whitelist WHERE id = :id";
            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':id', $whitelistId, PDO::PARAM_INT);
            
            return $stmt->execute();
        } catch (PDOException $e) {
            $this->handleError($e);
            return false;
        }
    }
    
    /**
     * Enable or disable an IP whitelist entry
     * 
     * @param int $whitelistId Whitelist entry ID
     * @param bool $active Active status
     * @return bool Success status
     */
    public function setIpWhitelistStatus($whitelistId, $active) 
    {
        try {
            $sql = "UPDATE ip_whitelist SET active = :active WHERE id = :id";
            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':id', $whitelistId, PDO::PARAM_INT);
            $stmt->bindValue(':active', $active ? 1 : 0, PDO::PARAM_INT);
            
            return $stmt->execute();
        } catch (PDOException $e) {
            $this->handleError($e);
            return false;
        }
    }
    
    /**
     * Get all IP whitelist entries
     * 
     * @param bool $activeOnly Only return active entries
     * @return array Whitelist entries
     */
    public function getIpWhitelist($activeOnly = false) 
    {
        try {
            $sql = "SELECT * FROM ip_whitelist";
            if ($activeOnly) {
                $sql .= " WHERE active = 1";
            }
            $sql .= " ORDER BY ip_address";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            $this->handleError($e);
            return [];
        }
    }
    
    /**
     * Check if an IP address is in the whitelist
     * 
     * @param string $ipAddress IP address to check
     * @return bool True if IP is whitelisted
     */
    public function isIpWhitelisted($ipAddress) 
    {
        try {
            // First, check for exact matches
            $sql = "
                SELECT COUNT(*) AS count
                FROM ip_whitelist
                WHERE ip_address = :ip_address AND active = 1
            ";
            
            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':ip_address', $ipAddress);
            $stmt->execute();
            
            $result = $stmt->fetch();
            if ($result['count'] > 0) {
                return true; // Exact match found
            }
            
            // If no exact match, check CIDR ranges
            $whitelist = $this->getIpWhitelist(true);
            foreach ($whitelist as $entry) {
                // Skip non-CIDR entries
                if (strpos($entry['ip_address'], '/') === false) {
                    continue;
                }
                
                if ($this->ipInCidrRange($ipAddress, $entry['ip_address'])) {
                    return true;
                }
            }
            
            return false;
        } catch (Exception $e) {
            $this->handleError($e);
            return false;
        }
    }
    
    /**
     * Check if an IP address is in a CIDR range
     * 
     * @param string $ip IP address to check
     * @param string $cidr CIDR range
     * @return bool True if IP is in range
     */
    protected function ipInCidrRange($ip, $cidr) 
    {
        list($subnet, $mask) = explode('/', $cidr);
        
        $ipLong = ip2long($ip);
        $subnetLong = ip2long($subnet);
        $maskLong = ~((1 << (32 - $mask)) - 1);
        
        return ($ipLong & $maskLong) === ($subnetLong & $maskLong);
    }
    
    /**
     * Initialize the default administrator account with a new password
     * 
     * @param string $password New password for admin
     * @return bool Success status
     */
    public function initializeAdminAccount($password) 
    {
        try {
            $this->beginTransaction();
            
            // Check if admin account is already initialized
            $admin = $this->findByUsername('scenes');
            if (!$admin || $admin['password_hash'] !== 'uninitialized') {
                throw new Exception("Admin account already initialized");
            }
            
            // Update the admin password
            $passwordHash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
            
            $sql = "
                UPDATE users 
                SET password_hash = :password_hash 
                WHERE username = 'scenes'
            ";
            
            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':password_hash', $passwordHash);
            $success = $stmt->execute();
            
            $this->commit();
            return $success;
        } catch (Exception $e) {
            $this->rollback();
            $this->handleError($e);
            return false;
        }
    }
    
    /**
     * Check if admin account has been initialized
     * 
     * @return bool True if admin account is initialized
     */
    public function isAdminInitialized() 
    {
        try {
            $admin = $this->findByUsername('scenes');
            return $admin && $admin['password_hash'] !== 'uninitialized';
        } catch (Exception $e) {
            $this->handleError($e);
            return false;
        }
    }
}
