<?php
declare(strict_types=1);

$db_host = "localhost";
$db_user = "root";
$db_pass = "";
$db_name = "shop_management";

function ensureDatabaseAndSchema(
    string $host,
    string $user,
    string $pass,
    string $dbName,
): mysqli {
    $adminConnection = new mysqli($host, $user, $pass);
    if ($adminConnection->connect_error) {
        die(
            "Database server connection failed: " .
                $adminConnection->connect_error
        );
    }

    $createDatabaseSql = "CREATE DATABASE IF NOT EXISTS `$dbName`
		CHARACTER SET utf8mb4
		COLLATE utf8mb4_unicode_ci";

    if (!$adminConnection->query($createDatabaseSql)) {
        die("Database creation failed: " . $adminConnection->error);
    }

    if (!$adminConnection->select_db($dbName)) {
        die("Unable to select database: " . $adminConnection->error);
    }

    $schemaCheck = $adminConnection->query("SHOW TABLES LIKE 'accounts'");
    if ($schemaCheck && $schemaCheck->num_rows === 0) {
        $schemaPath = __DIR__ . "/../data/shop_management.sql";
        if (file_exists($schemaPath) && is_readable($schemaPath)) {
            $sql = file_get_contents($schemaPath);
            if ($sql === false) {
                die("Unable to read database schema file.");
            }

            if (!$adminConnection->multi_query($sql)) {
                die(
                    "Database schema creation failed: " .
                        $adminConnection->error
                );
            }

            do {
                if ($result = $adminConnection->store_result()) {
                    $result->free();
                }
            } while (
                $adminConnection->more_results() &&
                $adminConnection->next_result()
            );
        }
    }

    $adminConnection->query(
        "ALTER TABLE shop_members DROP INDEX IF EXISTS uq_shop_member_account",
    );

    return $adminConnection;
}

mysqli_report(MYSQLI_REPORT_OFF);
$conn = ensureDatabaseAndSchema($db_host, $db_user, $db_pass, $db_name);

if ($conn->connect_error) {
    die("Database connection failed: " . $conn->connect_error);
}

$conn->set_charset("utf8mb4");

function findUser(string $username): ?array
{
    global $conn;
    $statement = $conn->prepare(
        ' SELECT id, tenant_id, tenant_name, username, password_hash, role, email, status
		 FROM accounts WHERE username = ? LIMIT 1',
    );
    $statement->bind_param("s", $username);
    $statement->execute();
    $result = $statement->get_result();
    $account = $result->fetch_assoc();
    $statement->close();

    return $account ?: null;
}

function createAccount(string $username, string $password): void
{
    global $conn;
    $passwordHash = password_hash($password, PASSWORD_DEFAULT);
    $statement = $conn->prepare(
        "INSERT INTO accounts (tenant_name, username, password_hash, role, status)
		 VALUES ('', ?, ?, 'shop_owner', 'active')",
    );
    $statement->bind_param("ss", $username, $passwordHash);
    if (!$statement->execute()) {
        if ($conn->errno === 1062) {
            throw new InvalidArgumentException(
                "That username is already in use.",
            );
        }
        throw new RuntimeException("Unable to create account.");
    }
    $statement->close();
}

