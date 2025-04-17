<?php

namespace Scenes\Controllers;

use Scenes\Models\Asset;
use Scenes\Models\Collection;
use Exception;

/**
 * AssetController
 * 
 * Handles all operations related to assets in the Scenes application
 */
class AssetController extends BaseController 
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
     * @var string Path to assets upload directory
     */
    protected $uploadPath;
    
    /**
     * Constructor
     */
    public function __construct() 
    {
        parent::__construct();
        $this->asset = new Asset();
        $this->collection = new Collection();
        
        // Set upload path
        $this->uploadPath = dirname(dirname(__DIR__)) . '/data/assets/';
        
        // Ensure upload directory exists
        if (!is_dir($this->uploadPath)) {
            mkdir($this->uploadPath, 0755, true);
        }
    }
    
    /**
     * Display upload form
     * 
     * @return void
     */
    public function upload() 
    {
        // Check if user is authenticated and has appropriate permissions
        if (!$this->isAuthenticated() || !$this->hasPermission('upload_assets')) {
            $this->forbidden('You do not have permission to upload assets.');
            return;
        }
        
        try {
            // Get all collections for target selection
            $collections = $this->collection->all();
            
            // Prepare the data for the view
            $viewData = [
                'collections' => $collections
            ];
            
            $this->renderTemplate('assets/upload.php', $viewData);
        } catch (Exception $e) {
            $this->handleError($e);
        }
    }
    
    /**
     * Process file upload
     * 
     * @return void
     */
    public function store() 
    {
        // Check if user is authenticated and has appropriate permissions
        if (!$this->isAuthenticated() || !$this->hasPermission('upload_assets')) {
            $this->forbidden('You do not have permission to upload assets.');
            return;
        }
        
        try {
            // Check if files were uploaded
            if (empty($_FILES['assets']) || $_FILES['assets']['error'][0] === UPLOAD_ERR_NO_FILE) {
                throw new Exception('No files were uploaded.');
            }
            
            // Begin transaction
            $this->asset->beginTransaction();
            
            $assetIds = [];
            $errors = [];
            
            // Process each uploaded file
            for ($i = 0; $i < count($_FILES['assets']['name']); $i++) {
                $fileData = [
                    'name' => $_FILES['assets']['name'][$i],
                    'type' => $_FILES['assets']['type'][$i],
                    'tmp_name' => $_FILES['assets']['tmp_name'][$i],
                    'error' => $_FILES['assets']['error'][$i],
                    'size' => $_FILES['assets']['size'][$i]
                ];
                
                if ($fileData['error'] === UPLOAD_ERR_OK) {
                    // Upload the file
                    $assetId = $this->asset->uploadFile($fileData, $this->uploadPath);
                    
                    if ($assetId) {
                        $assetIds[] = $assetId;
                        
                        // Add to selected collections if specified
                        if (isset($_POST['collection_ids']) && is_array($_POST['collection_ids'])) {
                            foreach ($_POST['collection_ids'] as $collectionId) {
                                $this->asset->addToCollection($assetId, $collectionId);
                            }
                        }
                    } else {
                        $errors[] = "Failed to upload file: {$fileData['name']}";
                    }
                } else {
                    $errors[] = "Error uploading file {$fileData['name']}: " . $this->getUploadErrorMessage($fileData['error']);
                }
            }
            
            // Commit the transaction
            $this->asset->commit();
            
            // Set flash messages
            if (!empty($assetIds)) {
                $this->setFlashMessage('success', 'Successfully uploaded ' . count($assetIds) . ' file(s).');
            }
            
            if (!empty($errors)) {
                $this->setFlashMessage('error', 'Errors occurred during upload: ' . implode(', ', $errors));
            }
            
            // Redirect to the assets list
            $this->redirect('/assets/list');
        } catch (Exception $e) {
            $this->asset->rollback();
            $this->setFlashMessage('error', 'Upload failed: ' . $e->getMessage());
            $this->redirect('/assets/upload');
        }
    }
    
    /**
     * List all assets
     * 
     * @return void
     */
    public function index() 
    {
        // Check if user is authenticated
        if (!$this->isAuthenticated()) {
            $this->redirect('/auth/login?redirect=' . urlencode($_SERVER['REQUEST_URI']));
            return;
        }
        
        try {
            // Get all assets from the assets collection (ID 2)
            $assets = $this->collection->getAssets(2);
            
            // Prepare the data for the view
            $viewData = [
                'assets' => $assets
            ];
            
            $this->renderTemplate('assets/index.php', $viewData);
        } catch (Exception $e) {
            $this->handleError($e);
        }
    }
    
    /**
     * View a single asset
     * 
     * @param int $id Asset ID
     * @return void
     */
    public function view($id) 
    {
        try {
            // Get the asset
            $asset = $this->asset->find($id);
            
            if (!$asset) {
                $this->notFound('Asset not found.');
                return;
            }
            
            // Get collections that contain this asset
            $collections = $this->asset->getCollections($id);
            
            // Check if any of the collections are protected and user is not authenticated
            $requiresAuth = false;
            foreach ($collections as $collection) {
                if ($collection['protected']) {
                    $requiresAuth = true;
                    break;
                }
            }
            
            if ($requiresAuth && !$this->isAuthenticated()) {
                $this->redirect('/auth/login?redirect=' . urlencode($_SERVER['REQUEST_URI']));
                return;
            }
            
            // Prepare the data for the view
            $viewData = [
                'asset' => $asset,
                'collections' => $collections
            ];
            
            $this->renderTemplate('assets/view.php', $viewData);
        } catch (Exception $e) {
            $this->handleError($e);
        }
    }
    
    /**
     * Edit asset information
     * 
     * @param int $id Asset ID
     * @return void
     */
    public function edit($id) 
    {
        // Check if user is authenticated and has appropriate permissions
        if (!$this->isAuthenticated() || !$this->hasPermission('edit_assets')) {
            $this->forbidden('You do not have permission to edit assets.');
            return;
        }
        
        try {
            // Get the asset
            $asset = $this->asset->find($id);
            
            if (!$asset) {
                $this->notFound('Asset not found.');
                return;
            }
            
            // Get collections that contain this asset
            $collections = $this->asset->getCollections($id);
            
            // Get all available collections for adding
            $allCollections = $this->collection->all();
            
            // Filter out collections that already contain this asset
            $collectionIds = array_column($collections, 'id');
            $availableCollections = array_filter(
                $allCollections,
                function($c) use ($collectionIds) {
                    return !in_array($c['id'], $collectionIds);
                }
            );
            
            // Prepare the data for the view
            $viewData = [
                'asset' => $asset,
                'collections' => $collections,
                'availableCollections' => $availableCollections
            ];
            
            $this->renderTemplate('assets/edit.php', $viewData);
        } catch (Exception $e) {
            $this->handleError($e);
        }
    }
    
    /**
     * Update asset information
     * 
     * @param int $id Asset ID
     * @return void
     */
    public function update($id) 
    {
        // Check if user is authenticated and has appropriate permissions
        if (!$this->isAuthenticated() || !$this->hasPermission('edit_assets')) {
            $this->forbidden('You do not have permission to edit assets.');
            return;
        }
        
        try {
            // Get the asset
            $asset = $this->asset->find($id);
            
            if (!$asset) {
                $this->notFound('Asset not found.');
                return;
            }
            
            // Update the asset
            $assetData = [
                'filename' => $_POST['filename'] ?? $asset['filename']
            ];
            
            $updated = $this->asset->update($id, $assetData);
            
            if (!$updated) {
                throw new Exception('Failed to update asset information.');
            }
            
            // Update collections if specified
            if (isset($_POST['add_to_collections']) && is_array($_POST['add_to_collections'])) {
                foreach ($_POST['add_to_collections'] as $collectionId) {
                    $this->asset->addToCollection($id, $collectionId);
                }
            }
            
            $this->setFlashMessage('success', 'Asset information updated successfully.');
            $this->redirect('/assets/view/' . $id);
        } catch (Exception $e) {
            $this->setFlashMessage('error', 'Failed to update asset: ' . $e->getMessage());
            $this->redirect('/assets/edit/' . $id);
        }
    }
    
    /**
     * Confirm deletion of an asset
     * 
     * @param int $id Asset ID
     * @return void
     */
    public function confirmDelete($id) 
    {
        // Check if user is authenticated and has appropriate permissions
        if (!$this->isAuthenticated() || !$this->hasPermission('delete_assets')) {
            $this->forbidden('You do not have permission to delete assets.');
            return;
        }
        
        try {
            // Get the asset
            $asset = $this->asset->find($id);
            
            if (!$asset) {
                $this->notFound('Asset not found.');
                return;
            }
            
            // Get collections that contain this asset
            $collections = $this->asset->getCollections($id);
            
            // Prepare the data for the view
            $viewData = [
                'asset' => $asset,
                'collections' => $collections
            ];
            
            $this->renderTemplate('assets/confirm_delete.php', $viewData);
        } catch (Exception $e) {
            $this->handleError($e);
        }
    }
    
    /**
     * Delete an asset
     * 
     * @param int $id Asset ID
     * @return void
     */
    public function delete($id) 
    {
        // Check if user is authenticated and has appropriate permissions
        if (!$this->isAuthenticated() || !$this->hasPermission('delete_assets')) {
            $this->forbidden('You do not have permission to delete assets.');
            return;
        }
        
        try {
            // Get the asset
            $asset = $this->asset->find($id);
            
            if (!$asset) {
                $this->notFound('Asset not found.');
                return;
            }
            
            // Delete the asset
            $deleteFile = isset($_POST['delete_file']) && $_POST['delete_file'] == 1;
            $deleted = $this->asset->deleteAsset($id, $deleteFile);
            
            if (!$deleted) {
                throw new Exception('Failed to delete asset.');
            }
            
            $this->setFlashMessage('success', 'Asset deleted successfully.');
            $this->redirect('/assets/list');
        } catch (Exception $e) {
            $this->setFlashMessage('error', 'Failed to delete asset: ' . $e->getMessage());
            $this->redirect('/assets/list');
        }
    }
    
    /**
     * Verify the integrity of an asset
     * 
     * @param int $id Asset ID
     * @return void
     */
    public function verifyIntegrity($id) 
    {
        // Check if user is authenticated and has appropriate permissions
        if (!$this->isAuthenticated() || !$this->hasPermission('manage_assets')) {
            $this->forbidden('You do not have permission to manage assets.');
            return;
        }
        
        try {
            // Get the asset
            $asset = $this->asset->find($id);
            
            if (!$asset) {
                $this->notFound('Asset not found.');
                return;
            }
            
            // Verify the integrity
            $integrity = $this->asset->verifyIntegrity($id);
            
            if ($integrity) {
                $this->setFlashMessage('success', 'Asset integrity verified successfully.');
            } else {
                $this->setFlashMessage('error', 'Asset integrity check failed. The file may be corrupted or missing.');
            }
            
            $this->redirect('/assets/view/' . $id);
        } catch (Exception $e) {
            $this->setFlashMessage('error', 'Failed to verify asset integrity: ' . $e->getMessage());
            $this->redirect('/assets/view/' . $id);
        }
    }
    
    /**
     * Get a human-readable error message for upload errors
     * 
     * @param int $errorCode The error code from $_FILES['error']
     * @return string Human-readable error message
     */
    protected function getUploadErrorMessage($errorCode) 
    {
        switch ($errorCode) {
            case UPLOAD_ERR_INI_SIZE:
                return 'The uploaded file exceeds the upload_max_filesize directive in php.ini';
            case UPLOAD_ERR_FORM_SIZE:
                return 'The uploaded file exceeds the MAX_FILE_SIZE directive in the HTML form';
            case UPLOAD_ERR_PARTIAL:
                return 'The uploaded file was only partially uploaded';
            case UPLOAD_ERR_NO_FILE:
                return 'No file was uploaded';
            case UPLOAD_ERR_NO_TMP_DIR:
                return 'Missing a temporary folder';
            case UPLOAD_ERR_CANT_WRITE:
                return 'Failed to write file to disk';
            case UPLOAD_ERR_EXTENSION:
                return 'A PHP extension stopped the file upload';
            default:
                return 'Unknown upload error';
        }
    }
}
