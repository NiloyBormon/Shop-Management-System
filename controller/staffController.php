<?php
session_start();
require_once __DIR__ . '/../model/somethingModel.php';

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'shop_owner') {
	header('Location: index.php');
	exit;
}

$shop = shopForUser((int) $_SESSION['user_id']);
if (!$shop) {
	header('Location: index.php');
	exit;
}

try {
	if (isset($_POST['create_staff'])) {
		createStaff((int) $shop['id'], trim((string) $_POST['username']), (string) $_POST['password'], trim((string) $_POST['email']));
		$_SESSION['staff_message'] = 'Staff account created successfully.';
	}
	if (isset($_POST['remove_staff'])) {
		removeStaff((int) $shop['id'], (int) $_POST['account_id']);
	}
} catch (Throwable $exception) {
	$_SESSION['staff_error'] = $exception->getMessage();
}

header('Location: ../index.php');
exit;
