<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ShopDesk | Account access</title>
    <link rel="stylesheet" href="view/css/auth.css">
</head>
<body>
    <main>
        <h1>Shop Management System</h1>
        <p>Sign in to your shop workspace.</p>
        <?php if ($error): ?><p class="error"><?= htmlspecialchars($error) ?></p><?php endif; ?>
        <form method="post">
            <label for="username">Username</label>
            <input type="text" id="username" name="username" required>
            <label for="password">Password</label>
            <input type="password" id="password" name="password" required>
            <button type="submit" name="login">Sign in</button>
        </form>
        <p class="hint">New shop owner? <a href="register.php">Create an account</a></p>
    </main>
</body>
</html>