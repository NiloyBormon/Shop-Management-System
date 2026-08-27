<?php
$totalStock = array_sum(array_column($products, 'stock'));
$inventoryValue = array_sum(array_map(static fn (array $product): float => $product['price'] * $product['stock'], $products));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($_SESSION['tenant_name']) ?> | ShopDesk</title>
    <link rel="stylesheet" href="view/css/dashboard.css">
</head>
<body>
    <header><h1><?= htmlspecialchars($_SESSION['tenant_name']) ?></h1><span><?= htmlspecialchars($_SESSION['username']) ?> · <a href="?logout=1">Log out</a></span></header>
    <main>
        <h2>Inventory overview</h2>
        <div class="stats"><div class="stat">Products<strong><?= count($products) ?></strong></div><div class="stat">Units in stock<strong><?= $totalStock ?></strong></div><div class="stat">Inventory value<strong>$<?= number_format($inventoryValue, 2) ?></strong></div></div>
        <section>
            <h2>Add product</h2>
            <?php if ($message): ?><p class="notice"><?= htmlspecialchars($message) ?></p><?php endif; ?>
            <?php if ($error): ?><p class="error"><?= htmlspecialchars($error) ?></p><?php endif; ?>
            <form method="post">
                <label>Product name<input name="name" required></label>
                <label>Price<input type="number" name="price" min="0" step="0.01" required></label>
                <label>Stock<input type="number" name="stock" min="0" required></label>
                <button name="add_product">Add product</button>
            </form>
            <table><thead><tr><th>Product</th><th>Price</th><th>Stock</th></tr></thead><tbody>
            <?php foreach ($products as $product): ?><tr><td><?= htmlspecialchars($product['name']) ?></td><td>$<?= number_format((float) $product['price'], 2) ?></td><td><?= (int) $product['stock'] ?></td></tr><?php endforeach; ?>
            </tbody></table>
        </section>
    </main>
</body>
</html>