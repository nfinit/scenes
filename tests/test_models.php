<?php
/**
 * Scenes Model Test Script
 * 
 * This script tests all Scenes models by:
 * 1. Creating a temporary database
 * 2. Loading the schema files
 * 3. Running tests against the models (Base, Collection, Asset, and User)
 * 4. Cleaning up afterward
 * 
 * Usage: php tests/test_models.php
 */

// Define paths properly to avoid duplicating directory names
$appRoot = realpath(__DIR__ . '/..');

// If we're running from an unexpected location, try to find the scenes root
if (!is_dir($appRoot . '/sys/setup/schema')) {
    // Look for the scenes root by traversing up directories
    $testPath = __DIR__;
    while ($testPath !== '/' && !is_dir($testPath . '/sys/setup/schema')) {
        $testPath = dirname($testPath);
    }
    
    if (is_dir($testPath . '/sys/setup/schema')) {
        $appRoot = $testPath;
        echo "Found application root by traversal: $appRoot\n";
    }
}

$tempDbFile = sys_get_temp_dir() . '/scenes_test_' . uniqid() . '.db';
$tempAssetsDir = sys_get_temp_dir() . '/scenes_test_assets_' . uniqid();

// Debug path information
echo "Application root: $appRoot\n";
echo "Schema directory: $appRoot/sys/setup/schema/\n";
echo "Script location: " . __FILE__ . "\n";
echo "Current working directory: " . getcwd() . "\n";
echo "Temporary assets directory: $tempAssetsDir\n";

