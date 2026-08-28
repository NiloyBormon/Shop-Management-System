# Shop Management System

## MySQL setup

1. Start Apache and MySQL in the XAMPP Control Panel.
2. Open phpMyAdmin, select **Import**, and import `data/shop_management.sql`.
3. The application connects to database `shop_management` as MySQL user `root` with an empty password. Update the connection settings at the top of `model/somethingModel.php` if your MySQL credentials are different.
4. Open `http://localhost/Shop-Management-System/` and register a shop.

The SQL file creates the database and the `accounts` and `products` tables when they do not already exist. Application reads and writes use mysqli prepared statements; JSON files are no longer used. New registrations are Shop Owner accounts. Usernames are globally unique, while shop names may be shared by multiple shops.
# Shop-Management-System