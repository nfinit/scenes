<?php
/**
 * Scenes - Universal Online Hierarchical Data Album
 * Initial Setup View
 */
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Setup - Scenes</title>
    <style><!--
    body { font-family: Arial, Helvetica, sans-serif; margin: 0; padding: 20px; }
    .container { max-width: 500px; margin: 0 auto; }
    h1 { color: #444; }
    .form-group { margin-bottom: 15px; }
    label { display: block; margin-bottom: 5px; }
    input[type="password"] { width: 100%; padding: 8px; box-sizing: border-box; }
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
        <h1>Scenes Initial Setup</h1>
        
        <?php if (isset($flashMessages) && !empty($flashMessages)): ?>
        <div class="flash-messages">
            <?php foreach($flashMessages as $message): ?>
            <div class="flash-message <?php echo htmlspecialchars($message['type']); ?>">
                <?php echo htmlspecialchars($message['message']); ?>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
        
        <p>Please set the administrator password to complete the setup:</p>
        
        <form action="/auth/process-setup" method="post">
            <div class="form-group">
                <label for="password">New Password:</label>
                <input type="password" id="password" name="password" required>
            </div>
            
            <div class="form-group">
                <label for="confirm_password">Confirm Password:</label>
                <input type="password" id="confirm_password" name="confirm_password" required>
            </div>
            
            <div class="form-group">
                <input type="submit" value="Initialize">
            </div>
        </form>
    </div>
</body>
</html>
