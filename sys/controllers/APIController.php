<?php

namespace Scenes\Controllers;

use Scenes\Models\Asset;
use Scenes\Models\Collection;
use Exception;

/**
 * APIController
 * 
 * Handles API requests for Scenes data
 */
class APIController extends BaseController 
{
    /**
     * @var Asset Asset model instance
     */
    protected $asset;
    
    /**
     * @var Collection Collection model instance
     */
    protected $collection;
    
    /**
     * Constructor
     */
    public function __construct() 
    {
        parent::__construct();
        $this->asset = new Asset();
        $this->collection = new Collection();
    }
    
    /**
     * Get a collection by slug
     * 
     * @param string $slug Collection slug
     * @return void
     */
    public function getCollection($slug) 
    {
        try {
            // Get the collection
            $collection = $this->collection->findBySlug($slug);
            
            if (!$collection) {
                $this->sendJsonResponse(['error' => 'Collection not found.'], 404);
                return;
            }
            
            // Check if collection is protected and request is not authenticated
            if ($collection['protected'] && !$this->isApiAuthenticated()) {
                $this->sendJsonResponse(['error' => 'Authentication required.'], 401);
                return;
            }
            
            // Get child collections
            $children = $this->collection->getChildren($collection['id']);
            
            // Get collection assets
            $assets = $this->collection->getAssets($collection['id']);
            
            // Group assets by their group_id
            $assetGroups = [];
            $ungroupedAssets = [];
            
            foreach ($assets as $asset) {
                // Clean up asset data for API response
                $assetData = $this->prepareAssetData($asset);
                
                if (!empty($asset['group_id'])) {
                    if (!isset($assetGroups[$asset['group_id']])) {
                        $assetGroups[$asset['group_id']] = [
                            'id' => $asset['group_id'],
                            'name' => $asset['group_name'],
                            'description' => $asset['group_description'],
                            'assets' => []
                        ];
                    }
                    $assetGroups[$asset['group_id']]['assets'][] = $assetData;
                } else {
                    $ungroupedAssets[] = $assetData;
                }
            }
            
            // Convert asset groups to a sequential array
            $assetGroupsList = array_values($assetGroups);
            
            // Prepare the response data
            $responseData = [
                'collection' => [
                    'id' => $collection['id'],
                    'slug' => $collection['slug'],
                    'name' => $collection['name'],
                    'title' => $collection['title'],
                    'description' => $collection['description'],
                    'created_at' => $collection['created_at'],
                    'updated_at' => $collection['updated_at']
                ],
                'children' => $this->prepareChildCollections($children),
                'assets' => $ungroupedAssets,
                'asset_groups' => $assetGroupsList
            ];
            
            $this->sendJsonResponse($responseData);
        } catch (Exception $e) {
            $this->sendJsonResponse(['error' => $e->getMessage()], 500);
        }
    }
    
    /**
     * Get an asset by ID
     * 
     * @param int $id Asset ID
     * @return void
     */
    public function getAsset($id) 
    {
        try {
            // Get the asset
            $asset = $this->asset->find($id);
            
            if (!$asset) {
                $this->sendJsonResponse(['error' => 'Asset not found.'], 404);
                return;
            }
            
            // Get collections that contain this asset
            $collections = $this->asset->getCollections($id);
            
            // Check if any of the collections are protected and request is not authenticated
            $requiresAuth = false;
            foreach ($collections as $collection) {
                if ($collection['protected']) {
                    $requiresAuth = true;
                    break;
                }
            }
            
            if ($requiresAuth && !$this->isApiAuthenticated()) {
                $this->sendJsonResponse(['error' => 'Authentication required.'], 401);
                return;
            }
            
            // Prepare the response data
            $responseData = [
                'asset' => $this->prepareAssetData($asset),
                'collections' => $this->prepareAssetCollections($collections)
            ];
            
            $this->sendJsonResponse($responseData);
        } catch (Exception $e) {
            $this->sendJsonResponse(['error' => $e->getMessage()], 500);
        }
    }
    
