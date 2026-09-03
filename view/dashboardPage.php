<?php
$totalProducts = count($products ?? []);
$totalStock = array_sum(array_column($products ?? [], "stock"));
$inventoryValue = array_sum(
    array_map(
        static fn(array $p): float => (float) ($p["price"] ?? 0) *
            (int) ($p["stock"] ?? 0),
        $products ?? [],
    ),
);
$outOfStockItems = count(
    array_filter(
        $products ?? [],
        static fn(array $p): bool => (int) $p["stock"] === 0,
    ),
);
$lowStockItems = count(
    array_filter(
        $products ?? [],
        static fn(array $p): bool => (int) $p["stock"] > 0 &&
            (int) $p["stock"] <= 5,
    ),
);
$inStockCount = $totalProducts - $lowStockItems - $outOfStockItems;
$avgPrice =
    $totalProducts > 0
        ? array_sum(array_column($products ?? [], "price")) / $totalProducts
        : 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars(
        $_SESSION["tenant_name"] ?? "Dashboard",
    ) ?> | ShopDesk</title>
    <link rel="stylesheet" href="view/css/dashboard.css">
</head>
<body>
    <header>
        <a class="brand-wrapper" href="index.php">
            <div class="brand-icon">🛍️</div>
            <h1><?= htmlspecialchars(
                $_SESSION["tenant_name"] ?? "Shop Management",
            ) ?></h1>
        </a>
        <div class="user-badge">
            <span>👤 <?= htmlspecialchars(
                $_SESSION["username"] ?? "User",
            ) ?></span>
            <span>·</span>
            <a href="?logout=1">Log out</a>
        </div>
    </header>

    <main>
        <div class="page-heading">
            <h2>Inventory Overview</h2>
            <p>Real-time analytics and product management for your shop.</p>
        </div>

        <!-- MAIN STATS -->
        <div class="stats">
            <div class="stat">
                <span class="stat-label">Total Products</span>
                <strong><?= $totalProducts ?></strong>
                <small>Active listed items</small>
            </div>
            <div class="stat">
                <span class="stat-label">Units in Stock</span>
                <strong><?= $totalStock ?></strong>
                <small>Total inventory units</small>
            </div>
            <div class="stat">
                <span class="stat-label">Inventory Value</span>
                <strong>$<?= number_format($inventoryValue, 2) ?></strong>
                <small>Total valuation</small>
            </div>
        </div>

        <!-- SUMMARY STATUS CARDS -->
        <div class="summary-grid">
            <div class="summary-item">
                <span>Avg. Unit Price</span>
                <strong>$<?= number_format($avgPrice, 2) ?></strong>
            </div>
            <div class="summary-item success">
                <span>In Stock</span>
                <strong class="success-text"><?= $inStockCount ?></strong>
            </div>
            <div class="summary-item warning">
                <span>Low Stock</span>
                <strong class="warning-text"><?= $lowStockItems ?></strong>
            </div>
            <div class="summary-item danger">
                <span>Out of Stock</span>
                <strong class="danger-text"><?= $outOfStockItems ?></strong>
            </div>
        </div>

        <!-- ADD PRODUCT FORM -->
        <section>
            <h2>Add Product</h2>
            <p class="section-description">Quickly add new inventory items to your catalog.</p>

            <?php if (
                !empty($message)
            ): ?><div class="notice">✓ <?= htmlspecialchars(
    $message,
) ?></div><?php endif; ?>
            <?php if (
                !empty($error)
            ): ?><div class="error">✕ <?= htmlspecialchars(
    $error,
) ?></div><?php endif; ?>

            <form method="post">
                <label>
                    Product Name
                    <input type="text" name="name" placeholder="e.g. Mechanical Keyboard" required>
                </label>
                <label>
                    Price ($)
                    <input type="number" name="price" min="0" step="0.01" placeholder="0.00" required>
                </label>
                <label>
                    Stock
                    <input type="number" name="stock" min="0" placeholder="0" required>
                </label>
                <button type="submit" name="add_product">Add Product</button>
            </form>
        </section>

        <!-- PRODUCT TABLE -->
        <section>
            <div class="table-heading">
                <div>
                    <h2>Products Catalog</h2>
                    <p class="section-description" style="margin-bottom: 0;">Manage pricing, stock status, and listings</p>
                </div>
                <div class="search-box">
                    <input type="text" id="searchInput" placeholder="Search product..." onkeyup="filterProducts()">
                </div>
            </div>

            <div class="table-wrapper">
                <table id="productsTable">
                    <thead>
                        <tr>
                            <th>Product</th>
                            <th>Price</th>
                            <th>Stock</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if (empty($products)): ?>
                        <tr>
                            <td colspan="4" class="empty-state">
                                <h3>No products available</h3>
                                <p>Add products using the form above to populate the catalog.</p>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($products as $product): ?>
                            <?php
                            $stock = (int) ($product["stock"] ?? 0);
                            $statusClass =
                                $stock === 0
                                    ? "danger"
                                    : ($stock <= 5
                                        ? "warning"
                                        : "success");
                            $statusText =
                                $stock === 0
                                    ? "Out of Stock"
                                    : ($stock <= 5
                                        ? "Low Stock"
                                        : "In Stock");
                            ?>
                            <tr>
                                <td class="product-name"><?= htmlspecialchars(
                                    $product["name"] ?? "",
                                ) ?></td>
                                <td>$<?= number_format(
                                    (float) ($product["price"] ?? 0),
                                    2,
                                ) ?></td>
                                <td><?= $stock ?></td>
                                <td><span class="status <?= $statusClass ?>"><?= $statusText ?></span></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>
    </main>

    <script>
        function filterProducts() {
            const filter = document.getElementById('searchInput').value.toLowerCase();
            const rows = document.querySelectorAll('#productsTable tbody tr');

            rows.forEach(row => {
                const nameCell = row.querySelector('.product-name');
                if (nameCell) {
                    const text = nameCell.textContent.toLowerCase();
                    row.style.display = text.includes(filter) ? '' : 'none';
                }
            });
        }
    </script>
</body>
</html>