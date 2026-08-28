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

	if ($user && $user['status'] === 'active' && password_verify($password, $user['password_hash'])) {
		session_regenerate_id(true);
		$_SESSION['user_id'] = $user['id'];
		$_SESSION['tenant_id'] = $user['tenant_id'];
		$_SESSION['tenant_name'] = $user['tenant_name'];
		$_SESSION['username'] = $user['username'];
		$_SESSION['email'] = $user['email'] ?? '';
		$_SESSION['role'] = $user['role'];
		header('Location: index.php');
		exit;
	}

	$error = 'Username or password is incorrect.';
}

if (!isset($_SESSION['user_id'])) {
	if (isset($_GET['login']) || $_SERVER['REQUEST_METHOD'] === 'POST') {
		require __DIR__ . '/../view/loginPage.php';
	} else {
		require __DIR__ . '/../view/landingPage.php';
	}
	exit;
}

require __DIR__ . '/dashboardController.php';
?>