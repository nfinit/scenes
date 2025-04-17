<?php

namespace Scenes\Controllers;

use Scenes\Models\Collection;
use Scenes\Models\Asset;
use Exception;

/**
 * CollectionController
 * 
 * Handles all operations related to collections in the Scenes application
 */
class CollectionController extends BaseController 
{
    /**
     * @var Collection Collection model instance
     */
    protected $collection;
    
    /**
     * @var Asset Asset model instance
     */
    protected $asset;
    
    /**
     * Constructor
     */
    public function __construct() 
    {
        parent::__construct();
        $this->collection = new Collection();
        $this->asset = new Asset();
    }
    
    /**
     * View a collection by its slug
     * 
     * @param string $slug Collection slug
     * @return void
     */
    public function view($slug = 'root') 
    {
        try {
            // Get the collection
            $collection = $this->collection->findBySlug($slug);
            
            if (!$collection) {
                $this->notFound('Collection not found.');
                return;
            }
            
            // Check if collection is protected and user is not authenticated
            if ($collection['protected'] && !$this->isAuthenticated()) {
                $this->redirect('/auth/login?redirect=' . urlencode($_SERVER['REQUEST_URI']));
                return;
            }
            
            // Get parent collections for breadcrumb navigation
            $parents = $this->collection->getParents($collection['id']);
            
            // Get child collections
            $children = $this->collection->getChildren($collection['id']);
            
            // Get collection display mode
            $displayMode = $this->collection->getDisplayMode($collection['id']);
            
            // Get collection assets
            $assets = $this->collection->getAssets($collection['id']);
            
            // Group assets by their group_id
            $assetGroups = [];
            $ungroupedAssets = [];
            
            foreach ($assets as $asset) {
                if (!empty($asset['group_id'])) {
                    if (!isset($assetGroups[$asset['group_id']])) {
                        $assetGroups[$asset['group_id']] = [
                            'id' => $asset['group_id'],
                            'name' => $asset['group_name'],
                            'description' => $asset['group_description'],
                            'assets' => []
                        ];
                    }
                    $assetGroups[$asset['group_id']]['assets'][] = $asset;
                } else {
                    $ungroupedAssets[] = $asset;
                }
            }
            
            // Prepare the data for the view
            $viewData = [
                'collection' => $collection,
                'parents' => $parents,
                'children' => $children,
                'displayMode' => $displayMode,
                'assetGroups' => $assetGroups,
                'ungroupedAssets' => $ungroupedAssets
            ];
            
            // Render the appropriate template based on display mode
            $template = 'collections/' . $displayMode . '.php';
            if (!file_exists($this->viewPath . $template)) {
                $template = 'collections/linear.php'; // Default to linear view
            }
            
            $this->renderTemplate($template, $viewData);
        } catch (Exception $e) {
            $this->handleError($e);
        }
    }
    
    /**
     * Display the form to create a new collection
     * 
     * @return void
     */
    public function create() 
    {
        // Check if user is authenticated and has appropriate permissions
        if (!$this->isAuthenticated() || !$this->hasPermission('create_collections')) {
            $this->forbidden('You do not have permission to create collections.');
            return;
        }
        
        try {
            // Get all collections for parent selection
            $allCollections = $this->collection->all();
            
            // Get available display modes
            $displayModes = $this->collection->getAvailableDisplayModes();
            
            // Prepare the data for the view
            $viewData = [
                'allCollections' => $allCollections,
                'displayModes' => $displayModes
            ];
            
            $this->renderTemplate('collections/create.php', $viewData);
        } catch (Exception $e) {
            $this->handleError($e);
        }
    }
    
