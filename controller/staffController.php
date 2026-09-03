<?php
session_start();
require_once __DIR__ . "/../model/somethingModel.php";

if (
    !isset($_SESSION["user_id"]) ||
    ($_SESSION["role"] ?? "") !== "shop_owner"
) {
    header("Location: ../index.php");
    exit();
}

$ownerId = (int) $_SESSION["user_id"];
$shopId = (int) ($_POST["shop_id"] ?? 0);
$shop = shopForOwner($ownerId, $shopId);
if (!$shop || in_array($shop["status"], ["suspended", "deleted"], true)) {
    header("Location: index.php");
    exit();
}

try {
    if (isset($_POST["create_staff"])) {
        $username = trim((string) ($_POST["username"] ?? ""));
        $password = (string) ($_POST["password"] ?? "");
        $email = trim((string) ($_POST["email"] ?? ""));
        if (
            $username === "" ||
            strlen($username) > 100 ||
            strlen($password) < 6 ||
            ($email !== "" && !filter_var($email, FILTER_VALIDATE_EMAIL))
        ) {
            throw new InvalidArgumentException(
                "Enter a valid username, email, and password of at least 6 characters.",
            );
        }
        createStaff($shopId, $username, $password, $email);
        $_SESSION["staff_message"] = "Staff account created successfully.";
    }
    if (isset($_POST["remove_staff"])) {
        removeStaff($shopId, (int) $_POST["account_id"]);
    }
} catch (Throwable $exception) {
    $_SESSION["staff_error"] = $exception->getMessage();
}

header("Location: ../index.php");
exit();