function createShop(int $ownerId, string $shopName): int
{
    global $conn;
    $shopName = trim($shopName);
    if ($shopName === "" || strlen($shopName) > 150) {
        throw new InvalidArgumentException(
            "Shop name must be between 1 and 150 characters.",
        );
    }
    if (!ownerCanCreateShop($ownerId)) {
        throw new InvalidArgumentException(
            "Upgrade your subscription before creating another shop.",
        );
    }

    $conn->begin_transaction();
    try {
        $shop = $conn->prepare(
            "INSERT INTO shops (name, owner_id, status) VALUES (?, ?, 'approved')",
        );
        $shop->bind_param("si", $shopName, $ownerId);
        if (!$shop->execute()) {
            throw new RuntimeException("Unable to create shop.");
        }
        $shopId = $conn->insert_id;
        $shop->close();

        $update = $conn->prepare(
            'UPDATE accounts SET tenant_id = ?, tenant_name = ? WHERE id = ? AND role = \'shop_owner\'',
        );
        $update->bind_param("isi", $shopId, $shopName, $ownerId);
        if (!$update->execute() || $update->affected_rows === 0) {
            throw new RuntimeException("Unable to link shop to account.");
        }
        $update->close();

        $member = $conn->prepare(
            "INSERT INTO shop_members (shop_id, account_id, member_role) VALUES (?, ?, 'owner')",
        );
        $member->bind_param("ii", $shopId, $ownerId);
        $member->execute();
        $member->close();

        $subscriptionPlan = ownerHasMultiShopPlan($ownerId) ? "pro" : "free";
        $subscriptionStatus = ownerHasMultiShopPlan($ownerId)
            ? "paid"
            : "unpaid";
        $subscription = $conn->prepare(
            "INSERT INTO subscriptions (shop_id, plan, payment_status) VALUES (?, ?, ?)",
        );
        $subscription->bind_param(
            "iss",
            $shopId,
            $subscriptionPlan,
            $subscriptionStatus,
        );
        $subscription->execute();
        $subscription->close();
        $conn->commit();
        return $shopId;
    } catch (Throwable $exception) {
        $conn->rollback();
        throw $exception;
    }
}

function shopsForOwner(int $ownerId): array
{
    global $conn;
    $statement = $conn->prepare(
        "SELECT s.id, s.name, s.status, sub.plan, sub.payment_status
		 FROM shops s LEFT JOIN subscriptions sub ON sub.shop_id = s.id
		 WHERE s.owner_id = ? AND s.status <> 'deleted' ORDER BY s.id ASC",
    );
    $statement->bind_param("i", $ownerId);
    $statement->execute();
    $shops = $statement->get_result()->fetch_all(MYSQLI_ASSOC);
    $statement->close();
    return $shops;
}

function shopForOwner(int $ownerId, int $shopId): ?array
{
    global $conn;
    $statement = $conn->prepare(
        "SELECT s.id, s.name, s.status, s.created_at, sub.plan, sub.payment_status
		 FROM shops s LEFT JOIN subscriptions sub ON sub.shop_id = s.id
		 WHERE s.owner_id = ? AND s.id = ? AND s.status <> 'deleted' LIMIT 1",
    );
    $statement->bind_param("ii", $ownerId, $shopId);
    $statement->execute();
    $shop = $statement->get_result()->fetch_assoc();
    $statement->close();
    return $shop ?: null;
}

function shopCountForOwner(int $ownerId): int
{
    global $conn;
    $statement = $conn->prepare(
        "SELECT COUNT(*) AS shop_count FROM shops WHERE owner_id = ? AND status <> 'deleted'",
    );
    $statement->bind_param("i", $ownerId);
    $statement->execute();
    $count = (int) ($statement->get_result()->fetch_assoc()["shop_count"] ?? 0);
    $statement->close();
    return $count;
}

function ownerHasMultiShopPlan(int $ownerId): bool
{
    global $conn;
    $statement = $conn->prepare(
        "SELECT 1 FROM subscriptions sub
		 JOIN shops s ON s.id = sub.shop_id
		 WHERE s.owner_id = ? AND sub.plan = 'pro' AND sub.payment_status = 'paid' LIMIT 1",
    );
    $statement->bind_param("i", $ownerId);
    $statement->execute();
    $hasPlan = $statement->get_result()->num_rows > 0;
    $statement->close();
    return $hasPlan;
}

function ownerCanCreateShop(int $ownerId): bool
{
    global $conn;
    if (ownerHasMultiShopPlan($ownerId)) {
        return true;
    }

    $statement = $conn->prepare(
        "SELECT COUNT(*) AS active_shop_count FROM shops WHERE owner_id = ? AND status NOT IN ('suspended', 'deleted')",
    );
    $statement->bind_param("i", $ownerId);
    $statement->execute();
    $activeShopCount =
        (int) ($statement->get_result()->fetch_assoc()["active_shop_count"] ??
            0);
    $statement->close();
    return $activeShopCount === 0;
}

