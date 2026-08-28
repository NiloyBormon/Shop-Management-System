<?php
require_once __DIR__ . '/../model/somethingModel.php';

$role = $_SESSION['role'] ?? '';
$error = null;
$message = null;

if (!empty($_SESSION['staff_message'])) {
	$message = $_SESSION['staff_message'];
	unset($_SESSION['staff_message']);
}
if (!empty($_SESSION['staff_error'])) {
	$error = $_SESSION['staff_error'];
	unset($_SESSION['staff_error']);
}

if ($role === 'saas_admin') {
	if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['shop_status'])) {
		setShopStatus((int) $_POST['shop_id'], (string) $_POST['shop_status']);
		$message = 'Shop status updated.';
	}
	if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['subscription'])) {
		updateSubscription((int) $_POST['shop_id'], (string) $_POST['plan'], (string) $_POST['payment_status']);
		$message = 'Subscription updated.';
	}
	$stats = platformStats();
	$shops = allShops();
	require __DIR__ . '/../view/adminDashboardPage.php';
	exit;
}

$shop = shopForUser((int) $_SESSION['user_id']);
if (!$shop || $shop['status'] !== 'approved') {
	$error = 'Your shop is not currently available.';
	require __DIR__ . '/../view/loginPage.php';
	exit;
}

$shopId = (int) $shop['id'];
$products = productsForTenant($shopId);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_shop']) && $role === 'shop_owner') {
	updateShop($shopId, (int) $_SESSION['user_id'], trim((string) $_POST['shop_name']));
	$_SESSION['tenant_name'] = trim((string) $_POST['shop_name']);
	$shop = shopForUser((int) $_SESSION['user_id']);
	$message = 'Shop profile updated.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_product']) && $role === 'shop_owner') {
	$name = trim((string) ($_POST['name'] ?? ''));
	$price = filter_var($_POST['price'] ?? null, FILTER_VALIDATE_FLOAT);
	$stock = filter_var($_POST['stock'] ?? null, FILTER_VALIDATE_INT);
	if ($name === '' || $price === false || $price < 0 || $stock === false || $stock < 0) {
		$error = 'Enter a valid product name, price, and stock quantity.';
	} else {
		addProduct($shopId, $name, (float) $price, (int) $stock);
		$message = 'Product added successfully.';
		$products = productsForTenant($shopId);
	}
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['stock_change'])) {
	try {
		updateStock((int) $_POST['product_id'], $shopId, (int) $_SESSION['user_id'], (int) $_POST['stock_change'], 'Stock update');
		$message = 'Stock updated.';
		$products = productsForTenant($shopId);
	} catch (Throwable $exception) {
		$error = $exception->getMessage();
	}
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['sale'])) {
	try {
		recordSale($shopId, (int) $_SESSION['user_id'], (int) $_POST['product_id'], (int) $_POST['quantity']);
		$message = 'Sale recorded.';
		$products = productsForTenant($shopId);
	} catch (Throwable $exception) {
		$error = $exception->getMessage();
	}
}

if ($role === 'shop_owner') {
	$staff = staffForShop($shopId);
	require __DIR__ . '/../view/ownerDashboardPage.php';
} else {
	require __DIR__ . '/../view/staffDashboardPage.php';
}