<?php
/**
 * Scenes Model Test Script
 * 
 * This script tests the Scenes models by:
 * 1. Creating a temporary database
 * 2. Loading the schema files
 * 3. Running tests against the models
 * 4. Cleaning up afterward
 * 
 * Usage: php tests/test_models.php
 */

// Define paths properly to avoid duplicating directory names
// First attempt: Get the absolute path to the project root
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

// Debug path information
echo "Application root: $appRoot\n";
echo "Schema directory: $appRoot/sys/setup/schema/\n";
echo "Script location: " . __FILE__ . "\n";
echo "Current working directory: " . getcwd() . "\n";

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
    
    // Load model classes after setting up test database
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
    
    // Load the Collection model
    require_once $appRoot . '/sys/models/collection.php';
    
    // Create a test-specific version of the Collection model
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
    
    // Run tests
    echo "\nRunning tests...\n";
    
    // Test Collection Model
    echo "Testing Collection Model...\n";
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
    
    echo "Cleanup complete.\n";
}