function ownerCanAccessShop(int $ownerId, int $shopId): bool
{
    global $conn;
    if (ownerHasMultiShopPlan($ownerId)) {
        return shopForOwner($ownerId, $shopId) !== null;
    }

    $statement = $conn->prepare(
        "SELECT s.id FROM shops s WHERE s.owner_id = ? AND s.status NOT IN ('suspended', 'deleted') ORDER BY s.id ASC LIMIT 1",
    );
    $statement->bind_param("i", $ownerId);
    $statement->execute();
    $firstActiveShopId =
        (int) ($statement->get_result()->fetch_assoc()["id"] ?? 0);
    $statement->close();
    return $firstActiveShopId > 0 && $firstActiveShopId === $shopId;
}

function purchaseMultiShopSubscription(int $ownerId, string $plan): void
{
    global $conn;
    if (!in_array($plan, ["basic", "pro"], true)) {
        throw new InvalidArgumentException(
            "Select a valid multi-shop subscription.",
        );
    }

    $statement = $conn->prepare(
        "UPDATE subscriptions sub JOIN shops s ON s.id = sub.shop_id
		 SET sub.plan = ?, sub.payment_status = 'paid'
		 WHERE s.owner_id = ? AND s.status <> 'deleted'",
    );
    $statement->bind_param("si", $plan, $ownerId);
    if (!$statement->execute() || $statement->affected_rows < 1) {
        $statement->close();
        throw new RuntimeException("Unable to activate the subscription.");
    }
    $statement->close();
}

function shopForUser(int $userId): ?array
{
    global $conn;
    $statement = $conn->prepare(
        'SELECT s.id, s.name, s.status, s.created_at, sub.plan, sub.payment_status
		 FROM shops s LEFT JOIN subscriptions sub ON sub.shop_id = s.id
		 WHERE s.owner_id = ? OR s.id IN (SELECT shop_id FROM shop_members WHERE account_id = ?) LIMIT 1',
    );
    $statement->bind_param("ii", $userId, $userId);
    $statement->execute();
    $shop = $statement->get_result()->fetch_assoc();
    $statement->close();
    return $shop ?: null;
}

function allShops(): array
{
    global $conn;
    $result = $conn->query(
        'SELECT s.id, s.name, s.status, s.created_at, a.username AS owner_username,
		 sub.plan, sub.payment_status,
		 (SELECT COUNT(*) FROM shop_members m WHERE m.shop_id = s.id) AS member_count
		 FROM shops s LEFT JOIN accounts a ON a.id = s.owner_id
		 LEFT JOIN subscriptions sub ON sub.shop_id = s.id ORDER BY s.id DESC',
    );
    return $result->fetch_all(MYSQLI_ASSOC);
}

function platformStats(): array
{
    global $conn;
    $result = $conn->query(
        "SELECT (SELECT COUNT(*) FROM shops WHERE status <> 'deleted') AS total_shops,
		 (SELECT COUNT(*) FROM accounts WHERE status = 'active') AS active_users,
		 (SELECT COUNT(*) FROM products) AS total_products,
		 (SELECT COALESCE(SUM(total), 0) FROM sales) AS total_sales",
    );
    return $result->fetch_assoc() ?: [];
}

function setShopStatus(int $shopId, string $status): void
{
    global $conn;
    if ($shopId < 1) {
        throw new InvalidArgumentException("Invalid shop selected.");
    }
    if (!in_array($status, ["approved", "suspended", "deleted"], true)) {
        throw new InvalidArgumentException("Invalid shop status.");
    }
    $statement = $conn->prepare("UPDATE shops SET status = ? WHERE id = ?");
    $statement->bind_param("si", $status, $shopId);
    if (!$statement->execute()) {
        $statement->close();
        throw new RuntimeException("Unable to update shop status.");
    }
    $statement->close();
}

