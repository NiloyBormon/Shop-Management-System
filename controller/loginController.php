<?php
session_start();
require_once __DIR__ . '/../model/somethingModel.php';

if (isset($_GET['logout'])) {
	session_destroy();
	header('Location: index.php');
	exit;
}

$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
	$username = trim((string) ($_POST['username'] ?? ''));
	$password = (string) ($_POST['password'] ?? '');
	$user = findUser($username);

	if ($user && password_verify($password, $user['password_hash'])) {
		session_regenerate_id(true);
		$_SESSION['user_id'] = $user['id'];
		$_SESSION['tenant_id'] = $user['tenant_id'];
		$_SESSION['tenant_name'] = $user['tenant_name'];
		$_SESSION['username'] = $user['username'];
		header('Location: index.php');
		exit;
	}

	$error = 'Username or password is incorrect.';
}

if (!isset($_SESSION['tenant_id'])) {
	require __DIR__ . '/../view/loginPage.php';
	exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_product'])) {
	$name = trim((string) ($_POST['name'] ?? ''));
	$price = filter_var($_POST['price'] ?? null, FILTER_VALIDATE_FLOAT);
	$stock = filter_var($_POST['stock'] ?? null, FILTER_VALIDATE_INT);

	if ($name === '' || $price === false || $price < 0 || $stock === false || $stock < 0) {
		$error = 'Enter a product name, a valid price, and a valid stock quantity.';
	} else {
		addProduct((int) $_SESSION['tenant_id'], $name, (float) $price, (int) $stock);
		$message = 'Product added successfully.';
	}
}

$products = productsForTenant((int) $_SESSION['tenant_id']);
require __DIR__ . '/../view/dashboardPage.php';
?>