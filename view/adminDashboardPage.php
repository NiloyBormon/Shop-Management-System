<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SaaS Admin Dashboard</title>
    <link rel="stylesheet" href="view/css/dashboard.css?v=3">
</head>
<body>
<header><div class="brand-wrapper"><div class="brand-icon">SA</div><h1>SaaS Admin</h1></div><div class="user-badge"><a href="account.php">Profile</a><a href="index.php?logout=1">Log out</a></div></header>
<main>
    <div class="page-heading"><h2>Platform overview</h2><p>Manage shops, subscriptions, and system activity.</p></div>
    <?php if (!empty($message)): ?><div class="notice"><?= htmlspecialchars($message) ?></div><?php endif; ?>
    <div class="admin-dashboard-layout">
        <aside class="admin-sidebar" aria-label="SaaS Admin dashboard sections">
            <button type="button" class="admin-nav-button active" data-panel="admin-overview-panel">Overview</button>
            <button type="button" class="admin-nav-button" data-panel="admin-shops-panel">Shop Management</button>
            <button type="button" class="admin-nav-button" data-panel="admin-subscriptions-panel">Subscriptions</button>
        </aside>
        <div class="admin-panels">
            <section id="admin-overview-panel" class="admin-panel active"><h2>Platform overview</h2><p class="section-description">A quick view of platform activity.</p><div class="stats"><div class="stat"><span class="stat-label">Total Shops</span><strong><?= (int) ($stats['total_shops'] ?? 0) ?></strong><small>Active platform shops</small></div><div class="stat"><span class="stat-label">Active Users</span><strong><?= (int) ($stats['active_users'] ?? 0) ?></strong><small>Owners and staff</small></div><div class="stat"><span class="stat-label">Platform Sales</span><strong>$<?= number_format((float) ($stats['total_sales'] ?? 0), 2) ?></strong><small>Recorded sales</small></div></div></section>
            <section id="admin-shops-panel" class="admin-panel"><h2>Shop management</h2><p class="section-description">Approve, suspend, or delete shops.</p><div class="table-wrapper"><table><thead><tr><th>Shop</th><th>Owner</th><th>Status</th><th>Actions</th></tr></thead><tbody><?php foreach ($shops as $shop): ?><tr><td><?= htmlspecialchars($shop['name']) ?></td><td><?= htmlspecialchars($shop['owner_username'] ?? 'Unassigned') ?></td><td><?= htmlspecialchars($shop['status']) ?></td><td><form method="post" class="inline-form"><input type="hidden" name="shop_id" value="<?= (int) $shop['id'] ?>"><select name="shop_status"><option value="approved">Approve</option><option value="suspended">Suspend</option><option value="deleted">Delete</option></select><button name="shop_status_submit">Save</button></form></td></tr><?php endforeach; ?></tbody></table></div></section>
            <section id="admin-subscriptions-panel" class="admin-panel"><h2>Subscriptions</h2><p class="section-description">Assign a plan and record payment status for each shop.</p><div class="table-wrapper"><table><thead><tr><th>Shop</th><th>Current plan</th><th>Payment</th><th>Update</th></tr></thead><tbody><?php foreach ($shops as $shop): ?><tr><td><?= htmlspecialchars($shop['name']) ?></td><td><?= htmlspecialchars($shop['plan'] ?? 'free') ?></td><td><?= htmlspecialchars($shop['payment_status'] ?? 'unpaid') ?></td><td><form method="post" class="inline-form"><input type="hidden" name="shop_id" value="<?= (int) $shop['id'] ?>"><input type="hidden" name="subscription" value="1"><select name="plan"><option>free</option><option>basic</option><option>pro</option><option>enterprise</option></select><select name="payment_status"><option>unpaid</option><option>paid</option></select><button>Save plan</button></form></td></tr><?php endforeach; ?></tbody></table></div></section>
        </div>
    </div>
</main>
<script>document.querySelectorAll('.admin-nav-button').forEach(function (button) { button.addEventListener('click', function () { document.querySelectorAll('.admin-nav-button').forEach(function (item) { item.classList.remove('active'); }); document.querySelectorAll('.admin-panel').forEach(function (panel) { panel.classList.remove('active'); }); button.classList.add('active'); document.getElementById(button.dataset.panel).classList.add('active'); }); });</script></body>
</html>