function updateSubscription(
    int $shopId,
    string $plan,
    string $paymentStatus,
): void {
    global $conn;
    if (
        !in_array($plan, ["free", "pro"], true) ||
        !in_array($paymentStatus, ["paid", "unpaid"], true)
    ) {
        throw new InvalidArgumentException("Invalid subscription selection.");
    }
    $statement = $conn->prepare(
        "UPDATE subscriptions SET plan = ?, payment_status = ? WHERE shop_id = ?",
    );
    $statement->bind_param("ssi", $plan, $paymentStatus, $shopId);
    $statement->execute();
    $statement->close();
}

function updateShop(int $shopId, int $ownerId, string $name): void
{
    global $conn;
    $name = trim($name);
    if ($name === "" || strlen($name) > 150) {
        throw new InvalidArgumentException(
            "Shop name must be between 1 and 150 characters.",
        );
    }
    $statement = $conn->prepare(
        "UPDATE shops SET name = ? WHERE id = ? AND owner_id = ?",
    );
    $statement->bind_param("sii", $name, $shopId, $ownerId);
    $statement->execute();
    $statement->close();
    $account = $conn->prepare(
        'UPDATE accounts SET tenant_name = ? WHERE id = ? AND role = \'shop_owner\'',
    );
    $account->bind_param("si", $name, $ownerId);
    $account->execute();
    $account->close();
}

function productsForTenant(int $tenantId): array
{
    global $conn;
    $statement = $conn->prepare(
        'SELECT id, tenant_id, name, price, stock
		 FROM products WHERE tenant_id = ? ORDER BY id DESC',
    );
    $statement->bind_param("i", $tenantId);
    $statement->execute();
    $products = $statement->get_result()->fetch_all(MYSQLI_ASSOC);
    $statement->close();

    return $products;
}

function addProduct(int $tenantId, string $name, float $price, int $stock): void
{
    global $conn;
    $name = trim($name);
    if ($name === "" || strlen($name) > 150 || $price < 0 || $stock < 0) {
        throw new InvalidArgumentException(
            "Enter a valid product name, price, and stock quantity.",
        );
    }
    $statement = $conn->prepare(
        "INSERT INTO products (tenant_id, name, price, stock) VALUES (?, ?, ?, ?)",
    );
    $statement->bind_param("isdi", $tenantId, $name, $price, $stock);
    $statement->execute();
    $statement->close();
}

function updateProduct(
    int $productId,
    int $tenantId,
    string $name,
    float $price,
): void {
    global $conn;
    $name = trim($name);
    if ($name === "" || strlen($name) > 150 || $price < 0) {
        throw new InvalidArgumentException(
            "Enter a valid product name and price.",
        );
    }
    $statement = $conn->prepare(
        "UPDATE products SET name = ?, price = ? WHERE id = ? AND tenant_id = ?",
    );
    $statement->bind_param("sdii", $name, $price, $productId, $tenantId);
    $statement->execute();
    $statement->close();
}

function deleteProduct(int $productId, int $tenantId): void
{
    global $conn;
    $statement = $conn->prepare(
        "DELETE FROM products WHERE id = ? AND tenant_id = ?",
    );
    $statement->bind_param("ii", $productId, $tenantId);
    $statement->execute();
    $statement->close();
}

