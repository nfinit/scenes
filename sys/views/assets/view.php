<?php
/**
 * Scenes - Universal Online Hierarchical Data Album
 * Asset View
 */
?>

<div class="asset-view">
    <h2><?php echo htmlspecialchars($asset['display_name'] ?? $asset['filename']); ?></h2>
    
    <div class="asset-metadata">
        <table class="data-table" border="1" cellspacing="0" cellpadding="4">
            <tr>
                <th>Filename</th>
                <td><?php echo htmlspecialchars($asset['filename']); ?></td>
            </tr>
            <tr>
                <th>Type</th>
                <td><?php echo htmlspecialchars($asset['filetype']); ?></td>
            </tr>
            <tr>
                <th>Size</th>
                <td><?php echo isset($asset['filesize']) ? number_format($asset['filesize']) . ' bytes' : ''; ?></td>
            </tr>
            <tr>
                <th>Uploaded</th>
                <td><?php echo htmlspecialchars($asset['created_at']); ?></td>
            </tr>
            <?php if (isset($asset['checksum']) && !empty($asset['checksum'])): ?>
            <tr>
                <th>Checksum</th>
                <td><?php echo htmlspecialchars($asset['checksum']); ?></td>
            </tr>
            <?php endif; ?>
        </table>
    </div>
    
    <?php if (isset($asset['description']) && !empty($asset['description'])): ?>
    <div class="asset-description">
        <h3>Description</h3>
        <?php echo $asset['description']; ?>
    </div>
    <?php endif; ?>
    
    <div class="asset-content">
        <?php if (in_array($asset['filetype'], ['image/jpeg', 'image/png', 'image/gif'])): ?>
        <div class="asset-image">
            <img src="/assets/stream/<?php echo (int)$asset['id']; ?>" alt="<?php echo htmlspecialchars($asset['display_name'] ?? $asset['filename']); ?>" style="max-width: 100%;">
        </div>
        <?php else: ?>
        <div class="asset-download">
            <p>
                <a href="/assets/stream/<?php echo (int)$asset['id']; ?>" class="download-button">
                    Download <?php echo htmlspecialchars($asset['filename']); ?>
                </a>
            </p>
        </div>
        <?php endif; ?>
    </div>
    
    <?php if (!empty($collections)): ?>
    <div class="asset-collections">
        <h3>Collections</h3>
        <ul>
            <?php foreach ($collections as $collection): ?>
            <li>
                <a href="/collections/view/<?php echo htmlspecialchars($collection['slug']); ?>">
                    <?php echo htmlspecialchars($collection['name']); ?>
                </a>
                <?php if ($collection['display_name'] && $collection['display_name'] != $asset['filename']): ?>
                (displayed as: <?php echo htmlspecialchars($collection['display_name']); ?>)
                <?php endif; ?>
            </li>
            <?php endforeach; ?>
        </ul>
    </div>
    <?php endif; ?>
    
    <?php if ($isAuthenticated && $hasPermission): ?>
    <div class="asset-admin-controls no-print">
        <hr class="divider">
        <h3>Administration</h3>
        <p>
            <a href="/assets/edit/<?php echo (int)$asset['id']; ?>">Edit Asset</a> |
            <a href="/assets/verify-integrity/<?php echo (int)$asset['id']; ?>">Verify Integrity</a> |
            <a href="/assets/confirm-delete/<?php echo (int)$asset['id']; ?>">Delete Asset</a>
        </p>
    </div>
    <?php endif; ?>
</div>
