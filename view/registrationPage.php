<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register Shop | Shop Management System</title>
    <link rel="stylesheet" href="view/css/auth.css">
</head>
<body>
    <main>
        <h1>Register Shop</h1>
        <p class="hint" style="text-align: left; margin: 0 0 15px;">Create an account to manage your store</p>

        <?php if (!empty($error)): ?>
            <div class="error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        <?php if (!empty($message)): ?>
            <div class="message"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>

        <form method="post" action="">
            <label for="shop_name">Shop / Store Name</label>
            <input type="text" id="shop_name" name="shop_name" placeholder="e.g. Daraz" required>

            <label for="username">Username</label>
            <input type="text" id="username" name="username" required autocomplete="username">

            <label for="password">Password</label>
            <input type="password" id="password" name="password" required minlength="4">

            <label for="confirm_password">Confirm Password</label>
            <input type="password" id="confirm_password" name="confirm_password" required minlength="6">

            <button type="submit" name="register" value="1">Create Account</button>
        </form>

        <p class="hint">Already have a shop? <a href="index.php">Sign In</a></p>
    </main>
</body>
</html>