    /**
     * Stream an asset file directly
     * 
     * @param int $id Asset ID
     * @return void
     */
    public function streamAsset($id) 
    {
        try {
            // Get the asset
            $asset = $this->asset->find($id);
            
            if (!$asset) {
                $this->sendJsonResponse(['error' => 'Asset not found.'], 404);
                return;
            }
            
            // Get collections that contain this asset
            $collections = $this->asset->getCollections($id);
            
            // Check if any of the collections are protected and request is not authenticated
            $requiresAuth = false;
            foreach ($collections as $collection) {
                if ($collection['protected']) {
                    $requiresAuth = true;
                    break;
                }
            }
            
            if ($requiresAuth && !$this->isApiAuthenticated()) {
                $this->sendJsonResponse(['error' => 'Authentication required.'], 401);
                return;
            }
            
            // Check if the file exists
            if (!file_exists($asset['filepath'])) {
                $this->sendJsonResponse(['error' => 'Asset file not found.'], 404);
                return;
            }
            
            // Set appropriate content type header
            header('Content-Type: ' . $asset['filetype']);
            header('Content-Disposition: inline; filename="' . $asset['filename'] . '"');
            header('Content-Length: ' . $asset['filesize']);
            
            // Disable output buffering
            if (ob_get_level()) {
                ob_end_clean();
            }
            
            // Stream the file
            readfile($asset['filepath']);
            exit;
        } catch (Exception $e) {
            $this->sendJsonResponse(['error' => $e->getMessage()], 500);
        }
    }
    
    /**
     * Get all public collections
     * 
     * @return void
     */
    public function getCollections() 
    {
        try {
            // Get all collections
            $allCollections = $this->collection->all();
            
            // Filter out protected collections if not authenticated
            $isAuthenticated = $this->isApiAuthenticated();
            $collections = [];
            
            foreach ($allCollections as $collection) {
                if (!$collection['protected'] || $isAuthenticated) {
                    $collections[] = [
                        'id' => $collection['id'],
                        'slug' => $collection['slug'],
                        'name' => $collection['name'],
                        'title' => $collection['title'],
                        'description' => $collection['description'],
                        'created_at' => $collection['created_at'],
                        'updated_at' => $collection['updated_at']
                    ];
                }
            }
            
            $this->sendJsonResponse(['collections' => $collections]);
        } catch (Exception $e) {
            $this->sendJsonResponse(['error' => $e->getMessage()], 500);
        }
    }
    
    /**
     * Get the collection hierarchy
     * 
     * @return void
     */
    public function getCollectionHierarchy() 
    {
        try {
            // Get the root collection
            $root = $this->collection->getRoot();
            
            if (!$root) {
                $this->sendJsonResponse(['error' => 'Root collection not found.'], 404);
                return;
            }
            
            // Build the hierarchy recursively
            $hierarchy = $this->buildCollectionHierarchy($root['id']);
            
            $this->sendJsonResponse($hierarchy);
        } catch (Exception $e) {
            $this->sendJsonResponse(['error' => $e->getMessage()], 500);
        }
    }
    
