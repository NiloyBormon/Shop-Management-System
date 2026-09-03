<?php
require_once __DIR__ . "/../model/somethingModel.php";

$role = $_SESSION["role"] ?? "";
$error = null;
$message = null;

if (!empty($_SESSION["staff_message"])) {
    $message = $_SESSION["staff_message"];
    unset($_SESSION["staff_message"]);
}
if (!empty($_SESSION["staff_error"])) {
    $error = $_SESSION["staff_error"];
    unset($_SESSION["staff_error"]);
}

if ($role === "saas_admin") {
    try {
        if (
            $_SERVER["REQUEST_METHOD"] === "POST" &&
            isset($_POST["shop_status"])
        ) {
            setShopStatus(
                (int) ($_POST["shop_id"] ?? 0),
                (string) $_POST["shop_status"],
            );
            $message = "Shop status updated.";
        }
        if (
            $_SERVER["REQUEST_METHOD"] === "POST" &&
            isset($_POST["subscription"])
        ) {
            updateSubscription(
                (int) ($_POST["shop_id"] ?? 0),
                (string) ($_POST["plan"] ?? ""),
                (string) ($_POST["payment_status"] ?? ""),
            );
            $message = "Subscription updated.";
        }
    } catch (Throwable $exception) {
        $error = $exception->getMessage();
    }
    $stats = platformStats();
    $shops = allShops();
    require __DIR__ . "/../view/adminDashboardPage.php";
    exit();
}

$shop = shopForUser((int) $_SESSION["user_id"]);
$ownerId = (int) $_SESSION["user_id"];
$ownerShops = [];
$ownerShopCount = shopCountForOwner($ownerId);
$ownerHasMultiShopAccess = ownerHasMultiShopPlan($ownerId);
$createShopMode = $role === "shop_owner" && isset($_GET["create_shop"]);

if ($role === "shop_owner") {
    $ownerShops = shopsForOwner($ownerId);
    foreach ($ownerShops as &$ownerShop) {
        $ownerShop["is_accessible"] = ownerCanAccessShop(
            $ownerId,
            (int) $ownerShop["id"],
        );
    }
    unset($ownerShop);
    $selectedShopId = (int) ($_GET["shop_id"] ?? ($_POST["shop_id"] ?? 0));
    $shop = $createShopMode
        ? null
        : ($selectedShopId > 0 && ownerCanAccessShop($ownerId, $selectedShopId)
            ? shopForOwner($ownerId, $selectedShopId)
            : null);
    if ($selectedShopId > 0 && !$shop && !$createShopMode) {
        $error = "Upgrade to Pro to access this shop.";
    }
}

if (
    $_SERVER["REQUEST_METHOD"] === "POST" &&
    isset($_POST["purchase_multi_shop"]) &&
    $role === "shop_owner"
) {
    try {
        $selectedPlan = (string) ($_POST["plan"] ?? "");
        purchaseMultiShopSubscription($ownerId, $selectedPlan);
        $ownerHasMultiShopAccess = ownerHasMultiShopPlan($ownerId);
        $message = $ownerHasMultiShopAccess
            ? "Pro subscription purchased. You can now add more shops."
            : "Basic subscription purchased. Upgrade to Pro to add more shops.";
    } catch (Throwable $exception) {
        $error = $exception->getMessage();
    }
}

if (!$shop && $role === "shop_owner") {
    if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["create_shop"])) {
        try {
            $shopName = (string) ($_POST["shop_name"] ?? "");
            if (!ownerCanCreateShop($ownerId)) {
                throw new InvalidArgumentException(
                    "Upgrade your subscription before creating another shop.",
                );
            }
            $selectedShopId = createShop($ownerId, $shopName);
            $_SESSION["tenant_name"] = trim($shopName);
            $shop = shopForOwner($ownerId, $selectedShopId);
            $ownerShops = shopsForOwner($ownerId);
            $ownerShopCount = shopCountForOwner($ownerId);
            $ownerHasMultiShopAccess = ownerHasMultiShopPlan($ownerId);
            $message = "Shop created successfully.";
        } catch (Throwable $exception) {
            $error = $exception->getMessage();
        }
    }
    if (!$shop) {
        $shopId = 0;
        $products = [];
        $staff = [];
        require __DIR__ . "/../view/ownerDashboardPage.php";
        exit();
    }
}

