CREATE DATABASE IF NOT EXISTS shop_management CHARACTER
SET
  utf8mb4 COLLATE utf8mb4_unicode_ci;

USE shop_management;

CREATE TABLE IF NOT EXISTS accounts (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  tenant_id INT UNSIGNED DEFAULT NULL,
  tenant_name VARCHAR(150) NOT NULL,
  username VARCHAR(100) NOT NULL,
  password_hash VARCHAR(255) NOT NULL,
  role ENUM ('shop_owner', 'shop_staff', 'saas_admin') NOT NULL DEFAULT 'shop_owner',
  email VARCHAR(150) DEFAULT NULL,
  status ENUM ('active', 'suspended', 'deleted') NOT NULL DEFAULT 'active',
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_accounts_tenant_id (tenant_id),
  UNIQUE KEY uq_accounts_username (username)
) ENGINE = InnoDB;

ALTER TABLE accounts
ADD COLUMN IF NOT EXISTS role ENUM ('shop_owner', 'shop_staff', 'saas_admin') NOT NULL DEFAULT 'shop_owner' AFTER password_hash;

ALTER TABLE accounts
DROP INDEX IF EXISTS uq_accounts_tenant_name;

ALTER TABLE accounts
ADD COLUMN IF NOT EXISTS email VARCHAR(150) DEFAULT NULL AFTER role;

ALTER TABLE accounts
ADD COLUMN IF NOT EXISTS status ENUM ('active', 'suspended', 'deleted') NOT NULL DEFAULT 'active' AFTER email;

CREATE TABLE IF NOT EXISTS shops (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  name VARCHAR(150) NOT NULL,
  owner_id INT UNSIGNED DEFAULT NULL,
  status ENUM ('pending', 'approved', 'suspended', 'deleted') NOT NULL DEFAULT 'approved',
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_shops_owner_id (owner_id),
  KEY idx_shops_status (status),
  CONSTRAINT fk_shops_owner FOREIGN KEY (owner_id) REFERENCES accounts (id) ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE = InnoDB;

INSERT IGNORE INTO shops (id, name, owner_id, status)
SELECT
  tenant_id,
  tenant_name,
  id,
  'approved'
FROM
  accounts
WHERE
  tenant_id IS NOT NULL;

CREATE TABLE IF NOT EXISTS shop_members (
  shop_id INT UNSIGNED NOT NULL,
  account_id INT UNSIGNED NOT NULL,
  member_role ENUM ('owner', 'staff') NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (shop_id, account_id),
  UNIQUE KEY uq_shop_member_account (account_id),
  CONSTRAINT fk_members_shop FOREIGN KEY (shop_id) REFERENCES shops (id) ON UPDATE CASCADE ON DELETE CASCADE,
  CONSTRAINT fk_members_account FOREIGN KEY (account_id) REFERENCES accounts (id) ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE = InnoDB;

ALTER TABLE shop_members
DROP INDEX IF EXISTS uq_shop_member_account;

INSERT IGNORE INTO shop_members (shop_id, account_id, member_role)
SELECT
  tenant_id,
  id,
  'owner'
FROM
  accounts
WHERE
  tenant_id IS NOT NULL
  AND role = 'shop_owner';

CREATE TABLE IF NOT EXISTS subscriptions (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  shop_id INT UNSIGNED NOT NULL,
  plan ENUM ('free', 'basic', 'pro', 'enterprise') NOT NULL DEFAULT 'free',
  payment_status ENUM ('paid', 'unpaid') NOT NULL DEFAULT 'unpaid',
  expires_at DATE DEFAULT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uq_subscription_shop (shop_id),
  CONSTRAINT fk_subscription_shop FOREIGN KEY (shop_id) REFERENCES shops (id) ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE = InnoDB;

INSERT IGNORE INTO subscriptions (shop_id, plan, payment_status)
SELECT
  id,
  'free',
  'unpaid'
FROM
  shops;

CREATE TABLE IF NOT EXISTS products (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  tenant_id INT UNSIGNED NOT NULL,
  name VARCHAR(150) NOT NULL,
  price DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
  stock INT UNSIGNED NOT NULL DEFAULT 0,
  PRIMARY KEY (id),
  KEY idx_products_tenant_id (tenant_id),
  CONSTRAINT fk_products_shop FOREIGN KEY (tenant_id) REFERENCES shops (id) ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE = InnoDB;

CREATE TABLE IF NOT EXISTS inventory_movements (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  product_id INT UNSIGNED NOT NULL,
  account_id INT UNSIGNED NOT NULL,
  quantity_change INT NOT NULL,
  reason VARCHAR(255) NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_movements_product (product_id),
  CONSTRAINT fk_movements_product FOREIGN KEY (product_id) REFERENCES products (id) ON UPDATE CASCADE ON DELETE CASCADE,
  CONSTRAINT fk_movements_account FOREIGN KEY (account_id) REFERENCES accounts (id) ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE = InnoDB;

CREATE TABLE IF NOT EXISTS sales (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  shop_id INT UNSIGNED NOT NULL,
  account_id INT UNSIGNED NOT NULL,
  total DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_sales_shop (shop_id),
  CONSTRAINT fk_sales_shop FOREIGN KEY (shop_id) REFERENCES shops (id) ON UPDATE CASCADE ON DELETE CASCADE,
  CONSTRAINT fk_sales_account FOREIGN KEY (account_id) REFERENCES accounts (id) ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE = InnoDB;

CREATE TABLE IF NOT EXISTS sale_items (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  sale_id INT UNSIGNED NOT NULL,
  product_id INT UNSIGNED NOT NULL,
  quantity INT UNSIGNED NOT NULL,
  unit_price DECIMAL(10, 2) NOT NULL,
  PRIMARY KEY (id),
  CONSTRAINT fk_sale_items_sale FOREIGN KEY (sale_id) REFERENCES sales (id) ON UPDATE CASCADE ON DELETE CASCADE,
  CONSTRAINT fk_sale_items_product FOREIGN KEY (product_id) REFERENCES products (id) ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE = InnoDB;
