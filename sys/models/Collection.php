<?php

namespace Scenes\Models;

use PDO;
use PDOException;
use Exception;

class Collection extends BaseModel 
{
    /**
     * The table associated with the model
     */
    protected $table = 'collections';
    
    /**
     * The columns that can be filled via mass assignment
     */
    protected $fillable = [
        'slug', 'name', 'title', 'description', 'protected'
    ];
    
    /**
     * Get a collection by its slug
     * 
     * @param string $slug The collection slug
     * @return array|null Collection data or null if not found
     */
    public function findBySlug($slug) 
    {
        return $this->where('slug', $slug)->first();
    }
    
    /**
     * Get all parent collections of this collection
     * 
     * @param int $collectionId The collection ID
     * @return array Parent collections with relationship data
     */
    public function getParents($collectionId) 
    {
        try {
            $sql = "
                SELECT c.*, cr.id as relationship_id, cr.show_metadata, cr.sort_order
                FROM collections c
                JOIN collection_relationships cr ON c.id = cr.parent_id
                WHERE cr.child_id = :collection_id
                ORDER BY cr.sort_order ASC
            ";
            
            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':collection_id', $collectionId, PDO::PARAM_INT);
            $stmt->execute();
            
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            $this->handleError($e);
            return [];
        }
    }
    
    /**
     * Get all child collections of this collection
     * 
     * @param int $collectionId The collection ID
     * @return array Child collections with relationship data
     */
    public function getChildren($collectionId) 
    {
        try {
            $sql = "
                SELECT c.*, cr.id as relationship_id, cr.show_metadata, cr.sort_order,
                       rdm.name as display_mode
                FROM collections c
                JOIN collection_relationships cr ON c.id = cr.child_id
                LEFT JOIN relationship_display_mode_configuration rdmc ON cr.id = rdmc.relationship_id
                LEFT JOIN relationship_display_modes rdm ON rdmc.display_mode_id = rdm.id
                WHERE cr.parent_id = :collection_id
                ORDER BY cr.sort_order ASC
            ";
            
            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':collection_id', $collectionId, PDO::PARAM_INT);
            $stmt->execute();
            
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            $this->handleError($e);
            return [];
        }
    }
    
    /**
     * Add a child collection to this collection
     * 
     * @param int $parentId The parent collection ID
     * @param int $childId The child collection ID
     * @param bool $showMetadata Whether to show the child's metadata
     * @param int $sortOrder Sort order in the parent collection
     * @param string $displayMode Display mode for the relationship
     * @return int|bool The relationship ID or false on failure
     */
    public function addChild($parentId, $childId, $showMetadata = true, $sortOrder = 0, $displayMode = 'linked') 
    {
        try {
            $this->beginTransaction();
            
            // Create the relationship
            $sql = "
                INSERT INTO collection_relationships (parent_id, child_id, show_metadata, sort_order)
                VALUES (:parent_id, :child_id, :show_metadata, :sort_order)
            ";
            
            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':parent_id', $parentId, PDO::PARAM_INT);
            $stmt->bindValue(':child_id', $childId, PDO::PARAM_INT);
            $stmt->bindValue(':show_metadata', $showMetadata ? 1 : 0, PDO::PARAM_INT);
            $stmt->bindValue(':sort_order', $sortOrder, PDO::PARAM_INT);
            $stmt->execute();
            
            $relationshipId = $this->db->lastInsertId();
            
            // Set the display mode
            $sql = "
                INSERT INTO relationship_display_mode_configuration (relationship_id, display_mode_id)
                VALUES (:relationship_id, (SELECT id FROM relationship_display_modes WHERE name = :display_mode))
            ";
            
            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':relationship_id', $relationshipId, PDO::PARAM_INT);
            $stmt->bindValue(':display_mode', $displayMode);
            $stmt->execute();
            
            $this->commit();
            return $relationshipId;
        } catch (PDOException $e) {
            $this->rollback();
            $this->handleError($e);
            return false;
        }
    }
    
    /**
     * Remove a child collection from this collection
     * 
     * @param int $parentId The parent collection ID
     * @param int $childId The child collection ID
     * @return bool Success status
     */
    public function removeChild($parentId, $childId) 
    {
        try {
            $sql = "
                DELETE FROM collection_relationships
                WHERE parent_id = :parent_id AND child_id = :child_id
            ";
            
            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':parent_id', $parentId, PDO::PARAM_INT);
            $stmt->bindValue(':child_id', $childId, PDO::PARAM_INT);
            
            return $stmt->execute();
        } catch (PDOException $e) {
            $this->handleError($e);
            return false;
        }
    }
    
    /**
     * Update a collection relationship
     * 
     * @param int $relationshipId The relationship ID
     * @param array $data Relationship data to update
     * @return bool Success status
     */
    public function updateRelationship($relationshipId, array $data) 
    {
        try {
            $this->beginTransaction();
            
            // Update basic relationship data
            if (isset($data['show_metadata']) || isset($data['sort_order'])) {
                $updates = [];
                $params = [':id' => $relationshipId];
                
                if (isset($data['show_metadata'])) {
                    $updates[] = "show_metadata = :show_metadata";
                    $params[':show_metadata'] = $data['show_metadata'] ? 1 : 0;
                }
                
                if (isset($data['sort_order'])) {
                    $updates[] = "sort_order = :sort_order";
                    $params[':sort_order'] = $data['sort_order'];
                }
                
                if (!empty($updates)) {
                    $sql = "
                        UPDATE collection_relationships
                        SET " . implode(', ', $updates) . "
                        WHERE id = :id
                    ";
                    
                    $stmt = $this->db->prepare($sql);
                    foreach ($params as $key => $value) {
                        $stmt->bindValue($key, $value);
                    }
                    $stmt->execute();
                }
            }
            
            // Update display mode if provided
            if (isset($data['display_mode'])) {
                // First delete existing display mode
                $sql = "
                    DELETE FROM relationship_display_mode_configuration 
                    WHERE relationship_id = :relationship_id
                ";
                
                $stmt = $this->db->prepare($sql);
                $stmt->bindValue(':relationship_id', $relationshipId, PDO::PARAM_INT);
                $stmt->execute();
                
                // Then insert new display mode
                $sql = "
                    INSERT INTO relationship_display_mode_configuration (relationship_id, display_mode_id)
                    VALUES (:relationship_id, (SELECT id FROM relationship_display_modes WHERE name = :display_mode))
                ";
                
                $stmt = $this->db->prepare($sql);
                $stmt->bindValue(':relationship_id', $relationshipId, PDO::PARAM_INT);
                $stmt->bindValue(':display_mode', $data['display_mode']);
                $stmt->execute();
            }
            
            $this->commit();
            return true;
        } catch (PDOException $e) {
            $this->rollback();
            $this->handleError($e);
            return false;
        }
    }
    
    /**
     * Get the display mode for a collection
     * 
     * @param int $collectionId The collection ID
     * @return string|null The display mode name or null
     */
    public function getDisplayMode($collectionId) 
    {
        try {
            $sql = "
                SELECT cdm.name
                FROM collection_display_mode_configuration cdmc
                JOIN collection_display_modes cdm ON cdmc.display_mode_id = cdm.id
                WHERE cdmc.collection_id = :collection_id
                LIMIT 1
            ";
            
            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':collection_id', $collectionId, PDO::PARAM_INT);
            $stmt->execute();
            
            $result = $stmt->fetch();
            return $result ? $result['name'] : null;
        } catch (PDOException $e) {
            $this->handleError($e);
            return null;
        }
    }
    
    /**
     * Set the display mode for a collection
     * 
     * @param int $collectionId The collection ID
     * @param string $displayMode The display mode name
     * @return bool Success status
     */
    public function setDisplayMode($collectionId, $displayMode) 
    {
        try {
            $this->beginTransaction();
            
            // First delete any existing display mode
            $sql = "
                DELETE FROM collection_display_mode_configuration 
                WHERE collection_id = :collection_id
            ";
            
            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':collection_id', $collectionId, PDO::PARAM_INT);
            $stmt->execute();
            
            // Then insert the new display mode
            $sql = "
                INSERT INTO collection_display_mode_configuration (collection_id, display_mode_id)
                VALUES (:collection_id, (SELECT id FROM collection_display_modes WHERE name = :display_mode))
            ";
            
            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':collection_id', $collectionId, PDO::PARAM_INT);
            $stmt->bindValue(':display_mode', $displayMode);
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
     * Get assets in a collection
     * 
     * @param int $collectionId The collection ID
     * @return array Asset data with collection-specific metadata
     */
    public function getAssets($collectionId) 
    {
        try {
            $sql = "
                SELECT a.*, acm.id as membership_id, acm.display_name, acm.description, acm.sort_order,
                       ag.id as group_id, ag.name as group_name, ag.description as group_description
                FROM assets a
                JOIN asset_collection_membership acm ON a.id = acm.asset_id
                LEFT JOIN asset_group_membership agm ON acm.id = agm.membership_id
                LEFT JOIN asset_groups ag ON agm.group_id = ag.id
                WHERE acm.collection_id = :collection_id
                ORDER BY COALESCE(ag.id, 0), acm.sort_order ASC
            ";
            
            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':collection_id', $collectionId, PDO::PARAM_INT);
            $stmt->execute();
            
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            $this->handleError($e);
            return [];
        }
    }
    
    /**
     * Add an asset to a collection
     * 
     * @param int $collectionId The collection ID
     * @param int $assetId The asset ID
     * @param array $metadata Asset metadata for this collection
     * @return int|bool The membership ID or false on failure
     */
    public function addAsset($collectionId, $assetId, array $metadata = []) 
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
     * @param int $collectionId The collection ID
     * @param int $assetId The asset ID
     * @return bool Success status
     */
    public function removeAsset($collectionId, $assetId) 
    {
        try {
            $sql = "
                DELETE FROM asset_collection_membership
                WHERE collection_id = :collection_id AND asset_id = :asset_id
            ";
            
            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':collection_id', $collectionId, PDO::PARAM_INT);
            $stmt->bindValue(':asset_id', $assetId, PDO::PARAM_INT);
            
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
    public function updateAssetMetadata($membershipId, array $metadata) 
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
     * Create an asset group in a collection
     * 
     * @param int $collectionId The collection ID
     * @param string $name The group name
     * @param string $description The group description
     * @param string $displayMode The display mode for the group
     * @return int|bool The group ID or false on failure
     */
    public function createAssetGroup($collectionId, $name = null, $description = null, $displayMode = 'linear') 
    {
        try {
            $this->beginTransaction();
            
            // Create the group
            $sql = "
                INSERT INTO asset_groups (collection_id, name, description)
                VALUES (:collection_id, :name, :description)
            ";
            
            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':collection_id', $collectionId, PDO::PARAM_INT);
            $stmt->bindValue(':name', $name);
            $stmt->bindValue(':description', $description);
            $stmt->execute();
            
            $groupId = $this->db->lastInsertId();
            
            // Set the display mode
            $sql = "
                INSERT INTO asset_group_display_mode_configuration (group_id, display_mode_id)
                VALUES (:group_id, (SELECT id FROM asset_group_display_modes WHERE name = :display_mode))
            ";
            
            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':group_id', $groupId, PDO::PARAM_INT);
            $stmt->bindValue(':display_mode', $displayMode);
            $stmt->execute();
            
            $this->commit();
            return $groupId;
        } catch (PDOException $e) {
            $this->rollback();
            $this->handleError($e);
            return false;
        }
    }
    
    /**
     * Add an asset to a group
     * 
     * @param int $groupId The group ID
     * @param int $membershipId The asset membership ID
     * @param int $sortOrder The sort order in the group
     * @return bool Success status
     */
    public function addAssetToGroup($groupId, $membershipId, $sortOrder = 0) 
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
     * @param int $membershipId The asset membership ID
     * @return bool Success status
     */
    public function removeAssetFromGroup($membershipId) 
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
     * Get all available display modes for collections
     * 
     * @return array Display modes data
     */
    public function getAvailableDisplayModes() 
    {
        try {
            $sql = "SELECT * FROM collection_display_modes";
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            $this->handleError($e);
            return [];
        }
    }
    
    /**
     * Get all available relationship display modes
     * 
     * @return array Relationship display modes data
     */
    public function getAvailableRelationshipDisplayModes() 
    {
        try {
            $sql = "SELECT * FROM relationship_display_modes";
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            $this->handleError($e);
            return [];
        }
    }
    
    /**
     * Get the root collection
     * 
     * @return array|null Root collection data or null if not found
     */
    public function getRoot() 
    {
        return $this->where('slug', 'root')->first();
    }
    
    /**
     * Clone a collection (metadata only)
     * 
     * @param int $sourceId The source collection ID
     * @param array $overrides Data to override in the clone
     * @return int|bool New collection ID or false on failure
     */
    public function cloneCollection($sourceId, array $overrides = []) 
    {
        try {
            // Get the source collection
            $source = $this->find($sourceId);
            if (!$source) {
                return false;
            }
            
            // Prepare clone data
            $cloneData = [
                'slug' => $overrides['slug'] ?? ($source['slug'] . '-clone'),
                'name' => $overrides['name'] ?? ($source['name'] . ' (Clone)'),
                'title' => $overrides['title'] ?? $source['title'],
                'description' => $overrides['description'] ?? $source['description'],
                'protected' => $overrides['protected'] ?? $source['protected']
            ];
            
            // Create the new collection
            $cloneId = $this->create($cloneData);
            if (!$cloneId) {
                return false;
            }
            
            // Clone display mode
            $displayMode = $this->getDisplayMode($sourceId);
            if ($displayMode) {
                $this->setDisplayMode($cloneId, $displayMode);
            }
            
            return $cloneId;
        } catch (PDOException $e) {
            $this->handleError($e);
            return false;
        }
    }
    
    /**
     * Clone a collection including its assets
     * 
     * @param int $sourceId The source collection ID
     * @param array $overrides Data to override in the clone
     * @return int|bool New collection ID or false on failure
     */
    public function cloneCollectionWithAssets($sourceId, array $overrides = []) 
    {
        try {
            $this->beginTransaction();
            
            // Clone the collection metadata
            $cloneId = $this->cloneCollection($sourceId, $overrides);
            if (!$cloneId) {
                $this->rollback();
                return false;
            }
            
            // Get assets from the source collection
            $assets = $this->getAssets($sourceId);
            
            // Create a map of source group IDs to clone group IDs
            $groupMap = [];
            
            // Group assets by group_id
            $assetsByGroup = [];
            foreach ($assets as $asset) {
                $groupId = $asset['group_id'] ?? 0;
                if (!isset($assetsByGroup[$groupId])) {
                    $assetsByGroup[$groupId] = [];
                }
                $assetsByGroup[$groupId][] = $asset;
            }
            
            // Clone assets and groups
            foreach ($assetsByGroup as $sourceGroupId => $groupAssets) {
                // If this is a group, create a clone of it
                if ($sourceGroupId > 0) {
                    $firstAsset = $groupAssets[0];
                    $newGroupId = $this->createAssetGroup(
                        $cloneId, 
                        $firstAsset['group_name'],
                        $firstAsset['group_description']
                    );
                    $groupMap[$sourceGroupId] = $newGroupId;
                }
                
                // Clone each asset in the group
                foreach ($groupAssets as $asset) {
                    // Add asset to the new collection
                    $newMembershipId = $this->addAsset($cloneId, $asset['id'], [
                        'display_name' => $asset['display_name'],
                        'description' => $asset['description'],
                        'sort_order' => $asset['sort_order']
                    ]);
                    
                    // If the asset was in a group, add it to the cloned group
                    if ($sourceGroupId > 0 && isset($groupMap[$sourceGroupId])) {
                        $this->addAssetToGroup($groupMap[$sourceGroupId], $newMembershipId);
                    }
                }
            }
            
            $this->commit();
            return $cloneId;
        } catch (PDOException $e) {
            $this->rollback();
            $this->handleError($e);
            return false;
        }
    }
}
