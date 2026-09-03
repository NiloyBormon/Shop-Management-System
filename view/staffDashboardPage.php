<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Staff Dashboard</title>
    <link rel="stylesheet" href="view/css/dashboard.css">
</head>
<body>
<header>
    <a class="brand-wrapper" href="index.php">
        <div class="brand-icon">ST</div>
        <h1><?= htmlspecialchars($shop["name"]) ?></h1>
    </a>
    <div class="user-badge">
        <span>Shop Staff</span>
        <a href="controller/account.php">Profile</a>
        <a href="index.php?logout=1">Log out</a>
    </div>
</header>
<main>
    <div class="page-heading">
        <h2>Staff workspace</h2>
        <p>Manage inventory, process sales, and review transactions.</p>
    </div>

    <?php if (!empty($message)): ?>
        <div class="notice"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>
    <?php if (!empty($error)): ?>
        <div class="error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <div class="staff-dashboard-layout">
        <aside class="staff-sidebar" aria-label="Staff dashboard sections">
            <button type="button" class="staff-nav-button <?= $activeStaffPanel === "staff-dashboard-panel" ? "active" : "" ?>" data-panel="staff-dashboard-panel">Dashboard</button>
            <button type="button" class="staff-nav-button <?= $activeStaffPanel === "pos-panel" ? "active" : "" ?>" data-panel="pos-panel">POS</button>
            <button type="button" class="staff-nav-button <?= $activeStaffPanel === "transactions-panel" ? "active" : "" ?>" data-panel="transactions-panel">Transaction History</button>
        </aside>

        <div class="staff-panels">
            <section id="staff-dashboard-panel" class="staff-panel <?= $activeStaffPanel === "staff-dashboard-panel" ? "active" : "" ?>">
                <h2>Inventory actions</h2>
                <div class="table-wrapper">
                    <table>
                        <thead>
                            <tr>
                                <th>Product</th>
                                <th>Price</th>
                                <th>Stock</th>
                                <th>Stock in/out</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php if (empty($products)): ?>
                            <tr><td colspan="4">No products are available.</td></tr>
                        <?php else: ?>
                            <?php foreach ($products as $product): ?>
                                <tr>
                                    <td><?= htmlspecialchars($product["name"]) ?></td>
                                    <td>$<?= number_format((float) $product["price"], 2) ?></td>
                                    <td><?= (int) $product["stock"] ?></td>
                                    <td>
                                        <form method="post" class="inline-form">
                                            <input type="hidden" name="product_id" value="<?= (int) $product["id"] ?>">
                                            <input name="stock_change" type="number" placeholder="+/-" required>
                                            <button>Update</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </section>

            <section id="pos-panel" class="staff-panel <?= $activeStaffPanel === "pos-panel" ? "active" : "" ?>">
                <h2>Point of sale</h2>
                <p class="section-description">Select a product and quantity to record a sale.</p>
                <form method="post" action="?tab=pos-panel" class="pos-form">
                    <label for="pos_product_id">Product
                        <select id="pos_product_id" name="product_id" required>
                            <option value="">Select product</option>
                            <?php foreach ($products as $product): ?>
                                <option value="<?= (int) $product["id"] ?>" <?= (int) $product["stock"] < 1 ? "disabled" : "" ?>>
                                    <?= htmlspecialchars($product["name"]) ?> - $<?= number_format((float) $product["price"], 2) ?> (<?= (int) $product["stock"] ?> in stock)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                    <label for="pos_quantity">Quantity
                        <input id="pos_quantity" name="quantity" type="number" min="1" required>
                    </label>
                    <button name="sale">Complete sale</button>
                </form>
            </section>

            <section id="transactions-panel" class="staff-panel <?= $activeStaffPanel === "transactions-panel" ? "active" : "" ?>">
                <h2>Transaction history</h2>
                <p class="section-description">Sales recorded for this shop.</p>
                <div class="table-wrapper">
                    <table>
                        <thead>
                            <tr>
                                <th>Transaction</th>
                                <th>Items</th>
                                <th>Total</th>
                                <th>Recorded by</th>
                                <th>Date</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php if (empty($transactions)): ?>
                            <tr><td colspan="5">No transactions recorded yet.</td></tr>
                        <?php else: ?>
                            <?php foreach ($transactions as $transaction): ?>
                                <tr>
                                    <td>#<?= (int) $transaction["id"] ?></td>
                                    <td><?= htmlspecialchars($transaction["items"] ?? "") ?></td>
                                    <td>$<?= number_format((float) $transaction["total"], 2) ?></td>
                                    <td><?= htmlspecialchars($transaction["recorded_by"]) ?></td>
                                    <td><?= htmlspecialchars($transaction["created_at"]) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </section>
        </div>
    </div>
</main>
<script>
document.querySelectorAll(".staff-nav-button").forEach(function (button) {
    button.addEventListener("click", function () {
        document.querySelectorAll(".staff-nav-button").forEach(function (item) { item.classList.remove("active"); });
        document.querySelectorAll(".staff-panel").forEach(function (panel) { panel.classList.remove("active"); });
        button.classList.add("active");
        document.getElementById(button.dataset.panel).classList.add("active");
    });
});
</script>
</body>
</html>