function updateStock(
    int $productId,
    int $tenantId,
    int $accountId,
    int $change,
    string $reason,
): void {
    global $conn;
    $conn->begin_transaction();
    try {
        $check = $conn->prepare(
            "SELECT stock FROM products WHERE id = ? AND tenant_id = ? FOR UPDATE",
        );
        $check->bind_param("ii", $productId, $tenantId);
        $check->execute();
        $product = $check->get_result()->fetch_assoc();
        $check->close();
        if (!$product || (int) $product["stock"] + $change < 0) {
            throw new InvalidArgumentException("Stock cannot become negative.");
        }
        $newStock = (int) $product["stock"] + $change;
        $update = $conn->prepare(
            "UPDATE products SET stock = ? WHERE id = ? AND tenant_id = ?",
        );
        $update->bind_param("iii", $newStock, $productId, $tenantId);
        $update->execute();
        $update->close();
        $movement = $conn->prepare(
            "INSERT INTO inventory_movements (product_id, account_id, quantity_change, reason) VALUES (?, ?, ?, ?)",
        );
        $movement->bind_param("iiis", $productId, $accountId, $change, $reason);
        $movement->execute();
        $movement->close();
        $conn->commit();
    } catch (Throwable $exception) {
        $conn->rollback();
        throw $exception;
    }
}

function recordSale(
    int $tenantId,
    int $accountId,
    int $productId,
    int $quantity,
): void {
    global $conn;
    $conn->begin_transaction();
    try {
        $check = $conn->prepare(
            "SELECT price, stock FROM products WHERE id = ? AND tenant_id = ? FOR UPDATE",
        );
        $check->bind_param("ii", $productId, $tenantId);
        $check->execute();
        $product = $check->get_result()->fetch_assoc();
        $check->close();
        if (!$product || $quantity < 1 || (int) $product["stock"] < $quantity) {
            throw new InvalidArgumentException(
                "Not enough stock for this sale.",
            );
        }
        $total = (float) $product["price"] * $quantity;
        $sale = $conn->prepare(
            "INSERT INTO sales (shop_id, account_id, total) VALUES (?, ?, ?)",
        );
        $sale->bind_param("iid", $tenantId, $accountId, $total);
        $sale->execute();
        $saleId = $conn->insert_id;
        $sale->close();
        $item = $conn->prepare(
            "INSERT INTO sale_items (sale_id, product_id, quantity, unit_price) VALUES (?, ?, ?, ?)",
        );
        $unitPrice = (float) $product["price"];
        $item->bind_param("iiid", $saleId, $productId, $quantity, $unitPrice);
        $item->execute();
        $item->close();
        $newStock = (int) $product["stock"] - $quantity;
        $update = $conn->prepare(
            "UPDATE products SET stock = ? WHERE id = ? AND tenant_id = ?",
        );
        $update->bind_param("iii", $newStock, $productId, $tenantId);
        $update->execute();
        $update->close();
        $movement = $conn->prepare(
            "INSERT INTO inventory_movements (product_id, account_id, quantity_change, reason) VALUES (?, ?, ?, 'Sale')",
        );
        $change = -$quantity;
        $movement->bind_param("iii", $productId, $accountId, $change);
        $movement->execute();
        $movement->close();
        $conn->commit();
    } catch (Throwable $exception) {
        $conn->rollback();
        throw $exception;
    }
}

function transactionsForShop(int $shopId): array
{
    global $conn;
    $statement = $conn->prepare(
        "SELECT s.id, s.total, s.created_at, a.username AS recorded_by,
                GROUP_CONCAT(CONCAT(p.name, ' x ', si.quantity) ORDER BY p.name SEPARATOR ', ') AS items
         FROM sales s
         JOIN accounts a ON a.id = s.account_id
         JOIN sale_items si ON si.sale_id = s.id
         JOIN products p ON p.id = si.product_id
         WHERE s.shop_id = ?
         GROUP BY s.id, s.total, s.created_at, a.username
         ORDER BY s.id DESC"
    );
    $statement->bind_param("i", $shopId);
    $statement->execute();
    $transactions = $statement->get_result()->fetch_all(MYSQLI_ASSOC);
    $statement->close();
    return $transactions;
}

function staffForShop(int $shopId): array
{
    global $conn;
    $statement = $conn->prepare(
        "SELECT a.id, a.username, a.email, a.status FROM shop_members m JOIN accounts a ON a.id = m.account_id WHERE m.shop_id = ? AND m.member_role = 'staff' ORDER BY a.id DESC",
    );
    $statement->bind_param("i", $shopId);
    $statement->execute();
    $staff = $statement->get_result()->fetch_all(MYSQLI_ASSOC);
    $statement->close();
    return $staff;
}