    /**
     * Search for collections and assets
     * 
     * @return void
     */
    public function search() 
    {
        try {
            // Get search query
            $query = $_GET['q'] ?? '';
            
            if (empty($query)) {
                $this->sendJsonResponse(['error' => 'Search query is required.'], 400);
                return;
            }
            
            // Search in collections
            $db = $this->collection->getDb();
            $stmt = $db->prepare("
                SELECT id, slug, name, title, description
                FROM collections
                WHERE (name LIKE :query OR title LIKE :query OR description LIKE :query)
                AND (protected = 0 OR :is_authenticated = 1)
            ");
            $stmt->bindValue(':query', '%' . $query . '%');
            $stmt->bindValue(':is_authenticated', $this->isApiAuthenticated() ? 1 : 0, \PDO::PARAM_INT);
            $stmt->execute();
            $collections = $stmt->fetchAll();
            
            // Search in assets
            $stmt = $db->prepare("
                SELECT a.id, a.filename, a.filetype, a.filesize, acm.display_name, acm.description
                FROM assets a
                JOIN asset_collection_membership acm ON a.id = acm.asset_id
                JOIN collections c ON acm.collection_id = c.id
                WHERE (a.filename LIKE :query OR acm.display_name LIKE :query OR acm.description LIKE :query)
                AND (c.protected = 0 OR :is_authenticated = 1)
                GROUP BY a.id
            ");
            $stmt->bindValue(':query', '%' . $query . '%');
            $stmt->bindValue(':is_authenticated', $this->isApiAuthenticated() ? 1 : 0, \PDO::PARAM_INT);
            $stmt->execute();
            $assets = $stmt->fetchAll();
            
            // Prepare asset data
            $assetResults = [];
            foreach ($assets as $asset) {
                $assetResults[] = [
                    'id' => $asset['id'],
                    'filename' => $asset['filename'],
                    'filetype' => $asset['filetype'],
                    'filesize' => $asset['filesize'],
                    'display_name' => $asset['display_name'],
                    'description' => $asset['description'],
                    'url' => '/api/assets/' . $asset['id']
                ];
            }
            
            // Prepare collection data
            $collectionResults = [];
            foreach ($collections as $collection) {
                $collectionResults[] = [
                    'id' => $collection['id'],
                    'slug' => $collection['slug'],
                    'name' => $collection['name'],
                    'title' => $collection['title'],
                    'description' => $collection['description'],
                    'url' => '/api/collections/' . $collection['slug']
                ];
            }
            
            // Prepare the response data
            $responseData = [
                'query' => $query,
                'collections' => $collectionResults,
                'assets' => $assetResults
            ];
            
            $this->sendJsonResponse($responseData);
        } catch (Exception $e) {
            $this->sendJsonResponse(['error' => $e->getMessage()], 500);
        }
    }
    
    /**
     * Authenticate API request
     * 
     * @return void
     */
    public function authenticate() 
    {
        try {
            // Get login data
            $username = $_POST['username'] ?? '';
            $password = $_POST['password'] ?? '';
            
            // Try to authenticate
            $user = $this->user->authenticate($username, $password);
            
            if (!$user) {
                $this->sendJsonResponse(['error' => 'Invalid username or password.'], 401);
                return;
            }
            
            // Generate API token
            $token = $this->generateApiToken($user['id']);
            
            // Prepare the response data
            $responseData = [
                'token' => $token,
                'user' => [
                    'id' => $user['id'],
                    'username' => $user['username']
                ]
            ];
            
            $this->sendJsonResponse($responseData);
        } catch (Exception $e) {
            $this->sendJsonResponse(['error' => $e->getMessage()], 500);
        }
    }
    
    /**
     * Check if API request is authenticated
     * 
     * @return bool True if authenticated
     */
    protected function isApiAuthenticated() 
    {
        // Check for Authorization header
        $authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
        $token = '';
        
        // Extract token from Bearer authorization
        if (strpos($authHeader, 'Bearer ') === 0) {
            $token = substr($authHeader, 7);
        }
        
        // If no token in header, check for token parameter
        if (empty($token)) {
            $token = $_GET['token'] ?? '';
        }
        
        // If still no token, not authenticated
        if (empty($token)) {
            return false;
        }
        
        // Verify token
        try {
            $userId = $this->verifyApiToken($token);
            return $userId !== false;
        } catch (Exception $e) {
            return false;
        }
    }
    
    /**
     * Generate an API token for a user
     * 
     * @param int $userId User ID
     * @return string API token
     */
    protected function generateApiToken($userId) 
    {
        // In a real application, you would use a more secure method
        // This is a simple example that creates a token with user ID and expiration
        
        $payload = [
            'user_id' => $userId,
            'exp' => time() + 3600, // 1 hour expiration
            'random' => bin2hex(random_bytes(16))
        ];
        
        // Convert payload to base64
        $tokenPayload = base64_encode(json_encode($payload));
        
        // Add a simple signature (in a real app, use HMAC with a secret key)
        $signature = hash('sha256', $tokenPayload . 'scenes_secret_key');
        
        // Combine payload and signature
        return $tokenPayload . '.' . $signature;
    }
    
    /**
     * Verify an API token
     * 
     * @param string $token API token
     * @return int|bool User ID if valid, false otherwise
     */
    protected function verifyApiToken($token) 
    {
        // Split token into payload and signature
        $parts = explode('.', $token);
        
        if (count($parts) !== 2) {
            return false;
        }
        
        list($tokenPayload, $signature) = $parts;
        
        // Verify signature
        $expectedSignature = hash('sha256', $tokenPayload . 'scenes_secret_key');
        
        if ($signature !== $expectedSignature) {
            return false;
        }
        
        // Decode payload
        $payload = json_decode(base64_decode($tokenPayload), true);
        
        if (!$payload || !isset($payload['user_id']) || !isset($payload['exp'])) {
            return false;
        }
        
        // Check if token is expired
        if ($payload['exp'] < time()) {
            return false;
        }
        
        return $payload['user_id'];
    }
    
    /**
     * Send a JSON response
     * 
     * @param mixed $data Response data
     * @param int $statusCode HTTP status code
     * @return void
     */
    protected function sendJsonResponse($data, $statusCode = 200) 
    {
        // Set status code
        http_response_code($statusCode);
        
        // Set headers
        header('Content-Type: application/json');
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Headers: Authorization, Content-Type');
        
        // Send JSON response
        echo json_encode($data, JSON_PRETTY_PRINT);
        exit;
    }
    
    /**
     * Prepare asset data for API response
     * 
     * @param array $asset Asset data
     * @return array Prepared asset data
     */
    protected function prepareAssetData($asset) 
    {
        // Basic asset data
        $data = [
            'id' => $asset['id'],
            'filename' => $asset['filename'],
            'filetype' => $asset['filetype'],
            'filesize' => $asset['filesize']
        ];
        
        // Add context-specific data if available
        if (isset($asset['display_name'])) {
            $data['display_name'] = $asset['display_name'];
        }
        
        if (isset($asset['description'])) {
            $data['description'] = $asset['description'];
        }
        
        if (isset($asset['sort_order'])) {
            $data['sort_order'] = $asset['sort_order'];
        }
        
        // Add URLs
        $data['url'] = '/api/assets/' . $asset['id'];
        $data['stream_url'] = '/api/assets/' . $asset['id'] . '/stream';
        
        return $data;
    }
    
    /**
     * Prepare child collections for API response
     * 
     * @param array $children Child collections
     * @return array Prepared child collections
     */
    protected function prepareChildCollections($children) 
    {
        $result = [];
        
        foreach ($children as $child) {
            $result[] = [
                'id' => $child['id'],
                'slug' => $child['slug'],
                'name' => $child['name'],
                'title' => $child['title'],
                'description' => $child['description'],
                'show_metadata' => $child['show_metadata'],
                'sort_order' => $child['sort_order'],
                'display_mode' => $child['display_mode'],
                'url' => '/api/collections/' . $child['slug']
            ];
        }
        
        return $result;
    }
    
    /**
     * Prepare asset collections for API response
     * 
     * @param array $collections Asset collections
     * @return array Prepared asset collections
     */
    protected function prepareAssetCollections($collections) 
    {
        $result = [];
        
        foreach ($collections as $collection) {
            $result[] = [
                'id' => $collection['id'],
                'slug' => $collection['slug'],
                'name' => $collection['name'],
                'title' => $collection['title'],
                'display_name' => $collection['display_name'],
                'description' => $collection['description'],
                'sort_order' => $collection['sort_order'],
                'url' => '/api/collections/' . $collection['slug']
            ];
        }
        
        return $result;
    }
    
    /**
     * Build the collection hierarchy recursively
     * 
     * @param int $collectionId Collection ID
     * @return array Collection hierarchy
     */
    protected function buildCollectionHierarchy($collectionId) 
    {
        // Get the collection
        $collection = $this->collection->find($collectionId);
        
        if (!$collection) {
            return null;
        }
        
        // Check if collection is protected and request is not authenticated
        if ($collection['protected'] && !$this->isApiAuthenticated()) {
            return [
                'id' => $collection['id'],
                'slug' => $collection['slug'],
                'name' => $collection['name'],
                'protected' => true,
                'url' => '/api/collections/' . $collection['slug']
            ];
        }
        
        // Get child collections
        $children = $this->collection->getChildren($collectionId);
        
        // Build child hierarchies
        $childHierarchies = [];
        foreach ($children as $child) {
            $childHierarchy = $this->buildCollectionHierarchy($child['id']);
            if ($childHierarchy) {
                $childHierarchies[] = $childHierarchy;
            }
        }
        
        // Build the result
        $result = [
            'id' => $collection['id'],
            'slug' => $collection['slug'],
            'name' => $collection['name'],
            'title' => $collection['title'],
            'protected' => $collection['protected'] == 1,
            'url' => '/api/collections/' . $collection['slug'],
            'children' => $childHierarchies
        ];
        
        return $result;
    }
}
