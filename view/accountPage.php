<!DOCTYPE html>
<html lang="en">
<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title>Account</title><link rel="stylesheet" href="view/css/auth.css"></head>
<body><main><h1>Account settings</h1><?php if (!empty($message)): ?><div class="message"><?= htmlspecialchars($message) ?></div><?php endif; ?><?php if (!empty($error)): ?><div class="error"><?= htmlspecialchars($error) ?></div><?php endif; ?>
<form method="post"><h2>Profile information</h2><label>Username<input name="username" value="<?= htmlspecialchars($_SESSION['username']) ?>" required></label><label>Email<input name="email" type="email" value="<?= htmlspecialchars($_SESSION['email'] ?? '') ?>"></label><button name="profile">Save profile</button></form>
<form method="post"><h2>Change password</h2><label>Current password<input name="current_password" type="password" required></label><label>New password<input name="new_password" type="password" minlength="6" required></label><button name="password">Change password</button></form>
<form method="post"><h2>Delete account</h2><p class="hint">This disables your account.</p><button name="delete_account">Delete account</button></form><p class="hint"><a href="index.php">Back to dashboard</a></p></main></body></html>
