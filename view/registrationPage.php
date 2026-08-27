<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ShopDesk | Register</title>
    <link rel="stylesheet" href="view/css/auth.css">
</head>
<body>
    <main>
        <h1>Create shop account</h1>
        <p>Register your shop before signing in.</p>
        <?php if ($error): ?><p class="error"><?= htmlspecialchars($error) ?></p><?php endif; ?>
        <?php if ($message): ?><p class="message"><?= htmlspecialchars($message) ?></p><?php endif; ?>
        <form method="post">
            <label for="shop_name">Shop name</label>
            <input type="text" id="shop_name" name="shop_name" required>
            <label for="username">Username</label>
            <input type="text" id="username" name="username" required>
            <label for="password">Password</label>
            <input type="password" id="password" name="password" minlength="6" required>
            <label for="confirm_password">Confirm password</label>
            <input type="password" id="confirm_password" name="confirm_password" minlength="6" required>
            <button type="submit">Create account</button>
        </form>
        <p class="hint">Already registered? <a href="index.php">Sign in</a></p>
    </main>
</body>
</html>
