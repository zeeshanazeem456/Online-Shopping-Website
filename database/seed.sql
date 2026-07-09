USE webhive_shop;

INSERT INTO users (name, email, password, role)
VALUES
  ('Admin User', 'admin@webhive.test', 'admin123', 'admin'),
  ('Demo User', 'user@webhive.test', 'user123', 'user')
ON DUPLICATE KEY UPDATE
  name = VALUES(name),
  password = VALUES(password),
  role = VALUES(role);

INSERT INTO categories (name)
VALUES
  ('Electronics'),
  ('Accessories'),
  ('Fashion'),
  ('Home'),
  ('Fitness')
ON DUPLICATE KEY UPDATE
  name = VALUES(name);

INSERT INTO products (name, description, price, stock, image, status)
SELECT 'Wireless Headphones', 'Comfortable wireless headphones with clear sound.', 4500.00, 15, 'phone.jpg', 'active'
WHERE NOT EXISTS (SELECT 1 FROM products WHERE name = 'Wireless Headphones');

INSERT INTO products (name, description, price, stock, image, status)
SELECT 'Smart Watch', 'Fitness tracking smart watch with a modern display.', 7200.00, 10, 'watch.jpg', 'active'
WHERE NOT EXISTS (SELECT 1 FROM products WHERE name = 'Smart Watch');

INSERT INTO products (name, description, price, stock, image, status)
SELECT 'Laptop Backpack', 'Durable backpack with padded laptop storage.', 3200.00, 20, 'laptop.jpg', 'active'
WHERE NOT EXISTS (SELECT 1 FROM products WHERE name = 'Laptop Backpack');

INSERT INTO products (name, description, price, stock, image, status)
SELECT 'Cotton T-Shirt', 'Soft cotton t-shirt for daily wear.', 1200.00, 30, 'shirt.jpg', 'active'
WHERE NOT EXISTS (SELECT 1 FROM products WHERE name = 'Cotton T-Shirt');

INSERT INTO products (name, description, price, stock, image, status)
SELECT 'Running Shoes', 'Lightweight shoes designed for comfort.', 5800.00, 12, 'shoes.jpg', 'active'
WHERE NOT EXISTS (SELECT 1 FROM products WHERE name = 'Running Shoes');

INSERT INTO products (name, description, price, stock, image, status)
SELECT 'Desk Lamp', 'Adjustable desk lamp for study and work.', 2500.00, 18, 'lamp.jpg', 'active'
WHERE NOT EXISTS (SELECT 1 FROM products WHERE name = 'Desk Lamp');

INSERT IGNORE INTO product_categories (product_id, category_id)
SELECT p.id, c.id
FROM products p
JOIN categories c ON c.name = 'Electronics'
WHERE p.name = 'Wireless Headphones';

INSERT IGNORE INTO product_categories (product_id, category_id)
SELECT p.id, c.id
FROM products p
JOIN categories c ON c.name IN ('Electronics', 'Fashion', 'Fitness')
WHERE p.name = 'Smart Watch';

INSERT IGNORE INTO product_categories (product_id, category_id)
SELECT p.id, c.id
FROM products p
JOIN categories c ON c.name IN ('Accessories', 'Fashion')
WHERE p.name = 'Laptop Backpack';

INSERT IGNORE INTO product_categories (product_id, category_id)
SELECT p.id, c.id
FROM products p
JOIN categories c ON c.name = 'Fashion'
WHERE p.name = 'Cotton T-Shirt';

INSERT IGNORE INTO product_categories (product_id, category_id)
SELECT p.id, c.id
FROM products p
JOIN categories c ON c.name IN ('Fashion', 'Fitness')
WHERE p.name = 'Running Shoes';

INSERT IGNORE INTO product_categories (product_id, category_id)
SELECT p.id, c.id
FROM products p
JOIN categories c ON c.name IN ('Home', 'Electronics')
WHERE p.name = 'Desk Lamp';
