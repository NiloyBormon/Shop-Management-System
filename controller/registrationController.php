<?php
session_start();
require_once __DIR__ . "/../model/somethingModel.php";

$error = null;
$message = null;

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $username = trim((string) ($_POST["username"] ?? ""));
    $password = (string) ($_POST["password"] ?? "");
    $confirmPassword = (string) ($_POST["confirm_password"] ?? "");

    if ($username === "" || $password === "") {
        $error = "Username and password are required.";
    } elseif ($password !== $confirmPassword) {
        $error = "Passwords do not match.";
    } elseif (strlen($password) < 6) {
        $error = "Password must be at least 6 characters.";
    } else {
        try {
            createAccount($username, $password);
            $message =
                "Account created. Sign in to create your shop from the owner dashboard.";
        } catch (InvalidArgumentException $exception) {
            $error = $exception->getMessage();
        } catch (Throwable $exception) {
            $error = "Account registration could not be completed.";
        }
    }
}

require __DIR__ . "/../view/registrationPage.php";
