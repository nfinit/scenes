<?php
/**
 * Scenes - Universal Online Hierarchical Data Album
 * Tabular Collection View
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
    <table class="data-table" border="1" cellspacing="0" cellpadding="4" width="100%">
        <thead>
            <tr>
                <th>Name</th>
                <th>Description</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($children as $child): ?>
            <?php if ($child['display_mode'] !== 'hidden'): ?>
            <tr>
                <td>
                    <a href="/collections/view/<?php echo htmlspecialchars($child['slug']); ?>">
                        <?php echo htmlspecialchars($child['name']); ?>
                    </a>
                </td>
                <td>
                    <?php if ($child['show_metadata'] && isset($child['description']) && !empty($child['description'])): ?>
                        <?php echo $child['description']; ?>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endif; ?>
        <?php endforeach; ?>
        </tbody>
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
        
        <table class="data-table" border="1" cellspacing="0" cellpadding="4" width="100%">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Type</th>
                    <th>Size</th>
                    <th>Description</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($group['assets'] as $asset): ?>
                <tr>
                    <td>
                        <a href="/assets/view/<?php echo (int)$asset['id']; ?>">
                            <?php echo htmlspecialchars($asset['display_name'] ?? $asset['filename']); ?>
                        </a>
                    </td>
                    <td><?php echo htmlspecialchars($asset['filetype']); ?></td>
                    <td><?php echo isset($asset['filesize']) ? number_format($asset['filesize']) . ' bytes' : ''; ?></td>
                    <td>
                        <?php if (isset($asset['description']) && !empty($asset['description'])): ?>
                            <?php echo $asset['description']; ?>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <br>
    <?php endforeach; ?>
</div>
<hr class="divider">
<?php endif; ?>

<?php if (!empty($ungroupedAssets)): ?>
<div class="ungrouped-assets">
    <h2>Assets</h2>
    <table class="data-table" border="1" cellspacing="0" cellpadding="4" width="100%">
        <thead>
            <tr>
                <th>Name</th>
                <th>Type</th>
                <th>Size</th>
                <th>Description</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($ungroupedAssets as $asset): ?>
            <tr>
                <td>
                    <a href="/assets/view/<?php echo (int)$asset['id']; ?>">
                        <?php echo htmlspecialchars($asset['display_name'] ?? $asset['filename']); ?>
                    </a>
                </td>
                <td><?php echo htmlspecialchars($asset['filetype']); ?></td>
                <td><?php echo isset($asset['filesize']) ? number_format($asset['filesize']) . ' bytes' : ''; ?></td>
                <td>
                    <?php if (isset($asset['description']) && !empty($asset['description'])): ?>
                        <?php echo $asset['description']; ?>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php endif; ?>