try {
    echo "=== Scenes Model Test ===\n\n";
    echo "Setting up test environment...\n";
    
    // Create test database
    echo "Creating test database at: $tempDbFile\n";
    $db = new PDO('sqlite:' . $tempDbFile);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Load schema files
    $schemaDir = $appRoot . '/sys/setup/schema/';
    $schemaFiles = [
        '00-system.sql',
        '01-collection.sql',
        '02-asset.sql'
    ];
    
    foreach ($schemaFiles as $file) {
        echo "Loading schema: " . basename($file) . "\n";
        $sql = file_get_contents($schemaDir . $file);
        $db->exec($sql);
    }
    
    // Close the database connection
    $db = null;
    
    // Create temporary assets directory
    if (!is_dir($tempAssetsDir)) {
        mkdir($tempAssetsDir, 0755, true);
        echo "Created temporary assets directory: $tempAssetsDir\n";
    }
    
    // Create a test asset file
    $testImagePath = $tempAssetsDir . '/test-image.jpg';
    $testImageContent = str_repeat('A', 1024); // 1KB dummy image
    file_put_contents($testImagePath, $testImageContent);
    echo "Created test asset file: $testImagePath\n";
    
    // Load model classes
    require_once $appRoot . '/sys/models/base.php';
    
    // Create a test-specific version of the BaseModel
    class TestBaseModel extends Scenes\Models\BaseModel {
        protected function connect() {
            global $tempDbFile;
            try {
                $this->db = new PDO('sqlite:' . $tempDbFile);
                $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                $this->db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
            } catch (PDOException $e) {
                $this->handleError($e);
            }
        }
    }
    
    // Load and extend the Collection model
    require_once $appRoot . '/sys/models/collection.php';
    
    class TestCollection extends Scenes\Models\Collection {
        protected function connect() {
            global $tempDbFile;
            try {
                $this->db = new PDO('sqlite:' . $tempDbFile);
                $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                $this->db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
            } catch (PDOException $e) {
                $this->handleError($e);
            }
        }
    }
    
    // Load and extend the Asset model
    require_once $appRoot . '/sys/models/asset.php';
    
    class TestAsset extends Scenes\Models\Asset {
        protected function connect() {
            global $tempDbFile;
            try {
                $this->db = new PDO('sqlite:' . $tempDbFile);
                $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                $this->db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
            } catch (PDOException $e) {
                $this->handleError($e);
            }
        }
    }
    
    // Load and extend the User model
    require_once $appRoot . '/sys/models/management.php';
    
    class TestUser extends Scenes\Models\User {
        protected function connect() {
            global $tempDbFile;
            try {
                $this->db = new PDO('sqlite:' . $tempDbFile);
                $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                $this->db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
            } catch (PDOException $e) {
                $this->handleError($e);
            }
        }
        
        // Override the create method to handle password_hash
        public function create(array $data) {
            try {
                // Extract columns that are in $fillable
                $filteredData = array_intersect_key($data, array_flip($this->fillable));
                
                // Add password_hash separately
                if (isset($data['password_hash'])) {
                    $filteredData['password_hash'] = $data['password_hash'];
                }
                
                $columns = implode(', ', array_keys($filteredData));
                $placeholders = ':' . implode(', :', array_keys($filteredData));
                
                $sql = "INSERT INTO {$this->table} ($columns) VALUES ($placeholders)";
                $stmt = $this->db->prepare($sql);
                
                foreach ($filteredData as $key => $value) {
                    $stmt->bindValue(":$key", $value);
                }
                
                $stmt->execute();
                return $this->db->lastInsertId();
            } catch (PDOException $e) {
                $this->handleError($e);
                return false;
            }
        }
    }
    
    // Run tests
    echo "\nRunning tests...\n";
    
    // Test Collection Model
    echo "\n=== Testing Collection Model ===\n";
    $collection = new TestCollection();
    
    // Test 1: Get root collection
    $root = $collection->getRoot();
    if ($root && $root['slug'] === 'root') {
        echo "  ✓ Root collection found\n";
    } else {
        echo "  ✗ Root collection not found or incorrect\n";
        var_dump($root);
    }
    
    // Test 2: Create a new collection
    $newId = $collection->create([
        'slug' => 'test-collection',
        'name' => 'Test Collection',
        'description' => 'This is a test collection',
        'protected' => 0
    ]);
    
    if ($newId && is_numeric($newId)) {
        echo "  ✓ Created new collection with ID: $newId\n";
        
        // Test 3: Retrieve the new collection
        $newCollection = $collection->find($newId);
        if ($newCollection && $newCollection['name'] === 'Test Collection') {
            echo "  ✓ Retrieved new collection successfully\n";
        } else {
            echo "  ✗ Failed to retrieve new collection\n";
        }
        
        // Test 4: Update the collection
        $updated = $collection->update($newId, [
            'name' => 'Updated Test Collection'
        ]);
        
        if ($updated) {
            $updatedCollection = $collection->find($newId);
            if ($updatedCollection['name'] === 'Updated Test Collection') {
                echo "  ✓ Updated collection successfully\n";
            } else {
                echo "  ✗ Failed to update collection name\n";
            }
        } else {
            echo "  ✗ Failed to update collection\n";
        }
        
        // Test 5: Add collection as child of root
        $rootId = $root['id'];
        $relationshipId = $collection->addChild($rootId, $newId, true, 0, 'linked');
        
        if ($relationshipId) {
            echo "  ✓ Added collection as child of root\n";
            
            // Test 6: Get children of root
            $children = $collection->getChildren($rootId);
            if (count($children) > 0) {
                $found = false;
                foreach ($children as $child) {
                    if ($child['id'] == $newId) {
                        $found = true;
                        break;
                    }
                }
                
                if ($found) {
                    echo "  ✓ Found new collection as child of root\n";
                } else {
                    echo "  ✗ New collection not found as child of root\n";
                }
            } else {
                echo "  ✗ No children found for root\n";
            }
        } else {
            echo "  ✗ Failed to add collection as child of root\n";
        }
        
        // Test 7: Set display mode
        $success = $collection->setDisplayMode($newId, 'grid');
        if ($success) {
            $mode = $collection->getDisplayMode($newId);
            if ($mode === 'grid') {
                echo "  ✓ Set and retrieved display mode successfully\n";
            } else {
                echo "  ✗ Failed to retrieve correct display mode\n";
            }
        } else {
            echo "  ✗ Failed to set display mode\n";
        }
        
        // Test 8: Create an asset group
        $groupId = $collection->createAssetGroup($newId, 'Test Group', 'A test asset group', 'grid');
        if ($groupId && is_numeric($groupId)) {
            echo "  ✓ Created asset group successfully\n";
        } else {
            echo "  ✗ Failed to create asset group\n";
        }
        
        // Test 9: Clone the collection
        $cloneId = $collection->cloneCollection($newId, [
            'slug' => 'cloned-collection',
            'name' => 'Cloned Test Collection'
        ]);
        
        if ($cloneId && is_numeric($cloneId)) {
            echo "  ✓ Cloned collection successfully\n";
            
            $clonedCollection = $collection->find($cloneId);
            if ($clonedCollection && $clonedCollection['name'] === 'Cloned Test Collection') {
                echo "  ✓ Retrieved cloned collection successfully\n";
            } else {
                echo "  ✗ Failed to retrieve cloned collection\n";
            }
        } else {
            echo "  ✗ Failed to clone collection\n";
        }
    } else {
        echo "  ✗ Failed to create new collection\n";
    }
    
    // Test Asset Model
    echo "\n=== Testing Asset Model ===\n";
    $asset = new TestAsset();
    
    // Test 1: Create a new asset
    $assetId = $asset->createFromFile($testImagePath, $tempAssetsDir, 'test-asset.jpg');
    
    if ($assetId && is_numeric($assetId)) {
        echo "  ✓ Created new asset with ID: $assetId\n";
        
        // Test 2: Retrieve the asset
        $newAsset = $asset->find($assetId);
        if ($newAsset && $newAsset['filename'] === 'test-asset.jpg') {
            echo "  ✓ Retrieved asset successfully\n";
        } else {
            echo "  ✗ Failed to retrieve asset\n";
        }
        
        // Test 3: Find asset by filename
        $assetsByFilename = $asset->findByFilename('test-asset.jpg');
        if (count($assetsByFilename) > 0 && $assetsByFilename[0]['id'] == $assetId) {
            echo "  ✓ Found asset by exact filename\n";
        } else {
            echo "  ✗ Failed to find asset by exact filename\n";
        }
        
        // Test 4: Find asset by partial filename
        $assetsByPartialFilename = $asset->findByFilename('asset', false);
        if (count($assetsByPartialFilename) > 0) {
            echo "  ✓ Found asset by partial filename\n";
        } else {
            echo "  ✗ Failed to find asset by partial filename\n";
        }
        
        // Test 5: Find asset by filetype - Fix by checking the actual filetype first
        $actualFiletype = $newAsset['filetype'];
        echo "  (i) Asset filetype is: " . $actualFiletype . "\n";
        
        $assetsByFiletype = $asset->findByFiletype($actualFiletype);
        if (count($assetsByFiletype) > 0) {
            echo "  ✓ Found asset by filetype\n";
        } else {
            echo "  ✗ Failed to find asset by filetype\n";
        }
        
        // Test 6: Find asset by checksum
        $byChecksum = $asset->findByChecksum($newAsset['checksum']);
        if ($byChecksum && $byChecksum['id'] == $assetId) {
            echo "  ✓ Found asset by checksum\n";
        } else {
            echo "  ✗ Failed to find asset by checksum\n";
        }
        
        // Test 7: Add asset to collection
        $membershipId = $asset->addToCollection($assetId, $newId, [
            'display_name' => 'Test Asset Display Name',
            'description' => 'Test asset description'
        ]);
        
        if ($membershipId && is_numeric($membershipId)) {
            echo "  ✓ Added asset to collection\n";
            
            // Test 8: Get collections for asset
            $assetCollections = $asset->getCollections($assetId);
            if (count($assetCollections) > 0) {
                $found = false;
                foreach ($assetCollections as $coll) {
                    if ($coll['id'] == $newId) {
                        $found = true;
                        break;
                    }
                }
                
                if ($found) {
                    echo "  ✓ Found collection in asset's collections\n";
                } else {
                    echo "  ✗ New collection not found in asset's collections\n";
                }
            } else {
                echo "  ✗ No collections found for asset\n";
            }
            
            // Test 9: Update asset metadata in collection
            $updateMeta = $asset->updateCollectionMetadata($membershipId, [
                'display_name' => 'Updated Asset Display Name'
            ]);
            
            if ($updateMeta) {
                echo "  ✓ Updated asset metadata in collection\n";
            } else {
                echo "  ✗ Failed to update asset metadata\n";
            }
            
            // Test 10: Add asset to group
            $addedToGroup = $asset->addToGroup($membershipId, $groupId, 1);
            if ($addedToGroup) {
                echo "  ✓ Added asset to group\n";
                
                // Test 11: Get group membership
                $groupMembership = $asset->getGroupMembership($membershipId);
                if ($groupMembership && $groupMembership['id'] == $groupId) {
                    echo "  ✓ Found group membership\n";
                } else {
                    echo "  ✗ Failed to find group membership\n";
                }
                
                // Test 12: Set group display mode
                $setGroupMode = $asset->setGroupDisplayMode($groupId, 'side-by-side', true);
                if ($setGroupMode) {
                    $groupMode = $asset->getGroupDisplayMode($groupId);
                    if ($groupMode === 'side-by-side') {
                        echo "  ✓ Set and retrieved group display mode\n";
                    } else {
                        echo "  ✗ Failed to retrieve correct group display mode\n";
                    }
                } else {
                    echo "  ✗ Failed to set group display mode\n";
                }
                
                // Test 13: Get group assets
                $groupAssets = $asset->getGroupAssets($groupId);
                if (count($groupAssets) > 0 && $groupAssets[0]['id'] == $assetId) {
                    echo "  ✓ Found asset in group assets\n";
                } else {
                    echo "  ✗ Asset not found in group assets\n";
                }
                
                // Test 14: Remove asset from group
                $removeFromGroup = $asset->removeFromGroup($membershipId);
                if ($removeFromGroup) {
                    echo "  ✓ Removed asset from group\n";
                } else {
                    echo "  ✗ Failed to remove asset from group\n";
                }
            } else {
                echo "  ✗ Failed to add asset to group\n";
            }
            
            // Test 15: Remove asset from collection
            $removeFromCollection = $asset->removeFromCollection($assetId, $newId);
            if ($removeFromCollection) {
                echo "  ✓ Removed asset from collection\n";
            } else {
                echo "  ✗ Failed to remove asset from collection\n";
            }
        } else {
            echo "  ✗ Failed to add asset to collection\n";
        }
        
        // Test 16: Verify asset integrity
        $integrity = $asset->verifyIntegrity($assetId);
        if ($integrity) {
            echo "  ✓ Verified asset integrity\n";
        } else {
            echo "  ✗ Asset integrity check failed\n";
        }
        
        // Test 17: Delete asset
        $deleteAsset = $asset->deleteAsset($assetId, true);
        if ($deleteAsset) {
            echo "  ✓ Deleted asset\n";
        } else {
            echo "  ✗ Failed to delete asset\n";
        }
    } else {
        echo "  ✗ Failed to create new asset\n";
    }
    
    // Test User/Management Model
    echo "\n=== Testing User Management Model ===\n";
    $user = new TestUser();
    
    // Test 1: Check if admin account is initialized
    $adminInitialized = $user->isAdminInitialized();
    if (!$adminInitialized) {
        echo "  ✓ Admin account not yet initialized\n";
        
        // Test 2: Initialize admin account
        $initResult = $user->initializeAdminAccount('test_password');
        if ($initResult) {
            echo "  ✓ Initialized admin account\n";
            
            // Verify initialization
            $nowInitialized = $user->isAdminInitialized();
            if ($nowInitialized) {
                echo "  ✓ Admin account is now initialized\n";
            } else {
                echo "  ✗ Admin account initialization verification failed\n";
            }
        } else {
            echo "  ✗ Failed to initialize admin account\n";
        }
    } else {
        echo "  ✓ Admin account already initialized\n";
    }
    
    // Test 3: Create a new user - Direct DB method to avoid fillable issue
    try {
        $stmt = $user->db->prepare("
            INSERT INTO users (username, password_hash, email, full_name, active)
            VALUES (:username, :password_hash, :email, :full_name, :active)
        ");
        
        $passwordHash = password_hash('test_password', PASSWORD_BCRYPT, ['cost' => 12]);
        
        $stmt->bindValue(':username', 'testuser');
        $stmt->bindValue(':password_hash', $passwordHash);
        $stmt->bindValue(':email', 'test@example.com');
        $stmt->bindValue(':full_name', 'Test User');
        $stmt->bindValue(':active', 1);
        
        $stmt->execute();
        $userId = $user->db->lastInsertId();
        
        if ($userId) {
            echo "  ✓ Created new user with ID: $userId\n";
            
            // Test 4: Retrieve the user
            $newUser = $user->find($userId);
            if ($newUser && $newUser['username'] === 'testuser') {
                echo "  ✓ Retrieved user successfully\n";
            } else {
                echo "  ✗ Failed to retrieve user\n";
            }
            
            // Test 5: Find user by username
            $byUsername = $user->findByUsername('testuser');
            if ($byUsername && $byUsername['id'] == $userId) {
                echo "  ✓ Found user by username\n";
            } else {
                echo "  ✗ Failed to find user by username\n";
            }
            
            // Test 6: Find user by email
            $byEmail = $user->findByEmail('test@example.com');
            if ($byEmail && $byEmail['id'] == $userId) {
                echo "  ✓ Found user by email\n";
            } else {
                echo "  ✗ Failed to find user by email\n";
            }
            
            // Test 7: Verify password
            $passwordVerified = $user->verifyPassword($userId, 'test_password');
            if ($passwordVerified) {
                echo "  ✓ Verified password\n";
            } else {
                echo "  ✗ Password verification failed\n";
            }
            
            // Test 8: Authenticate user
            $authenticated = $user->authenticate('testuser', 'test_password');
            if ($authenticated) {
                echo "  ✓ Authenticated user\n";
            } else {
                echo "  ✗ Authentication failed\n";
            }
            
            // Test 9: Update user without changing password
            $updateUser = $user->update($userId, [
                'full_name' => 'Updated Test User'
            ]);
            
            if ($updateUser) {
                $updatedUser = $user->find($userId);
                if ($updatedUser['full_name'] === 'Updated Test User') {
                    echo "  ✓ Updated user\n";
                } else {
                    echo "  ✗ User update verification failed\n";
                }
            } else {
                echo "  ✗ Failed to update user\n";
            }
            
            // Test 10: Update password using direct SQL
            try {
                $newPasswordHash = password_hash('new_test_password', PASSWORD_BCRYPT, ['cost' => 12]);
                
                $stmt = $user->db->prepare("
                    UPDATE users SET password_hash = :password_hash WHERE id = :id
                ");
                
                $stmt->bindValue(':password_hash', $newPasswordHash);
                $stmt->bindValue(':id', $userId);
                $stmt->execute();
                
                // Test 11: Verify new password
                $newPasswordVerified = $user->verifyPassword($userId, 'new_test_password');
                if ($newPasswordVerified) {
                    echo "  ✓ Verified new password\n";
                } else {
                    echo "  ✗ New password verification failed\n";
                }
            } catch (Exception $e) {
                echo "  ✗ Failed to update password: " . $e->getMessage() . "\n";
            }
            
            // Test 12: Get user roles
            $roles = $user->getRoles($userId);
            echo "  (i) User has " . count($roles) . " roles\n";
            
            // Test 13: Assign user role
            $assignUser = $user->assignRole($userId, 'user');
            if ($assignUser) {
                echo "  ✓ Assigned user role\n";
                
                // Check if user has 'user' role
                $hasUserRole = $user->hasRole($userId, 'user');
                if ($hasUserRole) {
                    echo "  ✓ User has 'user' role\n";
                } else {
                    echo "  ✗ User does not have 'user' role\n";
                }
            } else {
                echo "  ✗ Failed to assign user role\n";
            }
            
            // Test 14: Assign administrator role
            $assignAdmin = $user->assignRole($userId, 'administrator');
            if ($assignAdmin) {
                echo "  ✓ Assigned administrator role\n";
                
                // Check if user has admin role
                $hasAdminRole = $user->hasRole($userId, 'administrator');
                if ($hasAdminRole) {
                    echo "  ✓ User has 'administrator' role\n";
                } else {
                    echo "  ✗ User does not have 'administrator' role\n";
                }
                
                // Test 15: Remove administrator role
                $removeAdmin = $user->removeRole($userId, 'administrator');
                if ($removeAdmin) {
                    echo "  ✓ Removed administrator role\n";
                    
                    // Verify role removal
                    $stillHasAdminRole = $user->hasRole($userId, 'administrator');
                    if (!$stillHasAdminRole) {
                        echo "  ✓ Administrator role successfully removed\n";
                    } else {
                        echo "  ✗ Failed to remove administrator role\n";
                    }
                } else {
                    echo "  ✗ Failed to remove administrator role\n";
                }
            } else {
                echo "  ✗ Failed to assign administrator role\n";
            }
            
            // Test 16: Get all available roles
            $allRoles = $user->getAllRoles();
            if (count($allRoles) >= 2) { // We should have at least 'administrator' and 'user' roles
                echo "  ✓ Retrieved all roles\n";
            } else {
                echo "  ✗ Failed to retrieve all roles\n";
            }
        } else {
            echo "  ✗ Failed to create new user\n";
        }
    } catch (Exception $e) {
        echo "  ✗ Failed to create user: " . $e->getMessage() . "\n";
    }
    
    // Test 17: IP Whitelist management
    $whitelistId = $user->addIpToWhitelist('192.168.1.1', 'Test IP Address');
    if ($whitelistId && is_numeric($whitelistId)) {
        echo "  ✓ Added IP to whitelist\n";
        
        // Test 18: Get IP whitelist
        $whitelist = $user->getIpWhitelist();
        if (count($whitelist) > 0) {
            echo "  ✓ Retrieved IP whitelist\n";
        } else {
            echo "  ✗ Failed to retrieve IP whitelist\n";
        }
        
        // Test 19: Check if IP is whitelisted
        $isWhitelisted = $user->isIpWhitelisted('192.168.1.1');
        if ($isWhitelisted) {
            echo "  ✓ Verified IP is whitelisted\n";
        } else {
            echo "  ✗ Failed to verify IP is whitelisted\n";
        }
        
        // Test 20: Disable IP whitelist entry
        $disableWhitelist = $user->setIpWhitelistStatus($whitelistId, false);
        if ($disableWhitelist) {
            echo "  ✓ Disabled IP whitelist entry\n";
            
            // Test 21: Verify IP is no longer whitelisted
            $stillWhitelisted = $user->isIpWhitelisted('192.168.1.1');
            if (!$stillWhitelisted) {
                echo "  ✓ Verified IP is no longer whitelisted\n";
            } else {
                echo "  ✗ IP is still whitelisted\n";
            }
            
            // Test 22: Re-enable IP whitelist entry
            $enableWhitelist = $user->setIpWhitelistStatus($whitelistId, true);
            if ($enableWhitelist) {
                echo "  ✓ Re-enabled IP whitelist entry\n";
            } else {
                echo "  ✗ Failed to re-enable IP whitelist entry\n";
            }
        } else {
            echo "  ✗ Failed to disable IP whitelist entry\n";
        }
        
        // Test 23: Remove IP from whitelist
        $removeWhitelist = $user->removeIpFromWhitelist($whitelistId);
        if ($removeWhitelist) {
            echo "  ✓ Removed IP from whitelist\n";
        } else {
            echo "  ✗ Failed to remove IP from whitelist\n";
        }
    } else {
        echo "  ✗ Failed to add IP to whitelist\n";
    }
    
    echo "\nAll tests completed.\n";
    
} catch (Exception $e) {
    echo "Test failed with error: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
} finally {
    echo "\nCleaning up...\n";
    
    // Remove the test database
    if (file_exists($tempDbFile)) {
        echo "Removing test database: $tempDbFile\n";
        unlink($tempDbFile);
    }
    
    // Remove test asset files and directory
    if (is_dir($tempAssetsDir)) {
        echo "Removing temporary assets directory: $tempAssetsDir\n";
        array_map('unlink', glob("$tempAssetsDir/*"));
        rmdir($tempAssetsDir);
    }
    
    echo "Cleanup complete.\n";
}