    /**
     * Process the form to store a new collection
     * 
     * @return void
     */
    public function store() 
    {
        // Check if user is authenticated and has appropriate permissions
        if (!$this->isAuthenticated() || !$this->hasPermission('create_collections')) {
            $this->forbidden('You do not have permission to create collections.');
            return;
        }
        
        try {
            // Validate form inputs
            $this->validateRequest([
                'slug' => 'required|alpha_dash|unique:collections',
                'name' => 'required',
                'display_mode' => 'required|in:grid,linear,tabular'
            ]);
            
            // Get form data
            $collectionData = [
                'slug' => $_POST['slug'],
                'name' => $_POST['name'],
                'title' => $_POST['title'] ?? null,
                'description' => $_POST['description'] ?? null,
                'protected' => isset($_POST['protected']) ? 1 : 0
            ];
            
            // Begin transaction
            $this->collection->beginTransaction();
            
            // Create the collection
            $collectionId = $this->collection->create($collectionData);
            
            if (!$collectionId) {
                throw new Exception('Failed to create collection.');
            }
            
            // Set the display mode
            $displayMode = $_POST['display_mode'];
            $this->collection->setDisplayMode($collectionId, $displayMode);
            
            // Add parent relationships if specified
            if (isset($_POST['parent_ids']) && is_array($_POST['parent_ids'])) {
                foreach ($_POST['parent_ids'] as $parentId) {
                    $this->collection->addChild($parentId, $collectionId);
                }
            }
            
            // Commit the transaction
            $this->collection->commit();
            
            // Redirect to the new collection
            $this->redirect('/collections/view/' . $collectionData['slug']);
        } catch (Exception $e) {
            $this->collection->rollback();
            $this->handleError($e);
        }
    }
    
    /**
     * Display the form to edit a collection
     * 
     * @param int $id Collection ID
     * @return void
     */
    public function edit($id) 
    {
        // Check if user is authenticated and has appropriate permissions
        if (!$this->isAuthenticated() || !$this->hasPermission('edit_collections')) {
            $this->forbidden('You do not have permission to edit collections.');
            return;
        }
        
        try {
            // Get the collection
            $collection = $this->collection->find($id);
            
            if (!$collection) {
                $this->notFound('Collection not found.');
                return;
            }
            
            // Get parent collections
            $parents = $this->collection->getParents($id);
            
            // Get all collections for parent selection (excluding the current collection)
            $allCollections = array_filter(
                $this->collection->all(),
                function($c) use ($id) {
                    return $c['id'] != $id;
                }
            );
            
            // Get available display modes
            $displayModes = $this->collection->getAvailableDisplayModes();
            
            // Get current display mode
            $currentDisplayMode = $this->collection->getDisplayMode($id);
            
            // Prepare the data for the view
            $viewData = [
                'collection' => $collection,
                'parents' => $parents,
                'allCollections' => $allCollections,
                'displayModes' => $displayModes,
                'currentDisplayMode' => $currentDisplayMode
            ];
            
            $this->renderTemplate('collections/edit.php', $viewData);
        } catch (Exception $e) {
            $this->handleError($e);
        }
    }
    
    /**
     * Process the form to update a collection
     * 
     * @param int $id Collection ID
     * @return void
     */
    public function update($id) 
    {
        // Check if user is authenticated and has appropriate permissions
        if (!$this->isAuthenticated() || !$this->hasPermission('edit_collections')) {
            $this->forbidden('You do not have permission to edit collections.');
            return;
        }
        
        try {
            // Get the collection
            $collection = $this->collection->find($id);
            
            if (!$collection) {
                $this->notFound('Collection not found.');
                return;
            }
            
            // Validate form inputs
            $this->validateRequest([
                'slug' => 'required|alpha_dash|unique:collections,slug,' . $id,
                'name' => 'required',
                'display_mode' => 'required|in:grid,linear,tabular'
            ]);
            
            // Get form data
            $collectionData = [
                'slug' => $_POST['slug'],
                'name' => $_POST['name'],
                'title' => $_POST['title'] ?? null,
                'description' => $_POST['description'] ?? null,
                'protected' => isset($_POST['protected']) ? 1 : 0
            ];
            
            // Begin transaction
            $this->collection->beginTransaction();
            
            // Update the collection
            $updated = $this->collection->update($id, $collectionData);
            
            if (!$updated) {
                throw new Exception('Failed to update collection.');
            }
            
            // Update the display mode
            $displayMode = $_POST['display_mode'];
            $this->collection->setDisplayMode($id, $displayMode);
            
            // Get current parent relationships
            $currentParents = $this->collection->getParents($id);
            $currentParentIds = array_column($currentParents, 'id');
            
            // Get new parent relationships
            $newParentIds = isset($_POST['parent_ids']) && is_array($_POST['parent_ids']) 
                ? $_POST['parent_ids'] 
                : [];
            
            // Remove removed parent relationships
            foreach ($currentParentIds as $parentId) {
                if (!in_array($parentId, $newParentIds)) {
                    $this->collection->removeChild($parentId, $id);
                }
            }
            
            // Add new parent relationships
            foreach ($newParentIds as $parentId) {
                if (!in_array($parentId, $currentParentIds)) {
                    $this->collection->addChild($parentId, $id);
                }
            }
            
            // Commit the transaction
            $this->collection->commit();
            
            // Redirect to the updated collection
            $this->redirect('/collections/view/' . $collectionData['slug']);
        } catch (Exception $e) {
            $this->collection->rollback();
            $this->handleError($e);
        }
    }
    
