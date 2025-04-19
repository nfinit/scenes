<?php

namespace Scenes\Models;

use PDO;
use PDOException;
use Exception;

class Asset extends BaseModel 
{
    /**
     * The table associated with the model
     */
    protected $table = 'assets';
    
    /**
     * The columns that can be filled via mass assignment
     */
    protected $fillable = [
        'filename', 'filepath', 'filetype', 'filesize', 'checksum'
    ];
    
    /**
     * Find assets by filename
     * 
     * @param string $filename The filename to search for
     * @param bool $exact Whether to search for exact match or partial match
     * @return array Matching assets
     */
    public function findByFilename($filename, $exact = true) 
    {
        try {
            if ($exact) {
                return $this->where('filename', $filename)->get();
            } else {
                $sql = "SELECT * FROM {$this->table} WHERE filename LIKE :filename";
                $stmt = $this->db->prepare($sql);
                $stmt->bindValue(':filename', '%' . $filename . '%');
                $stmt->execute();
                
                return $stmt->fetchAll();
            }
        } catch (PDOException $e) {
            $this->handleError($e);
            return [];
        }
    }
    
    /**
     * Find assets by file type
     * 
     * @param string $filetype The file type to search for
     * @return array Matching assets
     */
    public function findByFiletype($filetype) 
    {
        try {
            return $this->where('filetype', $filetype)->get();
        } catch (PDOException $e) {
            $this->handleError($e);
            return [];
        }
    }
    
    /**
     * Find an asset by its checksum
     * 
     * @param string $checksum The file checksum
     * @return array|null The asset or null if not found
     */
    public function findByChecksum($checksum) 
    {
        try {
            return $this->where('checksum', $checksum)->first();
        } catch (PDOException $e) {
            $this->handleError($e);
            return null;
        }
    }
    
    /**
     * Get all collections that an asset belongs to
     * 
     * @param int $assetId The asset ID
     * @return array Collection data with membership info
     */
    public function getCollections($assetId) 
    {
        try {
            $sql = "
                SELECT c.*, acm.id as membership_id, acm.display_name, acm.description, acm.sort_order
                FROM collections c
                JOIN asset_collection_membership acm ON c.id = acm.collection_id
                WHERE acm.asset_id = :asset_id
                ORDER BY c.name ASC
            ";
            
            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':asset_id', $assetId, PDO::PARAM_INT);
            $stmt->execute();
            
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            $this->handleError($e);
            return [];
        }
    }
    
    /**
     * Get asset group membership details
     * 
     * @param int $membershipId The membership ID
     * @return array|null Group data or null if not in a group
     */
    public function getGroupMembership($membershipId) 
    {
        try {
            $sql = "
                SELECT ag.*, agm.sort_order
                FROM asset_groups ag
                JOIN asset_group_membership agm ON ag.id = agm.group_id
                WHERE agm.membership_id = :membership_id
            ";
            
            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':membership_id', $membershipId, PDO::PARAM_INT);
            $stmt->execute();
            
            return $stmt->fetch();
        } catch (PDOException $e) {
            $this->handleError($e);
            return null;
        }
    }

/**
 * Get all groups that an asset belongs to across collections
 * 
 * @param int $assetId The asset ID
 * @return array Groups with collection and membership info
 */
public function getGroups($assetId) 
{
    try {
        $sql = "
            SELECT ag.*, c.id as collection_id, c.name as collection_name,
                   agm.sort_order, acm.id as membership_id
            FROM asset_groups ag
            JOIN asset_group_membership agm ON ag.id = agm.group_id
            JOIN asset_collection_membership acm ON agm.membership_id = acm.id
            JOIN collections c ON ag.collection_id = c.id
            WHERE acm.asset_id = :asset_id
            ORDER BY c.name, ag.name
        ";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':asset_id', $assetId, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll();
    } catch (PDOException $e) {
        $this->handleError($e);
        return [];
    }
}

/**
 * Add an asset to a group
 * 
 * @param int $membershipId The asset collection membership ID
 * @param int $groupId The group ID
 * @param int $sortOrder The sort order in the group
 * @return bool Success status
 */
public function addToGroup($membershipId, $groupId, $sortOrder = 0) 
{
    try {
        $sql = "
            INSERT INTO asset_group_membership (group_id, membership_id, sort_order)
            VALUES (:group_id, :membership_id, :sort_order)
        ";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':group_id', $groupId, PDO::PARAM_INT);
        $stmt->bindValue(':membership_id', $membershipId, PDO::PARAM_INT);
        $stmt->bindValue(':sort_order', $sortOrder, PDO::PARAM_INT);
        
        return $stmt->execute();
    } catch (PDOException $e) {
        $this->handleError($e);
        return false;
    }
}

