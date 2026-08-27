<?php
session_start();
require_once __DIR__ . '/../model/somethingModel.php';

$error = null;
$message = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	$shopName = trim((string) ($_POST['shop_name'] ?? ''));
	$username = trim((string) ($_POST['username'] ?? ''));
	$password = (string) ($_POST['password'] ?? '');
	$confirmPassword = (string) ($_POST['confirm_password'] ?? '');

	if ($shopName === '' || $username === '' || $password === '') {
		$error = 'Shop name, username, and password are required.';
	} elseif ($password !== $confirmPassword) {
		$error = 'Passwords do not match.';
	} elseif (strlen($password) < 6) {
		$error = 'Password must be at least 6 characters.';
	} else {
		try {
			createAccount($shopName, $username, $password);
			$message = 'Account created. You can now sign in.';
		} catch (InvalidArgumentException $exception) {
			$error = $exception->getMessage();
		} catch (Throwable $exception) {
			$error = 'Account registration could not be completed.';
		}
	}
}

require __DIR__ . '/../view/registrationPage.php';