    /**
     * Confirm deletion of a collection
     * 
     * @param int $id Collection ID
     * @return void
     */
    public function confirmDelete($id) 
    {
        // Check if user is authenticated and has appropriate permissions
        if (!$this->isAuthenticated() || !$this->hasPermission('delete_collections')) {
            $this->forbidden('You do not have permission to delete collections.');
            return;
        }
        
        try {
            // Get the collection
            $collection = $this->collection->find($id);
            
            if (!$collection) {
                $this->notFound('Collection not found.');
                return;
            }
            
            // Prevent deletion of system collections
            if (in_array($collection['slug'], ['root', 'assets'])) {
                $this->forbidden('System collections cannot be deleted.');
                return;
            }
            
            // Prepare the data for the view
            $viewData = [
                'collection' => $collection
            ];
            
            $this->renderTemplate('collections/confirm_delete.php', $viewData);
        } catch (Exception $e) {
            $this->handleError($e);
        }
    }
    
    /**
     * Delete a collection
     * 
     * @param int $id Collection ID
     * @return void
     */
    public function delete($id) 
    {
        // Check if user is authenticated and has appropriate permissions
        if (!$this->isAuthenticated() || !$this->hasPermission('delete_collections')) {
            $this->forbidden('You do not have permission to delete collections.');
            return;
        }
        
        try {
            // Get the collection
            $collection = $this->collection->find($id);
            
            if (!$collection) {
                $this->notFound('Collection not found.');
                return;
            }
            
            // Prevent deletion of system collections
            if (in_array($collection['slug'], ['root', 'assets'])) {
                $this->forbidden('System collections cannot be deleted.');
                return;
            }
            
            // Delete the collection
            $deleted = $this->collection->delete($id);
            
            if (!$deleted) {
                throw new Exception('Failed to delete collection.');
            }
            
            // Redirect to the root collection
            $this->redirect('/collections/view/root');
        } catch (Exception $e) {
            $this->handleError($e);
        }
    }
    
    /**
     * Manage assets in a collection
     * 
     * @param int $id Collection ID
     * @return void
     */
    public function manageAssets($id) 
    {
        // Check if user is authenticated and has appropriate permissions
        if (!$this->isAuthenticated() || !$this->hasPermission('manage_collection_assets')) {
            $this->forbidden('You do not have permission to manage assets in collections.');
            return;
        }
        
        try {
            // Get the collection
            $collection = $this->collection->find($id);
            
            if (!$collection) {
                $this->notFound('Collection not found.');
                return;
            }
            
            // Get collection assets
            $assets = $this->collection->getAssets($id);
            
            // Group assets by their group_id
            $assetGroups = [];
            $ungroupedAssets = [];
            
            foreach ($assets as $asset) {
                if (!empty($asset['group_id'])) {
                    if (!isset($assetGroups[$asset['group_id']])) {
                        $assetGroups[$asset['group_id']] = [
                            'id' => $asset['group_id'],
                            'name' => $asset['group_name'],
                            'description' => $asset['group_description'],
                            'assets' => []
                        ];
                    }
                    $assetGroups[$asset['group_id']]['assets'][] = $asset;
                } else {
                    $ungroupedAssets[] = $asset;
                }
            }
            
            // Get available assets in the system ('assets' collection)
            $availableAssets = $this->collection->getAssets(2); // Assuming assets collection has ID 2
            
            // Filter out assets already in this collection
            $collectionAssetIds = array_column($assets, 'id');
            $availableAssets = array_filter(
                $availableAssets,
                function($asset) use ($collectionAssetIds) {
                    return !in_array($asset['id'], $collectionAssetIds);
                }
            );
            
            // Prepare the data for the view
            $viewData = [
                'collection' => $collection,
                'assetGroups' => $assetGroups,
                'ungroupedAssets' => $ungroupedAssets,
                'availableAssets' => $availableAssets
            ];
            
            $this->renderTemplate('collections/manage_assets.php', $viewData);
        } catch (Exception $e) {
            $this->handleError($e);
        }
    }
    
