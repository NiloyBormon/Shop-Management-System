<?php
declare(strict_types=1);

function accountsFile(): string
{
	return __DIR__ . '/../data/accounts.json';
}

function productsFile(): string
{
	return __DIR__ . '/../data/products.json';
}

function loadAccounts(): array
{
	$file = accountsFile();
	if (!file_exists($file)) {
		return [];
	}

	$accounts = json_decode((string) file_get_contents($file), true);
	return is_array($accounts) ? $accounts : [];
}

function saveAccounts(array $accounts): void
{
	saveJson(accountsFile(), $accounts, 'account');
}

function loadProducts(): array
{
	if (!file_exists(productsFile())) {
		return [];
	}

	$products = json_decode((string) file_get_contents(productsFile()), true);
	return is_array($products) ? $products : [];
}

function saveJson(string $file, array $data, string $type): void
{
	$dataDirectory = dirname($file);
	if (!is_dir($dataDirectory)) {
		mkdir($dataDirectory, 0775, true);
	}

	if (file_put_contents($file, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . PHP_EOL, LOCK_EX) === false) {
		throw new RuntimeException("Unable to save {$type} data.");
	}
}

function findUser(string $username): ?array
{
	foreach (loadAccounts() as $account) {
		if (($account['username'] ?? '') === $username) {
			return $account;
		}
	}

	return null;
}

function createAccount(string $shopName, string $username, string $password): void
{
	$accounts = loadAccounts();
	foreach ($accounts as $account) {
		if (($account['username'] ?? '') === $username) {
			throw new InvalidArgumentException('That username is already in use.');
		}
		if (($account['tenant_name'] ?? '') === $shopName) {
			throw new InvalidArgumentException('That shop name is already registered.');
		}
	}

	$tenantIds = array_column($accounts, 'tenant_id');
	$tenantId = $tenantIds ? max(array_map('intval', $tenantIds)) + 1 : 1;
	$accounts[] = [
		'id' => count($accounts) + 1,
		'tenant_id' => $tenantId,
		'tenant_name' => $shopName,
		'username' => $username,
		'password_hash' => password_hash($password, PASSWORD_DEFAULT),
		'created_at' => date(DATE_ATOM),
	];
	saveAccounts($accounts);
}

function productsForTenant(int $tenantId): array
{
	$products = array_filter(loadProducts(), static fn (array $product): bool => (int) ($product['tenant_id'] ?? 0) === $tenantId);
	usort($products, static fn (array $first, array $second): int => (int) $second['id'] <=> (int) $first['id']);
	return array_values($products);
}

function addProduct(int $tenantId, string $name, float $price, int $stock): void
{
	$products = loadProducts();
	$productIds = array_column($products, 'id');
	$productId = $productIds ? max(array_map('intval', $productIds)) + 1 : 1;
	$products[] = [
		'id' => $productId,
		'tenant_id' => $tenantId,
		'name' => $name,
		'price' => $price,
		'stock' => $stock,
	];
	saveJson(productsFile(), $products, 'product');
}
