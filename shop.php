<?php

require __DIR__ . '/includes/db.php';
require __DIR__ . '/includes/auth.php';

require_user();

$userId = (int) $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add_to_cart') {
        $productId = (int) ($_POST['product_id'] ?? 0);

        $statement = $pdo->prepare("SELECT id, name, stock FROM products WHERE id = ? AND status = 'active'");
        $statement->execute([$productId]);
        $product = $statement->fetch();

        if (!$product) {
            flash_error('Product not found.');
        } elseif ((int) $product['stock'] < 1) {
            flash_error('This product is out of stock.');
        } else {
            $statement = $pdo->prepare('SELECT quantity FROM cart_items WHERE user_id = ? AND product_id = ?');
            $statement->execute([$userId, $productId]);
            $cartItem = $statement->fetch();
            $currentQuantity = $cartItem ? (int) $cartItem['quantity'] : 0;

            if ($currentQuantity >= (int) $product['stock']) {
                flash_error('You cannot add more than available stock.');
            } else {
                $statement = $pdo->prepare(
                    'INSERT INTO cart_items (user_id, product_id, quantity)
                     VALUES (?, ?, 1)
                     ON DUPLICATE KEY UPDATE quantity = quantity + 1'
                );
                $statement->execute([$userId, $productId]);
                flash_success($product['name'] . ' added to cart.');
            }
        }

        redirect_to('shop.php');
    }
}

$message = get_flash_message();
$error = get_flash_error();

$products = $pdo
    ->query(
        "SELECT p.id, p.name, p.description, p.price, p.stock, p.image, p.status,
                GROUP_CONCAT(c.name ORDER BY c.name SEPARATOR ', ') AS category_names
         FROM products p
         LEFT JOIN product_categories pc ON pc.product_id = p.id
         LEFT JOIN categories c ON c.id = pc.category_id
         WHERE p.status = 'active'
         GROUP BY p.id, p.name, p.description, p.price, p.stock, p.image, p.status
         ORDER BY p.id"
    )
    ->fetchAll();

$cartCountStatement = $pdo->prepare('SELECT COALESCE(SUM(quantity), 0) FROM cart_items WHERE user_id = ?');
$cartCountStatement->execute([$userId]);
$cartCount = (int) $cartCountStatement->fetchColumn();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>WebHive Shop - Products</title>
  <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
  <div class="site-shell">
    <header class="topbar">
      <div class="topbar-inner">
        <a class="brand" href="shop.php">WebHive Shop</a>
        <nav class="nav">
          <a class="active" href="shop.php">Products</a>
          <a href="cart.php">Cart (<?php echo h($cartCount); ?>)</a>
          <a href="checkout.php">Checkout</a>
          <a href="my-orders.php">My Orders</a>
          <a href="logout.php">Logout</a>
        </nav>
      </div>
    </header>

    <main class="page">
      <div class="page-header">
        <div>
          <p class="eyebrow">User Panel</p>
          <h1>Products</h1>
          <p class="muted">Welcome, <?php echo h($_SESSION['name']); ?>.</p>
        </div>
      </div>

      <?php if ($message): ?>
        <div class="alert success"><?php echo h($message); ?></div>
      <?php endif; ?>

      <?php if ($error): ?>
        <div class="alert error"><?php echo h($error); ?></div>
      <?php endif; ?>

      <section class="grid product-grid">
        <?php foreach ($products as $product): ?>
          <article class="card product-card">
            <div class="product-image">
              <?php if (!empty($product['image']) && file_exists(__DIR__ . '/assets/' . $product['image'])): ?>
                <img src="assets/<?php echo h($product['image']); ?>" alt="<?php echo h($product['name']); ?>">
              <?php else: ?>
                <span><?php echo h(substr($product['name'], 0, 1)); ?></span>
              <?php endif; ?>
            </div>
            <div class="product-body">
              <div class="meta-row">
                <span class="badge"><?php echo h($product['category_names'] ?? 'No category'); ?></span>
                <span class="badge <?php echo (int) $product['stock'] > 0 ? 'success' : 'danger'; ?>">
                  Stock <?php echo h($product['stock']); ?>
                </span>
              </div>
              <h2 class="product-title"><?php echo h($product['name']); ?></h2>
              <p class="product-description"><?php echo h($product['description']); ?></p>
              <div class="meta-row">
                <span class="price">Rs <?php echo h(number_format((float) $product['price'], 2)); ?></span>
                <form method="post" action="shop.php">
                  <input type="hidden" name="action" value="add_to_cart">
                  <input type="hidden" name="product_id" value="<?php echo h($product['id']); ?>">
                  <button class="btn primary" type="submit">Add to Cart</button>
                </form>
              </div>
            </div>
          </article>
        <?php endforeach; ?>
      </section>
    </main>
  </div>
</body>
</html>
