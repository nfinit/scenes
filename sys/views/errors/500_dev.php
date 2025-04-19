<?php
/**
 * Scenes - Universal Online Hierarchical Data Album
 * 500 Internal Server Error View (Development)
 */
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>500 Internal Server Error - Scenes</title>
    <style><!--
    body { font-family: Arial, Helvetica, sans-serif; margin: 0; padding: 20px; }
    .container { max-width: 800px; margin: 0 auto; }
    h1 { color: #444; }
    a { color: #0000EE; }
    pre { background-color: #f5f5f5; padding: 10px; overflow: auto; border: 1px solid #ddd; }
    //--></style>
</head>
<body>
    <div class="container">
        <h1>500 Internal Server Error</h1>
        <p>An error occurred while processing your request.</p>
        
        <?php if (isset($exception)): ?>
        <h2>Error Details</h2>
        <p><strong>Message:</strong> <?php echo htmlspecialchars($exception->getMessage()); ?></p>
        <p><strong>File:</strong> <?php echo htmlspecialchars($exception->getFile()); ?></p>
        <p><strong>Line:</strong> <?php echo (int)$exception->getLine(); ?></p>
        
        <h3>Stack Trace</h3>
        <pre><?php echo htmlspecialchars($exception->getTraceAsString()); ?></pre>
        <?php endif; ?>
        
        <p><a href="/">Return to Home</a></p>
    </div>
</body>
</html>