if (!$shop || in_array($shop["status"], ["suspended", "deleted"], true)) {
    $error = "Your shop is not currently available.";
    require __DIR__ . "/../view/loginPage.php";
    exit();
}

$shopId = (int) $shop["id"];
$products = productsForTenant($shopId);
$transactions = $role === "shop_staff" ? transactionsForShop($shopId) : [];
$activeStaffPanel = in_array(
    $_GET["tab"] ?? "",
    ["staff-dashboard-panel", "pos-panel", "transactions-panel"],
    true,
)
    ? $_GET["tab"]
    : "staff-dashboard-panel";

if (
    $_SERVER["REQUEST_METHOD"] === "POST" &&
    isset($_POST["update_shop"]) &&
    $role === "shop_owner"
) {
    try {
        $shopName = trim((string) ($_POST["shop_name"] ?? ""));
        updateShop($shopId, (int) $_SESSION["user_id"], $shopName);
        $_SESSION["tenant_name"] = $shopName;
        $shop = shopForOwner($ownerId, $shopId);
        $message = "Shop profile updated.";
    } catch (Throwable $exception) {
        $error = $exception->getMessage();
    }
}

if (
    $_SERVER["REQUEST_METHOD"] === "POST" &&
    isset($_POST["add_product"]) &&
    $role === "shop_owner"
) {
    $name = trim((string) ($_POST["name"] ?? ""));
    $price = filter_var($_POST["price"] ?? null, FILTER_VALIDATE_FLOAT);
    $stock = filter_var($_POST["stock"] ?? null, FILTER_VALIDATE_INT);
    if (
        $name === "" ||
        $price === false ||
        $price < 0 ||
        $stock === false ||
        $stock < 0
    ) {
        $error = "Enter a valid product name, price, and stock quantity.";
    } else {
        addProduct($shopId, $name, (float) $price, (int) $stock);
        $message = "Product added successfully.";
        $products = productsForTenant($shopId);
    }
}

if (
    $_SERVER["REQUEST_METHOD"] === "POST" &&
    isset($_POST["update_product"]) &&
    $role === "shop_owner"
) {
    try {
        $price = filter_var($_POST["price"] ?? null, FILTER_VALIDATE_FLOAT);
        if ($price === false) {
            throw new InvalidArgumentException("Enter a valid product price.");
        }
        updateProduct(
            (int) ($_POST["product_id"] ?? 0),
            $shopId,
            (string) ($_POST["name"] ?? ""),
            (float) $price,
        );
        $message = "Product updated.";
        $products = productsForTenant($shopId);
    } catch (Throwable $exception) {
        $error = $exception->getMessage();
    }
}

if (
    $_SERVER["REQUEST_METHOD"] === "POST" &&
    isset($_POST["delete_product"]) &&
    $role === "shop_owner"
) {
    deleteProduct((int) ($_POST["product_id"] ?? 0), $shopId);
    $message = "Product deleted.";
    $products = productsForTenant($shopId);
}

if (
    $_SERVER["REQUEST_METHOD"] === "POST" &&
    isset($_POST["stock_change"]) &&
    in_array($role, ["shop_owner", "shop_staff"], true)
) {
    try {
        updateStock(
            (int) $_POST["product_id"],
            $shopId,
            (int) $_SESSION["user_id"],
            (int) $_POST["stock_change"],
            "Stock update",
        );
        $message = "Stock updated.";
        $products = productsForTenant($shopId);
    } catch (Throwable $exception) {
        $error = $exception->getMessage();
    }
}

if (
    $_SERVER["REQUEST_METHOD"] === "POST" &&
    isset($_POST["sale"]) &&
    $role === "shop_staff"
) {
    try {
        recordSale(
            $shopId,
            (int) $_SESSION["user_id"],
            (int) $_POST["product_id"],
            (int) $_POST["quantity"],
        );
        $message = "Sale recorded.";
        $products = productsForTenant($shopId);
        $transactions = transactionsForShop($shopId);
    } catch (Throwable $exception) {
        $error = $exception->getMessage();
    }
}

if ($role === "shop_owner") {
    $staff = staffForShop($shopId);
    $ownerShopCount = shopCountForOwner($ownerId);
    $ownerHasMultiShopAccess = ownerHasMultiShopPlan($ownerId);
    require __DIR__ . "/../view/ownerDashboardPage.php";
} else {
    require __DIR__ . "/../view/staffDashboardPage.php";
}
