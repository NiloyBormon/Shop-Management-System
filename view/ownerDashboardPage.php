<?php
$activePanel = in_array(
    $_GET["tab"] ?? "",
    [
        "dashboard-panel",
        "profile-panel",
        "inventory-panel",
        "staff-panel",
        "subscription-panel",
    ],
    true,
)
    ? $_GET["tab"]
    : "dashboard-panel"; ?>
<!DOCTYPE html>
<html lang="en">
<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title><?= htmlspecialchars(
    $shop["name"] ?? "Owner",
) ?> Dashboard</title><link rel="stylesheet" href="view/css/dashboard.css?v=2"></head>
<body>
<header><a class="brand-wrapper" href="<?= $shop
    ? "index.php?shop_id=" . (int) $shop["id"] . "&tab=dashboard-panel"
    : "index.php" ?>"><div class="brand-icon">OW</div><h1><?= htmlspecialchars(
    $shop["name"] ?? "Owner account",
) ?></h1></a><div class="user-badge"><span>Shop Owner</span><?php if (
    $shop
): ?><a href="index.php">Switch shop</a><?php endif; ?><a href="controller/account.php">Profile</a><a href="index.php?logout=1">Log out</a></div></header>
<main><?php if (!$shop): ?><div class="page-heading"><h2><?= $createShopMode
    ? "Create another shop"
    : (!empty($ownerShops)
        ? "Choose a shop"
        : "Owner dashboard") ?></h2><p><?= $createShopMode
    ? "Add another shop to your owner account."
    : (!empty($ownerShops)
        ? "Select which shop you want to enter."
        : "Create your first shop to get started.") ?></p></div><?php endif; ?>
<?php
if (!empty($message)): ?><div class="notice"><?= htmlspecialchars(
    $message,
) ?></div><?php endif;
if (!empty($error)): ?><div class="error"><?= htmlspecialchars(
    $error,
) ?></div><?php endif;
?>
<?php if (
    !$shop &&
    !empty($ownerShops) &&
    empty($createShopMode)
): ?><section class="owner-panel active"><h2>Your shops</h2><div class="shop-selector-grid"><?php foreach (
    $ownerShops
    as $ownerShop
):
    if (
        $ownerShop["is_accessible"]
    ): ?><form method="get" class="shop-choice"><input type="hidden" name="shop_id" value="<?= (int) $ownerShop[
    "id"
] ?>"><button type="submit"><strong><?= htmlspecialchars(
    $ownerShop["name"],
) ?></strong><span><?= htmlspecialchars(
    ucfirst($ownerShop["status"]),
) ?></span></button></form><?php else: ?><div class="shop-choice locked"><strong><?= htmlspecialchars(
    $ownerShop["name"],
) ?></strong><span>Upgrade to Pro to access</span></div><?php endif;
endforeach; ?></div><div class="chooser-action"><a class="secondary-action" href="index.php?create_shop=1">Create another shop</a></div></section>
<?php elseif (
    !$shop
): ?><section class="owner-panel active"><h2><?= $createShopMode
    ? "Create another shop"
    : "Create a shop" ?></h2><p class="section-description"><?= $createShopMode
    ? "Enter a name for your new shop."
    : "Your personal account is ready. Add the shop you want to manage." ?></p><form method="post"><label for="shop_name">Shop / Store name<input id="shop_name" name="shop_name" placeholder="e.g. Daraz" maxlength="150" required></label><button name="create_shop">Create shop</button></form></section>
<?php else: ?>
<div class="owner-dashboard-layout">
	<aside class="owner-sidebar" aria-label="Owner dashboard sections">
		<button type="button" class="owner-nav-button <?= $activePanel ===
  "dashboard-panel"
      ? "active"
      : "" ?>" data-panel="dashboard-panel">Dashboard</button>
		<button type="button" class="owner-nav-button <?= $activePanel ===
  "profile-panel"
      ? "active"
      : "" ?>" data-panel="profile-panel">Shop Profile</button>
		<button type="button" class="owner-nav-button <?= $activePanel ===
  "inventory-panel"
      ? "active"
      : "" ?>" data-panel="inventory-panel">Inventory</button>
		<button type="button" class="owner-nav-button <?= $activePanel === "staff-panel"
      ? "active"
      : "" ?>" data-panel="staff-panel">Staff Management</button>
		<button type="button" class="owner-nav-button <?= $activePanel ===
  "subscription-panel"
      ? "active"
      : "" ?>" data-panel="subscription-panel">Subscriptions</button>
	</aside>
	<div class="owner-panels">
		<section id="dashboard-panel" class="owner-panel <?= $activePanel ===
  "dashboard-panel"
      ? "active"
      : "" ?>"><h2>Dashboard</h2><p class="section-description">Choose what you want to manage for this shop.</p><div class="owner-shop-summary"><strong><?= $ownerShopCount ?></strong> shop<?= $ownerShopCount ===
