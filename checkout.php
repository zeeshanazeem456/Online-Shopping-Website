<?php

require __DIR__ . '/includes/db.php';
require __DIR__ . '/includes/auth.php';

require_user();

$userId = (int) $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $shippingAddress = trim($_POST['shipping_address'] ?? '');
    $paymentMethod = $_POST['payment_method'] ?? 'cod';

    if ($shippingAddress === '') {
        flash_error('Shipping address is required.');
        redirect_to('checkout.php');
    }

    if (!in_array($paymentMethod, ['cod', 'card'], true)) {
        $paymentMethod = 'cod';
    }

    try {
        $pdo->beginTransaction();

        $statement = $pdo->prepare(
            'SELECT c.product_id, c.quantity, p.name, p.price, p.stock
             FROM cart_items c
             JOIN products p ON p.id = c.product_id
             WHERE c.user_id = ?
             FOR UPDATE'
        );
        $statement->execute([$userId]);
        $cartItems = $statement->fetchAll();

        if (!$cartItems) {
            throw new Exception('Your cart is empty.');
        }

        $totalAmount = 0;
        foreach ($cartItems as $item) {
            if ((int) $item['quantity'] > (int) $item['stock']) {
                throw new Exception($item['name'] . ' does not have enough stock.');
            }

            $totalAmount += (float) $item['price'] * (int) $item['quantity'];
        }

        $statement = $pdo->prepare(
            'INSERT INTO orders (user_id, total_amount, payment_method, order_status, shipping_address)
             VALUES (?, ?, ?, ?, ?)'
        );
        $statement->execute([$userId, $totalAmount, $paymentMethod, 'pending', $shippingAddress]);
        $orderId = (int) $pdo->lastInsertId();

        $insertItem = $pdo->prepare(
            'INSERT INTO order_items (order_id, product_id, quantity, price)
             VALUES (?, ?, ?, ?)'
        );
        $updateStock = $pdo->prepare('UPDATE products SET stock = stock - ? WHERE id = ?');

        foreach ($cartItems as $item) {
            $insertItem->execute([$orderId, $item['product_id'], $item['quantity'], $item['price']]);
            $updateStock->execute([$item['quantity'], $item['product_id']]);
        }

        $statement = $pdo->prepare('DELETE FROM cart_items WHERE user_id = ?');
        $statement->execute([$userId]);

        $pdo->commit();

        flash_success('Order placed successfully. Order ID: ' . $orderId);
        redirect_to('my-orders.php');
    } catch (Throwable $exception) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }

        flash_error($exception->getMessage());
        redirect_to('checkout.php');
    }
}

$message = get_flash_message();
$error = get_flash_error();

$statement = $pdo->prepare(
    'SELECT c.quantity, p.name, p.price, c.quantity * p.price AS line_total
     FROM cart_items c
     JOIN products p ON p.id = c.product_id
     WHERE c.user_id = ?
     ORDER BY c.id'
);
$statement->execute([$userId]);
$cartItems = $statement->fetchAll();

$cartTotal = 0;
foreach ($cartItems as $item) {
    $cartTotal += (float) $item['line_total'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>WebHive Shop - Checkout</title>
  <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
  <div class="site-shell">
    <header class="topbar">
      <div class="topbar-inner">
        <a class="brand" href="shop.php">WebHive Shop</a>
        <nav class="nav">
          <a href="shop.php">Products</a>
          <a href="cart.php">Cart</a>
          <a class="active" href="checkout.php">Checkout</a>
          <a href="my-orders.php">My Orders</a>
          <a href="logout.php">Logout</a>
        </nav>
      </div>
    </header>

    <main class="page">
      <div class="page-header">
        <div>
          <p class="eyebrow">User Panel</p>
          <h1>Checkout</h1>
          <p class="muted">Complete your order details.</p>
        </div>
      </div>

      <?php if ($message): ?>
        <div class="alert success"><?php echo h($message); ?></div>
      <?php endif; ?>

      <?php if ($error): ?>
        <div class="alert error"><?php echo h($error); ?></div>
      <?php endif; ?>

      <?php if ($cartItems): ?>
        <div class="checkout-layout">
          <section class="card">
            <div class="card-body">
              <h2>Delivery</h2>
              <form method="post" action="checkout.php">
                <div class="form-row">
                  <label for="shipping_address">Shipping Address</label>
                  <textarea id="shipping_address" name="shipping_address" required></textarea>
                </div>

                <div class="form-row">
                  <label for="payment_method">Payment Method</label>
                  <select id="payment_method" name="payment_method">
                    <option value="cod">Cash on Delivery</option>
                    <option value="card">Card</option>
                  </select>
                </div>

                <button class="btn primary" type="submit">Place Order</button>
              </form>
            </div>
          </section>

          <aside class="card">
            <div class="card-body">
              <h2>Summary</h2>
              <div class="table-wrap">
                <table>
                  <tbody>
                    <?php foreach ($cartItems as $item): ?>
                      <tr>
                        <td><?php echo h($item['name']); ?> x <?php echo h($item['quantity']); ?></td>
                        <td>Rs <?php echo h(number_format((float) $item['line_total'], 2)); ?></td>
                      </tr>
                    <?php endforeach; ?>
                    <tr class="total-row">
                      <th>Total</th>
                      <td>Rs <?php echo h(number_format($cartTotal, 2)); ?></td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </div>
          </aside>
        </div>
      <?php else: ?>
        <div class="empty-state">Add products to your cart before checkout.</div>
      <?php endif; ?>
    </main>
  </div>
</body>
</html>
