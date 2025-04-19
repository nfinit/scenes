<?php
/**
 * Scenes - Universal Online Hierarchical Data Album
 * Login View
 */
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Login - Scenes</title>
    <style><!--
    body { font-family: Arial, Helvetica, sans-serif; margin: 0; padding: 20px; }
    .container { max-width: 500px; margin: 0 auto; }
    h1 { color: #444; }
    .form-group { margin-bottom: 15px; }
    label { display: block; margin-bottom: 5px; }
    input[type="text"], input[type="password"] { width: 100%; padding: 8px; box-sizing: border-box; }
    input[type="submit"] { padding: 8px 16px; background-color: #4285f4; color: white; border: none; cursor: pointer; }
    input[type="submit"]:hover { background-color: #0d47a1; }
    .flash-message { padding: 10px; margin-bottom: 10px; border: 1px solid #ccc; }
    .flash-message.success { background-color: #dff0d8; border-color: #d6e9c6; }
    .flash-message.error { background-color: #f2dede; border-color: #ebccd1; }
    a { color: #0000EE; }
    //--></style>
</head>
<body>
    <div class="container">
        <h1>Login to Scenes</h1>
        
        <?php if (isset($flashMessages) && !empty($flashMessages)): ?>
        <div class="flash-messages">
            <?php foreach($flashMessages as $message): ?>
            <div class="flash-message <?php echo htmlspecialchars($message['type']); ?>">
                <?php echo htmlspecialchars($message['message']); ?>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
        
        <?php if (isset($isIpWhitelistingEnabled) && $isIpWhitelistingEnabled && !$isIpWhitelisted): ?>
        <div class="flash-message error">
            <p>Your IP address is not in the whitelist. Please contact the administrator.</p>
        </div>
        <?php else: ?>
        
        <?php if (isset($setupComplete) && !$setupComplete): ?>
        <div class="flash-message error">
            <p>Administrator account has not been initialized yet. <a href="/auth/setup">Complete setup</a> to continue.</p>
        </div>
        <?php else: ?>
        
        <form action="/auth/authenticate" method="post">
            <div class="form-group">
                <label for="username">Username:</label>
                <input type="text" id="username" name="username" required>
            </div>
            
            <div class="form-group">
                <label for="password">Password:</label>
                <input type="password" id="password" name="password" required>
            </div>
            
            <input type="hidden" name="redirect" value="<?php echo htmlspecialchars($redirect ?? '/'); ?>">
            
            <div class="form-group">
                <input type="submit" value="Login">
            </div>
        </form>
        
        <?php endif; ?>
        <?php endif; ?>
        
        <p><a href="/">Return to Home</a></p>
    </div>
</body>
</html>