    /**
     * Add an asset to a collection
     * 
     * @param int $collectionId Collection ID
     * @param int $assetId Asset ID
     * @return void
     */
    public function addAsset($collectionId, $assetId) 
    {
        // Check if user is authenticated and has appropriate permissions
        if (!$this->isAuthenticated() || !$this->hasPermission('manage_collection_assets')) {
            $this->forbidden('You do not have permission to manage assets in collections.');
            return;
        }
        
        try {
            // Get the collection
            $collection = $this->collection->find($collectionId);
            
            if (!$collection) {
                $this->notFound('Collection not found.');
                return;
            }
            
            // Get the asset
            $asset = $this->asset->find($assetId);
            
            if (!$asset) {
                $this->notFound('Asset not found.');
                return;
            }
            
            // Add the asset to the collection
            $metadata = [
                'display_name' => $_POST['display_name'] ?? null,
                'description' => $_POST['description'] ?? null,
                'sort_order' => $_POST['sort_order'] ?? 0
            ];
            
            $membershipId = $this->collection->addAsset($collectionId, $assetId, $metadata);
            
            if (!$membershipId) {
                throw new Exception('Failed to add asset to collection.');
            }
            
            // Redirect back to asset management
            $this->redirect('/collections/manage-assets/' . $collectionId);
        } catch (Exception $e) {
            $this->handleError($e);
        }
    }
    
    /**
     * Remove an asset from a collection
     * 
     * @param int $collectionId Collection ID
     * @param int $assetId Asset ID
     * @return void
     */
    public function removeAsset($collectionId, $assetId) 
    {
        // Check if user is authenticated and has appropriate permissions
        if (!$this->isAuthenticated() || !$this->hasPermission('manage_collection_assets')) {
            $this->forbidden('You do not have permission to manage assets in collections.');
            return;
        }
        
        try {
            // Get the collection
            $collection = $this->collection->find($collectionId);
            
            if (!$collection) {
                $this->notFound('Collection not found.');
                return;
            }
            
            // Get the asset
            $asset = $this->asset->find($assetId);
            
            if (!$asset) {
                $this->notFound('Asset not found.');
                return;
            }
            
            // Remove the asset from the collection
            $removed = $this->collection->removeAsset($collectionId, $assetId);
            
            if (!$removed) {
                throw new Exception('Failed to remove asset from collection.');
            }
            
            // Redirect back to asset management
            $this->redirect('/collections/manage-assets/' . $collectionId);
        } catch (Exception $e) {
            $this->handleError($e);
        }
    }
    
