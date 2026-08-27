<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign In | Shop Management System</title>
    <link rel="stylesheet" href="view/css/auth.css">
</head>
<body>
    <main>
        <h1>Sign In</h1>
        <p class="hint" style="text-align: left; margin: 0 0 15px;">Access your shop dashboard</p>

        <?php if (!empty($error)): ?>
            <div class="error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        <?php if (!empty($message)): ?>
            <div class="message"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>

        <form method="post" action="">
            <label for="username">Username</label>
            <input type="text" id="username" name="username" required autocomplete="username">

            <label for="password">Password</label>
            <input type="password" id="password" name="password" required autocomplete="current-password">

            <button type="submit" name="login" value="1">Sign In</button>
        </form>

        <p class="hint">Don't have an account? <a href="register.php">Register your shop</a></p>
    </main>
</body>
</html>