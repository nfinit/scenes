<?php
/**
 * Scenes - Universal Online Hierarchical Data Album
 * Grid Collection View
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
    <table class="asset-grid">
        <tr>
        <?php $count = 0; ?>
        <?php foreach ($children as $child): ?>
            <?php if ($child['display_mode'] !== 'hidden'): ?>
            <?php if ($count > 0 && $count % 3 == 0): ?>
            </tr><tr>
            <?php endif; ?>
            <td class="asset-grid-item" style="width: 33%;">
                <h3><a href="/collections/view/<?php echo htmlspecialchars($child['slug']); ?>"><?php echo htmlspecialchars($child['name']); ?></a></h3>
                <?php if ($child['show_metadata'] && isset($child['description']) && !empty($child['description'])): ?>
                <div class="collection-description">
                    <?php echo $child['description']; ?>
                </div>
                <?php endif; ?>
            </td>
            <?php $count++; ?>
            <?php endif; ?>
        <?php endforeach; ?>
        <!-- Fill remaining cells in the last row -->
        <?php for ($i = 0; $i < (3 - ($count % 3)) % 3; $i++): ?>
            <td class="asset-grid-item" style="width: 33%;"></td>
        <?php endfor; ?>
        </tr>
    </table>
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
        
        <table class="asset-grid">
            <tr>
            <?php $count = 0; ?>
            <?php foreach ($group['assets'] as $asset): ?>
                <?php if ($count > 0 && $count % 3 == 0): ?>
                </tr><tr>
                <?php endif; ?>
                <td class="asset-grid-item" style="width: 33%;">
                    <div>
                        <a href="/assets/view/<?php echo (int)$asset['id']; ?>">
                            <?php echo htmlspecialchars($asset['display_name'] ?? $asset['filename']); ?>
                        </a>
                    </div>
                    <?php if (in_array($asset['filetype'], ['image/jpeg', 'image/png', 'image/gif'])): ?>
                    <div class="asset-preview">
                        <a href="/assets/view/<?php echo (int)$asset['id']; ?>">
                            <img src="/assets/view/<?php echo (int)$asset['id']; ?>" alt="<?php echo htmlspecialchars($asset['display_name'] ?? $asset['filename']); ?>" style="max-width: 160px; max-height: 120px;">
                        </a>
                    </div>
                    <?php endif; ?>
                </td>
                <?php $count++; ?>
            <?php endforeach; ?>
            <!-- Fill remaining cells in the last row -->
            <?php for ($i = 0; $i < (3 - ($count % 3)) % 3; $i++): ?>
                <td class="asset-grid-item" style="width: 33%;"></td>
            <?php endfor; ?>
            </tr>
        </table>
    </div>
    <?php endforeach; ?>
</div>
<hr class="divider">
<?php endif; ?>

<?php if (!empty($ungroupedAssets)): ?>
<div class="ungrouped-assets">
    <h2>Assets</h2>
    <table class="asset-grid">
        <tr>
        <?php $count = 0; ?>
        <?php foreach ($ungroupedAssets as $asset): ?>
            <?php if ($count > 0 && $count % 3 == 0): ?>
            </tr><tr>
            <?php endif; ?>
            <td class="asset-grid-item" style="width: 33%;">
                <div>
                    <a href="/assets/view/<?php echo (int)$asset['id']; ?>">
                        <?php echo htmlspecialchars($asset['display_name'] ?? $asset['filename']); ?>
                    </a>
                </div>
                <?php if (in_array($asset['filetype'], ['image/jpeg', 'image/png', 'image/gif'])): ?>
                <div class="asset-preview">
                    <a href="/assets/view/<?php echo (int)$asset['id']; ?>">
                        <img src="/assets/view/<?php echo (int)$asset['id']; ?>" alt="<?php echo htmlspecialchars($asset['display_name'] ?? $asset['filename']); ?>" style="max-width: 160px; max-height: 120px;">
                    </a>
                </div>
                <?php endif; ?>
            </td>
            <?php $count++; ?>
        <?php endforeach; ?>
        <!-- Fill remaining cells in the last row -->
        <?php for ($i = 0; $i < (3 - ($count % 3)) % 3; $i++): ?>
            <td class="asset-grid-item" style="width: 33%;"></td>
        <?php endfor; ?>
        </tr>
    </table>
</div>
<?php endif; ?>