    /**
     * Edit asset metadata in a collection
     * 
     * @param int $membershipId Membership ID
     * @return void
     */
    public function editAssetMetadata($membershipId) 
    {
        // Check if user is authenticated and has appropriate permissions
        if (!$this->isAuthenticated() || !$this->hasPermission('manage_collection_assets')) {
            $this->forbidden('You do not have permission to manage assets in collections.');
            return;
        }
        
        try {
            // Get the membership data (join asset_collection_membership with assets and collections)
            $db = $this->collection->getDb();
            $stmt = $db->prepare("
                SELECT acm.*, a.filename, a.filepath, a.filetype, c.name as collection_name, c.id as collection_id
                FROM asset_collection_membership acm
                JOIN assets a ON acm.asset_id = a.id
                JOIN collections c ON acm.collection_id = c.id
                WHERE acm.id = :membership_id
            ");
            $stmt->bindValue(':membership_id', $membershipId, \PDO::PARAM_INT);
            $stmt->execute();
            $membership = $stmt->fetch();
            
            if (!$membership) {
                $this->notFound('Asset membership not found.');
                return;
            }
            
            // Prepare the data for the view
            $viewData = [
                'membership' => $membership
            ];
            
            $this->renderTemplate('collections/edit_asset_metadata.php', $viewData);
        } catch (Exception $e) {
            $this->handleError($e);
        }
    }
    
    /**
     * Update asset metadata in a collection
     * 
     * @param int $membershipId Membership ID
     * @return void
     */
    public function updateAssetMetadata($membershipId) 
    {
        // Check if user is authenticated and has appropriate permissions
        if (!$this->isAuthenticated() || !$this->hasPermission('manage_collection_assets')) {
            $this->forbidden('You do not have permission to manage assets in collections.');
            return;
        }
        
        try {
            // Get the membership data
            $db = $this->collection->getDb();
            $stmt = $db->prepare("
                SELECT acm.*, c.id as collection_id
                FROM asset_collection_membership acm
                JOIN collections c ON acm.collection_id = c.id
                WHERE acm.id = :membership_id
            ");
            $stmt->bindValue(':membership_id', $membershipId, \PDO::PARAM_INT);
            $stmt->execute();
            $membership = $stmt->fetch();
            
            if (!$membership) {
                $this->notFound('Asset membership not found.');
                return;
            }
            
            // Update the metadata
            $metadata = [
                'display_name' => $_POST['display_name'] ?? null,
                'description' => $_POST['description'] ?? null,
                'sort_order' => $_POST['sort_order'] ?? 0
            ];
            
            $updated = $this->collection->updateAssetMetadata($membershipId, $metadata);
            
            if (!$updated) {
                throw new Exception('Failed to update asset metadata.');
            }
            
            // Redirect back to asset management
            $this->redirect('/collections/manage-assets/' . $membership['collection_id']);
        } catch (Exception $e) {
            $this->handleError($e);
        }
    }
    
    /**
     * Create a new asset group in a collection
     * 
     * @param int $collectionId Collection ID
     * @return void
     */
    public function createAssetGroup($collectionId) 
    {
        // Check if user is authenticated and has appropriate permissions
        if (!$this->isAuthenticated() || !$this->hasPermission('manage_collection_assets')) {
            $this->forbidden('You do not have permission to manage assets in collections.');
            return;
        }
        
        try {
            // Get the collection
            $collection = $this->collection->find($collectionId);
            
            if (!$collection) {
                $this->notFound('Collection not found.');
                return;
            }
            
            // Create the group
            $name = $_POST['name'] ?? null;
            $description = $_POST['description'] ?? null;
            $displayMode = $_POST['display_mode'] ?? 'linear';
            
            $groupId = $this->collection->createAssetGroup($collectionId, $name, $description, $displayMode);
            
            if (!$groupId) {
                throw new Exception('Failed to create asset group.');
            }
            
            // Redirect back to asset management
            $this->redirect('/collections/manage-assets/' . $collectionId);
        } catch (Exception $e) {
            $this->handleError($e);
        }
    }
    
    /**
     * Add an asset to a group
     * 
     * @param int $groupId Group ID
     * @param int $membershipId Membership ID
     * @return void
     */
    public function addAssetToGroup($groupId, $membershipId) 
    {
        // Check if user is authenticated and has appropriate permissions
        if (!$this->isAuthenticated() || !$this->hasPermission('manage_collection_assets')) {
            $this->forbidden('You do not have permission to manage assets in collections.');
            return;
        }
        
        try {
            // Get the group
            $db = $this->collection->getDb();
            $stmt = $db->prepare("
                SELECT ag.*, c.id as collection_id
                FROM asset_groups ag
                JOIN collections c ON ag.collection_id = c.id
                WHERE ag.id = :group_id
            ");
            $stmt->bindValue(':group_id', $groupId, \PDO::PARAM_INT);
            $stmt->execute();
            $group = $stmt->fetch();
            
            if (!$group) {
                $this->notFound('Asset group not found.');
                return;
            }
            
            // Get the membership
            $stmt = $db->prepare("
                SELECT acm.*
                FROM asset_collection_membership acm
                WHERE acm.id = :membership_id
            ");
            $stmt->bindValue(':membership_id', $membershipId, \PDO::PARAM_INT);
            $stmt->execute();
            $membership = $stmt->fetch();
            
            if (!$membership) {
                $this->notFound('Asset membership not found.');
                return;
            }
            
            // Add the asset to the group
            $sortOrder = $_POST['sort_order'] ?? 0;
            $added = $this->collection->addAssetToGroup($groupId, $membershipId, $sortOrder);
            
            if (!$added) {
                throw new Exception('Failed to add asset to group.');
            }
            
            // Redirect back to asset management
            $this->redirect('/collections/manage-assets/' . $group['collection_id']);
        } catch (Exception $e) {
            $this->handleError($e);
        }
    }
    
    /**
     * Remove an asset from a group
     * 
     * @param int $membershipId Membership ID
     * @return void
     */
    public function removeAssetFromGroup($membershipId) 
    {
        // Check if user is authenticated and has appropriate permissions
        if (!$this->isAuthenticated() || !$this->hasPermission('manage_collection_assets')) {
            $this->forbidden('You do not have permission to manage assets in collections.');
            return;
        }
        
        try {
            // Get the membership data
            $db = $this->collection->getDb();
            $stmt = $db->prepare("
                SELECT acm.*, c.id as collection_id
                FROM asset_collection_membership acm
                JOIN collections c ON acm.collection_id = c.id
                WHERE acm.id = :membership_id
            ");
            $stmt->bindValue(':membership_id', $membershipId, \PDO::PARAM_INT);
            $stmt->execute();
            $membership = $stmt->fetch();
            
            if (!$membership) {
                $this->notFound('Asset membership not found.');
                return;
            }
            
            // Remove the asset from the group
            $removed = $this->collection->removeAssetFromGroup($membershipId);
            
            if (!$removed) {
                throw new Exception('Failed to remove asset from group.');
            }
            
            // Redirect back to asset management
            $this->redirect('/collections/manage-assets/' . $membership['collection_id']);
        } catch (Exception $e) {
            $this->handleError($e);
        }
    }
    
    /**
     * Clone a collection (metadata only)
     * 
     * @param int $id Collection ID
     * @return void
     */
    public function cloneCollection($id) 
    {
        // Check if user is authenticated and has appropriate permissions
        if (!$this->isAuthenticated() || !$this->hasPermission('create_collections')) {
            $this->forbidden('You do not have permission to clone collections.');
            return;
        }
        
        try {
            // Get the collection
            $collection = $this->collection->find($id);
            
            if (!$collection) {
                $this->notFound('Collection not found.');
                return;
            }
            
            // Prepare clone data
            $cloneData = [
                'slug' => $collection['slug'] . '-clone',
                'name' => $collection['name'] . ' (Clone)',
                'title' => $collection['title'],
                'description' => $collection['description'],
                'protected' => $collection['protected']
            ];
            
            // Clone the collection
            $cloneId = $this->collection->cloneCollection($id, $cloneData);
            
            if (!$cloneId) {
                throw new Exception('Failed to clone collection.');
            }
            
            // Redirect to the cloned collection
            $this->redirect('/collections/view/' . $cloneData['slug']);
        } catch (Exception $e) {
            $this->handleError($e);
        }
    }
    
    /**
     * Clone a collection with its assets
     * 
     * @param int $id Collection ID
     * @return void
     */
    public function cloneCollectionWithAssets($id) 
    {
        // Check if user is authenticated and has appropriate permissions
        if (!$this->isAuthenticated() || !$this->hasPermission('create_collections')) {
            $this->forbidden('You do not have permission to clone collections.');
            return;
        }
        
        try {
            // Get the collection
            $collection = $this->collection->find($id);
            
            if (!$collection) {
                $this->notFound('Collection not found.');
                return;
            }
            
            // Prepare clone data
            $cloneData = [
                'slug' => $collection['slug'] . '-clone',
                'name' => $collection['name'] . ' (Clone with Assets)',
                'title' => $collection['title'],
                'description' => $collection['description'],
                'protected' => $collection['protected']
            ];
            
            // Clone the collection with assets
            $cloneId = $this->collection->cloneCollectionWithAssets($id, $cloneData);
            
            if (!$cloneId) {
                throw new Exception('Failed to clone collection with assets.');
            }
            
            // Redirect to the cloned collection
            $this->redirect('/collections/view/' . $cloneData['slug']);
        } catch (Exception $e) {
            $this->handleError($e);
        }
    }
}
