<?php
/**
 * Scenes - Universal Online Hierarchical Data Album
 * Linear Collection View
 */
?>

<?php if (isset($collection['description']) && !empty($collection['description'])): ?>
<div class="collection-description">
    <?php echo $collection['description']; ?>
</div>
<hr class="divider">
<?php endif; ?>

<?php if (!empty($children)): ?>
<div class="child-collections">
    <h2>Collections</h2>
    <?php foreach ($children as $child): ?>
        <?php if ($child['display_mode'] !== 'hidden'): ?>
        <div class="collection-item">
            <h3><a href="/collections/view/<?php echo htmlspecialchars($child['slug']); ?>"><?php echo htmlspecialchars($child['name']); ?></a></h3>
            <?php if ($child['show_metadata'] && isset($child['description']) && !empty($child['description'])): ?>
            <div class="collection-description">
                <?php echo $child['description']; ?>
            </div>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    <?php endforeach; ?>
</div>
<hr class="divider">
<?php endif; ?>

<?php if (!empty($assetGroups)): ?>
<div class="asset-groups">
    <h2>Asset Groups</h2>
    <?php foreach ($assetGroups as $group): ?>
    <div class="asset-group">
        <?php if (isset($group['name']) && !empty($group['name'])): ?>
        <h3><?php echo htmlspecialchars($group['name']); ?></h3>
        <?php endif; ?>
        
        <?php if (isset($group['description']) && !empty($group['description'])): ?>
        <div class="group-description">
            <?php echo $group['description']; ?>
        </div>
        <?php endif; ?>
        
        <div class="asset-list">
            <?php foreach ($group['assets'] as $asset): ?>
            <div class="asset-linear-item">
                <h4>
                    <a href="/assets/view/<?php echo (int)$asset['id']; ?>">
                        <?php echo htmlspecialchars($asset['display_name'] ?? $asset['filename']); ?>
                    </a>
                </h4>
                <?php if (in_array($asset['filetype'], ['image/jpeg', 'image/png', 'image/gif'])): ?>
                <div class="asset-preview">
                    <a href="/assets/view/<?php echo (int)$asset['id']; ?>">
                        <img src="/assets/view/<?php echo (int)$asset['id']; ?>" alt="<?php echo htmlspecialchars($asset['display_name'] ?? $asset['filename']); ?>" style="max-width: 320px; max-height: 240px;">
                    </a>
                </div>
                <?php endif; ?>
                
                <?php if (isset($asset['description']) && !empty($asset['description'])): ?>
                <div class="asset-description">
                    <?php echo $asset['description']; ?>
                </div>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endforeach; ?>
</div>
<hr class="divider">
<?php endif; ?>

<?php if (!empty($ungroupedAssets)): ?>
<div class="ungrouped-assets">
    <h2>Assets</h2>
    <?php foreach ($ungroupedAssets as $asset): ?>
    <div class="asset-linear-item">
        <h4>
            <a href="/assets/view/<?php echo (int)$asset['id']; ?>">
                <?php echo htmlspecialchars($asset['display_name'] ?? $asset['filename']); ?>
            </a>
        </h4>
        <?php if (in_array($asset['filetype'], ['image/jpeg', 'image/png', 'image/gif'])): ?>
        <div class="asset-preview">
            <a href="/assets/view/<?php echo (int)$asset['id']; ?>">
                <img src="/assets/view/<?php echo (int)$asset['id']; ?>" alt="<?php echo htmlspecialchars($asset['display_name'] ?? $asset['filename']); ?>" style="max-width: 320px; max-height: 240px;">
            </a>
        </div>
        <?php endif; ?>
        
        <?php if (isset($asset['description']) && !empty($asset['description'])): ?>
        <div class="asset-description">
            <?php echo $asset['description']; ?>
        </div>
        <?php endif; ?>
    </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>
