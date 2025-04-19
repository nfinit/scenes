<?php
/**
 * Scenes - Universal Online Hierarchical Data Album
 * 500 Internal Server Error View
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
    //--></style>
</head>
<body>
    <div class="container">
        <h1>500 Internal Server Error</h1>
        <p><?php echo htmlspecialchars($message ?? 'An error occurred while processing your request.'); ?></p>
        <p><a href="/">Return to Home</a></p>
    </div>
</body>
</html>
