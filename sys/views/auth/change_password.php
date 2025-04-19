<?php
/**
 * Scenes - Universal Online Hierarchical Data Album
 * Change Password View
 */
?>

<?php $this->renderTemplate('layout.php', [
    'pageTitle' => 'Change Password',
    'content' => '
        <h2>Change Password</h2>
        
        <form action="/auth/update-password" method="post">
            <div class="form-group">
                <label for="current_password">Current Password:</label>
                <input type="password" id="current_password" name="current_password" required>
            </div>
            
            <div class="form-group">
                <label for="new_password">New Password:</label>
                <input type="password" id="new_password" name="new_password" required>
            </div>
            
            <div class="form-group">
                <label for="confirm_password">Confirm New Password:</label>
                <input type="password" id="confirm_password" name="confirm_password" required>
            </div>
            
            <div class="form-group">
                <input type="submit" value="Update Password">
            </div>
        </form>
    ',
    'stylesheets' => $stylesheets ?? [],
    'navigation' => $navigation ?? null,
    'navigationPosition' => $navigationPosition ?? 0,
    'footerNav' => $footerNav ?? false,
    'flashMessages' => $flashMessages ?? []
]); ?>
