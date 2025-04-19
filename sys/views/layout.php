<?php
/**
 * Scenes - Universal Online Hierarchical Data Album
 * Base Layout Template
 */
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle ?? 'Scenes'; ?></title>
    <!-- Legacy browser support - CSS embedded in HTML comment -->
    <style><!--
    body { font-family: Arial, Helvetica, sans-serif; margin: 0; padding: 0; }
    a { color: #0000EE; text-decoration: none; }
    a:visited { color: #551A8B; }
    a:hover { text-decoration: underline; }
    a img { border: 0; }
    .container { width: 100%; max-width: 1024px; margin: 0 auto; padding: 10px; }
    .header { margin-bottom: 20px; }
    .footer { font-size: smaller; margin-top: 20px; border-top: 1px solid #ccc; padding-top: 10px; }
    .navigation { margin: 10px 0; }
    .navigation a { margin-right: 10px; }
    .breadcrumb { margin-bottom: 10px; font-size: smaller; }
    .asset-grid { display: table; }
    .asset-grid-item { display: table-cell; padding: 10px; text-align: center; vertical-align: top; }
    .asset-linear-item { margin-bottom: 20px; }
    .collection-item { margin-bottom: 15px; }
    .asset-group { margin-bottom: 30px; border: 1px solid #eee; padding: 10px; }
    .flash-message { padding: 10px; margin-bottom: 10px; border: 1px solid #ccc; }
    .flash-message.success { background-color: #dff0d8; border-color: #d6e9c6; }
    .flash-message.error { background-color: #f2dede; border-color: #ebccd1; }
    .no-print { display: inline; }
    @media print { .no-print { display: none !important; } }
    //--></style>
    <!-- Modern browsers get the full stylesheet -->
    <?php if (isset($stylesheets) && is_array($stylesheets)): ?>
        <?php foreach($stylesheets as $stylesheet): ?>
        <link rel="stylesheet" href="<?php echo htmlspecialchars($stylesheet); ?>">
        <?php endforeach; ?>
    <?php endif; ?>
    <?php if (isset($favicon)): ?>
    <link rel="icon" href="<?php echo htmlspecialchars($favicon); ?>" type="image/x-icon">
    <?php endif; ?>
</head>
<body>
    <div class="container">
        <div class="header">
            <?php if (isset($logo)): ?>
            <table>
                <tr>
                    <td><img src="<?php echo htmlspecialchars($logo); ?>" alt="Logo"></td>
                    <td><h1><?php echo htmlspecialchars($pageTitle ?? 'Scenes'); ?></h1></td>
                </tr>
            </table>
            <?php else: ?>
            <h1><?php echo htmlspecialchars($pageTitle ?? 'Scenes'); ?></h1>
            <?php endif; ?>
        </div>
        
        <?php if (isset($flashMessages) && !empty($flashMessages)): ?>
        <div class="flash-messages">
            <?php foreach($flashMessages as $message): ?>
            <div class="flash-message <?php echo htmlspecialchars($message['type']); ?>">
                <?php echo htmlspecialchars($message['message']); ?>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
        
        <?php if (isset($breadcrumb)): ?>
        <div class="breadcrumb">
            <?php echo $breadcrumb; ?>
        </div>
        <?php endif; ?>
        
        <?php if (isset($navigation) && $navigationPosition > 0): ?>
        <div class="navigation no-print">
            <?php echo $navigation; ?>
        </div>
        <hr class="divider">
        <?php endif; ?>
        
        <div class="content">
            <?php echo $content ?? ''; ?>
        </div>
        
        <?php if (isset($navigation) && $navigationPosition < 0): ?>
        <hr class="divider">
        <div class="navigation no-print">
            <?php echo $navigation; ?>
        </div>
        <?php endif; ?>
        
        <div class="footer">
            <table width="100%">
                <tr>
                    <td align="left">
                        <span class="timestamp">
                            Generated: <?php echo date('Y-m-d H:i:s'); ?>
                        </span>
                    </td>
                    <td align="right">
                        <?php if (isset($footerNav) && $footerNav): ?>
                        <span class="footer-navigation no-print">
                            <a href="..">Up</a> | <a href="/">Home</a>
                        </span>
                        <?php endif; ?>
                    </td>
                </tr>
            </table>
        </div>
    </div>
</body>
</html>