/**
 * Remove an asset from a group
 * 
 * @param int $membershipId The asset collection membership ID
 * @return bool Success status
 */
public function removeFromGroup($membershipId) 
{
    try {
        $sql = "
            DELETE FROM asset_group_membership
            WHERE membership_id = :membership_id
        ";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':membership_id', $membershipId, PDO::PARAM_INT);
        
        return $stmt->execute();
    } catch (PDOException $e) {
        $this->handleError($e);
        return false;
    }
}

/**
 * Get the display mode for an asset group
 * 
 * @param int $groupId The group ID
 * @return string|null The display mode name or null
 */
public function getGroupDisplayMode($groupId) 
{
    try {
        $sql = "
            SELECT agdm.name
            FROM asset_group_display_mode_configuration agdmc
            JOIN asset_group_display_modes agdm ON agdmc.display_mode_id = agdm.id
            WHERE agdmc.group_id = :group_id
            LIMIT 1
        ";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':group_id', $groupId, PDO::PARAM_INT);
        $stmt->execute();
        
        $result = $stmt->fetch();
        return $result ? $result['name'] : null;
    } catch (PDOException $e) {
        $this->handleError($e);
        return null;
    }
}

/**
 * Set the display mode for an asset group
 * 
 * @param int $groupId The group ID
 * @param string $displayMode The display mode name
 * @param bool $composite Whether to compose group into single image with image map
 * @return bool Success status
 */
public function setGroupDisplayMode($groupId, $displayMode, $composite = false) 
{
    try {
        $this->beginTransaction();
        
        // First delete any existing display mode
        $sql = "
            DELETE FROM asset_group_display_mode_configuration 
            WHERE group_id = :group_id
        ";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':group_id', $groupId, PDO::PARAM_INT);
        $stmt->execute();
        
        // Then insert the new display mode
        $sql = "
            INSERT INTO asset_group_display_mode_configuration 
                (group_id, display_mode_id, composite)
            VALUES 
                (:group_id, 
                 (SELECT id FROM asset_group_display_modes WHERE name = :display_mode),
                 :composite)
        ";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':group_id', $groupId, PDO::PARAM_INT);
        $stmt->bindValue(':display_mode', $displayMode);
        $stmt->bindValue(':composite', $composite ? 1 : 0, PDO::PARAM_INT);
        $stmt->execute();
        
        $this->commit();
        return true;
    } catch (PDOException $e) {
        $this->rollback();
        $this->handleError($e);
        return false;
    }
}

/**
 * Update a group's sort order for an asset
 * 
 * @param int $membershipId The asset collection membership ID
 * @param int $sortOrder The new sort order
 * @return bool Success status
 */
public function updateGroupSortOrder($membershipId, $sortOrder) 
{
    try {
        $sql = "
            UPDATE asset_group_membership
            SET sort_order = :sort_order
            WHERE membership_id = :membership_id
        ";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':membership_id', $membershipId, PDO::PARAM_INT);
        $stmt->bindValue(':sort_order', $sortOrder, PDO::PARAM_INT);
        
        return $stmt->execute();
    } catch (PDOException $e) {
        $this->handleError($e);
        return false;
    }
}

/**
 * Get all assets in a group
 * 
 * @param int $groupId The group ID
 * @return array Assets with membership info
 */
