<!DOCTYPE html>
<html lang="en">
<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title>Staff Dashboard</title><link rel="stylesheet" href="view/css/dashboard.css"></head>
<body>
<header><div class="brand-wrapper"><div class="brand-icon">ST</div><h1><?= htmlspecialchars($shop['name']) ?></h1></div><div class="user-badge"><span>Shop Staff</span><a href="account.php">Profile</a><a href="index.php?logout=1">Log out</a></div></header>
<main><div class="page-heading"><h2>Staff dashboard</h2><p>Update stock and record sales for your assigned shop.</p></div>
<?php if (!empty($message)): ?><div class="notice"><?= htmlspecialchars($message) ?></div><?php endif; ?><?php if (!empty($error)): ?><div class="error"><?= htmlspecialchars($error) ?></div><?php endif; ?>
<section><h2>Inventory actions</h2><div class="table-wrapper"><table><thead><tr><th>Product</th><th>Price</th><th>Stock</th><th>Stock in/out</th><th>Sale</th></tr></thead><tbody><?php foreach ($products as $product): ?><tr><td><?= htmlspecialchars($product['name']) ?></td><td>$<?= number_format((float) $product['price'], 2) ?></td><td><?= (int) $product['stock'] ?></td><td><form method="post" class="inline-form"><input type="hidden" name="product_id" value="<?= (int) $product['id'] ?>"><input name="stock_change" type="number" placeholder="+/-" required><button>Update</button></form></td><td><form method="post" class="inline-form"><input type="hidden" name="product_id" value="<?= (int) $product['id'] ?>"><input name="quantity" type="number" min="1" required><button name="sale">Sell</button></form></td></tr><?php endforeach; ?></tbody></table></div></section>
</main></body></html>
