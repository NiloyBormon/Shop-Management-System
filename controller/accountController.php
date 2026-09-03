<?php
session_start();
require_once __DIR__ . "/../model/somethingModel.php";

if (!isset($_SESSION["user_id"])) {
    header("Location: ../index.php?login=1");
    exit();
}

$error = null;
$message = null;
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["profile"])) {
    try {
        updateProfile(
            (int) $_SESSION["user_id"],
            trim((string) $_POST["username"]),
            trim((string) $_POST["email"]),
        );
        $_SESSION["username"] = trim((string) $_POST["username"]);
        $_SESSION["email"] = trim((string) $_POST["email"]);
        $message = "Profile updated.";
    } catch (Throwable $exception) {
        $error = $exception->getMessage();
    }
}
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["password"])) {
    try {
        changePassword(
            (int) $_SESSION["user_id"],
            (string) $_POST["current_password"],
            (string) $_POST["new_password"],
        );
        $message = "Password changed.";
    } catch (Throwable $exception) {
        $error = $exception->getMessage();
    }
}
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["delete_account"])) {
    deleteAccount((int) $_SESSION["user_id"]);
    session_destroy();
    header("Location: ../index.php");
    exit();
}
require __DIR__ . "/../view/accountPage.php";