1
    ? ""
    : "s" ?> owned</div><a class="owner-add-shop-link dashboard-add-shop" href="index.php?create_shop=1">Add another shop</a></section>
		<section id="profile-panel" class="owner-panel <?= $activePanel ===
  "profile-panel"
      ? "active"
      : "" ?>"><h2>Shop profile</h2><p class="section-description">Update the public name of your shop.</p><form method="post"><label>Shop name<input name="shop_name" value="<?= htmlspecialchars(
    $shop["name"],
) ?>" required></label><button name="update_shop">Save shop profile</button></form></section>
		<section id="inventory-panel" class="owner-panel"><h2>Products and inventory</h2><p class="section-description">Add products and keep stock levels current.</p><form method="post"><label>Product name<input name="name" required></label><label>Price<input name="price" type="number" min="0" step="0.01" required></label><label>Opening stock<input name="stock" type="number" min="0" required></label><button name="add_product">Add product</button></form><div class="table-wrapper"><table><thead><tr><th>Product</th><th>Price</th><th>Stock</th><th>Actions</th></tr></thead><tbody><?php foreach (
      $products
      as $product
  ): ?><tr><td colspan="4"><form method="post" class="inline-form"><input type="hidden" name="product_id" value="<?= (int) $product[
    "id"
] ?>"><input name="name" value="<?= htmlspecialchars(
    $product["name"],
) ?>" required><input name="price" type="number" min="0" step="0.01" value="<?= htmlspecialchars(
    $product["price"],
) ?>" required><span><?= (int) $product[
    "stock"
] ?></span><button name="update_product">Save</button><button name="delete_product" formnovalidate>Delete</button></form></td></tr><?php endforeach; ?></tbody></table></div></section>
		<section id="staff-panel" class="owner-panel"><h2>Staff management</h2><p class="section-description">Create and remove staff accounts for this shop.</p><form method="post" action="controller/staffController.php"><input type="hidden" name="shop_id" value="<?= $shopId ?>"><label>Username<input name="username" required></label><label>Email<input name="email" type="email"></label><label>Temporary password<input name="password" type="password" minlength="6" required></label><button name="create_staff">Create staff account</button></form><?php if (
    !empty($staff)
): ?><div class="table-wrapper"><table><thead><tr><th>Username</th><th>Email</th><th>Status</th><th>Action</th></tr></thead><tbody><?php foreach (
    $staff
    as $member
): ?><tr><td><?= htmlspecialchars(
    $member["username"],
) ?></td><td><?= htmlspecialchars(
    $member["email"] ?? "",
) ?></td><td><?= htmlspecialchars(
    $member["status"],
) ?></td><td><form method="post" action="controller/staffController.php"><input type="hidden" name="account_id" value="<?= (int) $member[
    "id"
] ?>"><button name="remove_staff">Remove</button></form></td></tr><?php endforeach; ?></tbody></table></div><?php endif; ?></section>
		<section id="subscription-panel" class="owner-panel"><h2>Multi-shop subscription</h2><p class="section-description">Basic provides single-shop access. Pro unlocks multiple shops.</p><?php if (
      !$ownerHasMultiShopAccess
  ): ?><form method="post" action="?tab=subscription-panel"><label>Plan<select name="plan"><option value="basic">Basic - single shop access</option><option value="pro">Pro - multi-shop access</option></select></label><button name="purchase_multi_shop">Simulate purchase</button></form><?php else: ?><p class="notice">Pro subscription is active. Multi-shop access is unlocked.</p><?php endif; ?></section>
	</div>
</div>
<?php endif; ?>
</main><script>document.querySelectorAll('.owner-nav-button').forEach(function (button) { button.addEventListener('click', function () { document.querySelectorAll('.owner-nav-button').forEach(function (item) { item.classList.remove('active'); }); document.querySelectorAll('.owner-panel').forEach(function (panel) { panel.classList.remove('active'); }); button.classList.add('active'); document.getElementById(button.dataset.panel).classList.add('active'); }); }); var activeButton = document.querySelector('[data-panel="<?= htmlspecialchars(
    $activePanel,
    ENT_QUOTES,
) ?>"]'); if (activeButton) { activeButton.click(); }</script></body></html>