public function getGroupAssets($groupId) 
{
    try {
        $sql = "
            SELECT a.*, acm.id as membership_id, acm.display_name, 
                   acm.description, agm.sort_order as group_sort_order
            FROM assets a
            JOIN asset_collection_membership acm ON a.id = acm.asset_id
            JOIN asset_group_membership agm ON acm.id = agm.membership_id
            WHERE agm.group_id = :group_id
            ORDER BY agm.sort_order ASC
        ";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':group_id', $groupId, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll();
    } catch (PDOException $e) {
        $this->handleError($e);
        return [];
    }
}
    
    /**
     * Create a new asset from a file
     * 
     * @param string $sourceFile Path to the source file
     * @param string $destinationDir Path to the destination directory
     * @param string $filename Optional custom filename (if not provided, uses source filename)
     * @return int|bool Asset ID or false on failure
     */
    public function createFromFile($sourceFile, $destinationDir, $filename = null) 
    {
        try {
            // Ensure the destination directory exists
            if (!is_dir($destinationDir)) {
                if (!mkdir($destinationDir, 0755, true)) {
                    throw new Exception("Failed to create destination directory: {$destinationDir}");
                }
            }
            
            // Get file information
            $sourceFilename = basename($sourceFile);
            $filename = $filename ?? $sourceFilename;
            $filesize = filesize($sourceFile);
            $filetype = mime_content_type($sourceFile) ?: pathinfo($sourceFile, PATHINFO_EXTENSION);
            
            // Generate a unique filename for storage
            $storedFilename = time() . '_' . md5($filename) . '.' . pathinfo($filename, PATHINFO_EXTENSION);
            $destinationPath = $destinationDir . '/' . $storedFilename;
            
            // Copy the file to the destination
            if (!copy($sourceFile, $destinationPath)) {
                throw new Exception("Failed to copy file to destination: {$destinationPath}");
            }
            
            // Generate a checksum for verification
            $checksum = md5_file($destinationPath);
            
            // Create the asset record
            $assetData = [
                'filename' => $filename,
                'filepath' => $destinationPath,
                'filetype' => $filetype,
                'filesize' => $filesize,
                'checksum' => $checksum
            ];
            
            $assetId = $this->create($assetData);
            
            // Add the asset to the assets collection by default
            if ($assetId) {
                $assetsCollectionId = $this->getAssetsCollectionId();
                if ($assetsCollectionId) {
                    $this->addToCollection($assetId, $assetsCollectionId);
                }
            }
            
            return $assetId;
        } catch (Exception $e) {
            $this->handleError($e);
            return false;
        }
    }
    
    /**
     * Upload a file from a form submission and create an asset
     * 
     * @param array $fileData $_FILES array entry
     * @param string $destinationDir Path to the destination directory
     * @return int|bool Asset ID or false on failure
     */
    public function uploadFile($fileData, $destinationDir) 
    {
        try {
            // Validate the uploaded file
            if ($fileData['error'] !== UPLOAD_ERR_OK) {
                throw new Exception("File upload error: " . $this->getUploadErrorMessage($fileData['error']));
            }
            
            // Create the asset from the temporary file
            return $this->createFromFile(
                $fileData['tmp_name'],
                $destinationDir,
                $fileData['name']
            );
        } catch (Exception $e) {
            $this->handleError($e);
            return false;
        }
    }
    
    /**
     * Delete an asset and its file
     * 
     * @param int $assetId The asset ID
     * @param bool $deleteFile Whether to delete the physical file
     * @return bool Success status
     */
    public function deleteAsset($assetId, $deleteFile = true) 
    {
        try {
            $this->beginTransaction();
            
            // Get the asset data before deletion
            $asset = $this->find($assetId);
            if (!$asset) {
                throw new Exception("Asset not found: {$assetId}");
            }
            
            // Delete all collection memberships
            $sql = "DELETE FROM asset_collection_membership WHERE asset_id = :asset_id";
            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':asset_id', $assetId, PDO::PARAM_INT);
            $stmt->execute();
            
            // Delete the asset record
            $deleted = $this->delete($assetId);
            
            if ($deleted && $deleteFile && file_exists($asset['filepath'])) {
                // Delete the physical file
                if (!unlink($asset['filepath'])) {
                    // Log the error but continue with the transaction
                    error_log("Failed to delete file: {$asset['filepath']}");
                }
            }
            
            $this->commit();
            return $deleted;
        } catch (PDOException $e) {
            $this->rollback();
            $this->handleError($e);
            return false;
        }
    }
    
    /**
     * Add an asset to a collection
     * 
     * @param int $assetId The asset ID
     * @param int $collectionId The collection ID
     * @param array $metadata Asset metadata for this collection
     * @return int|bool The membership ID or false on failure
     */
    public function addToCollection($assetId, $collectionId, array $metadata = []) 
    {
        try {
            $displayName = $metadata['display_name'] ?? null;
            $description = $metadata['description'] ?? null;
            $sortOrder = $metadata['sort_order'] ?? 0;
            
            $sql = "
                INSERT INTO asset_collection_membership 
                    (asset_id, collection_id, display_name, description, sort_order)
                VALUES 
                    (:asset_id, :collection_id, :display_name, :description, :sort_order)
            ";
            
            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':asset_id', $assetId, PDO::PARAM_INT);
            $stmt->bindValue(':collection_id', $collectionId, PDO::PARAM_INT);
            $stmt->bindValue(':display_name', $displayName);
            $stmt->bindValue(':description', $description);
            $stmt->bindValue(':sort_order', $sortOrder, PDO::PARAM_INT);
            $stmt->execute();
            
            return $this->db->lastInsertId();
        } catch (PDOException $e) {
            $this->handleError($e);
            return false;
        }
    }
    
    /**
     * Remove an asset from a collection
     * 
     * @param int $assetId The asset ID
     * @param int $collectionId The collection ID
     * @return bool Success status
     */
    public function removeFromCollection($assetId, $collectionId) 
    {
        try {
            $sql = "
                DELETE FROM asset_collection_membership
                WHERE asset_id = :asset_id AND collection_id = :collection_id
            ";
            
            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':asset_id', $assetId, PDO::PARAM_INT);
            $stmt->bindValue(':collection_id', $collectionId, PDO::PARAM_INT);
            
            return $stmt->execute();
        } catch (PDOException $e) {
            $this->handleError($e);
            return false;
        }
    }
    
    /**
     * Update asset metadata in a collection
     * 
     * @param int $membershipId The membership ID
     * @param array $metadata The metadata to update
     * @return bool Success status
     */
    public function updateCollectionMetadata($membershipId, array $metadata) 
    {
        try {
            $updates = [];
            $params = [':id' => $membershipId];
            
            if (isset($metadata['display_name'])) {
                $updates[] = "display_name = :display_name";
                $params[':display_name'] = $metadata['display_name'];
            }
            
            if (isset($metadata['description'])) {
                $updates[] = "description = :description";
                $params[':description'] = $metadata['description'];
            }
            
            if (isset($metadata['sort_order'])) {
                $updates[] = "sort_order = :sort_order";
                $params[':sort_order'] = $metadata['sort_order'];
            }
            
            if (empty($updates)) {
                return true; // Nothing to update
            }
            
            $sql = "
                UPDATE asset_collection_membership
                SET " . implode(', ', $updates) . "
                WHERE id = :id
            ";
            
            $stmt = $this->db->prepare($sql);
            foreach ($params as $key => $value) {
                $stmt->bindValue($key, $value);
            }
            
            return $stmt->execute();
        } catch (PDOException $e) {
            $this->handleError($e);
            return false;
        }
    }
    
    /**
     * Copy asset metadata from one collection to another
     * 
     * @param int $assetId The asset ID
     * @param int $sourceCollectionId The source collection ID
     * @param int $targetCollectionId The target collection ID
     * @return bool Success status
     */
    public function copyMetadataBetweenCollections($assetId, $sourceCollectionId, $targetCollectionId) 
    {
        try {
            $this->beginTransaction();
            
            // Get source metadata
            $sql = "
                SELECT display_name, description
                FROM asset_collection_membership
                WHERE asset_id = :asset_id AND collection_id = :collection_id
            ";
            
            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':asset_id', $assetId, PDO::PARAM_INT);
            $stmt->bindValue(':collection_id', $sourceCollectionId, PDO::PARAM_INT);
            $stmt->execute();
            
            $sourceMetadata = $stmt->fetch();
            
            if (!$sourceMetadata) {
                throw new Exception("Asset not found in source collection");
            }
            
            // Update target metadata
            $sql = "
                UPDATE asset_collection_membership
                SET display_name = :display_name, description = :description
                WHERE asset_id = :asset_id AND collection_id = :collection_id
            ";
            
            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':display_name', $sourceMetadata['display_name']);
            $stmt->bindValue(':description', $sourceMetadata['description']);
            $stmt->bindValue(':asset_id', $assetId, PDO::PARAM_INT);
            $stmt->bindValue(':collection_id', $targetCollectionId, PDO::PARAM_INT);
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
     * Verify the integrity of an asset file
     * 
     * @param int $assetId The asset ID
     * @return bool True if the file integrity is valid
     */
    public function verifyIntegrity($assetId) 
    {
        try {
            $asset = $this->find($assetId);
            if (!$asset) {
                return false;
            }
            
            // Check if the file exists
            if (!file_exists($asset['filepath'])) {
                return false;
            }
            
            // Verify the checksum
            $currentChecksum = md5_file($asset['filepath']);
            return $currentChecksum === $asset['checksum'];
        } catch (Exception $e) {
            $this->handleError($e);
            return false;
        }
    }
    
    /**
     * Update the checksum of an asset
     * 
     * @param int $assetId The asset ID
     * @return bool Success status
     */
    public function updateChecksum($assetId) 
    {
        try {
            $asset = $this->find($assetId);
            if (!$asset || !file_exists($asset['filepath'])) {
                return false;
            }
            
            $checksum = md5_file($asset['filepath']);
            
            return $this->update($assetId, ['checksum' => $checksum]);
        } catch (Exception $e) {
            $this->handleError($e);
            return false;
        }
    }
    
    /**
     * Get the system's assets collection ID
     * 
     * @return int|null The assets collection ID or null if not found
     */
    protected function getAssetsCollectionId() 
    {
        try {
            $sql = "SELECT id FROM collections WHERE slug = 'assets' LIMIT 1";
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            
            $result = $stmt->fetch();
            return $result ? $result['id'] : null;
        } catch (PDOException $e) {
            $this->handleError($e);
            return null;
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