function createStaff(
    int $shopId,
    string $username,
    string $password,
    string $email,
): void {
    global $conn;
    $passwordHash = password_hash($password, PASSWORD_DEFAULT);
    $conn->begin_transaction();
    try {
        $account = $conn->prepare(
            "INSERT INTO accounts (tenant_name, username, password_hash, role, email, status) VALUES ('', ?, ?, 'shop_staff', ?, 'active')",
        );
        $account->bind_param("sss", $username, $passwordHash, $email);
        if (!$account->execute()) {
            if ($conn->errno === 1062) {
                throw new InvalidArgumentException(
                    "That username is already in use.",
                );
            }
            throw new RuntimeException("Unable to create staff account.");
        }
        $accountId = $conn->insert_id;
        $account->close();
        $member = $conn->prepare(
            "INSERT INTO shop_members (shop_id, account_id, member_role) VALUES (?, ?, 'staff')",
        );
        $member->bind_param("ii", $shopId, $accountId);
        if (!$member->execute()) {
            throw new RuntimeException(
                "Unable to assign the staff account to this shop.",
            );
        }
        $member->close();
        $conn->commit();
    } catch (Throwable $exception) {
        $conn->rollback();
        throw $exception;
    }
}

function removeStaff(int $shopId, int $accountId): void
{
    global $conn;
    $statement = $conn->prepare(
        "DELETE FROM shop_members WHERE shop_id = ? AND account_id = ? AND member_role = 'staff'",
    );
    $statement->bind_param("ii", $shopId, $accountId);
    $statement->execute();
    $statement->close();
    $delete = $conn->prepare(
        "UPDATE accounts SET status = 'deleted' WHERE id = ? AND role = 'shop_staff'",
    );
    $delete->bind_param("i", $accountId);
    $delete->execute();
    $delete->close();
}

function updateProfile(int $accountId, string $username, string $email): void
{
    global $conn;
    $username = trim($username);
    $email = trim($email);
    if (
        $username === "" ||
        strlen($username) > 100 ||
        ($email !== "" && !filter_var($email, FILTER_VALIDATE_EMAIL))
    ) {
        throw new InvalidArgumentException(
            "Enter a valid username and email address.",
        );
    }
    $statement = $conn->prepare(
        "UPDATE accounts SET username = ?, email = ? WHERE id = ?",
    );
    $statement->bind_param("ssi", $username, $email, $accountId);
    if (!$statement->execute() && $conn->errno === 1062) {
        $statement->close();
        throw new InvalidArgumentException("That username is already in use.");
    }
    $statement->close();
}

function changePassword(
    int $accountId,
    string $currentPassword,
    string $newPassword,
): void {
    global $conn;
    if (strlen($newPassword) < 6) {
        throw new InvalidArgumentException(
            "New password must be at least 6 characters.",
        );
    }
    $statement = $conn->prepare(
        "SELECT password_hash FROM accounts WHERE id = ?",
    );
    $statement->bind_param("i", $accountId);
    $statement->execute();
    $account = $statement->get_result()->fetch_assoc();
    $statement->close();
    if (
        !$account ||
        !password_verify($currentPassword, $account["password_hash"])
    ) {
        throw new InvalidArgumentException("Current password is incorrect.");
    }
    $hash = password_hash($newPassword, PASSWORD_DEFAULT);
    $update = $conn->prepare(
        "UPDATE accounts SET password_hash = ? WHERE id = ?",
    );
    $update->bind_param("si", $hash, $accountId);
    $update->execute();
    $update->close();
}

function deleteAccount(int $accountId): void
{
    global $conn;
    $statement = $conn->prepare(
        "UPDATE accounts SET status = 'deleted' WHERE id = ?",
    );
    $statement->bind_param("i", $accountId);
    $statement->execute();
    $statement->close();
}
